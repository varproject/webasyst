<?php

class filesLockModel extends filesModel
{
    const SCOPE_EXCLUSIVE = 'exclusive';
    const SCOPE_SHARED = 'shared';

    const DEPTH_ZERO = '0';
    const DEPTH_INFINITY = 'infinity';

    const RESOURCE_TYPE_FILE = 'file';
    const RESOURCE_TYPE_STORAGE = 'storage';
    const RESOURCE_TYPE_APP = 'app';
    const RESOURCE_TYPE_ANY = 'any';

    const DEFAULT_OWNER = 'app';

    /**
     * @var int
     */
    private $token_length;

    /**
     * @var string
     */
    private $default_timeout;

    /**
     * @var string
     */
    protected $table = 'files_lock';

    /**
     * Set lock for resource(s). Type of resource managed by parameter
     * @param int|array[int]|null $resource_id
     * @param array|null $data
     * @param $resource_type
     * @see constants with prefix RESOURCE_TYPE_
     * @return bool
     */
    public function set($resource_id, $data = array(), $resource_type = self::RESOURCE_TYPE_FILE)
    {
        if ($resource_id === null && $resource_type !== self::RESOURCE_TYPE_APP) {
            return false;
        }

        $data = (array) $data;

        if ($resource_id === null) {
            $data['id'] = null;
            $data = $this->prepareBeforeAdd($data, $resource_type);
            $this->insert($data);
            return true;
        }

        $resource_ids = filesApp::toIntArray($resource_id);

        if (!$resource_ids) {
            return false;
        }

        $insert = array();
        foreach ($resource_ids as $rid) {
            $data['id'] = $rid;
            $insert[] = $this->prepareBeforeAdd($data, $resource_type);
        }

        $this->multipleInsert($insert);

        return true;
    }

    /**
     * Set lock for app
     * @param $data
     */
    public function lockApp($data)
    {
        $this->set(null, $data, self::RESOURCE_TYPE_APP);
    }

    /**
     * Delete lock for app
     */
    public function unlockApp()
    {
        $this->delete(null, self::RESOURCE_TYPE_APP);
    }

    /**
     * Check is app locked
     * @return bool
     */
    public function isAppLocked()
    {
        return !!$this->getByField(array(
            'file_id' => 0,
            'storage_id' => 0
        ));
    }

    /**
     * Slice off resources that locked exclusively
     * @param array[int] $resource_ids
     * @param $resource_type
     * @see constants with prefix RESOURCE_TYPE_
     * @param string|null $scope constant or null. If null scope isn't taken into account
     * @see constants with prefix SCOPE_
     * @param null|string $ignore_owner If not null ignore locks with that owner
     * @return array[int]
     * @throws waException
     */
    public function sliceOffLocked($resource_ids, $resource_type = self::RESOURCE_TYPE_FILE,
                                   $scope = self::SCOPE_EXCLUSIVE, $ignore_owner = null)
    {
        $resource_ids = filesApp::toIntArray($resource_ids);
        if (!$resource_ids) {
            return array();
        };
        $field = $this->getWhereByResourceType($resource_type, $resource_ids, true);
        if ($scope !== null) {
            $field['scope'] = self::SCOPE_EXCLUSIVE;
        }
        $where = $this->getWhereByField($field);

        if ($ignore_owner !== null) {
            $ignore_owner = (array) $ignore_owner;
            $where .= ($where ? ' AND ' : '') . 'owner NOT IN(s:owner)';
        }

        if (!$where) {
            return $resource_ids;
        }

        $select = $resource_type === self::RESOURCE_TYPE_STORAGE ? 'storage_id' : 'file_id';

        $locked = $this->select($select)->where(
            $where, array(
                'owner' => $ignore_owner
            ))->fetchAll(null, true);

        return array_diff($resource_ids, $locked);
    }

    public function getLocks($resource_type = self::RESOURCE_TYPE_FILE, $scope = self::SCOPE_EXCLUSIVE)
    {
        $field = $this->getWhereByResourceType($resource_type, false, true);
        if ($scope !== null) {
            $field['scope'] = self::SCOPE_EXCLUSIVE;
        }
        $where = $this->getWhereByField($field);

        $select = $resource_type === self::RESOURCE_TYPE_STORAGE ? 'storage_id' : 'file_id';

        return $this->select($select)->where($where)->fetchAll(null, true);
    }

    /**
     * Prolong exclusive lock by owner
     * @param string|array[string] $owner
     * @param $resource_type
     * @see constants with prefix RESOURCE_TYPE_
     */
    public function prolongExclusiveByOwner($owner = self::DEFAULT_OWNER, $resource_type = self::RESOURCE_TYPE_FILE)
    {
        $field = array_merge(
            array(
                'owner' => $owner,
                'scope' => self::SCOPE_EXCLUSIVE
            ),
            $this->getWhereByResourceType($resource_type, false, true)
        );

        $this->updateByField(
            $field,
            array(
                'expired_datetime' => date('Y-m-d H:i:s', time() + $this->getDefaultTimeout())
            ));
    }

    public function prolongForCopytask()
    {
        $sql = "UPDATE `files_copytask` cp 
                  LEFT JOIN `files_lock` fl_source ON cp.source_id = fl_source.file_id AND fl_source.owner = :owner AND fl_source.scope = :scope
                  LEFT JOIN `files_lock` fl_target ON cp.target_id = fl_target.file_id AND fl_target.owner = :owner AND fl_source.scope = :scope
                  SET fl_source.expired_datetime = :expired_datetime, fl_target.expired_datetime = :expired_datetime
                  WHERE fl_source.token IS NOT NULL AND fl_target.token IS NOT NULL
              ";
        $this->exec($sql, array(
            'owner' => filesLockModel::DEFAULT_OWNER,
            'scope' => filesLockModel::SCOPE_EXCLUSIVE,
            'expired_datetime' => date('Y-m-d H:i:s', time() + $this->getDefaultTimeout())
        ));
    }

    /**
     * Delete expired locks to resources
     * @param $resource_type
     * @see contants with prefix RESOURCE_TYPE_
     */
    public function deleteExpired($resource_type = self::RESOURCE_TYPE_FILE)
    {
        $where = array(
            'expired_datetime <= :expired_datetime'
        );

        $rt_where = $this->getWhereByResourceType($resource_type);
        if ($rt_where) {
            $where[] = $rt_where;
        }

        $where = "WHERE " . join(' AND ', $where);

        $this->exec("DELETE FROM `files_lock` {$where}",
            array(
                'expired_datetime' => date('Y-m-d H:i:s')
            ));
    }

    /**
     * Delete exclusive lock to resource(s). Type of resource managed by parameter
     * @param int|array[int]|null $resource_id
     * @param $resource_type
     * @see constants with prefix RESOURCE_TYPE_
     * @param string|null $scope constant or null. If null scope isn't taken into account
     * @see constants with prefix SCOPE_
     */
    public function delete($resource_id, $resource_type = self::RESOURCE_TYPE_FILE, $scope = self::SCOPE_EXCLUSIVE)
    {
        $resource_ids = filesApp::toIntArray($resource_id);
        $field = $this->getWhereByResourceType($resource_type, $resource_ids, true);
        if ($scope === null) {
            $field['scope'] = $scope;
        }
        $this->deleteByField($field);
    }

    /**
     * Get length of token
     * @return int
     */
    private function getTokenLength()
    {
        if ($this->token_length === null) {
            $metadata = $this->getMetadata();
            $len = (int)ifset($metadata['token']['params']);
            if (!$len) {
                $len = 100;
            }
            $this->token_length = $len;
        }
        return $this->token_length;
    }

    /**
     * Generate unique token string
     * @return string
     */
    private function generateToken()
    {
        $length = $this->getTokenLength();
        $chunk_count = $length / 32;
        $chunks = array();
        for ($chunk_num = 0; $chunk_num < $chunk_count; $chunk_num += 1) {
            $chunks[] = md5(uniqid('', true));
        }
        $token = join('', $chunks);
        return substr($token, 0, $length);
    }

    /**
     * Get default live time of lock
     * @return mixed|null
     */
    public function getDefaultTimeout()
    {
        if ($this->default_timeout === null) {
            $this->default_timeout = filesApp::inst()->getConfig()->getFileLockTimeout();
        }
        return $this->default_timeout;
    }

    /**
     * Prepare data for adding
     * @param mixed $data Has required key 'id'. It is resource id itself
     * @param $resource_type
     * @return mixed
     */
    private function prepareBeforeAdd($data, $resource_type = self::RESOURCE_TYPE_FILE)
    {
        if (!isset($data['contact_id'])) {
            $data['contact_id'] = $this->contact_id;
        }
        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        if (empty($data['timeout'])) {
            $data['timeout'] = $this->getDefaultTimeout();
        }
        if (empty($data['scope'])) {
            $data['scope'] = self::SCOPE_EXCLUSIVE;
        }
        if (empty($data['depth'])) {
            $data['depth'] = self::DEPTH_ZERO;
        }
        if (!isset($data['owner'])) {
            $data['owner'] = self::DEFAULT_OWNER;
        }
        if (!isset($data['token'])) {
            $data['token'] = $this->generateToken();
        }
        $data['expired_datetime'] = date('Y-m-d H:i:s', strtotime($data['create_datetime']) + $data['timeout']);
        $id = (int) $data['id'];
        unset($data['id']);
        $data = array_merge($data, $this->getWhereByResourceType($resource_type, $id, true));
        return $data;
    }

    /**
     * Helper method for build piece of where condition
     * @param $type
     * @see constants with prefix RESOURCE_TYPE_
     * @param mixed $id If false - id not mixin into where condition
     * @param bool $ret_field Flag for return value format. If false - return map (key => value), otherwise string where piece
     * @return array|string
     */
    private function getWhereByResourceType($type = self::RESOURCE_TYPE_ANY, $id = false, $ret_field = false)
    {
        $field = array();
        if ($type === self::RESOURCE_TYPE_FILE) {
            if ($id !== false) {
                $field['file_id'] = $id;
            }
            $field['storage_id'] = 0;
        } else if ($type === self::RESOURCE_TYPE_STORAGE) {
            if ($id !== false) {
                $field['storage_id'] = $id;
            }
            $field['file_id'] = 0;
        } else if ($type === self::RESOURCE_TYPE_APP) {
            $field['file_id'] = 0;
            $field['storage_id'] = 0;
        }

        // return format - piece of sql
        if (!$ret_field) {
            $where = array();
            foreach ($field as $key => $val) {
                if (is_array($val) && $val) {
                    $where[] = "{$key} IN ('".join("','", $val)."')";
                } else {
                    $where[] = "{$key} = '{$val}'";
                }
            }
            return join(' AND ', $where);
        }

        // otherwise map

        return $field;

    }

    public function getByToken($token)
    {
        return $this->getByField('token', $token);
    }

    public function updateByToken($token, $data)
    {
        return $this->updateByField('token', $token, $data);
    }

    public function deleteByToken($token)
    {
        return $this->deleteByField('token', $token);
    }
}

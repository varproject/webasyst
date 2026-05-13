<?php

class filesStorageModel extends filesModel
{
    protected $table = 'files_storage';

    const ACCESS_TYPE_PERSONAL = 'personal';
    const ACCESS_TYPE_LIMITED = 'limited';
    const ACCESS_TYPE_EVERYONE = 'everyone';

    const ICON_DEFAULT_LIMIT = 'contact fas fa-users';
    const ICON_DEFAULT_PERSONAL = 'lock fas fa-lock';
    const ICON_DEFAULT_EVERYONE = 'contact fas fa-users';

    /**
     * @var mixed
     */
    private $owner;

    static private $items_cache = array();

    private function getLimitedStorageIdsByLevel($level = null, $op = '=')
    {
        $groups = filesRights::inst()->getGroupIds();

        $where = array(
            "app_id = '{$this->app_id}'",
            "group_id IN (".join(',', $groups).")"
        );
        if (!in_array($op, array('=', '<', '>', '<=', '>='))) {
            $op = '=';
        }
        if ($level !== null) {
            $where[] = "value {$op} {$level}";
        }

        $preix = "storage.";
        $preix_len = strlen($preix);

        $where[] = "name LIKE '{$preix}%'";
        $where = join(' AND ', $where);

        $ids = array();
        foreach ($this->cacheQuery("SELECT name FROM `wa_contact_rights` WHERE {$where}")->fetchAll() as $res_item) {
            $ids[] = (int) substr($res_item['name'], $preix_len);
        }
        return $ids;
    }

    public function getLimitedStorages($storage_id = null, $level = null)
    {
        $available_ids = $this->getLimitedStorageIdsByLevel($level);
        $conds = array('access_type' => self::ACCESS_TYPE_LIMITED);
        if ($storage_id !== null) {
            $conds['id'] = array_diff($available_ids, filesApp::toIntArray($storage_id));
        } else {
            $conds['id'] = $available_ids;
        }

        return $this->workupStorages($this->getList($conds));
    }

    public function getPersonalStorages($storage_id = null)
    {
        $conds = array('access_type' => self::ACCESS_TYPE_PERSONAL);
        if ($storage_id !== null) {
            $conds['id'] = filesApp::toIntArray($storage_id);
        }
        return $this->workupStorages($this->getList($conds));
    }

    public function getOwnStorages($storage_id = null)
    {
        $conds = array('access_type' => self::ACCESS_TYPE_PERSONAL, 'contact_id' => $this->contact_id);
        if ($storage_id !== null) {
            $conds['id'] = filesApp::toIntArray($storage_id);
        }
        return $this->workupStorages($this->getList($conds));
    }

    public function getEveryoneStorages($storage_id = null)
    {
        $conds = array('access_type' => self::ACCESS_TYPE_EVERYONE);
        if ($storage_id !== null) {
            $conds['id'] = filesApp::toIntArray($storage_id);
        }
        return $this->workupStorages($this->getList($conds));
    }

    public function getForeignerStorages($storage_id = null)
    {
        $conds = array('access_type' => self::ACCESS_TYPE_PERSONAL, 'contact_id:not' => $this->contact_id);
        if ($storage_id !== null) {
            $conds['id'] = $storage_ids = array_map('intval', (array) $storage_id);
        }
        return $this->workupStorages($this->getList($conds));
    }

    public function getPersistenStorage()
    {
        $persisten_storage_id = $this->getPersistentStorageId();
        if (!$persisten_storage_id) {
            return false;
        }
        return $this->getStorage($persisten_storage_id);
    }

    public function getPersistentStorageId()
    {
        $cms = new waContactSettingsModel();
        $persisten_storage_id = $cms->getOne($this->contact_id, $this->app_id, 'persistent_storage');
        return $persisten_storage_id;
    }

    public function createPersistentStorage($name = null)
    {
        $id = $this->query("
            SELECT id FROM `files_storage`
            WHERE access_type = s:access_type AND contact_id = i:contact_id
            LIMIT 1",
            array(
                'access_type' => filesStorageModel::ACCESS_TYPE_PERSONAL,
                'contact_id' => wa('files')->getUser()->getId()
            ))->fetchField();
        if (!$id) {
            $id = $this->add(array(
                'icon' => self::ICON_DEFAULT_PERSONAL,
                'name' => $name === null ? _w('My files') : $name,
                'contact_id' => $this->contact_id,
                'access_type' => self::ACCESS_TYPE_PERSONAL
            ));
        }
        $cms = new waContactSettingsModel();
        $cms->set($this->contact_id, $this->app_id, 'persistent_storage', $id);
        return $id;
    }

    public function isOwnStorage($storage)
    {
        if (is_array($storage)) {
             return ifset($storage['access_type'], '') === self::ACCESS_TYPE_PERSONAL && ifset($storage['contact_id'], '') == $this->contact_id;
        } else {
            $storage_id = (int) $storage;
            $storage = $this->getStorage($storage_id);
            return $storage['access_type'] === self::ACCESS_TYPE_PERSONAL && $storage['contact_id'] == $this->contact_id;
        }
    }

    public function getAvailableStorages($level_of_limited = filesRightConfig::RIGHT_LEVEL_READ)
    {
        $personal = self::ACCESS_TYPE_PERSONAL;
        $everyone = self::ACCESS_TYPE_EVERYONE;
        $limited = self::ACCESS_TYPE_LIMITED;
        $contact_id = $this->contact_id;
        $sql = "SELECT * FROM `{$this->table}`
                    WHERE (access_type = '{$personal}' AND contact_id = '{$contact_id}') OR
                        access_type = '{$everyone}'";
        if (filesRights::inst()->isAdmin()) {
            $sql .= " OR access_type = '{$limited}'";
        } else {
            $ids = $this->getLimitedStorageIdsByLevel($level_of_limited, '>=');
            if ($ids) {
                $sql .= " OR  (access_type = '{$limited}' AND id IN(" . join(',', $ids) . "))";
            }
        }
        return $this->workupStorages($this->cacheQuery($sql)->fetchAll('id'));
    }

    public function getByType($access_type)
    {
        $storages = $this->getList(array('access_type' => $access_type));
        if ($access_type === self::ACCESS_TYPE_PERSONAL && empty($storages)) {
            $storage = $this->getVirtualPersonalStorage();
            $storages[$storage['id']] = $storage;
        }
        return $this->workupStorages($storages);
    }

    private function getList($conds, $order = 'id ASC', $limit = '')
    {
        $sql = "SELECT * FROM ".$this->table;

        $where = array();
        foreach ($conds as $cond_key => $cond_val) {
            if (strtolower(substr($cond_key, -4)) === ':not') {
                $field = substr($cond_key, 0, -4);
                if ($this->fieldExists($field)) {
                    $where[] = "`{$field}` NOT IN('" . join("','", (array) $this->escape($cond_val)) . "')";
                }
                unset($conds[$cond_key]);
            }
        }

        if ($conds) {
            $where[] = $this->getWhereByField($conds, false);
        }
        $where = join(" AND ", $where);
        if ($where != '') {
            $sql .= " WHERE ".$where;
        }
        if (!empty($limit)) {
            $sql .= " LIMIT {$limit}";
        }
        if (!empty($order)) {
            $sql .= " ORDER BY {$order}";
        }
        return $this->query($sql)->fetchAll($this->id);
    }

    public function isNameUnique($name, $storage_id = null, $except = array())
    {
        $except = filesApp::toIntArray($except);
        $conds = array(
            'name' => $name
        );
        if ($storage_id !== null) {
            $conds['id'] = filesApp::toIntArray($storage_id);
        }
        $where = $this->getWhereByField($conds);
        if ($except) {
            $where .= " AND id NOT IN(i:id)";
        }
        return !$this->query("SELECT id FROM `{$this->table}` WHERE {$where}", array(
            'id' => $except
        ))->fetchField();
    }

    public function workupStorages($storages)
    {
        foreach ($storages as &$storage) {
            $storage['backend_url'] = '#/storage/' . $storage['id'] . '/';
            if ($storage['access_type'] === self::ACCESS_TYPE_PERSONAL && $storage['contact_id'] == $this->contact_id) {
                $storage['icon'] = '';
                $storage['icon_str'] = '<i class="icon userpic icon16 userpic20 f-userpick" style="background-image: url('.$this->getUser()->getPhoto(20).');"></i>';
            } else if ($storage['access_type'] === self::ACCESS_TYPE_PERSONAL && empty($storage['icon'])) {
                $storage['icon'] = self::ICON_DEFAULT_PERSONAL;
                $storage['icon_str'] = '<i class="icon16 ' . $storage['icon'] . '"></i>';
            } else if ($storage['access_type'] === self::ACCESS_TYPE_LIMITED && empty($storage['icon'])) {
                $storage['icon'] = self::ICON_DEFAULT_LIMIT;
                $storage['icon_str'] = '<i class="icon16 ' . $storage['icon'] .'"></i>';
            } else if ($storage['access_type'] === self::ACCESS_TYPE_EVERYONE && empty($storage['icon'])) {
                $storage['icon'] = self::ICON_DEFAULT_EVERYONE;
                $storage['icon_str'] = '<i class="icon16 ' . $storage['icon'] .'"></i>';
            } else {
                $storage['icon_str'] = '<i class="icon16 ' . htmlspecialchars($storage['icon']) .'"></i>';
            }
        }

        unset($storage);
        return $storages;
    }

    public function add($data)
    {
        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['contact_id'])) {
            $data['contact_id'] = $this->contact_id;
        }
        $access_type = ifset($data['access_type']);
        if (!in_array($access_type, $this->getAllAccessTypes())) {
            $access_type = self::ACCESS_TYPE_LIMITED;
        }
        $data['access_type'] = $access_type;

        $data['name'] = $this->generateUniqueName($data['name']);

        return $this->insert($data);
    }

    public function update($id, $data)
    {
        // ignore locked
        if (!$this->sliceOffLocked(array($id))) {
            return;
        }
        // not allowed change personal access type
        if (isset($data['access_type'])) {
            $item = $this->getById($id);
            if ($item['access_type'] === self::ACCESS_TYPE_PERSONAL && $id == $this->getPersistentStorageId()) {
                unset($data['access_type']);
            }
        }
        $this->updateById($id, $data);
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function clearOwner()
    {
        $this->setOwner(null);
    }

    /**
     * Slice off files that is locked
     * @param array[int] $storage_ids
     * @return array[int]
     */
    private function sliceOffLocked($storage_ids)
    {
        $lm = new filesLockModel();
        return $lm->sliceOffLocked($storage_ids, filesLockModel::RESOURCE_TYPE_STORAGE,
            filesLockModel::SCOPE_EXCLUSIVE, $this->getOwner());
    }

    private function getVirtualPersonalStorage()
    {
        $storage = $this->getEmptyRow();
        $storage['name'] = _w('My files');
        return $storage;
    }

    protected function generateUniqueName($name, $exclude_id = null)
    {
        $exclude_ids = filesApp::dropNotPositive(filesApp::toIntArray($exclude_id));

        $prefix = "";
        $max_tries = 100;
        for ($try = 0; $try < $max_tries; $try += 1) {
            $prefix = $try > 0 ? " ({$try})" : "";
            $where = $this->getWhereByField(array('name' => $name . $prefix));
            if ($exclude_ids) {
                $where .= " AND id NOT IN(".join(',', $exclude_ids).")";
            }
            $file = $this->select('*')->where($where)->limit(1)->fetch();
            if (!$file) {
                break;
            }
        }
        return $try > $max_tries ? $name : $name . $prefix;
    }

    public function getEmptyRow() {
        $storage = parent::getEmptyRow();
        $storage['id'] = 0;
        $storage['access_type'] = self::ACCESS_TYPE_PERSONAL;
        $storage['icon'] = self::ICON_DEFAULT_LIMIT;
        $storages = array($storage['id'] => $storage);
        $storages = $this->workupStorages($storages);
        $storage = $storages[$storage['id']];
        return $storage;
    }

    /**
     * @param int $id
     * @param bool|false $reset_cache
     * @return bool
     */
    public function getStorage($id, $reset_cache = false)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        if ($reset_cache && array_key_exists($id, self::$items_cache)) {
            unset(self::$items_cache[$id]);
        }
        if (!isset(self::$items_cache[$id])) {
            $item = $this->getById($id);
            if (!$item) {
                return false;
            }
            $items = array($item['id'] => $item);
            $items = $this->workupStorages($items);
            $item = $items[$item['id']];
            self::$items_cache[$id] = $item;
        }
        return self::$items_cache[$id];
    }

    /**
     * @param int|array[]int|null $id
     */
    public function updateCount($id = null)
    {
        $where = '';
        if ($id !== null) {
            $ids = array_map('intval', (array) $id);
            $ids_str = "'" . join("','", $ids) . "'";
            $where = "WHERE s.id IN({$ids_str})";
        }
        // reset to zero (because 0 will not be catch by inner join)
        $this->exec("UPDATE `{$this->table}` s SET s.count = 0 {$where}");

        $type = filesFileModel::TYPE_FILE;

        $this->exec("UPDATE `{$this->table}` s JOIN (
            SELECT s.id, COUNT(*) AS count FROM `{$this->table}` s
                    JOIN `files_file` f ON f.storage_id = s.id AND f.type = '{$type}'
                    {$where}
                    GROUP BY s.id
            ) r ON s.id = r.id
           SET s.count = r.count");
    }

    public function getCount($id = null)
    {
        $map = array();
        $where = '';
        if ($id !== null) {
            $ids = array_map('intval', (array) $id);
            $ids_str = "'" . join("','", $ids) . "'";
            $where = "WHERE id IN({$ids_str})";
            $map = array_fill_keys($ids, 0);
        }
        $res = $this->query("SELECT id, count FROM `{$this->table}` {$where}")->fetchAll('id', true);
        $map = $res + $map;
        if (is_numeric($id)) {
            return $map[$id];
        }
        return $map;
    }

    public function getByFiles($file_ids)
    {
        $file_ids = filesApp::toIntArray($file_ids);
        $file_ids = filesApp::dropNotPositive($file_ids);
        $sql = "SELECT DISTINCT s.* FROM `files_storage` s
                JOIN `files_file` f ON s.id = f.storage_id
                WHERE f.id IN(i:id)";
        return $this->query($sql, array(
            'id' => $file_ids
        ))->fetchAll('id');
    }

    /**
     * @param array[int] $ids
     */
    public function deleteStorage($ids)
    {
        if (!$ids) {
            return;
        }

        $file_ids = $this->query("
              SELECT f.id FROM `files_file` f
                JOIN `files_storage` s ON (f.storage_id = s.id OR f.storage_id = -s.id)
                WHERE s.id IN(i:storage_id)",
            array('storage_id' => $ids))->fetchAll(null, true);

        $fm = new filesFileModel();
        $fm->delete($file_ids);

        $this->delGroups($ids);
        $this->deleteById($ids);
    }

    /**
     * @param array[int]|int $id
     */
    public function delete($id)
    {
        $ids = filesApp::toIntArray($id);
        $persistent_storage_id = (int) $this->getPersistentStorageId();
        $ids = array_diff($ids, array($persistent_storage_id));
        $this->deleteStorage($ids);
    }

    /**
     * Delete personal storages of contacts (if null, current user)
     * @param array[int]|int|null $contact_id
     */
    public function deletePersonal($contact_id = null)
    {
        if ($contact_id === null) {
            $contact_ids = array($this->contact_id);
        } else {
            $contact_ids = filesApp::toIntArray($contact_id);
        }

        $ids = $this->query("
            SELECT id FROM {$this->table}
            WHERE access_type = s:access_type AND contact_id IN (i:contact_id)",
            array(
                'access_type' => self::ACCESS_TYPE_PERSONAL,
                'contact_id' => $contact_ids
            ))->fetchAll(null, true);

        $this->deleteStorage($ids);
    }

    public function getGroups($storage_id = null)
    {
        $where = "app_id = 'files' ";
        $storage_ids = array();

        if ($storage_id !== null) {
            $storage_ids = filesApp::toIntArray($storage_id);
            if (!$storage_ids) {
                return array();
            }
            $names = array();
            foreach ($storage_ids as $sid) {
                $names[] = "storage.{$sid}";
            }
            $names = "'" . join("','", $names) . "'";
            $where .= "AND name IN ({$names})";
        }

        $groups = array();
        if ($storage_id !== null) {
            $groups = array_fill_keys($storage_ids, array());
        }

        $query = $this->cacheQuery("SELECT * FROM `wa_contact_rights` WHERE {$where}");
        foreach($query as $item) {
            $sid = (int) substr($item['name'], 8);
            $groups[$sid][$item['group_id']] = $item['value'];
        }
        if ($storage_id !== null && !is_array($storage_id) && $groups) {
            $groups = $groups[(int) $storage_id];
        }
        return $groups;
    }

    /**
     * @param string $type user|group
     * @param null|int|array[int] $storage_id
     * @param bool|true $format
     * @return array
     */
    private function getGroupsOfType($type, $storage_id, $format = true)
    {
        $res_groups = array();
        foreach ($this->getGroups((array) $storage_id) as $st_id => $groups) {
            if ($type === 'group') {
                $res_groups[$st_id] = $this->extractGroups($groups);
            } else if ($type === 'user') {
                $res_groups[$st_id] = $this->extractUsers($groups);
            } else {
                $res_groups[$st_id] = array();
            }
        }
        if ($storage_id !== null && !is_array($storage_id)) {
            $res_groups = $res_groups[(int) $storage_id];
        }
        return $res_groups;
    }

    /**
     * Get just groups (not users)
     * @param null|int|array[int] $storage_id
     * @param bool|true $format
     * @return array
     */
    public function getJustGroups($storage_id = null, $format = true)
    {
        return $this->getGroupsOfType('group', $storage_id, $format);
    }

    /**
     * Get just users (not groups)
     * @param null|int|array[int] $storage_id
     * @param bool|true $format
     * @return array
     */
    public function getJustUsers($storage_id = null, $format = true)
    {
        return $this->getGroupsOfType('user', $storage_id, $format);
    }

    public function delGroups($storage_id)
    {
        $storage_ids = filesApp::toIntArray($storage_id);
        $this->sliceOffLocked($storage_ids);
        if (!$storage_ids) {
            return;
        }

        $crm = new waContactRightsModel();
        $names = array();
        foreach ($storage_ids as $sid) {
            $names[] = "storage.{$sid}";
        }
        $crm->deleteByField(array(
            'app_id' => $this->app_id,
            'name' => $names
        ));
    }

    public function setGroups($storage_id, $groups)
    {
        $storage_ids = filesApp::toIntArray($storage_id);
        $this->sliceOffLocked($storage_ids);
        if (!$storage_ids) {
            return;
        }

        $this->delGroups($storage_id);

        $levels = filesApp::inst()->getRightConfig()->getRightLevels();
        unset($levels[filesRightConfig::RIGHT_LEVEL_NONE]);

        filesRights::inst()->setLimitedAccess(array_keys($groups));

        $crm = new waContactRightsModel();
        foreach ($storage_ids as $sid) {
            foreach ($groups as $g_id => $level) {
                if (isset($levels[$level])) {
                    $crm->save(-$g_id, $this->app_id, "storage.{$sid}", $level);
                }
            }
        }

    }

    public function getAllAccessTypes()
    {
        return $this->getConstantsByPrefix("ACCESS_TYPE_");
    }

    /**
     * Delete all files inside storage
     * @param int $id
     */
    public function clear($id)
    {
        $storage = $this->getStorage($id);
        if (!$storage) {
            return;
        }
        $file_ids = $this->select('id')->where('parent_id = i:0', array($storage['id']))->fetchAll(null, true);
        $fm = new filesFileModel();
        $fm->delete($file_ids);
    }

    /**
     * @param int|array $storages
     * @see filesApp::typecastStorages method for checking format
     * @return bool|array[]bool
     */
    public function inSync($storages)
    {
        $typecast = filesApp::typecastStorages($storages);
        $source_id_storage_id_map = array();
        foreach ($typecast['storages'] as $storage) {
            $source_id = abs((int) ifset($storage['source_id']));
            $source_id_storage_id_map[$source_id] = $storage['id'];
        }

        $source_ids = array_unique(array_keys($source_id_storage_id_map));
        $sync_sources_map = filesSourceSync::inSync($source_ids);

        $source_sync_map = array_fill_keys($source_id_storage_id_map, false);
        if ($typecast['type'] === 'id' || $typecast['type'] === 'record') {
            $storage = reset($typecast['storages']);
            return ifset($sync_sources_map[abs((int) ifset($storage['source_id']))], false);
        }

        foreach ($sync_sources_map as $source_id => $is_synced) {
            $storage_id = $source_id_storage_id_map[$source_id];
            $source_sync_map[$storage_id] = $is_synced;
        }
        return $source_sync_map;
    }

    public function getAllSourcesInsideStorage($storage_id)
    {
        $storage_id = (int) $storage_id;
        return $this->query("SELECT DISTINCT source_id FROM `files_file` WHERE storage_id = ?", $storage_id)->fetchAll(null, true);
    }

}

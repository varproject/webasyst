<?php

class filesModel extends waModel
{
    /**
     * @var waUser
     */
    protected $user;

    /**
     * @var int
     */
    protected $contact_id;

    /**
     * @var string
     */
    protected $app_id = 'files';

    /**
     * @var array
     */
    static private $query_cache = array();

    /**
     * @var array
     */
    private $models = array();

    protected $banned_symbols = array('/', '\\', ':', '?', '*', '"', /*"'",*/ '|');

    public function __construct($type = null, $writable = false) {
        parent::__construct($type, $writable);
        $this->user = wa()->getUser();
        $this->contact_id = $this->user->getId();
    }

    /**
     * @return waContact
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param waContact $user
     */
    public function setUser(waContact $user)
    {
        $this->user = $user;
    }

    public function getFirst($field = null)
    {
        if (!$field) {
            $q = $this->select('*')->limit(1);
        } else {
            $where = $this->getWhereByField($field);
            $q = $this->select('*')->where($where)->limit(1);
        }
        $res = $q->fetchAll();
        return reset($res);
    }

    public function getEmptyRow()
    {
        $result = array();
        foreach ($this->getMetadata() as $fld_id => $fld) {
            if (isset($fld['default'])) {
                $result[$fld_id] = $fld['default'];
            } else {
                if (!isset($fld['null']) || $fld['null']) {
                    $result[$fld_id] = null;
                } else {
                    $result[$fld_id] = '';
                }
            }
        }
        return $result;
    }

    public function getBannedSymbols($regexp = true)
    {
        if (!$regexp) {
            return $this->banned_symbols;
        } else {
            $str = array();
            foreach ($this->banned_symbols as $symbol) {
                $str[] = preg_quote($symbol, '/');
            }
            return "/(" . join('|', $str) . ")/";
        }
    }

    protected function extractGroups($group_and_users = array())
    {
        $groups = array();
        $all_groups = filesRights::inst()->getAllGroups();
        foreach ($group_and_users as $gid => $item) {
            if ($gid > 0) {
                if (!is_array($item)) {
                    $item = array('level' => $item);
                }
                $name = ifset($all_groups[$gid], sprintf(_w('Group %s'), $gid));
                $item['name'] = $name;
                $groups[$gid] = $item;
            }
        }
        return $groups;
    }

    protected function extractUsers($group_and_users = array())
    {
        // get group ids with < 0 - these are contact ids
        $negative_group_ids = array_filter(array_keys($group_and_users), wa_lambda('$id', 'return $id < 0;'));
        $contact_ids = filesApp::negativeValues($negative_group_ids);
        if (!$contact_ids) {
            return array();
        }
        // get contacts
        $contacts = filesApp::getContacts($contact_ids);

        // collect users where
        $users = array();
        foreach ($group_and_users as $gid => $item) {
            if ($gid < 0) {
                $uid = -$gid;
                if (!is_array($item)) {
                    $item = array('level' => $item);
                }
                $contact = $contacts[$uid];
                if (!$contact['exists']) {
                    $contact['id'] = $uid;
                    $contact['name'] = sprintf(_w("User %s"), $uid);
                }
                $users[$uid] = array_merge($contact, $item);
            }
        }

        return $users;
    }

    /**
     * @param string $sql
     * @param string|null $key
     * @return waDbResultDelete|waDbResultInsert|waDbResultReplace|waDbResultSelect|waDbResultUpdate
     */
    public function cacheQuery($sql, $key = null)
    {
        $key = $key ? $key : md5($sql);
        if (!isset(self::$query_cache[$key])) {
            self::$query_cache[$key] = $this->query($sql);
        }
        return self::$query_cache[$key];
    }

    /**
     * @param string|null $key
     */
    public function clearQueryCache($key = null)
    {
        if ($key === null) {
            self::$query_cache = array();
        } else if (array_key_exists($key, self::$query_cache)) {
            unset(self::$query_cache[$key]);
        }
    }

    /**
     * @return filesStorageModel
     */
    public function getStorageModel()
    {
        return ifset($this->models['storage'], new filesStorageModel());
    }

    /**
     * @param mixed $owner
     * @return filesFileModel
     */
    public function getFileModel($owner = null)
    {
        /**
         * @var filesFileModel $fm
         */
        $fm = ifset($this->models['file'], new filesFileModel());
        $fm->setOwner($owner);
        return $fm;
    }


    /**
     * @return filesFileRightsModel
     */
    public function getFileRightsModel()
    {
        return ifset($this->models['file_rights'], new filesFileRightsModel());
    }

    /**
     * @return filesFavoriteModel
     */
    public function getFavoriteModel()
    {
        return ifset($this->models['favorite'], new filesFavoriteModel());
    }

    /**
     * @return filesFileCommentsModel
     */
    public function getFileCommentsModel()
    {
        return ifset($this->models['file_comments'], new filesFileCommentsModel());
    }

    /**
     * @return filesFilterModel
     */
    public function getFilterModel()
    {
        return ifset($this->models['filter'], new filesFilterModel());
    }

    /**
     * @return filesTagModel
     */
    public function getTagModel()
    {
        return ifset($this->models['tag'], new filesTagModel());
    }

    /**
     * @return filesFileTagsModel
     */
    public function getFileTagsModel()
    {
        return ifset($this->models['file_tags'], new filesFileTagsModel());
    }

    /**
     * @return filesCopytaskModel
     */
    public function getCopytaskModel()
    {
        return ifset($this->models['copytask'], new filesCopytaskModel());
    }

    /**
     * @return filesTasksQueueModel
     */
    public function getTasksQueueModel()
    {
        return ifset($this->models['tasks_queue'], new filesTasksQueueModel());
    }

    /**
     * @return filesSourceModel
     */
    public function getSourceModel()
    {
        return ifset($this->models['source'], new filesSourceModel());
    }

    /**
     * @return filesSourceParamsModel
     */
    public function getSourceParamsModel()
    {
        return ifset($this->models['source_params'], new filesSourceParamsModel());
    }

    /**
     * @return filesMessagesQueueModel
     */
    public function getMessageQueueModel()
    {
        return ifset($this->models['message_queue'], new filesMessagesQueueModel());
    }

    /**
     * @return filesLockModel
     */
    public function getLockModel()
    {
        return ifset($this->models['lock'], new filesLockModel());
    }

    protected function getConstantsByPrefix($prefix = '')
    {
        $len = strlen($prefix);
        $refl = new ReflectionClass($this);
        $constants = array();
        foreach ($refl->getConstants() as $name => $val) {
            if (substr($name, 0, $len) === $prefix) {
                $constants[$name] = $val;
            }
        }
        return $constants;
    }

    /**
     * @return filesConfig
     */
    protected function getConfig()
    {
        return filesApp::inst()->getConfig();
    }
}

<?php

/**
 *
 * Singleton class for work with rights in app
 *
 */
class filesRights
{
    /**
     * @var \filesRights
     */
    private static $instance;

    /**
     * @var string
     */
    private $app_id = 'files';

    /**
     * @var waContact
     */
    private $user;

    /**
     * @var int
     */
    private $contact_id;

    private $all_groups;
    private $group_ids;
    private $all_admins = array();
    private $can_create_storage;


    private function __construct() {
        $this->user = wa($this->app_id)->getUser();
        $this->contact_id = $this->user->getId();
    }

    private function __clone() {
        ;
    }

    /**
     * @return filesRights
     */
    public static function inst()
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->app_id;
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

    /**
     * Is current user admin to app
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->hasFullAccess();
    }

    /**
     * Has current user full access to app
     * @return boolean
     */
    public function hasFullAccess()
    {
        return $this->getUser()->getRights($this->app_id, 'webasyst') ||
                $this->getUser()->getRights($this->app_id, 'backend') > 1;
    }

    /**
     * Has current user limited access to app
     * @return boolean
     */
    public function hasLimitedAccess()
    {
        return $this->getUser()->getRights($this->app_id, 'backend') == 1;
    }

    /**
     * Check can current user create any storage
     * @return bool
     */
    public function canCreateStorage()
    {
        if ($this->can_create_storage === null) {
            $this->can_create_storage = false;
            if ($this->hasFullAccess()) {
                $this->can_create_storage = true;
            } else {
                $has_that_right = $this->getUser()->getRights($this->app_id, filesRightConfig::RIGHT_CREATE_STORAGE) == 1;
                if ($this->hasLimitedAccess() && $has_that_right) {
                    $this->can_create_storage = true;
                }
            }
        }
        return $this->can_create_storage;
    }

    /**
     * Check can current user create any folder in current place
     * @param int $storage_id
     * @param int $folder_id
     * @return bool
     */
    public function canCreateFolder($storage_id, $folder_id)
    {
        $storage_id = (int) $storage_id;
        $folder_id = (int) $folder_id;
        if ($folder_id) {
            return filesRights::inst()->canAddNewFilesIntoFolder($folder_id);
        } else {
            return filesRights::inst()->canAddNewFilesIntoStorage($storage_id);
        }
    }

    public function hasAccessToSourceModule()
    {
        return $this->canCreateStorage();
    }

    public function hasAccessToSource($source_id)
    {
        if ($source_id <= 0 || $this->isAdmin()) {
            return true;
        }
        $sm = new filesSourceModel();
        $source = $sm->getById($source_id);
        if (!$source) {
            return true;
        }
        return $source['contact_id'] == $this->contact_id;
    }

    /**
     * Has current user no access to app
     * @param array[int]|int|null $group_id
     * @return array|bool
     */
    public function hasNoAccess($group_id = null)
    {
        if ($group_id !== null) {
            $group_ids = filesApp::toIntArray($group_id);
            if (empty($group_id)) {
                return;
            }
        } else {
            $group_ids[] = -$this->contact_id;
        }

        $crm = new waContactRightsModel();
        $map = array_fill_keys($group_ids, true);
        $group_ids_str = join(',', $group_ids);
        $app_id = $this->getAppId();


        $res = $crm->select('*')->where("group_id IN({$group_ids_str}) AND app_id IN ('{$app_id}', 'webasyst') AND name='backend'")->fetchAll();
        foreach ($res as $item) {
            if ($item['value'] > 0) {
                $map[$item['group_id']] = false;
            }
        }

        if (is_array($group_id)) {
            return $map;
        } else if ($group_id === null) {
            return $map[-$this->contact_id];
        } else {
            return $map[(int) $group_id];
        }
    }

    /**
     * Check and set if not set yet limited access to specified groups and users
     * @param null $group_id
     */
    public function setLimitedAccess($group_id)
    {
        if (empty($group_id)) {
            return;
        }
        $group_ids = filesApp::toIntArray($group_id);

        // set limited access only for groups has no access
        $no_access_map = filesRights::inst()->hasNoAccess($group_ids);

        $set_limited = array();
        foreach ($no_access_map as $group_id => $bool) {
            if ($bool) {
                $set_limited[] = $group_id;
            }
        }
        if (!$set_limited) {
            return;
        }

        $group_ids = $set_limited;

        $app_id = $this->getAppId();
        $crm = new waContactRightsModel();

        $values = array();
        foreach ($group_ids as $group_id) {
            $values[] = "({$group_id}, '{$app_id}', 'backend', 1)";
        }
        $values = join(',', $values);

        $table_name = $crm->getTableName();
        $crm->exec("INSERT IGNORE {$table_name} (group_id, app_id, name, value) VALUES {$values}");
    }

    /**
     * Check access level of file(s) or folder(s)
     * Take into account max rights of file(s) or folder(s) and
     * increasing  nature of rights (none < read < ... < full)
     *
     * @param array|int $file_id
     * @param int $level level of access
     * @see \filesRightConfig for checking access levels
     *
     * @return array|boolean
     *  If $file_id is array then returns map of boolean for each file_id
     *  otherwise boolean
     */
    public function checkAccessLevelOfFile($file, $level)
    {
        $typecast = filesApp::typecastFiles($file);
        $input_type = $typecast['type'];
        $files = $typecast['files'];
        $file_ids = array_keys($files);
        $map = array_fill_keys($file_ids, false);
        $frm = new filesFileRightsModel();
        foreach ($frm->getMaxRightsToFile($file_ids) as $f_id => $lvl) {
            $map[$f_id] = $lvl >= $level;
        }

        if ($input_type === 'record' || $input_type === 'id') {
            return $map[$file_ids[0]];
        } else {
            return $map;
        }

    }

    /**
     * Check access level of storage
     * Take into account max rights of storage and
     * increasing  nature of rights (read < read_and_comment < ... < full)
     *
     * Check for NO ACCESS level by this method is not allowed
     *
     * @param array|int $storages
     * @param int $level level of access
     * @see filesRightConfig for checking access levels
     * @throws waException If level is not corrected generate exception
     * @return array|boolean
     *  If $storage_id is array then returns map of boolean for each storage_id
     *  otherwise boolean
     */
    public function checkAccessLevelOfStorage($storages, $level)
    {
        if ($level == filesRightConfig::RIGHT_LEVEL_NONE) {
            throw new waException("Right level is not allowed");
        }

        $input_storages = $storages;

        $typecast = $this->typecastStorages($storages);
        $input_type = $typecast['type'];
        $storages = $typecast['storages'];

        $empty_mark = 0;
        $foreign_mark = -1;
        $everyone_mark = 1;
        $own_mark = 2;
        $full_access_mark = 3;

        $storage_ids = array_keys($storages);

        // map of access, if value <= 0 - mean no access
        $map = array_fill_keys($storage_ids, $empty_mark);

        // mark storages
        foreach ($storages as $storage_id => $storage) {
            if ($storage['access_type'] === filesStorageModel::ACCESS_TYPE_PERSONAL &&
                $storage['contact_id'] != $this->contact_id)
            {
                $map[$storage_id] = $foreign_mark;
            } else if ($storage['access_type'] === filesStorageModel::ACCESS_TYPE_PERSONAL &&
                $storage['contact_id'] == $this->contact_id) {
                $map[$storage_id] = $own_mark;
            } else if ($storage['access_type'] === filesStorageModel::ACCESS_TYPE_EVERYONE) {
                $map[$storage_id] = $everyone_mark;
            }
        }

        if ($this->hasFullAccess()) {
            // has access to all storages that not marked
            foreach ($storages as $storage_id => $storage) {
                $map[$storage_id] = $map[$storage_id] !== $empty_mark ? $map[$storage_id] : $full_access_mark;
            }
        } else if ($this->hasLimitedAccess()) {
            // in limited access, check setted rights, but take into account foreigner
            $user = $this->getUser();
            foreach ($storages as $storage_id => $storage) {
                // check only that storages that are not marked
                if ($map[$storage_id] === $empty_mark) {
                    $lvl = $user->getRights($this->app_id, 'storage.' . $storage_id);
                    $map[$storage_id] = $lvl >= $level;
                }
            }
        }

        // convert to true/false map, if mark >= 0 - true (has access to storage), otherwise false
        foreach ($map as &$mark) {
            $mark = $mark > 0;
        }
        unset($mark);

        if ($input_type === 'id') {
            return ifset($map[$input_storages], false);
        } else if ($input_type === 'record') {
            return ifset($map[$input_storages['id']], false);
        } else {
            return $map;
        }
    }

    /**
     * Check can current user add new files into storage(s)
     *
     * @param array|int $storage_id
     * @return array|boolean
     *  If $storage_id is array then returns map of boolean for each storage_id
     *  otherwise boolean
     */
    public function canAddNewFilesIntoStorage($storage_id)
    {
        return $this->checkAccessLevelOfStorage($storage_id,
                filesRightConfig::RIGHT_LEVEL_ADD_FILES);
    }


    /**
     * Check can current user read files in this storage(s)
     *
     * @param array|int $storage_id
     * @return array|boolean
     *  If $storage_id is array then returns map of boolean for each storage_id
     *  otherwise boolean
     */
    public function canReadFilesInStorage($storage_id)
    {
        return $this->checkAccessLevelOfStorage($storage_id,
                filesRightConfig::RIGHT_LEVEL_READ);
    }

    /**
     * Check has current user full access to storage(s)
     *
     * @param array|int $storage_id
     * @return array|boolean
     *  If $storage_id is array then returns map of boolean for each storage_id
     *  otherwise boolean
     */
    public function hasFullAccessToStorage($storage_id)
    {
        return $this->checkAccessLevelOfStorage($storage_id,
                filesRightConfig::RIGHT_LEVEL_FULL);
    }

    public function canDeleteStorage($storage)
    {
        $sm = new filesStorageModel();
        if (is_numeric($storage)) {
            $storage = $sm->getStorage($storage);
        }
        $persistent_id = $sm->getPersistentStorageId();
        $storage['is_persistent'] = $storage['id'] === $persistent_id;
        return $this->canEditStorage($storage) && !$storage['is_persistent'];
    }

    public function canEditStorage($storage)
    {
        $sm = new filesStorageModel();
        if (is_numeric($storage)) {
            $storage = $sm->getStorage($storage);
        }
        $full_access = $this->hasFullAccessToStorage($storage['id']);
        return $this->canCreateStorage() && $full_access;
    }

    /**
     *
     * Check full access to folder(s)/file(s)
     *
     * Full access to folder/file is
     *
     * User is ADMIN and storage is not personal for another user OR
     * Storage has everyone access type (sandbox) OR
     * Storage is personal for current user OR
     * Full access to folder/file (by storage or sharing rights) OR
     * Add files access to folder/file (by storage or sharing rights) + current user is author of folder/file
     *
     * @param array|int $files Can be associative array of folders/files db-records or array of its IDs or int ID
     *
     * @return array|bool Depends of type of input parameter.
     *  If input parameter was asociative array than return array of same asociative array but with flag :access
     *  If input parameter was array of IDs return map file_id => bool
     *  If input paremater was int ID return bool
     *
     */
    public function hasFullAccessToFile($files)
    {
        // typecast
        $typecast = filesApp::typecastFiles($files);
        $input_type = $typecast['type'];
        $files = $typecast['files'];

        // trivial case
        if (!$files) {
            return $input_type !== 'id' ? array() : false;
        }
        $files_count = count($files);
        $file_ids = array_keys($files);

        // set access for every file to false
        foreach ($files as &$file) {
            $file[':access'] = false;
        }
        unset($file);

        // get storage IDs (take into account that storage id could be negative - for files in trash)
        $storage_ids = filesApp::getFieldValues($files, 'storage_id');
        $storage_ids = filesApp::absValues($storage_ids);

        $storage_model = new filesStorageModel();

        // get storages (optimization hack: use cached method if it's one item array)
        if (count($storage_ids) === 1) {
            $storages = array($storage_ids[0] => $storage_model->getStorage($storage_ids[0]));
        } else {
            $storages = $storage_model->getById($storage_ids);
        }


        // map storage_id => type-mark
        // type-marks: null, 'foreigner' (personal but not mine), 'mine', 'everyone'
        $storage_marks = array();
        foreach ($storages as $st_id => $st) {
            $storage_marks[$st_id] = null;
            $access = $st['access_type'];
            $contact_id = $st['contact_id'];
            if ($access == filesStorageModel::ACCESS_TYPE_PERSONAL && $contact_id != $this->contact_id) {
                $storage_marks[$st_id] = 'foreigner';
            } else if ($access == filesStorageModel::ACCESS_TYPE_PERSONAL && $contact_id == $this->contact_id) {
                $storage_marks[$st_id] = 'mine';
            } else if ($access == filesStorageModel::ACCESS_TYPE_EVERYONE) {
                $storage_marks[$st_id] = 'everyone';
            }
        }

        $is_admin = $this->hasFullAccess();

        // map of allowed files
        $allowed_files = array();

        // check this simpliest conditions
        foreach ($files as $id => &$file) {
            $st_id = $file['storage_id'];
            $mark = $storage_marks[$st_id];
            if (($is_admin && $st_id === 'foreigner') || $mark === 'everyone' || $mark === 'mine') {
                $allowed_files[$id] = true;
                $file[':access'] = true;
            }
        }
        unset($file);

        // optimization, if all files are allowed, function returns
        if (count($allowed_files) === $files_count) {
            return $input_type !== 'id' ?
                ($input_type === 'ids' ? array_fill_keys($file_ids, true) : $files) :
                true;
        }

        // Check full access (by storage or sharing rights)
        $full_access_to_storage = $this->hasFullAccessToStorage($storage_ids);
        $full_access_to_file_by_sharing = $this->checkAccessLevelOfFile($file_ids, filesRightConfig::RIGHT_LEVEL_FULL);
        foreach ($files as $id => &$file) {
            $st_id = $file['storage_id'];
            if ($full_access_to_storage[$st_id] || $full_access_to_file_by_sharing[$id]) {
                $allowed_files[$id] = $file;
                $file[':access'] = true;
            }
        }
        unset($file);

        // optimization, if all files are allowed, function returns
        if (count($allowed_files) === $files_count) {
            return $input_type !== 'id' ?
                ($input_type === 'ids' ? array_fill_keys($file_ids, true) : $files) :
                true;
        }

        // Check add files access (by storage or sharing rights) + current user is author of folder/file
        $add_files_access_to_storage = $this->canAddNewFilesIntoStorage($storage_ids);
        $add_files_access_to_file_by_sharing = $this->checkAccessLevelOfFile($file_ids, filesRightConfig::RIGHT_LEVEL_ADD_FILES);
        foreach ($files as $id => &$file) {
            $st_id = $file['storage_id'];
            $add_files_access_to_file = $add_files_access_to_storage[$st_id] || $add_files_access_to_file_by_sharing[$id];
            $is_author = $file['contact_id'] == $this->contact_id;
            if ($add_files_access_to_file && $is_author) {
                $allowed_files[$id] = $file;
                $file[':access'] = true;
            }
        }
        unset($file);

        // that's all checking
        if ($input_type === 'id') {
            return !empty($allowed_files);
        } else if ($input_type == 'ids') {
            $map = array();
            foreach ($files as $id => $file) {
                $map[$id] = $file[':access'];
            }
            return $map;
        } else {
            return $files;
        }
    }

    /**
     * Check can user read file or folder
     * Take into account access to storage
     *
     * @param int $file_id
     * @return boolean
     */
    public function canReadFile($file_id)
    {
        $file_model = new filesFileModel();
        $file = $file_model->getItem($file_id, false);
        if (!$file) {
            return false;
        }

        $storage_model = new filesStorageModel();
        $storage = $storage_model->getStorage($file['storage_id']);
        if (!$storage) {
            return false;
        }

        if ($this->checkAccessLevelOfStorage($storage, filesRightConfig::RIGHT_LEVEL_READ)) {
            return true;
        }

        return $this->checkAccessLevelOfFile($file['id'], filesRightConfig::RIGHT_LEVEL_READ);
    }

    /**
     * Check can user comment file
     * @param int $file_id
     * @return bool
     * @throws waException
     */
    public function canCommentFile($file_id)
    {
        $file_model = new filesFileModel();
        $file = $file_model->getItem($file_id, false);
        if (!$file) {
            return false;
        }

        $storage_model = new filesStorageModel();
        $storage = $storage_model->getStorage($file['storage_id']);
        if (!$storage) {
            return false;
        }


        if ($this->checkAccessLevelOfStorage($storage, filesRightConfig::RIGHT_LEVEL_READ_COMMENT)) {
            return true;
        }

        return $this->checkAccessLevelOfFile($file['id'], filesRightConfig::RIGHT_LEVEL_READ_COMMENT);

    }

    /**
     * Check can current user add new files into folder
     * Take into account access to storage
     * @param int $folder_id
     * @return boolean
     */
    public function canAddNewFilesIntoFolder($folder_id)
    {
        $file_model = new filesFileModel();
        $folder = $file_model->getItem($folder_id, false);
        if (!$folder || $folder['type'] !== filesFileModel::TYPE_FOLDER) {
            return false;
        }
        $storage_model = new filesStorageModel();
        $storage = $storage_model->getStorage($folder['storage_id']);
        if (!$storage) {
            return false;
        }
        // hash full access to storage
        if ($this->hasFullAccessToStorage($storage['id'])) {
            return true;
        }

        $level = filesRightConfig::RIGHT_LEVEL_ADD_FILES;

        $folder_allows = $this->checkAccessLevelOfFile($folder, $level);
        $storage_allows = $this->checkAccessLevelOfStorage($storage, $level);

        return $folder_allows || $storage_allows;
    }

    /**
     * Get all groups in system
     * @return array group_id => name
     */
    public function getAllGroups()
    {
        if ($this->all_groups === null) {
            $group_model = new waGroupModel();
            $groups = $group_model->getNames();
            asort($groups);
            $this->all_groups = $groups;
        }
        return $this->all_groups;
    }

    /**
     * Get group ids of current user
     * @return array
     */
    public function getGroupIds()
    {
        if ($this->group_ids === null) {
            $model = new waUserGroupsModel();
            $groups = $model->getGroupIds($this->contact_id);
            $groups[] = -$this->contact_id;
            $this->group_ids = $groups;
        }
        return $this->group_ids;
    }

    /**
     * Get all admins for app
     * @return array
     */
    public function getAllAdmins()
    {
        $all = array();
        $all += $this->getAllAdminGroups();
        $all += filesApp::negativeKeys($this->getAllAdminUsers());
        return $all;
    }

    /**
     * @return array
     */
    public function getAllAdminGroups()
    {
        if (!isset($this->all_admins['gropus'])) {
            $crm = new waContactRightsModel();
            $glob_admin_groups = array_keys($crm->getAllowedGroups('webasyst', 'backend'));
            $app_admin_groups = array();
            foreach ($crm->getAllowedGroups($this->getAppId(), 'backend') as $g_id => $v) {
                if ($v > 1) {
                    $app_admin_groups[] = $g_id;
                }
            }

            $all_groups = $this->getAllGroups();
            $groups = array();
            foreach (array_merge($app_admin_groups, $glob_admin_groups) as $gid) {
                $name = ifset($all_groups[$gid], _w('Group: ') . $gid);
                $groups[$gid] = array(
                    'name' => $name
                );
            }
            $this->all_admins['groups'] = $groups;
        }

        return $this->all_admins['groups'];
    }

    /**
     * @return array
     */
    public function getAllAdminUsers()
    {
        if (!isset($this->all_admins['users'])) {
            $crm = new waContactRightsModel();
            $glob_admin_users = array_keys($crm->getAllowedUsers('webasyst', 'backend'));
            $app_admin_users = array();
            foreach ($crm->getAllowedUsers($this->getAppId(), 'backend') as $u_id => $v) {
                if ($v > 1) {
                    $app_admin_users[] = $u_id;
                }
            }
            $contact_ids = array_merge($app_admin_users, $glob_admin_users);
            $contacts = filesApp::getContacts($contact_ids);
            foreach ($contacts as $contact_id => &$contact) {
                if (!$contact['exists']) {
                    $contact['id'] = $contact_id;
                    $contact['name'] = sprintf(_w('User %s'), $contact['id']);
                }
            }
            unset($contact);
            $this->all_admins['users'] = $contacts;
        }

        return $this->all_admins['users'];

    }

    /**
     * Private helper method for typecast input parameter storages.
     *
     * @param array|int $storages
     * It can be
     *     numeric ID,
     *     array of IDs,
     *     associative array-record from DB
     *     array of associative array-records from DB
     *
     * @return array with keys type (id|ids|record|records) and storages: storages from DB
     *
     */
    private function typecastStorages($storages)
    {
        $input_type = '';

        if (is_numeric($storages)) {
            $input_type = 'id';
        }

        $storages = (array) $storages;

        // if it single record ?
        if (isset($storages['id'])) {
            $input_type = 'record';
            $storages = array(
                $storages['id'] => $storages
            );
        }

        $storage = reset($storages);

        if (isset($storage['id']) && !$input_type) {
            $input_type = 'records';
        }

        if (is_numeric($storage)) {
            $storage_ids = filesApp::toIntArray($storages);
            $storage_model = new filesStorageModel();

            // optimization hack: use cached method if one item array
            if (count($storage_ids) === 1) {
                $storages = array($storage_ids[0] => $storage_model->getStorage($storage_ids[0], false));
            } else {
                $storages = $storage_model->getById($storage_ids);
            }

            $input_type = $input_type ? $input_type : 'ids';
        }

        return array(
            'type' => $input_type,
            'storages' => $storages
        );
    }

    /**
     * Drop folders/files that can't be restored
     * @param array $files array of IDs or array of folder/files (records from DB)
     * @return array|null
     *  If input parameter is correct than array of ids or array of files (records from DB)
     *  Otherwise null
     */
    public function dropUnallowedToMove($files = array())
    {
        $typecast = filesApp::typecastFiles($files);
        $input_type = $typecast['type'];
        if ($input_type == 'id') {
            return null;
        }
        $files = $typecast['files'];
        $allowed_files = array();
        foreach ($this->hasFullAccessToFile($files) as $id => $file) {
            if ($file[':access']) {
                $allowed_files[$id] = $file;
            }
        }
        return $input_type === 'ids' ? array_keys($allowed_files) : $allowed_files;
    }

    /**
     * @param array $files
     * @return null
     */
    public function dropUnallowedToCopy($files = array())
    {
        $typecast = filesApp::typecastFiles($files);
        $input_type = $typecast['type'];
        if ($input_type == 'id') {
            return null;
        }
        $file_ids = $typecast['ids'];

        $hash = 'list/' . join(',', $file_ids);
        $collection = new filesCollection($hash);
        $allowed_files = $collection->getItems('*', 0, count($file_ids));
        return $input_type === 'ids' ? array_keys($allowed_files) : $allowed_files;
    }

    /**
     * Drop that files to which isn't any access
     * @param array $files array of IDs or array of folder/files (records from DB)
     * @param array $options
     * @return array|null
     *  If input parameter is correct than array of ids or array of files (records from DB)
     *  Otherwise null
     */
    public function dropHasNotAnyAccess($files = array(), $options = array())
    {
        $typecast = filesApp::typecastFiles($files);
        $input_type = $typecast['type'];
        if ($input_type == 'id') {
            return null;
        }
        $files = $typecast['files'];
        if (!$files) {
            return array();
        }
        $file_ids = array_keys($files);
        $col = new filesCollection('trash/' . implode(',', $file_ids), $options);
        $rest_files = $col->getItems('*', 0, count($file_ids));
        return $input_type === 'ids' ? array_keys($rest_files) : $rest_files;
    }

    /**
     * Check can be that all folders/files be replaced in this storage
     *
     * @param array $files of file-records (from DB)
     * @return boolean
     */
    public function canReplaceFiles($files)
    {
        $files = $this->hasFullAccessToFile($files);
        $can = true;
        foreach ($files as $file) {
            if (!$file[':access']) {
                $can = false;
                break;
            }
        }
        return $can;
    }

    public function isStoragesPersonal($storages)
    {
        $typecast = filesApp::typecastStorages($storages);
        $storages = $typecast['storages'];
        $map = array_fill_keys($typecast['ids'], false);
        foreach ($storages as $storage) {
            $map[$storage['id']] = $storage['access_type'] === filesStorageModel::ACCESS_TYPE_PERSONAL;
        }
        if ($typecast['type'] === 'id' || $typecast['type'] === 'record') {
            return $map[$typecast['ids'][0]];
        } else {
            return $map;
        }
    }

    public function isFilesPersonal($files)
    {
        $typecast = filesApp::typecastFiles($files);
        $files = $typecast['files'];

        // receive storage personality map
        $storage_ids = array();
        foreach ($files as $file) {
            $storage_ids[] = (int) $file['storage_id'];
        }
        $storage_ids = array_unique($storage_ids);
        $storage_map = $this->isStoragesPersonal($storage_ids);

        // receive files rights map
        $frm = new filesFileRightsModel();
        $groups = $frm->getGroups($typecast['ids'], true);


        $map = array_fill_keys($typecast['ids'], false);
        foreach ($files as $file) {
            $is_storage_personal = ifset($storage_map[$file['storage_id']], false);
            $is_shared = ifset($groups[$file['id']], false);
            $map[$file['id']] = $is_storage_personal && !$is_shared;
        }

        if ($typecast['type'] === 'id' || $typecast['type'] === 'record') {
            return $map[$typecast['ids'][0]];
        } else {
            return $map;
        }
    }
}

<?php

class filesFileModel extends filesNestedSetModel
{
    const TYPE_FOLDER = 'folder';
    const TYPE_FILE = 'file';
    const TRASH_TYPE_ALL = '';
    const TRASH_TYPE_PERSONAL = 'personal';

    /**
     * @var string
     */
    protected $table = 'files_file';

    /**
     * @var string
     */
    protected $left = 'left_key';

    /**
     * @var string
     */
    protected $right = 'right_key';

    /**
     * @var string
     */
    protected $depth = 'depth';

    /**
     * @var string
     */
    protected $parent = 'parent_id';

    /**
     * @var string
     */
    protected $root = 'storage_id';

    /**
     * @var waContact
     */
    protected $user;

    /**
     * @var int
     */
    protected $contact_id;

    /**
     * @var int
     */
    private $ext_max_len;

    /**
     * @var string
     */
    private $trash_type_key = 'trash_type';

    /**
     * @var string
     */
    private $owner;

    /**
     * @var mixed
     */
    private $source_ignoring = false;

    /**
     * @var bool
     */
    private $check_in_sync = false;

    /**
     * @var array
     */
    protected $async_operations = array();

    /**
     * @var array
     */
    static private $items_cache = array();

    public function __construct($type = null, $writable = false)
    {
        parent::__construct($type, $writable);
        $this->user = wa()->getUser();
        $this->contact_id = wa()->getUser()->getId();
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

    public function addFile($data, $upload_file = null)
    {
        // available types of upload_file
        if (!empty($upload_file) &&
            !filesApp::isStream($upload_file) &&
            !filesApp::isRequestFile($upload_file))
        {
            return false;
        }

        // set upload file
        $data['upload_file'] = $upload_file;

        if (!isset($data['source_id'])) {
            $data['source_id'] = $this->getItemSourceId($data);
        }

        // prepare data
        $data = $this->prepareBeforeAdd($data, self::TYPE_FILE);
        if (!$data) {
            return false;
        }

        // find out source
        $source = $this->getSource(ifset($data['source_id'], ''));
        if ($this->check_in_sync && $source->inSync()) {
            return false;
        }

        $params = array('files' => array($data));
        $params = $source->beforeAdd($params);
        $data = ifset($params['files'][0]);
        if (!$data) {
            return false;
        }

        // insert itself
        $file_info = $this->insertFile($data);
        if (!$file_info) {

            // if source want to rollback
            $data['db_fail'] = true;
            $params['files'] = array($data);
            $source->afterAdd($params);

            return false;
        }

        // update counter of storage
        $storage_id = $file_info['storage_id'];
        $file_id = $file_info['id'];
        $storage_model = new filesStorageModel();
        $storage_model->updateCount($storage_id);

        // update parent related data
        $parent = null;
        if ($file_info['parent_id']) {
            $parent = $this->getFolder($file_info['parent_id']);
        }
        if ($parent) {
            $ancestors = $this->getAncestors($parent['id']);
            $parent_chain_ids = array_keys($ancestors);
            $parent_chain_ids[] = $parent['id'];
            $this->refreshFoldersByContent($parent_chain_ids);
            $frm = new filesFileRightsModel();
            $frm->cloneRights($parent['id'], $file_info['id']);
            if ($parent['hash']) {
                $this->updateById($file_id, array(
                    'hash' => $parent['hash']
                ));
            }
        }

        $files = array($file_info['id'] => array_merge($data, $file_info));
        $params['files'] = $files;
        $params = $source->afterAdd($params);
        if ($params) {
            $this->afterAddOrReplaceUpdate($files, ifset($params['files']));
        }

        return $file_info['id'];
    }

    public function replaceFile($data, $upload_file = null)
    {
        if (empty($data['id'])) {
            return false;
        }

        $in_copy_process = $this->select('in_copy_process')->where('id = ?', $data['id'])->fetchField();
        if ($in_copy_process) {
            return false;
        }

        if (!$this->sliceOffLocked(array($data['id']))) {
            return false;
        }

        // available types of upload_file
        if (!empty($upload_file) &&
            !filesApp::isStream($upload_file) &&
            !filesApp::isRequestFile($upload_file))
        {
            return false;
        }

        // set upload file
        $data['upload_file'] = $upload_file;

        // prepare data
        $data = $this->prepareBeforeReplace($data);
        if (!$data) {
            return false;
        }

        // found out source
        $source = $this->getSource(ifset($data['source_id'], ''));
        
        if ($this->check_in_sync && $source->inSync()) {
            return false;
        }

        $data['type'] = self::TYPE_FILE;

        $params = array('files' => array($data));
        $params = $source->beforeReplace($params);
        $data = ifset($params['files'][0]);
        if (!$data) {
            return false;
        }

        $file_info = $this->getFile($data['id']);
        $update = array();
        foreach ($this->getMetadata() as $field => $meta) {
            if ($field == 'id' || !array_key_exists($field, $data)) {
                continue;
            }
            if (!array_key_exists($field, $file_info) || $data[$field] != $file_info[$field]) {
                $update[$field] = $data[$field];
            }
        }

        if ($update) {
            $this->updateById($data['id'], $update);
            $file_info = $this->getFile($data['id'], true);
        }

        if (!$file_info) {
            $data['db_fail'] = true;
            $params['files'] = array($data);
            $source->afterReplace($params);
            return false;
        }

        // update parent related data
        $parent = null;
        if ($update && $file_info['parent_id']) {
            $parent = $this->getFolder($file_info['parent_id']);
        }
        if ($parent) {
            $ancestors = $this->getAncestors($parent['id']);
            $parent_chain_ids = array_keys($ancestors);
            $parent_chain_ids[] = $parent['id'];
            $this->refreshFoldersByContent($parent_chain_ids, array('update_datetime'));
        }

        // after add source event
        $files = array($file_info['id'] => array_merge($data, $file_info));
        $params['files'] = $files;
        $source->afterReplace($params);
        if ($params) {
            $this->afterAddOrReplaceUpdate($files, ifset($params['files']));
        }

        return $file_info['id'];
    }

    protected function afterAddOrReplaceUpdate($before_files, $after_files)
    {
        $fields = array_keys($this->getMetadata());

        foreach ($after_files as $id => $new_file) {
            if (isset($before_files[$id]) && $before_files[$id]['type'] === self::TYPE_FILE) {
                $old_file = $before_files[$id];

                $update = array();
                foreach ($fields as $field) {
                    if (ifset($new_file[$field]) != ifset($old_file[$field])) {
                        $update[$field] = $new_file[$field];
                    }
                }

                if ($update) {
                    $this->updateById($id, $update);
                }
            }
        }
    }

    public function syncAddFolder($folder, $source_id)
    {
        $hierarchy_keys = array('left_key', 'right_key', 'depth');
        foreach ($hierarchy_keys as $field) {
            if (array_key_exists($field, $folder)) {
                unset($folder[$field]);
            }
        }

        $folder['source_id'] = $source_id;

        $duplicate_by_path = $this->getByField(array(
            'source_id' => $folder['source_id'],
            'source_path' => $folder['source_path']
        ));
        
        $duplicate_by_inner_id = false;
        if (!empty($folder['source_inner_id'])) {
            $duplicate_by_inner_id = $this->getByField(array(
                'source_id' => $folder['source_id'],
                'source_path' => $folder['source_inner_id']
            ));
        }

        if ($duplicate_by_path && $duplicate_by_inner_id && $duplicate_by_path['id'] == $duplicate_by_inner_id['id']) {
            unset($folder['source_path'], $folder['source_inner_id']);
            $this->updateById($duplicate_by_inner_id['id'], $folder);
            return $duplicate_by_inner_id['id'];
        }

        if ($duplicate_by_path || $duplicate_by_inner_id) {
            foreach (array($duplicate_by_path, $duplicate_by_inner_id) as $duplicate) {
                if ($duplicate) {
                    // move to garbage
                    $this->deleteById($duplicate_by_path['id'], array(
                        'left_key' => null,
                        'right_key' => null,
                        'depth' => 0,
                        'source_id' => -$folder['source_id'],
                        'source_path' => null,
                        'source_inner_id' => null
                    ));
                }
            }
            $this->repairStorage($folder['storage_id']);
        }

        if (!$duplicate_by_inner_id) {
            return $this->addFolder($folder);
        }

        $inserted = $this->addFolder($folder);
        $folder['left_key'] = $inserted['left_key'];
        $folder['right_key'] = $inserted['right_key'];
        $folder['depth'] = $inserted['depth'];
        $this->deleteById($inserted['id']);
        $this->updateById($duplicate_by_inner_id['id'], $folder);
        return $duplicate_by_inner_id['id'];
    }

    public function syncAddFiles($files, $source_id)
    {
        $source_inner_ids_map = array();
        foreach ($files as $file) {
            if (!empty($file['source_inner_id'])) {
                $source_inner_ids_map[$file['source_inner_id']] = true;
            }
        }

        $files_to_update = array();
        if ($source_inner_ids_map) {
            $files_to_update = $this->getByField(array(
                'source_id' => array($source_id, -$source_id),
                'source_inner_id' => array_keys($source_inner_ids_map)
            ), 'source_inner_id');
        }

        foreach ($files as $index => $file) {
            if ($file['source_inner_id'] && isset($files_to_update[$file['source_inner_id']])) {
                $file['update_datetime'] = date('Y-m-d H:i:s');
                $this->updateByField(array(
                    'source_id' => array($source_id, -$source_id),
                    'source_inner_id' => $file['source_inner_id'],
                ), $file);
                $parent_ids[] = $file['parent_id'];
                unset($files[$index]);
                // for update ancestors and storages
                $files_to_update[$file['source_inner_id']] = array_merge(
                    $files_to_update[$file['source_inner_id']],
                    $file
                );
            }
        }

        if ($files_to_update) {
            $this->updateAncestorsOfFiles($files_to_update);
            $this->updateStoragesOfFiles($files_to_update);
            $this->updateShareSettingsOfFiles($files_to_update, true);
        }

        if ($files) {
            $this->fastAddFiles($files);
        }
    }

    /**
     * Sync delete files for sync process. Actually records will not delete, just move to garbage-area (where source < 0)
     * Work only for singular source_id
     * @param $files
     * @param int $source_id
     * @throws waDbException
     */
    public function syncDeleteFiles($files, $source_id)
    {
        $paths = array();
        foreach ($files as $file) {
            $paths[] = $file['source_path'];
        }

        $files_to_delete = $this->getByField(array(
            'source_id' => $source_id,
            'source_path' => $paths
        ), 'id');

        $repair_storage = null;

        if ($files_to_delete) {

            foreach ($files_to_delete as $item) {
                if ($item['type'] === self::TYPE_FOLDER) {
                    $repair_storage = $item['storage_id'];
                    break;
                }
            }

            // try find out source_id_inner_id violations
            $duplicates = $this->getByField(array(
                'source_id' => -$source_id,
                'source_inner_id' => filesApp::getFieldValues($files_to_delete, 'source_inner_id')
            ), 'id');

            if ($duplicates) {
                $this->deleteById(array_keys($duplicates));
            }

            // Here is time passed, maybe somehow crazy concurrency way, we have duplicate again
            // So..here is a algorithm
            // 1. Try mass moving to garbage area (where source < 0),
            // 2. If mass moving broke unique condition, then we will use singular move for each file
            //     2.1. If moving of current file broke unique condition, then we will try singular delete+update moving
            //     2.2. If delete+update moving for current file broke unique condition, than reset source_inner_id,
            //              and later delete (after foreach and maintaining invariants)


            // SQL query template
            $sql_template = "
                UPDATE files_file 
                SET source_id = -:source_id, 
                    update_datetime = :update_datetime,
                    left_key = NULL,
                    right_key = NULL,
                    depth = 0,
                    source_path = NULL
                    :EXTRA_SET
                WHERE id IN (:id)            
            ";

            // SQL query bind params template
            $params_template = array(
                'source_id' => $source_id,
                'update_datetime' => date('Y-m-d H:i:s')
            );

            // Exception on previous step
            $exception = null;

            // Step 1
            $sql = str_replace(':EXTRA_SET', '', $sql_template);
            $params = $params_template + array('id' => array_keys($files_to_delete));
            try {
                $exception = null;
                $this->exec($sql, $params);
            } catch (waDbException $e) {
                if (!$this->isDuplicateKeyError($e->getCode())) {
                    throw $e;
                }
                $exception = $e;
            }

            // Step 2
            if ($exception) {
                foreach ($files_to_delete as $file_id => $file) {
                    try {
                        $exception = null;
                        $sql = str_replace(':EXTRA_SET', '', $sql_template);
                        $params = $params_template + array('id' => array($file_id));
                        $this->exec($sql, $params);
                    } catch (waDbException $e) {
                        if (!$this->isDuplicateKeyError($e->getCode())) {
                            throw $e;
                        }
                        $exception = $e;
                    }

                    // Step 2.1.
                    if ($exception) {
                        try {
                            $exception = null;
                            $sql = str_replace(':EXTRA_SET', '', $sql_template);
                            $params = $params_template + array('id' => array($file_id));
                            $this->deleteByField(array(
                                'source_id' => -$source_id,
                                'source_inner_id' => $file['source_inner_id']
                            ));
                            $this->exec($sql, $params);
                        } catch (waDbException $e) {
                            if (!$this->isDuplicateKeyError($e->getCode())) {
                                throw $e;
                            }
                            $exception = $e;
                        }                        
                    }

                    // Step 2.2.
                    if ($exception) {
                        try {
                            $exception = null;
                            $sql = str_replace(':EXTRA_SET', ', source_inner_id = NULL', $sql_template);
                            $params = $params_template + array('id' => array($file_id));
                            $this->exec($sql, $params);
                        } catch (waDbException $e) {
                            throw $e;
                        }
                    }
                }
            }

            // maintaining invariants
            $this->updateAncestorsOfFiles($files_to_delete);
            $this->updateStoragesOfFiles($files_to_delete);

            // in garbage-area, clear rights
            $frm = new filesFileRightsModel();
            $frm->deleteByField(array(
                'file_id' => array_keys($files_to_delete)
            ));

            // cleaning, delete redundant null-items of step 2.2.
            $files_to_permanent_delete = $this->getByField(array('source_id' => -$source_id, 'source_inner_id' => null));
            if ($files_to_permanent_delete) {
                $this->delete($files_to_permanent_delete);
            }
        }

        if ($repair_storage !== null) {
            $this->repairStorage($repair_storage);
        }
    }
    
    /**
     * Fast mass inserting files
     * Ignore hashes
     * @param $files
     * @return array
     */
    private function fastAddFiles($files)
    {
        $all_files = array();

        $sids = array();

        // preparation
        foreach ($files as $file) {
            $file['storage_id'] = (int) ifset($file['storage_id']);
            if (!$file['storage_id']) {
                continue;
            }
            $file['parent_id'] = (int) ifset($file['parent_id']);
            $file['contact_id'] = (int) ifset($file['contact_id'], $this->contact_id);
            $file['create_datetime'] = date('Y-m-d H:i:s');
            $file['update_datetime'] = $file['create_datetime'];
            $file['type'] = self::TYPE_FILE;
            $file['sid'] = $this->generateHash();
            $sids[] = $file['sid'];
            $pathinfo = $this->getPathInfo($file['name']);
            $file['ext'] = $pathinfo['ext'];
            foreach (array($this->left, $this->right, $this->depth) as $key) {
                if (isset($file[$key])) {
                    unset($file[$key]);
                }
            }
            $all_files[] = $file;
        }

        if (!$all_files) {
            return array();
        }

        // last id for accelerate select after multiple insert
        $last_id = $this->select('MAX(id)')->where('type = :type', array('type' => self::TYPE_FILE))->fetchField();


        // so fast insert
        $this->multipleInsert($all_files);

        // Receive just inserted files. Use accelerate trick, use primary key and than other field
        $all_files = $this->select('*')->where('id > :id AND sid IN(:sid)', array(
            'id' => $last_id,
            'sid' => $sids
        ))->fetchAll('id');

        // update invariants
        $this->updateAncestorsOfFiles($all_files);
        $this->updateStoragesOfFiles($all_files);
        $this->updateShareSettingsOfFiles($all_files);

        return $all_files;
    }

    private function updateAncestorsOfFiles($files)
    {
        $parents = array();
        foreach ($files as $file) {
            $pid = (int) ifset($file['parent_id']);
            if ($pid > 0) {
                $parents[] = $pid;
            }
        }
        $parents = array_unique($parents);
        $ancestors = array_keys($this->getAncestors($parents));
        $all_ancestors = array_merge($ancestors, $parents);
        $all_ancestors = array_unique($all_ancestors);
        $this->refreshFoldersByContent($all_ancestors);
    }

    private function updateStoragesOfFiles($files)
    {
        $storage_ids = array();
        foreach ($files as $file) {
            $sid = (int) ifset($file['storage_id']);
            if ($sid > 0) {
                $storage_ids[] = $sid;
            }
        }
        $storage_ids = array_unique($storage_ids);
        $storage_model = new filesStorageModel();
        $storage_model->updateCount($storage_ids);
    }

    private function updateShareSettingsOfFiles($files, $reset_rights = false)
    {
        $file_ids = array();
        $parent_file_map = array();
        foreach ($files as $file) {
            $pid = (int) ifset($file['parent_id']);
            if ($pid > 0) {
                if (!isset($parent_file_map[$pid])) {
                    $parent_file_map[$pid] = array();
                }
                $parent_file_map[$pid][] = $file['id'];
                $file_ids[] = $file['id'];
            }
        }
        $parents = $this->getById(array_keys($parent_file_map));

        $frm = new filesFileRightsModel();
        if ($reset_rights) {
            $frm->deleteByField(array(
                'file_id' => $file_ids
            ));
        }
        
        // clone rights & update hashes
        foreach ($parents as $parent) {
            $files = $parent_file_map[$parent['id']];
            $frm->cloneRights($parent['id'], $files);
            if ($parent['hash']) {
                $this->updateById($files, array(
                    'hash' => $parent['hash']
                ));
            }
        }
    }

    /**
     * @param $data
     * @return bool|int
     */
    public function addFolder($data)
    {
        if (!isset($data['source_id'])) {
            $data['source_id'] = $this->getItemSourceId($data);
        }

        // prepare data
        $data = $this->prepareBeforeAdd($data, self::TYPE_FOLDER);
        if (!$data) {
            return false;
        }

        // find out source
        $source = $this->getSource(ifset($data['source_id'], ''));
        if ($this->check_in_sync && $source->inSync()) {
            return false;
        }

        $params = array('files' => array($data));
        $params = $source->beforeAdd($params);
        $data = ifset($params['files'][0]);
        if (!$data) {
            return false;
        }

        // insert itself
        $folder_id = $this->insertFolder($data);
        if (!$folder_id) {
            $data['db_fail'] = true;
            $params['files'] = array($data);
            $source->afterAdd($params);
            return false;
        }
        $folder = $this->getFolder($folder_id);

        // Copy parent rights for this folder
        $parent = $this->getById($folder['parent_id']);
        if ($parent) {
            $frm = new filesFileRightsModel();
            $frm->cloneRights($parent['id'], $folder['id']);
            if ($parent['hash']) {
                $this->updateById($folder['id'], array('hash' => $parent['hash']));
            }
        }

        // after add source event
        $params['files'] = array($folder['id'] => array_merge($data, $folder));
        $source->afterAdd($params);

        return $folder['id'];
    }

    private function prepareBeforeReplace($data)
    {
        if (empty($data['contact_id'])) {
            $data['contact_id'] = $this->contact_id;
        }
        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        if (empty($data['update_datetime'])) {
            $data['update_datetime'] = $data['create_datetime'];
        }

        foreach (array($this->left, $this->right, $this->depth) as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }

        // type-cast parent_id and storage_id and check in valid values
        $parent_id = (int) ifset($data['parent_id'], 0);
        $storage_id = (int) ifset($data['storage_id'], 0);
        if ($storage_id <= 0 && $parent_id <= 0) {
            return false;
        }

        if ($parent_id > 0 && $storage_id <= 0) {
            $parent = $this->getFolder($parent_id);
            if (!$parent) {
                return false;
            }
            $storage_id = $parent['storage_id'];
        }

        $data['parent_id'] = $parent_id;
        $data['storage_id'] = $storage_id;

        if (!empty($data['upload_file'])) {
            $data = $this->updateDataByUploadFile($data);
        }

        if (empty($data['name'])) {
            return false;
        }

        return $data;

    }

    /**
     * @param $data
     * @return null|int
     */
    private function getItemSourceId($data)
    {
        $folder_id = ifset($data['parent_id']);
        if ($folder_id) {
            $folder = $this->getFolder($folder_id);
            if ($folder) {
                return $folder['source_id'];
            }
        }

        $storage_id = ifset($data['storage_id']);
        if ($storage_id) {
            $sm = new filesStorageModel();
            $storage = $sm->getStorage($storage_id);
            if ($storage) {
                return $storage['source_id'];
            }
        }

        return null;
    }

    private function prepareBeforeAdd($data, $type)
    {
        if (empty($data['contact_id'])) {
            $data['contact_id'] = $this->contact_id;
        }
        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        if (empty($data['update_datetime'])) {
            $data['update_datetime'] = $data['create_datetime'];
        }

        // type-cast parent_id and storage_id and check in valid values
        $parent_id = (int) ifset($data['parent_id'], 0);
        $storage_id = (int) ifset($data['storage_id'], 0);
        if ($storage_id <= 0 && $parent_id <= 0) {
            return false;
        }

        if ($parent_id > 0 && $storage_id <= 0) {
            $parent = $this->getFolder($parent_id);
            if (!$parent) {
                return false;
            }
            $storage_id = $parent['storage_id'];
        }

        $data['parent_id'] = $parent_id;
        $data['storage_id'] = $storage_id;
        $data['type'] = $type;

        if (!empty($data['upload_file'])) {
            $data = $this->updateDataByUploadFile($data);
        }

        if (!isset($data['name']) || strlen($data['name']) <= 0) {
            return false;
        }

        // prepare name
        $name = ifset($data['name'], '');
        $suffix = $this->generateUniqueNameSuffix(array(
            'name' => $name,
            'storage_id' => $storage_id,
            'parent_id' => $parent_id,
            'type' => $type
        ));
        if ($suffix === false) {
            return false;
        }

        if ($type === self::TYPE_FOLDER) {
            $name .= $suffix;
        } else {
            $pathinfo = $this->getPathInfo($name);
            $name = $this->buildFileName($pathinfo, $suffix);
            $data['ext'] = $pathinfo['ext'];
        }
        $data['name'] = $name;

        return $data;
    }

    public function add($data, $parent_id = null, $before_id = null)
    {
        $type = ifset($data['type'], '');
        $data['parent_id'] = $parent_id;
        if ($type === self::TYPE_FILE) {
            return $this->addFile($data);
        } else if ($type === self::TYPE_FOLDER) {
            return $this->addFolder($data);
        } else {
            return false;
        }
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
     * @param array[int] $file_ids
     * @return array[int]
     */
    private function sliceOffLocked($file_ids)
    {
        $lm = new filesLockModel();
        return $lm->sliceOffLocked($file_ids, filesLockModel::RESOURCE_TYPE_FILE,
            filesLockModel::SCOPE_EXCLUSIVE, $this->getOwner());
    }

    /**
     * @param $files
     */
    private function sliceOffInSync($files)
    {
        if (!$this->check_in_sync) {
            return $files;
        }
        $sync_map = $this->inSync($files);
        foreach ($files as $i => $file) {
            if (wa_is_int($file)) {
                $file_id = (int) $file;
            } else {
                $file_id = $file['id'];
            }
            if (!empty($sync_map[$file_id])) {
                unset($files[$i]);
            }
        }
        return $files;
    }

    public function copy($id, $storage_id = 0, $parent_id = 0, $options = array())
    {
        $ids = filesApp::toIntArray($id);
        $ids = $this->sliceOffLocked($ids);
        if (!$ids) {
            return true;
        }

        $ids = $this->sliceOffInSync($ids);
        if (!$ids) {
            return false;
        }

        $place = array(
            'storage' => array(),
            'folder' => array(),
            'source_id' => 0
        );

        $storage_id = (int) $storage_id;
        $parent_id = (int) $parent_id;
        if ($parent_id) {
            $parent = $this->getById($parent_id);
            if (!$parent) {
                return false;
            }
            $place['folder'] = $parent;
            $place['source_id'] = $parent['source_id'];
            $storage_id = $parent['storage_id'];
        }

        if (!$storage_id && !$parent_id) {
            return false;
        }

        $sm = new filesStorageModel();
        $place['storage'] = $sm->getById($storage_id);
        if (!$place['folder']) {
            $place['source_id'] = $place['storage']['source_id'];
        }

        // get files and folders
        $items = $this->getById($ids);

        // find out source
        $source = $this->getSource($place['source_id']);

        $folders = array();
        $files = array();
        foreach ($items as $id => $item) {
            if ($item) {
                $type = ifset($item['type']);
                if ($type === self::TYPE_FILE) {
                    $files[$id] = $item;
                    $items[$id] = $item;
                } else if ($type === self::TYPE_FOLDER) {
                    $folders[$id] = $item;
                    $items[$id] = $item;
                }
            }
        }

        $copytask = new filesCopytaskModel();
        $process_id = !empty($options['process_id']) ? $options['process_id'] : $copytask->generateProcessId();

        $copy_result = array(
            'folders_count' => count($folders),
            'files_count' => count($files),
            'count' => count($items)
        );


        if (!$items) {
            return $copy_result;
        }

        $copy_result['process_id'] = $process_id;

        $is_async = !empty($this->async_operations['copy']);
        if (array_key_exists('is_async', $options)) {
            $is_async = (bool) $options['is_async'];
        }
        $ignore_children = !empty($options['ignore_children']);

        if ($folders) {

            $max_copy_folders = filesApp::inst()->getConfig()->getMaxCopyFolders();
            if ($this->countChildrenWithParents($folders) > $max_copy_folders) {
                throw new filesException(
                    sprintf(_w('Maximum amount of folders for one copy process is %s.'), $max_copy_folders)
                );
            }

            $res_folders = $this->copyFolders(
                $folders,
                array(
                    'storage' => $place['storage'],
                    'parent' => $place['folder'],
                    'source' => $source,
                    'process_id' => $process_id,
                    'is_async' => $is_async,
                    'ignore_children' => $ignore_children
                )
            );
        }

        if ($files) {
            $res_files = $this->copyFiles(
                $files,
                array(
                    'storage' => $place['storage'],
                    'parent' => $place['folder'],
                    'root' => $place['folder'],
                    'source' => $source,
                    'process_id' => $process_id,
                    'is_async' => $is_async
                )
            );
        }

        $copy_result['folders_count'] = count($folders);
        $copy_result['files_count'] = count($files);
        $copy_result['count'] = $copy_result['folders_count'] + $copy_result['files_count'];

        $copy_result['track_ids'] = array();
        $copy_result['track_ids'] += !empty($res_folders) ? $res_folders['track_ids'] : array();
        $copy_result['track_ids'] += !empty($res_files) ? $res_files['track_ids'] : array();

        $copy_result['is_async'] = $is_async;

        if ($is_async) {
            $copy_result['tasks_map'] = array();
            $copy_result['tasks_map'] += !empty($res_folders) ? $res_folders['tasks_map'] : array();
            $copy_result['tasks_map'] += !empty($res_files) ? $res_files['tasks_map'] : array();
            return $copy_result;
        }

        // update counters in all storages
        $storage_ids = filesApp::getFieldValues($items, 'storage_id');
        $storage_ids[] = $place['storage']['id'];
        $storage_ids = array_unique($storage_ids);
        $storage_ids = filesApp::dropNotPositive($storage_ids);

        $sm = new filesStorageModel();
        $sm->updateCount($storage_ids);

        // update count and update_datetime in all old parents chains and one new parent chains
        $parent_ids = filesApp::getFieldValues($items, 'parent_id');
        $parent_ids[] = $place['folder'] ? $place['folder']['id'] : 0;
        $parent_ids = array_unique($parent_ids);
        $parent_ids = filesApp::dropNotPositive($parent_ids);

        $all_ancestors = array_keys($this->getAncestors($parent_ids));
        $all_ancestors = array_merge($all_ancestors, $parent_ids);
        $all_ancestors = array_unique($all_ancestors);

        $this->refreshFoldersByContent($all_ancestors);

        // check copytask queue, and copytask queue empty for this process_id
        // don't return process_id
        if ($copytask->countByField(array('process_id' => $process_id)) <= 0) {
            unset($copy_result['process_id']);
        }

        return $copy_result;
    }

    private function copyFolders($folders, array $params)
    {

        $storage = $params['storage'];
        $parent = $params['parent'];
        $process_id = $params['process_id'];
        $is_async = !empty($params['is_async']);
        $ignore_children = !empty($params['ignore_children']);

        /**
         * @var filesSource $target_source
         */
        $target_source = $params['source'];

        // collect all new ids
        // $all_new_ids = array();

        // transform map from id of folder to clone of this folder
        // need for correct relink parents
        $transform = array();

        // id -> to clone id, for files and folders
        $track_ids = array();

        // id of folder -> id of task
        $tasks_map = array();

        $queue = new filesTasksQueueModel();

        // copy each parent folder individually (cause name conflict)
        foreach ($folders as &$folder) {

            if ($is_async) {
                $task_id = $queue->addToCopy(
                    $folder['id'],
                    $storage['id'],
                    $parent ? $parent['id'] : 0,
                    array(
                        'process_id' => $process_id
                    )
                );
                $tasks_map[$folder['id']] = $task_id;
                $track_ids[$folder['id']] = $folder['id'];
                $transform[$folder['id']] = $folder;
                continue;
            }

            $res = $this->generateUniqueNameAndExt(array(
                'name' => $folder['name'],
                'storage_id' => $storage['id'],
                'parent_id' => $parent ? $parent['id'] : 0,
                'type' => self::TYPE_FOLDER
            ));

            if ($res === false) {
                continue;
            }

            $folder['name'] = $res['name'];

            // prev source, prev storage, prev parent
            $folder['prev_parent_id'] = $folder['parent_id'];
            $folder['prev_storage_id'] = $folder['storage_id'];
            $folder['prev_source_id'] = $folder['source_id'];

            // new source, new storage, new parent
            $folder['parent_id'] = $parent ? $parent['id'] : 0;
            $folder['storage_id'] = $storage['id'];
            $folder['source_id'] = $target_source->getId();

            // new hash
            $folder['hash'] = $parent && $parent['hash'] ? $parent['hash'] : null;

            /**
             * @var filesSource $source_source
             */
            $source_source = $this->getSource($folder['prev_source_id']);

            $folder = $this->beforeInsertCopyItem(
                $folder,
                array(
                    'storage' => $storage,
                    'root' => $parent,
                    'parent' => $parent,
                ),
                $source_source,
                $target_source
            );

            if (!$folder) {
                continue;
            }

            // no id, because copy
            $folder_id = $folder['id'];
            unset($folder['id']);

            $folder['create_datetime'] = date('Y-m-d H:i:s');
            $folder['update_datetime'] = $folder['create_datetime'];

            $id = $this->insert($folder, 1);
            if (!$id) {
                $folder['db_fail'] = true;
            }

            $this->afterInsertCopyItem(
                $folder,
                array(
                    'storage' => $storage,
                    'root' => $parent,
                    'parent' => $parent,
                ),
                $source_source,
                $target_source
            );

            if (!$id) {
                continue;
            }

            $folder['id'] = $id;
            $transform[$folder_id] = $folder;
            $track_ids[$folder_id] = $id;
        }
        unset($folder);

        $child_folders = array();

        // get child folders (order by left_key is important, for correct tracking transform maps @see above)
        if (!$ignore_children) {
            $child_folders = $this->getChildren(array_keys($folders), '*', self::TYPE_FOLDER);
        }

        // copy child folders and take into account parents and its hashes
        foreach ($child_folders as $child_folder_id => $child_folder) {

            if (empty($transform[$child_folder['parent_id']])) {
                continue;
            }

            if ($is_async) {
                $task_id = $queue->addToCopy(
                    $child_folder['id'],
                    $storage['id'],
                    $parent ? $parent['id'] : 0,
                    array(
                        'parent_task_id' => ifset($tasks_map[$child_folder['parent_id']]),
                        'process_id' => $process_id
                    )
                );
                $tasks_map[$child_folder['id']] = $task_id;
                $track_ids[$child_folder['id']] = $child_folder['id'];
                $transform[$child_folder['id']] = $child_folder;
                continue;
            }

            // prev source, prev storage, prev parent
            $child_folder['prev_parent_id'] = $child_folder['parent_id'];
            $child_folder['prev_storage_id'] = $child_folder['storage_id'];
            $child_folder['prev_source_id'] = $child_folder['source_id'];

            // new source, new storage, new parent
            $child_folder['source_id'] = $target_source->getId();
            $child_folder['storage_id'] = $storage['id'];
            $new_parent = $transform[$child_folder['parent_id']];
            $child_folder['parent_id'] = $new_parent['id'];

            // new hash
            $child_folder['hash'] = $new_parent['hash'];

            // no id, because copy
            unset($child_folder['id']);

            /**
             * @var filesSource $source_source
             */
            $source_source = $this->getSource($child_folder['prev_source_id']);

            $child_folder = $this->beforeInsertCopyItem(
                $child_folder,
                array(
                    'storage' => $storage,
                    'root' => $parent,
                    'parent' => $new_parent
                ),
                $source_source,
                $target_source
            );

            if (!$child_folder) {
                continue;
            }

            $child_folder['create_datetime'] = date('Y-m-d H:i:s');
            $child_folder['update_datetime'] = $child_folder['create_datetime'];

            $id = $this->insert($child_folder, 1);
            if (!$id) {
                $child_folder['db_fail'] = true;
            }

            $this->afterInsertCopyItem(
                $child_folder,
                array(
                    'storage' => $storage,
                    'root' => $parent,
                    'parent' => $new_parent
                ),
                $source_source,
                $target_source
            );

            if (!$id) {
                continue;
            }

            $child_folder['id'] = $id;
            $transform[$child_folder_id] = $child_folder;
            $track_ids[$child_folder_id] = $id;
        }

        // repair keys
        $this->repairStorage($storage['id']);

        if ($parent) {
            $frm = new filesFileRightsModel();
            $frm->cloneRights($parent['id'], array_values($track_ids));
        }

        // get all folder ids
        $all_folder_ids = array_merge(array_keys($folders), array_keys($child_folders));

        // COPY FILES

        $child_files = array();

        // get child files (order by left_key is important, for correct tracking transform maps @see above)
        if (!$ignore_children) {
            $child_files = $this->getByField(array(
                'type' => self::TYPE_FILE,
                'parent_id' => $all_folder_ids
            ), 'id');
        }

        // group by parent_id
        $group_child_files = array();
        foreach ($child_files as $child_file_id => $child_file) {
            $parent_id = $child_file['parent_id'];
            $group_child_files[$parent_id] = ifset($group_child_files[$parent_id], array());
            $group_child_files[$parent_id][$child_file['id']] = $child_file;
        }

        foreach ($group_child_files as $parent_id => $files) {

            if (empty($transform[$parent_id])) {
                continue;
            }

            $new_parent = $transform[$parent_id];
            $res_files = $this->copyFiles(
                $files,
                array(
                    'storage' => $storage,
                    'parent' => $new_parent,
                    'root' => $parent,
                    'source' => $target_source,
                    'process_id' => $process_id,
                    'is_async' => $is_async,
                    'tasks_map' => $tasks_map
                )
            );
            $track_ids += !empty($res_files['track_ids']) ? $res_files['track_ids'] : array();
            $tasks_map += !empty($res_files['tasks_map']) ? $res_files['tasks_map'] : array();
        }

        return array(
            'folders' => $folders,
            'track_ids' => $track_ids,
            'tasks_map' => $tasks_map
        );

    }

    private function copyFiles($files, array $params)
    {
        $storage = $params['storage'];
        $parent = $params['parent'];
        $root = $params['root'];
        $process_id = $params['process_id'];

        $is_async = !empty($params['is_async']);

        $queue = new filesTasksQueueModel();

        $tasks_map = ifset($params['tasks_map'], array());

        /**
         * @var filesSource $target_source
         */
        $target_source = $params['source'];

        $copytask = new filesCopytaskModel();

        // id -> to clone id
        $track_ids = array();

        foreach ($files as &$file) {

            if ($is_async) {
                $task_id = $queue->addToCopy(
                    $file['id'],
                    $storage['id'],
                    $parent ? $parent['id'] : 0,
                    array(
                        'parent_task_id' => isset($tasks_map[$file['parent_id']]) ? $tasks_map[$file['parent_id']] : null,
                        'process_id' => $process_id
                    )
                );
                $tasks_map[$file['id']] = $task_id;
                $track_ids[$file['id']] = $file['id'];
                continue;
            }

            $res = $this->generateUniqueNameAndExt(array(
                'name' => ifset($file['name'], ''),
                'storage_id' => $storage['id'],
                'parent_id' => $parent ? $parent['id'] : 0,
                'type' => self::TYPE_FILE
            ));

            if ($res === false) {
                continue;
            }

            $file['name'] = $res['name'];
            $file['ext'] = $res['ext'];

            // prev parent, prev storage, prev source
            $file['prev_parent_id'] = $file['parent_id'];
            $file['prev_storage_id'] = $file['storage_id'];
            $file['prev_source_id'] = $file['source_id'];

            // new storage, new parent, new source
            $file['storage_id'] = $storage['id'];
            $file['parent_id'] = $parent ? $parent['id'] : 0;
            $file['source_id'] = $target_source->getId();

            // new hash, new sid
            $file['hash'] = $parent && $parent['hash'] ? $parent['hash'] : null;
            $file['sid'] = $this->generateHash();

            /**
             * @var filesSource $source_source
             */
            $source_source = $this->getSource($file['prev_source_id']);

            $file = $this->beforeInsertCopyItem(
                $file,
                array(
                    'storage' => $storage,
                    'root' => $root,
                    'parent' => $parent
                ),
                $source_source,
                $target_source
            );
            if (!$file) {
                continue;
            }

            $file_id = $file['id'];
            unset($file['id']);

            $use_copytask = $source_source->isApp() || $target_source->isApp() || $source_source->getId() != $target_source->getId();

            $file['in_copy_process'] = $use_copytask ? 1 : 0;

            $file['create_datetime'] = date('Y-m-d H:i:s');
            $file['update_datetime'] = $file['create_datetime'];
            $file['source_id'] = $target_source->getId();

            $id = $this->insert($file, 1);
            if (!$id) {
                $file['db_fail'] = true;
            }

            $this->afterInsertCopyItem(
                $file,
                array(
                    'storage' => $storage,
                    'root' => $root,
                    'parent' => $parent
                ),
                $source_source,
                $target_source
            );

            if (!$id) {
                continue;
            }

            $track_ids[$file_id] = $id;

            if ($use_copytask) {
                $copytask->add($file_id, $id, $process_id);
            }
        }
        unset($file);

        if ($parent) {
            $frm = new filesFileRightsModel();
            $frm->cloneRights($parent['id'], array_values($track_ids));
        }

        return array(
            'files' => $files,
            'track_ids' => $track_ids,
            'tasks_map' => $tasks_map
        );
    }

    private function beforeInsertCopyItem($item, $options, filesSource $source_source, filesSource $target_source)
    {
        $params = array_merge(array(
            'role' => 'source',
            'file' => $item
        ), $options);
        $params = (array) $source_source->beforeCopy($params);
        $item = ifset($params['file']);
        if (!$item) {
            return false;
        }

        $params = array_merge(array(
            'role' => 'target',
            'file' => $item
        ), $options);
        $params = (array) $target_source->beforeCopy($params);
        $item = ifset($params['file']);
        if (!$item) {
            return false;
        }

        return $item;
    }

    private function afterInsertCopyItem($item, $options, filesSource $source_source, filesSource $target_source)
    {
        $params = array_merge(array(
            'role' => 'target',
            'file' => $item
        ), $options);
        $target_source->afterCopy($params);

        $params = array_merge(array(
            'role' => 'source',
            'file' => $item
        ), $options);
        $source_source->afterCopy($params);

        return $item;
    }

    private function beforeMoveItem($item, $options, filesSource $source_source, filesSource $target_source)
    {
        $params = array_merge(array(
            'role' => 'source',
            'file' => $item
        ), $options);
        $params = $source_source->beforeMove($params);
        $item = ifset($params['file']);
        if (!$item) {
            return false;
        }

        $params = array_merge(array(
            'role' => 'target',
            'file' => $item
        ), $options);
        $params = $target_source->beforeMove($params);
        $item = ifset($params['file']);
        if (!$item) {
            return false;
        }

        return $item;
    }

    private function afterMoveItem($item, $options, filesSource $source_source, filesSource $target_source)
    {
        $params = array_merge(array(
            'role' => 'target',
            'file' => $item
        ), $options);
        $target_source->afterMove($params);

        $params = array_merge(array(
            'role' => 'source',
            'file' => $item
        ), $options);
        $source_source->afterMove($params);
    }

    private function insertFolder($data)
    {
        if (empty($data['depth'])) {
            $data['depth'] = 0;
        }
        $data['type'] = self::TYPE_FOLDER;

        $data['source_id'] = (int) ifset($data['source_id'], 0);
        $data['parent_id'] = (int) ifset($data['parent_id'], 0);

        $duplicate_ids = array();

        // check broking unique keys
        if (!empty($data['source_path'])) {
            $duplicate = $this->getByField(array(
                'source_id' => $data['source_id'],
                'source_path' => $data['source_path']
            ));
            if ($duplicate) {
                // uncommon situation, if will be exception - ok
                if ($duplicate['storage_id'] != $data['storage_id']) {
                    return parent::add($data, $data['parent_id']);
                }
                $duplicate_ids[] = $duplicate['id'];
                unset($duplicate['id']);
                $data = array_merge($duplicate, $data);
            }
        }
        if (!empty($data['source_inner_id'])) {
            $duplicate = $this->getByField(array(
                'source_id' => $data['source_id'],
                'source_inner_id' => $data['source_inner_id']
            ));
            if ($duplicate) {
                // uncommon situation, if will be exception - ok
                if ($duplicate['storage_id'] != $data['storage_id']) {
                    return parent::add($data, $data['parent_id']);
                }
                $duplicate_ids[] = $duplicate['id'];
                unset($duplicate['id']);
                $data = array_merge($duplicate, $data);
            }
        }

        $duplicate_ids = array_unique($duplicate_ids);

        if ($duplicate_ids) {
            $id = array_pop($duplicate_ids);
            if ($duplicate_ids) {
                $this->deleteById($duplicate_ids);
            }
            $this->updateById($id, $data);
            $this->repairStorage($data['storage_id']);
            return $id;
        }

        return parent::add($data, $data['parent_id']);


    }

    private function insertFile($data)
    {
        foreach (array($this->left, $this->right, $this->depth) as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }
        $data['type'] = self::TYPE_FILE;
        $data['sid'] = $this->generateHash();
        $file_id = $this->insert($data, 1);
        $file_info = $this->getFile($file_id);
        if (!$file_info) {
            return false;
        }
        return $file_info;
    }

    public function getFileFolder($file_id)
    {
        return filesApp::getFileFolder($file_id);
    }

    public function getConflictFiles($files, $storage_id = 0, $folder_id = 0)
    {
        $files = filesFileModel::dropByType($files, filesFileModel::TYPE_FOLDER);
        $names = filesApp::getFieldValues($files, 'name');
        $conflict_files = $this->getFilesByNames($names, $storage_id, $folder_id);

        if (!$conflict_files) {
            return array();
        }
        $conflict_names = filesApp::getFieldValues($conflict_files, 'name');

        $original_conflict_files = array();
        foreach ($files as $file_id => $file) {
            if (in_array($file['name'], $conflict_names)) {
                $original_conflict_files[$file_id] = $file;
            }
        }

        return array(
            'src_conflict_files' => $original_conflict_files,
            'dst_conflict_files' => $conflict_files
        );
    }

    public function getFilesByNames($names, $storage_id = 0, $parent_id = 0)
    {
        if (!$parent_id) {
            $field = array(
                'parent_id' => 0,
                'storage_id' => $storage_id,
                'name' => $names
            );
        } else {
            $field = array(
                'parent_id' => $parent_id,
                'name' => $names
            );
        }
        return $this->getByField($field, 'id');
    }

    public function generateUniqueNameSuffix($options)
    {
        // extract option keys
        $name = ifset($options['name'], '');
        $storage_id = ifset($options['storage_id'], 0);
        $parent_id = ifset($options['parent_id'], 0);
        $type = ifset($options['type']);
        $exclude_id = ifset($options['exclude_id']);
        $exclude_ids = filesApp::dropNotPositive(filesApp::toIntArray($exclude_id));

        if ($type !== self::TYPE_FILE && $type !== self::TYPE_FOLDER) {
            if (!empty($exclude_ids[0])) {
                $item = $this->getItem($exclude_ids[0], false);
                $type = $item['type'];
            } else {
                return false;
            }
        }


        $max_tries = 100;
        if (!$parent_id) {
            $field = array(
                'parent_id' => 0,
                'storage_id' => $storage_id,
                'name' => $name
            );
        } else {
            $field = array(
                'parent_id' => $parent_id,
                'name' => $name
            );
        }
        $where = $this->getWhereByField($field);
        if ($exclude_ids) {
            $where .= " AND id NOT IN(".join(',', $exclude_ids).")";
        }
        $where .= " AND source_id >= 0";

        if (!$this->select('*')->where($where)->limit(1)->fetch()) {
            return "";
        }

        for ($try = 1; $try < $max_tries; $try += 1) {
            if ($type === self::TYPE_FILE) {
                $pathinfo = $this->getPathInfo($name);
                $field['name'] = $this->buildFileName($pathinfo, " ({$try})");
            } else {
                $field['name'] = $name . " ({$try})";
            }
            $where = $this->getWhereByField($field);
            if ($exclude_ids) {
                $where .= " AND id NOT IN(".join(',', $exclude_ids).")";
            }
            $file = $this->select('*')->where($where)->limit(1)->fetch();
            if (!$file) {
                break;
            }
        }
        if ($try > $max_tries) {
            return false;
        } else {
            return " ({$try})";
        }
    }

    public function getPathToFolder($id, $check_rights = true)
    {
        $folder = $this->getItem($id, false);
        if (!$folder || $folder['type'] !== self::TYPE_FOLDER) {
            return array();
        }
        $folder['backend_url'] = $this->getBackendUrl($folder);

        $storage_model = new filesStorageModel();
        $storage = $storage_model->getStorage($folder['storage_id']);
        if (!$storage) {
            return array();
        }
        $storage['type'] = 'storage';

        $folders = array();
        $ancestors = $this->getAncestors($folder['id']);
        foreach ($ancestors as $ancestor) {
            $ancestor['backend_url'] = $this->getBackendUrl($ancestor);
            $folders[$ancestor['id']] = $ancestor;
        }
        $folders[$folder['id']] = $folder;

        $path = array();

        if (!$check_rights || filesRights::inst()->canReadFilesInStorage($storage['id'])) {
            $path[] = $storage;
            $path = array_merge($path, $folders);
        } else {
            $rm = new filesFileRightsModel();
            $rights_to_folders = $rm->getMaxRightsToFile(array_keys($folders));
            foreach ($folders as $folder_id => $folder) {
                if (empty($rights_to_folders[$folder_id])) {
                    unset($folders[$folder_id]);
                }
            }
            $path = array_merge($path, $folders);
        }

        return $path;
    }

    /**
     * Typed getter with workuping. Check is item is TYPE_FILE.
     * If so returns it otherwise return false
     * @param int $id
     * @param boolean $reset_cache
     * @return boolean|array
     */
    public function getFile($id, $reset_cache = false)
    {
        if ($id <= 0) {
            return false;
        }
        $item = $this->getItem($id, true, $reset_cache);
        if (!$item || $item['type'] !== self::TYPE_FILE) {
            return false;
        }
        return $item;
    }

    /**
     * Typed getter with workuping. Check is item is TYPE_FOLDER.
     * If so returns it otherwise return false
     * @param int $id
     * @param boolean $reset_cache
     * @return boolean|array
     */
    public function getFolder($id, $reset_cache = false)
    {
        if ($id <= 0) {
            return false;
        }
        $item = $this->getItem($id, true, $reset_cache);
        if (!$item || $item['type'] !== self::TYPE_FOLDER) {
            return false;
        }
        return $item;
    }

    /**
     * @param int $id
     * @param bool $workup
     * @param bool $reset_cache
     * @return bool|array
     */
    public function getItem($id, $workup = true, $reset_cache = false)
    {
        if ($id <= 0) {
            return false;
        }
        if ($reset_cache && array_key_exists($id, self::$items_cache)) {
            unset(self::$items_cache[$id]);
        }
        if (!isset(self::$items_cache[$id])) {
            self::$items_cache[$id] = $this->getById($id);
        }
        $item = self::$items_cache[$id];
        if ($item && $workup) {
            $items = array($item['id'] => $item);
            $items = $this->workupItems($items);
            $item = $items[$item['id']];
        }
        return $item;
    }

    public function getDownloadUrl($file)
    {
        return "?module={$file['type']}&action=download&id={$file['id']}";
    }

    public function getBackendUrl($file)
    {
        $slug = ($file['type'] === self::TYPE_FOLDER ? 'folder' : 'file');
        return "#/{$slug}/{$file['id']}/";
    }

    public function getFilePath($file)
    {
        $path = '';
        if ($file['type'] === filesFileModel::TYPE_FILE) {
            $source = filesSource::factory($file['source_id']);
            $path = $source->getFilePath($file);
        }
        return $path;
    }

    public function getPhotoUrls($file, filesSource $source = null)
    {
        $photo_url = array();
        if ($file['type'] === filesFileModel::TYPE_FILE) {
            $photo_url = $source->getFilePhotoUrls($file);
            if (!isset($photo_url['file_list_small'])) {
                $photo_url['file_list_small'] = filesApp::inst()->getExtImg($file['ext']);
            }
            if (!isset($file['photo_url']['sidebar'])) {
                $photo_url['sidebar'] = $photo_url['file_list_small'];
            }
        } else {
            $photo_url['file_list_small'] = filesApp::inst()->getFolderImg();
            $photo_url['sidebar'] = $photo_url['file_list_small'];
        }
        return $photo_url;
    }

    public function formatDatetime($datetime)
    {
        $datetime_str = waDateTime::format('humandatetime', $datetime, null, 'en_US');
        if (strpos($datetime_str, 'Yesterday') !== false || strpos($datetime_str, 'Today') !== false) {
            $datetime_str = $datetime_str = waDateTime::format('humandatetime', $datetime);
        } else {
            $datetime_str = waDateTime::format('humandate', $datetime);
        }
        return $datetime_str;
    }

    private function formatTags($tags)
    {
        $tag_names = array();
        foreach ($tags as $tag) {
            $tag_names[] = $tag['name'];
        }
        return join(',', $tag_names);
    }

    public function getFileTypeOfFile($file)
    {
        if ($file['type'] !== self::TYPE_FILE) {
            return null;
        }
        $file_types = filesApp::inst()->getConfig()->getFileTypes();
        $file_type = null;
        foreach ($file_types as $ft_id => $ft) {
            if (in_array($file['ext'], $ft['ext'])) {
                $file_type = $ft_id;
                break;
            }
        }
        return $file_type;
    }

    public function workupItems($files)
    {
        if (!$files) {
            return array();
        }

        $file_ids = array_keys($files);

        $fav_model = new filesFavoriteModel();
        $favorites = $fav_model->getFavorites($file_ids);

        $fc_model = new filesFileCommentsModel();
        $comment_counters = $fc_model->getCounters($file_ids);

        $source_ids = filesApp::getFieldValues($files, 'source_id');
        $sources = $this->getSources($source_ids);

        $tag_model = new filesTagModel();
        $tags = $tag_model->getByFile($file_ids);

        $contact_ids = filesApp::getFieldValues($files, 'contact_id');
        $contacts = filesApp::getContacts($contact_ids);

        $lm = new filesLockModel();
        $locks = $lm->getLocks(filesLockModel::RESOURCE_TYPE_FILE, filesLockModel::SCOPE_EXCLUSIVE);
        $lock_map = array_fill_keys($locks, true);

        // set on is_personal flags
        $personal_map = filesRights::inst()->isFilesPersonal($file_ids);

        foreach ($files as &$file) {
            $source = ifset($sources[$file['source_id']]);
            $file['file_type'] = $this->getFileTypeOfFile($file);
            $file['download_url'] = $this->getDownloadUrl($file);
            $file['backend_url'] = $this->getBackendUrl($file);
            $file['frontend_url'] = $this->getPrivateLink($file, false);
            $file['path'] = $this->getFilePath($file);
            $file['photo_url'] = $this->getPhotoUrls($file, $source);
            $file['size_str'] = filesApp::formatFileSize($file['size']);
            $file['update_datetime_str'] = $this->formatDatetime($file['update_datetime']);
            $file['tags'] = $tags[$file['id']];
            $file['tags_str'] = $this->formatTags($file['tags']);
            $file['favorite'] = isset($favorites[$file['id']]);
            $file['comment_count'] = $comment_counters[$file['id']];
            $file['contact'] = $contacts[$file['contact_id']];
            $file['locked'] = !empty($lock_map[$file['id']]);
            $file['is_personal'] = !empty($personal_map[$file['id']]);
        }
        unset($file);

        return $files;
    }


    /**
     * @param null $file_id
     * @param string $alias
     * @param bool $with_where_prefix
     * @return string
     */
    private function getWhereById($file_id = null, $alias = '', $with_where_prefix = true)
    {
        $where = $file_id !== null
            ? ( ($alias ? "{$alias}." : '') . $this->getWhereByField(array('id' => $file_id)) )
            : '';
        if ($where && $with_where_prefix) {
            $where = "WHERE {$where}";
        }
        return $where;
    }

    /**
     * Update folders fields (update_datetime, count) by inner content (files inside it)
     *   - update_datetime as MAX of update_datetime of ALL inner files (if there are exist such)
     *   - count - COUNT of ALL inner files
     * @param int|array[int]|null $folder_id
     * @param array[string]|null $update_options May has 'update_datetime', 'count'.
     *   If null all available keys is used. If there is not any available key, return from method
     * @return void
     */
    private function refreshFoldersByContent($folder_id = null, $update_options = null) {

        if (is_array($folder_id) && empty($folder_id)) {
            return;
        }

        // prepare options
        $available_options = array('update_datetime', 'count');
        if ($update_options === null) {
            $update_options = $available_options;
        }
        foreach ($update_options as $k => $v) {
            if (!in_array($v, $available_options)) {
                unset($update_options[$k]);
            }
        }
        if (empty($update_options)) {
            return;
        }

        if (is_string($folder_id) && strlen($folder_id) > 1 && substr($folder_id, 0, 2) === 'p.') {
            $where = 'WHERE ' . $folder_id;
        } else {
            $where = $this->getWhereById($folder_id, 'p');
        }

        // prepare part of sql
        $set = array();
        $inner_select = array();
        if (in_array('update_datetime', $update_options)) {
            $inner_select[] = 'IFNULL(MAX(f.update_datetime), p.create_datetime) dt';
            $set[] = 'pp.update_datetime = m.dt';
        }
        if (in_array('count', $update_options)) {
            $inner_select[] = 'COUNT(f.id) cnt';
            $set[] = 'pp.count = m.cnt';
        }
        $set = join(',', $set);
        $inner_select = join(',', $inner_select);


        // prepare sql
        $file_type = self::TYPE_FILE;
        $sql = "
            UPDATE LOW_PRIORITY files_file pp
            INNER JOIN (
                SELECT p.id, {$inner_select}
                FROM files_file p
                INNER JOIN files_file sf ON p.left_key <= sf.left_key AND p.right_key >= sf.right_key AND p.storage_id = sf.storage_id AND p.type = sf.type
                LEFT JOIN files_file f ON f.parent_id = sf.id AND f.type = '{$file_type}'
                {$where}
                GROUP BY p.id
            ) AS m ON pp.id = m.id
            SET {$set}";

        // so, exec
        $this->exec($sql);
    }

    /**
     * Update count of folders
     * @param array[int]|int|null $folder_id if null - all folders
     */
    public function updateCount($folder_id = null)
    {
        $this->refreshFoldersByContent($folder_id, array('count'));
    }

    public function updateFolderCounters($storage_id)
    {
        $storage_id = (int) $storage_id;
        $type = self::TYPE_FOLDER;
        $this->refreshFoldersByContent("p.type = '{$type}' AND p.storage_id = '{$storage_id}'", array('count'));
    }

    /**
     * @param array[int]|int|null $file_id
     * @return array
     */
    public function getAncestors($file_id = null)
    {
        if (empty($file_id)) {
            return array();
        }
        $where = $this->getWhereById($file_id, 'f');

        $sql = "SELECT DISTINCT a.*
            FROM files_file f
            INNER JOIN files_file p ON f.parent_id = p.id
            INNER JOIN files_file a ON a.left_key <= p.left_key AND a.right_key >= p.right_key AND p.storage_id = a.storage_id AND p.type = a.type
            {$where}
            ORDER BY a.left_key";
        $items = $this->query($sql)->fetchAll('id');
        return $items;
    }

    public function getSharedItems()
    {
        $groups = filesRights::inst()->getGroupIds();
        $groups_str = "'" . join("','", $groups) . "'";

        $where = "r.group_id IN({$groups_str})";

        $sql = "SELECT DISTINCT f.* FROM files_file_rights r
                JOIN files_file f ON f.id = r.root_file_id
                WHERE {$where}
                ORDER BY f.type DESC";

        $items = $this->query($sql)->fetchAll('id');

        $source_ids = filesApp::getFieldValues($items, 'source_id');
        $sources = $this->getSources($source_ids);

        foreach ($items as &$item) {
            $source = ifset($sources[$item['source_id']]);
            $item['photo_url'] = $this->getPhotoUrls($item, $source);
            $item['backend_url'] = $this->getBackendUrl($item);
        }
        unset($item);

        return $items;
    }

    public function resetFolder($folder_id)
    {
        $all_folder_ids = array_keys($this->getAncestors($folder_id));
        $all_folder_ids[] = $folder_id;
        $this->refreshFoldersByContent($all_folder_ids);
    }

    public function delete($files, $options = array())
    {
        $typecast = filesApp::typecastFiles($files);
        $ids = $typecast['ids'];
        $ids = $this->sliceOffLocked($ids);
        if (!$ids) {
            return true;
        }

        $ids = $this->sliceOffInSync($ids);
        if (!$ids) {
            return false;
        }

        $items = array();
        $source_map = array();
        foreach ($ids as $id) {
            $item = ifset($typecast['files'][$id]);
            if ($item) {
                $items[$id] = $item;
                $source_map[$item['source_id']] = ifset($source_map[$item['source_id']], array('files' => array()));
                $source_map[$item['source_id']]['files'][$id] = $item;
            }
        }

        $is_async = !empty($this->async_operations['delete']);
        if (array_key_exists('is_async', $options)) {
            $is_async = (bool) $options['is_async'];
        }
        if ($is_async) {
            $queue = new filesTasksQueueModel();

            $storage_id_file_id = array();
            foreach ($items as $item) {
                $storage_id = abs($item['storage_id']);
                $storage_id_file_id[$storage_id] = (array) ifset($storage_id_file_id[$storage_id]);
                $storage_id_file_id[$storage_id][] = $item['id'];
            }

            $queue->addToDelete($ids);
            
            return true;
        }

        $items = array();
        $sources = $this->getSources(array_keys($source_map));
        if (!empty($options['source_options'])) {
            foreach ($sources as $source) {
                $source->setOptions($options['source_options']);
            }
        }
        foreach ($sources as $source_id => $source) {
            $params = ifset($source_map[$source_id], array('files' => array()));
            $params = (array) $source->beforeDelete($params);
            foreach ((array) ifset($params['files']) as $item_id => $item) {
                $items[$item_id] = $item;
            }
            $source_map[$source_id] = $params;
        }

        if (!$items) {
            return false;
        }

        $all_files = array();
        $all_children = array();
        foreach ($items as $item) {
            if ($item['type'] === self::TYPE_FOLDER) {
                foreach ($this->getChildren($item) as $child_id => $child) {
                    $all_children[$child_id] = $child;
                }
            }
            $all_files[$item['id']] = $item;
        }

        foreach ($all_children as $child_id => $child) {
            $all_files[$child_id] = $child;
        }

        $only_files_ids = array();
        $storage_ids = array();

        foreach ($all_files as $file_id => $file_info) {
            if (!$file_info) {
                continue;
            }
            if ($file_info['type'] === self::TYPE_FOLDER) {
                parent::delete($file_id);
            } else {
                $storage_ids[] = abs($file_info['storage_id']);
                $only_files_ids[] = $file_id;
            }
        }

        // delete files and update ancestors info
        if ($only_files_ids) {
            $ancestors = $this->getAncestors($only_files_ids);
            $this->deleteById($only_files_ids);
            $this->refreshFoldersByContent(array_keys($ancestors));
        }

        $all_files_ids = array_keys($all_files);

        foreach ($this->getRelatedModels() as $rm) {
            /**
             * @var filesFileRelatedInterface $rm
             */
            $rm->onDeleteFile($all_files_ids);

            // TODO: clear copy files. Tricky case, it needed take into account side sources
//            if ($rm instanceof filesCopytaskModel) {
//                $this->deleteByField(array(
//                    'source_id' => 0,
//                    'source_path' => $ids
//                ));
//            }
        }

        $storage_ids = array_unique($storage_ids);
        $storage_model = new filesStorageModel();
        $storage_model->updateCount($storage_ids);

        foreach ($sources as $source_id => $source) {
            $params = ifset($source_map[$source_id], array('files' => array()));
            $source->afterDelete($params);
        }

        return true;
    }

    /**
     * @return array[filesFileRelatedInterface]
     */
    public function getRelatedModels()
    {
        return array(
            new filesFileRightsModel(),
            new filesFavoriteModel(),
            new filesFileCommentsModel(),
            new filesFileTagsModel(),
            new filesCopytaskModel()
        );
    }

    /**
     * @param int $folder_id
     */
    public function clearFolder($folder_id)
    {
        $folder = $this->getFolder($folder_id);
        if (!$folder) {
            return;
        }
        $file_ids = $this->select('id')->where('parent_id = i:0', array($folder['id']))->fetchAll(null, true);
        $this->delete($file_ids);
    }

    public function moveToTrash($files)
    {
        $typecast = filesApp::typecastFiles($files);
        $ids = $typecast['ids'];
        $ids = $this->sliceOffLocked($ids);
        if (!$ids) {
            return true;
        }

        $source_map = array();

        $items = array();
        foreach ($ids as $id) {
            $item = ifset($typecast['files'][$id]);
            if ($item) {
                $items[$id] = $item;
                $source_map[$item['source_id']] = ifset($source_map[$item['source_id']], array('files' => array()));
                $source_map[$item['source_id']]['files'][$id] = $item;
            }
        }

        $items = array();
        $sources = $this->getSources(array_keys($source_map));
        foreach ($sources as $source_id => $source) {
            $params = ifset($source_map[$source_id], array('files' => array()));
            $params = $source->beforeMoveToTrash($params);
            foreach ($params['files'] as $item_id => $item) {
                $items[$item_id] = $item;
            }
            $source_map[$source_id] = $params;
        }

        if (!$items) {
            return false;
        }

        $all_files_ids = array();

        $files = array();           // all file ids for updating
        $storages = array();      // related storages for update counters
        foreach ($items as $item) {
            // move one folder or collecting file ids for mass-updating
            if ($item['type'] === self::TYPE_FOLDER) {
                $all_files_ids = array_merge($all_files_ids, $this->getChildrenIds($item));
                $this->moveToTrashOneFolder($item['id']);
            } else {
                $files[] = $item['id'];
                $storages[] = $item['storage_id'];
            }
            $all_files_ids[] = $item['id'];
        }
        if ($files) {

            $ancestors = $this->getAncestors($files);

            // update fields
            $this->exec("
            UPDATE files_file
            SET storage_id = -storage_id,
                parent_id = 0,
                hash = NULL
            WHERE id IN (i:id)", array('id' => $files));

            $this->refreshFoldersByContent(array_keys($ancestors));
            
            $storages = array_unique($storages);
            $storage_model = new filesStorageModel();
            $storage_model->updateCount($storages);
        }

        // clear rights
        $file_rights_model = new filesFileRightsModel();
        $file_rights_model->onDeleteFile($all_files_ids);

        foreach ($sources as $source_id => $source) {
            $params = ifset($source_map[$source_id], array('files' => array()));
            $source->afterMoveToTrash($params);
        }

        return true;
    }

    public function moveItems($id, $storage_id = 0, $parent_id = 0, $options = array())
    {
        $ids = filesApp::toIntArray($id);
        $ids = $this->sliceOffLocked($ids);
        if (!$ids) {
            return true;
        }
        $ids = $this->sliceOffInSync($ids);
        if (!$ids) {
            return false;
        }

        $place = array(
            'storage' => array(),
            'folder' => array(),
            'source_id' => 0
        );

        $storage_id = (int) $storage_id;
        $parent_id = (int) $parent_id;
        if ($parent_id) {
            $parent = $this->getById($parent_id);
            if (!$parent) {
                return false;
            }
            $place['folder'] = $parent;
            $place['source_id'] = $parent['source_id'];
            $storage_id = $parent['storage_id'];
        }

        if (!$storage_id && !$parent_id) {
            return false;
        }

        $sm = new filesStorageModel();
        $place['storage'] = $sm->getById($storage_id);
        if (!$place['folder']) {
            $place['source_id'] = $place['storage']['source_id'];
        }

        // get files and folders
        $items = $this->getById($ids);

        // find out source
        $source = $this->getSource($place['source_id']);

        $folders = array();
        $files = array();
        foreach ($items as $id => $item) {
            if ($item) {
                $type = ifset($item['type']);
                if ($type === self::TYPE_FILE) {
                    $files[$id] = $item;
                } else if ($type === self::TYPE_FOLDER) {
                    $folders[$id] = $item;
                }
            }
        }

        if (!$items) {
            return false;
        }

        $item_ids = array_keys($items);

        $copytask = new filesCopytaskModel();
        $process_id = !empty($options['process_id']) ? $options['process_id'] : $copytask->generateProcessId();

        $is_async = !empty($this->async_operations['move']);
        if (array_key_exists('is_async', $options)) {
            $is_async = (bool) $options['is_async'];
        }
        $ignore_children = !empty($options['ignore_children']);

        if ($folders) {

            $max_move_folders = filesApp::inst()->getConfig()->getMaxMoveFolders();
            if ($this->countChildrenWithParents($folders) > $max_move_folders) {
                throw new filesException(
                    sprintf(_w('Maximum amount of folders for one copy process is %s.'), $max_move_folders)
                );
            }

            $res_folders = $this->moveFolders(
                $folders,
                array(
                    'storage' => $place['storage'],
                    'parent' => $place['folder'],
                    'source' => $source,
                    'process_id' => $process_id,
                    'is_async' => $is_async,
                    'async_params' => (array) ifset($options['async_params']),
                    'ignore_children' => $ignore_children
                )
            );
        }

        if ($files) {
            $res_files = $this->moveFiles($files,
                array(
                    'storage' => $place['storage'],
                    'parent' => $place['folder'],
                    'source' => $source,
                    'process_id' => $process_id,
                    'is_async' => $is_async,
                    'async_params' => (array) ifset($options['async_params'])
                )
            );
        }

        $move_result = array(
            'is_async' => $is_async,
            'track_ids' => array()
        );
        $move_result['track_ids'] += !empty($res_folders) ? $res_folders['track_ids'] : array();
        $move_result['track_ids'] += !empty($res_files) ? $res_files['track_ids'] : array();

        if ($is_async) {
            $move_result['tasks_map'] = array();
            $move_result['tasks_map'] += !empty($res_folders) ? $res_folders['tasks_map'] : array();
            $move_result['tasks_map'] += !empty($res_files) ? $res_files['tasks_map'] : array();
            return $move_result;
        }

        $ignore_held_move_folders = !empty($options['ignore_held_move_folders']);

        $storage_ids_of_held_items = array();
        if (!$ignore_held_move_folders) {
            $storage_ids_of_held_items = $this->getStorageIdsOfHeldMoveFolders();
            $this->deleteHeldMoveFoldersIgnoreStorages();
        }

        // repair keys in all storages
        $storage_ids = filesApp::getFieldValues($items, 'storage_id');
        $storage_ids[] = $place['storage']['id'];
        $storage_ids = array_merge($storage_ids, $storage_ids_of_held_items);
        $storage_ids = array_unique($storage_ids);
        $storage_ids = filesApp::dropNotPositive($storage_ids);

        foreach ($storage_ids as $storage_id) {
            $this->repairStorage($storage_id);
        }

        // update count of storages
        $sm = new filesStorageModel();
        $sm->updateCount($storage_ids);

        // update count and update_datetime in all old parents
        $parent_ids = filesApp::getFieldValues($items, 'parent_id');
        $parent_ids[] = $place['folder'] ? $place['folder']['id'] : 0;
        $parent_ids = array_unique($parent_ids);
        $parent_ids = filesApp::dropNotPositive($parent_ids);

        $all_ancestors = array_keys($this->getAncestors($parent_ids));
        $all_ancestors = array_merge($all_ancestors, $parent_ids);
        $all_ancestors = array_unique($all_ancestors);

        $this->refreshFoldersByContent($all_ancestors);

        // delete old rights
        $frm = new filesFileRightsModel();
        $frm->deleteByField(array(
            'file_id' => $item_ids
        ));

        // clone new rights from parent folder
        $frm->cloneRights($place['folder'] ? $place['folder']['id'] : 0, $item_ids);

        // check copytask queue, and copytask queue empty for this process_id
        // don't return process_id
        if ($copytask->countByField(array('process_id' => $process_id)) > 0) {
            $move_result['process_id'] = $process_id;
        }

        return $move_result;
    }

    /**
     * That folders that is source folders in moving process, so called 'held' folders
     * need to be deleted, because all files inside that folder have been moved already
     */
    public function deleteHeldMoveFolders()
    {
        $storage_ids = $this->getStorageIdsOfHeldMoveFolders();
        $this->deleteHeldMoveFoldersIgnoreStorages();

        foreach ($storage_ids as $storage_id) {
            $this->repairStorage($storage_id);
        }

        // update count of storages
        $sm = new filesStorageModel();
        $sm->updateCount($storage_ids);
    }

    /**
     * That folders that is source folders in moving process, so called 'held' folders
     * need to be reset, because all files inside that folder have been corrupted when move, so stay where there are
     */
    public function resetHeldMoveFolders()
    {
        $held_folder_ids = $this->getHeldMoveFolders();
        $lm = new filesLockModel();
        $lm->delete($held_folder_ids);
        $this->updateById($held_folder_ids, array('pid' => 0));
        return true;
    }

    public function getChildrenItems($folder_id)
    {
        $folder = $this->getFolder($folder_id);
        return $this->getChildren($folder);
    }

    private function getHeldMoveFolders()
    {
        $sql = "SELECT DISTINCT f.id FROM `files_file` f 
                  LEFT JOIN `files_copytask` cp ON f.in_copy_process = cp.process_id
                  WHERE f.in_copy_process >= :pid AND cp.target_id IS NULL AND f.storage_id > 0 AND f.type = :type";
        return $this->query($sql, array(
            'pid' => filesCopytaskModel::MIN_PROCESS_ID,
            'type' => self::TYPE_FOLDER
        ))->fetchAll(null, true);
    }

    /**
     * Delete held folders, but not recount storages (ignore storeges)
     * @return bool
     */
    private function deleteHeldMoveFoldersIgnoreStorages()
    {
        $held_folder_ids = $this->getHeldMoveFolders();
        $lm = new filesLockModel();
        $lm->delete($held_folder_ids);
        $this->delete($held_folder_ids);
        return true;
    }

    /**
     * Get storage ids where held folders placed
     * @return array
     */
    private function getStorageIdsOfHeldMoveFolders()
    {
        $sql = "SELECT f.storage_id FROM `files_file` f 
                  LEFT JOIN `files_copytask` cp ON f.in_copy_process = cp.process_id
                  WHERE f.in_copy_process >= :pid AND cp.target_id IS NULL AND f.storage_id > 0 AND f.type = :type
                  GROUP BY f.id";
        return $this->query($sql, array(
            'pid' => filesCopytaskModel::MIN_PROCESS_ID,
            'type' => self::TYPE_FOLDER
        ))->fetchAll(null, true);
    }

    private function moveFolders($folders, array $params)
    {
        /**
         * @var filesSource $target_source
         */
        $target_source = $params['source'];

        $storage = $params['storage'];
        $parent = $params['parent'];
        $process_id = $params['process_id'];
        $is_async = !empty($params['is_async']);
        $ignore_children = !empty($params['ignore_children']);

        $queue = new filesTasksQueueModel();

        $folder_ids = array_keys($folders);

        $all_folder_ids = $folder_ids;

        $child_folders = array();
        $child_files = array();

        if (!$ignore_children) {
            $children = $this->getChildren($folder_ids);
            foreach ($children as $child) {
                if ($child['type'] === self::TYPE_FILE) {
                    $child_files[$child['id']] = $child;
                } else {
                    $child_folders[$child['id']] = $child;
                    $all_folder_ids[] = $child['id'];
                }
            }
        }

        $all_prev_folders = $this->getById($all_folder_ids);

        // transform map from old parent to new parent
        // need for correct relink parents
        $transform = array();

        // id of folder -> id of task
        $tasks_map = array();

        // id -> to "moved" id, for files and folders
        $track_ids = array();

        foreach ($folders as $folder) {
            if ($is_async) {
                $async_params = (array) ifset($params['async_params']);
                $async_params['process_id'] = $process_id;
                $task_id = $queue->addToMove(
                    $folder['id'],
                    $storage['id'],
                    $parent ? $parent['id'] : 0,
                    $async_params
                );
                $tasks_map[$folder['id']] = $task_id;
                $track_ids[$folder['id']] = $folder['id'];
                $transform[$folder['id']] = $folder;
                continue;
            }

            $prev_folder = $all_prev_folders[$folder['id']];
            if ($prev_folder) {
                $res = $this->moveOneItem($folder, $prev_folder,
                    array(
                        'storage' => $storage,
                        'parent' => $parent,
                        'root' => $parent,
                        'source' => $target_source,
                        'process_id' => $process_id
                    )
                );
                $transform[$folder['id']] = $res;
                $track_ids[$folder['id']] = $res['id'];
            }
        }

        foreach ($child_folders as $child_folder) {
            if ($is_async) {
                $async_params = (array) ifset($params['async_params']);
                $async_params['parent_task_id'] = ifset($tasks_map[$child_folder['parent_id']]);
                $async_params['process_id'] = $process_id;
                $task_id = $queue->addToMove(
                    $child_folder['id'],
                    $storage['id'],
                    $parent ? $parent['id'] : 0,
                    $async_params
                );
                $tasks_map[$child_folder['id']] = $task_id;
                $track_ids[$child_folder['id']] = $child_folder['id'];
                $transform[$child_folder['id']] = $child_folder;
                continue;
            }

            $new_parent = ifset($transform[$child_folder['parent_id']]);
            if (!$new_parent) {
                continue;
            }
            $res = $this->updateOneChildWhenMoveParent($child_folder, $child_folder,
                array(
                    'storage' => $storage,
                    'parent' => $new_parent,
                    'root' => $parent,
                    'source' => $target_source,
                    'process_id' => $process_id
                )
            );
            $transform[$child_folder['id']] = $res;
            $track_ids[$child_folder['id']] = $res['id'];
        }

        foreach ($child_files as $child_file) {
            if ($is_async) {
                $async_params = (array) ifset($params['async_params']);
                $async_params['parent_task_id'] = ifset($tasks_map[$child_file['parent_id']]);
                $async_params['process_id'] = $process_id;
                $task_id = $queue->addToMove(
                    $child_file['id'],
                    $storage['id'],
                    $parent ? $parent['id'] : 0,
                    $async_params
                );
                $tasks_map[$child_file['id']] = $task_id;
                $track_ids[$child_file['id']] = $child_file['id'];
                continue;
            }

            $new_parent = ifset($transform[$child_file['parent_id']]);
            if (!$new_parent) {
                continue;
            }

            $res = $this->updateOneChildWhenMoveParent($child_file, $child_file,
                array(
                    'storage' => $storage,
                    'parent' => $new_parent,
                    'root' => $parent,
                    'source' => $target_source,
                    'process_id' => $process_id
                )
            );
            $track_ids[$child_file['id']] = $res['id'];
        }

        return array(
            'track_ids' => $track_ids,
            'tasks_map' => $tasks_map
        );

    }

    private function moveFiles($files, array $params)
    {
        /**
         * @var filesSource $target_source
         */
        $target_source = $params['source'];

        $storage = $params['storage'];
        $parent = $params['parent'];
        $process_id = $params['process_id'];
        $is_async = !empty($params['is_async']);

        $file_ids = array_keys($files);
        $prev_files = $this->getById($file_ids);

        // id -> to "moved" id, for files and folders
        $track_ids = array();

        // id of folder -> id of task
        $tasks_map = array();

        $queue = new filesTasksQueueModel();

        foreach ($files as $file) {
            if ($is_async) {
                $async_params = (array) ifset($params['async_params']);
                $async_params['process_id'] = $process_id;
                $task_id = $queue->addToMove(
                    $file['id'],
                    $storage['id'],
                    $parent ? $parent['id'] : 0,
                    $async_params
                );
                $tasks_map[$file['id']] = $task_id;
                $track_ids[$file['id']] = $file['id'];
                continue;
            }
            $prev_file = $prev_files[$file['id']];
            if ($prev_file) {
                $res = $this->moveOneItem($file, $prev_file,
                    array(
                        'storage' => $storage,
                        'parent' => $parent,
                        'root' => $parent,
                        'source' => $target_source,
                        'process_id' => $process_id
                    )
                );
                $track_ids[$file['id']] = $res['id'];
            }
        }

        return array(
            'track_ids' => $track_ids,
            'tasks_map' => $tasks_map
        );
    }

    private function moveOneItem(array $item, array $prev_item, array $params)
    {
        /**
         * @var filesSource $target_source
         */
        $target_source = $params['source'];

        $storage = $params['storage'];
        $parent = $params['parent'];
        $root = $params['root'];
        $process_id = $params['process_id'];

        /**
         * @var filesSource $source_source
         */
        $source_source = $this->getSource($item['source_id']);

        $sources_differs = $source_source->getId() != $target_source->getId();

        $item['in_copy_process'] = 0;

        $res = $this->generateUniqueNameAndExt(array(
            'name' => $item['name'],
            'storage_id' => $storage['id'],
            'parent_id' => $parent ? $parent['id'] : 0,
            'type' => $item['type']
        ));

        if ($res === false) {
            return false;
        }

        $item['name'] = $res['name'];
        $item['ext'] = $res['ext'];

        // prev parent, prev storage, prev source
        $item['prev_parent_id'] = $item['parent_id'];
        $item['prev_storage_id'] = $item['storage_id'];
        $item['prev_source_id'] = $item['source_id'];

        // new parent, new storage, new source
        $item['parent_id'] = $parent ? $parent['id'] : 0;
        $item['storage_id'] = $storage['id'];
        $item['source_id'] = $target_source->getId();

        // new hash
        $item['hash'] = $parent ? $parent['hash'] : null;

        $item = $this->beforeMoveItem(
            $item,
            array(
                'storage' => $storage,
                'root' => $root,
                'parent' => $parent,
            ),
            $source_source,
            $target_source
        );

        if (!$item) {
            return false;
        }

        if (!$sources_differs && $item['type'] === self::TYPE_FOLDER) {
            $this->move($item['id'], $parent ? $parent['id'] : 0, $storage['id'],
                array(
                    'type' => self::TYPE_FOLDER
                )
            );
        }

        $item['create_datetime'] = date('Y-m-d H:i:s');
        $item['update_datetime'] = $item['create_datetime'];

        if ($sources_differs) {
            // insert new item, unset prev id
            unset($item['id']);

            if ($item['type'] == self::TYPE_FILE) {
                $item['in_copy_process'] = $process_id;
            }

            $id = $this->insert($item, 1);
            $item['prev_id'] = $prev_item['id'];
            $item['db_fail'] = !$id;
            $item['id'] = $id;
            if ($id) {
                if ($item['type'] == self::TYPE_FILE) {
                    $item['id'] = $id;
                    $copytask = new filesCopytaskModel();
                    $copytask->add($prev_item['id'], $item['id'], $process_id, true);
                }
                $this->updateById($prev_item['id'], array('in_copy_process' => $process_id));
                if ($prev_item['type'] == self::TYPE_FOLDER) {
                    $lm = new filesLockModel();
                    $lm->set(
                        $prev_item['id'],
                        array(
                            'contact_id' => 0,
                            'scope' => filesLockModel::SCOPE_EXCLUSIVE
                        ),
                        filesLockModel::RESOURCE_TYPE_FILE
                    );
                }
            }
        } else {
            $update = $this->getItemsDiff($item, $prev_item);
            $this->updateById($item['id'], $update);
        }

        $this->afterMoveItem(
            $item,
            array(
                'storage' => $storage,
                'root' => $root,
                'parent' => $parent,
            ),
            $source_source,
            $target_source
        );

        return $item;
    }

    private function updateOneChildWhenMoveParent(array $item, array $prev_item, array $params)
    {
        /**
         * @var filesSource $target_source
         */
        $target_source = $params['source'];

        $storage = $params['storage'];
        $parent = $params['parent'];
        $process_id = $params['process_id'];
        $root = $params['root'];

        /**
         * @var filesSource $source_source
         */
        $source_source = $this->getSource($item['source_id']);

        $sources_differs = $source_source->getId() != $target_source->getId();

        $item['in_copy_process'] = 0;

        // prev parent, prev storage, prev source
        $item['prev_parent_id'] = $item['parent_id'];
        $item['prev_storage_id'] = $item['storage_id'];
        $item['prev_source_id'] = $item['source_id'];

        // new parent, new storage, new source
        $item['parent_id'] = $parent ? $parent['id'] : 0;
        $item['storage_id'] = $storage['id'];
        $item['source_id'] = $target_source->getId();

        // new hash
        $item['hash'] = $parent ? $parent['hash'] : null;

        $item = $this->beforeMoveItem(
            $item,
            array(
                'storage' => $storage,
                'parent' => $parent,
                'root' => $root
            ),
            $source_source,
            $target_source
        );
        if (!$item) {
            return false;
        }

        $item['create_datetime'] = date('Y-m-d H:i:s');
        $item['update_datetime'] = $item['create_datetime'];

        if ($sources_differs) {
            // insert new item, unset prev id
            unset($item['id']);

            if ($item['type'] == self::TYPE_FILE) {
                $item['in_copy_process'] = $process_id;
            }

            $id = $this->insert($item, 1);
            $item['prev_id'] = $prev_item['id'];
            $item['db_fail'] = !$id;
            $item['id'] = $id;
            if ($id) {
                if ($item['type'] == self::TYPE_FILE) {
                    $item['id'] = $id;
                    $copytask = new filesCopytaskModel();
                    $copytask->add($prev_item['id'], $item['id'], $process_id, true);
                }
                $this->updateById($prev_item['id'], array('in_copy_process' => $process_id));
                if ($prev_item['type'] == self::TYPE_FOLDER) {
                    $lm = new filesLockModel();
                    $lm->set(
                        $prev_item['id'],
                        array(
                            'contact_id' => 0,
                            'scope' => filesLockModel::SCOPE_EXCLUSIVE
                        ),
                        filesLockModel::RESOURCE_TYPE_FILE
                    );
                }
            }
        } else {
            $update = $this->getItemsDiff($item, $prev_item);
            $this->updateById($item['id'], $update);
        }

        $this->afterMoveItem(
            $item,
            array(
                'storage' => $storage,
                'parent' => $parent,
                'root' => $root
            ),
            $source_source,
            $target_source
        );

        return $item;
    }

    private function generateUniqueNameAndExt($options)
    {
        $suffix = $this->generateUniqueNameSuffix($options);
        if ($suffix === false) {
            return false;
        }
        $type = ifset($options);
        $name = ifset($options['name'], '');
        $ext = null;
        if ($type === self::TYPE_FOLDER) {
            $name .= $suffix;
        } else {
            $pathinfo = $this->getPathInfo($name);
            $name = $this->buildFileName($pathinfo, $suffix);
            $ext = $pathinfo['ext'];
        }
        return array('name' => $name, 'ext' => $ext);
    }

    private function getItemsDiff(array $res_item, $prev_item)
    {
        if ($res_item == $prev_item) {
            return array();
        } else if ($prev_item) {
            return array_diff_assoc($res_item, $prev_item);
        } else {
            return $res_item;
        }
    }

    private function moveToTrashOneFolder($folder)
    {
        if (!is_array($folder)) {
            $folder = $this->getById((int) $folder);
        }
        // update children because after move storage_id is changed and children are gotten incorrectly
        $this->updateChildren($folder, array(
            'storage_id' => -$folder['storage_id'],
            'hash' => null
        ));

        $ancestors = $this->getAncestors($folder['id']);
        
        // move folder to trash
        $this->move($folder['id'], 0, -$folder['storage_id'], array(
            'type' => self::TYPE_FOLDER
        ));

        // repair keys
        $this->repairStorage(-$folder['storage_id']);

        // remove hash
        $this->updateById($folder['id'], array(
            'hash' => null
        ));

        $this->refreshFoldersByContent(array_keys($ancestors));
        
        // update storage counter
        $storage_model = new filesStorageModel();
        $storage_model->updateCount($folder['storage_id']);
    }

    private function getChildrenIds($folder)
    {
        $children = $this->getChildren($folder, 'id');
        return filesApp::toIntArray($children);
    }

    private function getChildren($folder, $fields = '*', $type = null)
    {
        if (is_array($folder) && array_key_exists('id', $folder)) {
            $folder_ids = filesApp::toIntArray($folder['id']);
        } else {
            $folder_ids = filesApp::toIntArray($folder);
        }
        $folder_ids = filesApp::dropNotPositive($folder_ids);

        if (!$folder_ids) {
            return array();
        }
        if ($fields !== '*') {
            $fields_ar = array();
            foreach (explode(',', $fields) as $field) {
                $field = trim($field);
                if ($this->fieldExists($field)) {
                    $fields_ar[] = $field;
                }
            }
            $fields = $fields_ar;
        }
        $fields = (array) $fields;

        $types_map = array( self::TYPE_FILE => 1, self::TYPE_FOLDER => 1 );
        if ($type === self::TYPE_FILE) {
            $types_map[self::TYPE_FOLDER] = 0;
        } else if ($type === self::TYPE_FOLDER) {
            $types_map[self::TYPE_FILE] = 0;
        }

        $all_child_items = array();

        if ($types_map[self::TYPE_FOLDER]) {

            $sql_fields = array();
            foreach ($fields as $field) {
                $sql_fields[] = 'child_folder.' . $field;
            }
            $sql_fields = join(',', $sql_fields);

            // important: don't touch order by statement
            $sql = "
                SELECT {$sql_fields} 
                FROM `files_file` parent_folder
                    JOIN `files_file` child_folder 
                      ON parent_folder.left_key < child_folder.left_key 
                        AND parent_folder.right_key > child_folder.right_key 
                          AND parent_folder.storage_id = child_folder.storage_id
                           AND child_folder.`type` = :folder_type
                WHERE parent_folder.id IN (:parent_ids)
                ORDER BY child_folder.depth, child_folder.left_key
            ";

            $child_folders = $this->query($sql,
                array(
                    'folder_type' => self::TYPE_FOLDER,
                    'parent_ids' => $folder_ids
                )
            )->fetchAll('id');

            foreach ($child_folders as $id => $child_folder) {
                $all_child_items[$id] = $child_folder;
            }
        }

        if ($types_map[self::TYPE_FILE]) {

            $sql_fields = array();
            foreach ($fields as $field) {
                $sql_fields[] = 'child_file.' . $field;
            }
            $sql_fields = join(',', $sql_fields);

            $sql = "
                SELECT {$sql_fields} 
                FROM `files_file` parent_folder
                    JOIN `files_file` child_and_parent_folder 
                      ON parent_folder.left_key <= child_and_parent_folder.left_key 
                        AND parent_folder.right_key >= child_and_parent_folder.right_key 
                          AND parent_folder.storage_id = child_and_parent_folder.storage_id
                           AND child_and_parent_folder.`type` = :folder_type
                    JOIN `files_file` child_file
                      ON child_and_parent_folder.id = child_file.parent_id AND child_file.`type` = :file_type
                    WHERE parent_folder.id IN (:parent_ids)
            ";
            $child_files = $this->query($sql,
                array(
                    'folder_type' => self::TYPE_FOLDER,
                    'file_type' => self::TYPE_FILE,
                    'parent_ids' => $folder_ids
                )
            )->fetchAll('id');

            foreach ($child_files as $id => $child_file) {
                $all_child_items[$id] = $child_file;
            }

        }

        return $all_child_items;
    }

    /**
     * @param $folders
     * @param bool $group
     * @return int|array
     */
    public function countChildren($folders, $group = false)
    {
        $typecast = filesApp::typecastFiles($folders);
        $folders = array();
        foreach ($typecast['files'] as $folder_id => $folder) {
            if ($folder['type'] == self::TYPE_FOLDER && $folder_id > 0) {
                $folders[$folder_id] = $folder;
            }
        }

        $is_single = $typecast['type'] == 'id' || $typecast['type'] == 'record';

        $folder_ids = array_keys($folders);
        $counters = array_fill_keys($folder_ids, 0);

        if (!$folder_ids) {
            return !$group || $is_single ? 0 : array();
        }

        $group_by = '';
        $fields = array(
            'COUNT(child_folder.id) AS count'
        );
        if ($group) {
            $fields[] = 'parent_folder.id';
            $group_by = 'GROUP BY parent_folder.id';
        }
        $fields = join(',', $fields);

        $sql = "SELECT {$fields} 
                FROM `files_file` parent_folder
                    JOIN `files_file` child_folder 
                      ON parent_folder.left_key < child_folder.left_key 
                        AND parent_folder.right_key > child_folder.right_key 
                          AND parent_folder.storage_id = child_folder.storage_id
                           AND child_folder.`type` = :folder_type
                WHERE parent_folder.id IN (:parent_ids)
                {$group_by}";

        $bind_params = array(
            'folder_type' => self::TYPE_FOLDER,
            'parent_ids' => $folder_ids
        );

        if ($group) {
            foreach ($this->query($sql, $bind_params) as $item) {
                $counters[$item['id']] = (int) $item['count'];
            }
            return $is_single ? (int) ifset($counters[$folder_ids[0]], 0) : $counters;
        }

        return (int) $this->query($sql, $bind_params)->fetchField();
    }

    public function countChildrenWithParents($folders)
    {
        $typecast = filesApp::typecastFiles($folders);
        $folders = array();
        foreach ($typecast['files'] as $folder_id => $folder) {
            if ($folder['type'] == self::TYPE_FOLDER && $folder_id > 0) {
                $folders[$folder_id] = $folder;
            }
        }
        return $this->countChildren($folders) + count($folders);
    }

    public function getAllSourcesInsideFolders($id)
    {
        $sql = "SELECT DISTINCT c.`source_id`
                FROM `files_file` p
                JOIN `files_file` c ON c.left_key >= p.left_key AND c.right_key <= p.right_key 
                  AND c.type = :type AND p.type = :type AND p.storage_id = c.storage_id
                WHERE p.id IN(:id)";
        return $this->query($sql, array(
            'type' => self::TYPE_FOLDER,
            'id' => $id
        ))->fetchAll(null, true);
    }

    public function getAllSourcesOfFolders($id)
    {
        $ids = filesApp::toIntArray($id);
        $sql = "SELECT `id`, `source_id`
                FROM `files_file`
                WHERE type = :type AND id IN(:id)";
        return $this->query($sql, array(
            'type' => self::TYPE_FOLDER,
            'id' => $ids
        ))->fetchAll('id', true);
    }

    public function updateChildren($folder, $update_map, $conds = null)
    {
        if (!is_array($folder)) {
            $folder = $this->getById((int) $folder);
        }
        if (!$folder) {
            return false;
        }

        $where = '';
        if ($conds !== null) {
            $where = $this->getWhereByField($conds, 'f') . " AND ";
        }
        $set_sql = array();
        foreach ($update_map as $field => $val) {
            if ($this->fieldExists($field)) {
                $val = $val !== null ? "'" . $this->escape($val) . "'" : 'NULL';
                $set_sql[] = "f.`{$field}` = {$val}";
            }
        }
        if (!$set_sql) {
            return false;
        }
        $set_sql = join(',', $set_sql);

        // update files
        $sql = "UPDATE {$this->table} p
            JOIN files_file f ON f.parent_id = p.id AND f.type = '" . self::TYPE_FILE . "'
            SET {$set_sql}
            WHERE {$where}
                (p.left_key >= i:left_key AND p.right_key <= i:right_key AND
                    p.type = s:type AND p.storage_id = i:storage_id)";

        if (!$this->exec($sql, $folder)) {
            return false;
        }

        // update folders
        $sql = "UPDATE {$this->table} f
            SET {$set_sql}
            WHERE {$where}
                (f.left_key > i:left_key AND f.right_key < i:right_key AND
                    f.type = s:type AND f.storage_id = i:storage_id)";

        if (!$this->exec($sql, $folder)) {
            return false;
        }

        return true;
    }

    /**
     * Repair "broken" nested-set tree. "Broken" is when keys combination is illegal and(or) full_urls is incorrect
     */
    public function repair()
    {
        $sql = "SELECT `storage_id` FROM `files_file`";
        foreach ($this->query($sql)->fetchAll('storage_id') as $item) {
            $this->repairStorage($item['storage_id']);
        }
    }

    /**
     * @param null $storage_id
     */
    public function repairStorage($storage_id)
    {
        $storage_id = (int) $storage_id;
        $tree = array(0 => array('children' => array(), 'url' => ''));
        $parent_ids = array(0);

        $access_table = array(0 => & $tree[0]);
        $type = self::TYPE_FOLDER;

        // if child item miss parent set parent_id of child to 0
        $sql = "UPDATE files_file f
                LEFT JOIN files_file p ON p.id = f.parent_id AND p.storage_id = {$storage_id}
                SET f.parent_id = 0
                WHERE p.id IS NULL AND f.type = '{$type}' AND f.storage_id = {$storage_id}";

        $this->exec($sql);

        while ($parent_ids) {
            $result = $this->query("SELECT * FROM files_file
                WHERE parent_id IN (" . implode(',', $parent_ids) . ")
                    AND storage_id = $storage_id AND type = '{$type}'
                ORDER BY `left_key`");
            $parent_ids = array();
            foreach ($result as $item) {
                $parent_id = $item['parent_id'];
                $item['children'] = array();
                $access_table[$parent_id]['children'][$item['id']] = $item;
                $access_table[$item['id']] = &$access_table[$parent_id]['children'][$item['id']];
                $parent_ids[] = $item['id'];
            }
        }

        $this->repairSubtree($access_table[0]);

        foreach ($access_table as $item) {
            if (isset($item['id'])) {
                $id = $item['id'];
                unset($item['children']);
                unset($item['id']);
                $this->updateById($id, $item);
            }
        }
    }

    public static function dropByType($files, $type)
    {
        if (filesApp::isPlainArray($files)) {
            $model = new self();
            $files = $model->getById($files);
        }
        foreach ($files as $file_id => $file) {
            if ($file['type'] === $type) {
                unset($files[$file_id]);
            }
        }
        return $files;
    }

    public function rename($id, $name)
    {
        $name = (string) $name;
        if (strlen($name) <= 0) {
            return false;
        }

        if (!$this->sliceOffInSync(array($id))) {
            return false;
        }

        if (!$this->sliceOffLocked(array($id))) {
            return true;
        }

        $item = $this->getById($id);

        // solve name conflict
        $suffix = $this->generateUniqueNameSuffix(array(
            'name' => $name,
            'storage_id' => $item['storage_id'],
            'parent_id' => $item['parent_id'],
            'type' => $item['type'],
            'exclude_id' => $item['id']
        ));
        if ($suffix === false) {
            return false;
        }

        $data = array(
            'name' => $item['name'],
            'ext' => $item['ext']
        );

        if ($item['type'] === self::TYPE_FOLDER) {
            $name .= $suffix;
        } else {
            $pathinfo = $this->getPathInfo($name);
            $name = $this->buildFileName($pathinfo, $suffix);
            $data['ext'] = $pathinfo['ext'];
        }

        $data['name'] = $name;

        $item['prev_name'] = $item['name'];
        $item['prev_ext'] = $item['ext'];
        $item = array_merge($item, $data);

        if ($item['name'] === $item['prev_name']) {
            return true;
        }

        $source = $this->getSource(ifset($item['source_id'], ''));

        $params = array('files' => array($item['id'] => $item));
        $params = $source->beforeRename($params);
        $res_item = ifset($params['files'][$item['id']]);
        if (!$res_item) {
            return false;
        }

        $prev_source_path = $item['source_path'];
        $item = $res_item;

        if (!$this->updateById($id, $item)) {
            $item['db_fail'] = true;
            $params['files'] = array($item['id'] => $item);
            $source->afterRename($params);
            return false;
        }

        // update children source_paths
        if ($item['type'] === self::TYPE_FOLDER && $prev_source_path !== $item['source_path']) {
            $this->correctChildrenSourcePaths($item + array('prev_source_path' => $prev_source_path));
        }

        $params['files'] = array($item['id'] => $item);
        $source->afterRename($params);

        return true;
    }

    public function correctChildrenSourcePaths($file)
    {
        // update children folder paths
        $this->exec("
            UPDATE `files_file`
            SET source_path = CONCAT(:source_path, SUBSTRING(source_path, CHAR_LENGTH(:prev_source_path) + 1))
            WHERE left_key > :left_key AND right_key < :right_key AND source_id = :source_id
        ", $file);

        // update children file paths
        $this->exec("
            UPDATE `files_file` r
            JOIN `files_file` fldr ON fldr.left_key >= r.left_key AND fldr.right_key <= r.right_key AND fldr.source_id = r.source_id
            JOIN `files_file` f ON f.parent_id = fldr.id AND f.type = :file
            SET f.source_path = CONCAT(:source_path, SUBSTRING(f.source_path, CHAR_LENGTH(:prev_source_path) + 1))
            WHERE r.id = :id
        ", $file + array('file' => self::TYPE_FILE));
    }

    public function generateHash()
    {
        return md5(uniqid(time(), true));
    }

    public function setHash($item, $hash)
    {
        if (!is_array($item)) {
            $item = $this->getById((int) $item);
        }
        $item['hash'] = $hash;
        $this->updateById($item['id'], array(
            'hash' => $hash
        ));
        if ($item['type'] == self::TYPE_FOLDER) {
            if ($item['type'] == self::TYPE_FOLDER) {
                $this->updateChildren($item, array(
                    'hash' => $hash
                ));
            }
        }
        return $item;
    }



    public function getHashFromPrivateLink($private_link)
    {
        $pos = strrpos($private_link, '/');
        $hash = '';
        if ($pos !== false) {
            $hash_with_id = substr($private_link, $pos + 1);
            $prefix = substr($hash_with_id, 0, 16);
            $suffix = substr($hash_with_id, -16);
            $hash = $prefix . $suffix;
        }
        return $hash ? $hash : null;
    }

    public function getPrivateLink($file, $gen_hash_if_empty = true)
    {
        if (empty($file['hash']) && $gen_hash_if_empty) {
            $hash = $this->generateHash();
            $file = $this->setHash($file, $hash);
        }

        $hash = $file['hash'];
        $url = empty($hash) ? '' : substr($hash, 0, 16) . $file['id'] . substr($hash, -16);
        $route_path = 'files/frontend/file';
        if ($file['type'] === self::TYPE_FOLDER) {
            $route_path = 'files/frontend/folder';
        }
        return wa()->getRouteUrl($route_path, array('hash' => $url), true);
    }

    public function deleteHash($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $folders = $this->getByField(array(
            'type' => self::TYPE_FOLDER,
            'id' => $file_ids
            ), 'id');

        foreach ($folders as $folder) {
            $file_ids = array_merge($file_ids, $this->getChildrenIds($folder));
        }
        $file_ids = array_unique($file_ids);
        $this->updateById($file_ids, array(
            'hash' => null
        ));
    }

    private function buildFileName($pathinfo, $suffix)
    {
        $filename = $pathinfo['filename'];
        if ($suffix) {
            $filename .= $suffix;
        }
        $ext = $pathinfo['ext_original'];
        if ($ext) {
            $filename .= '.' . $ext;
        }
        if (!$ext && substr($pathinfo['original'], -1) === '.') {
            $filename .= '.';
        }
        return $filename;
    }

    public function getAvailableMarks()
    {
        return array('gray', 'blue', 'red', 'purple', 'yellow', 'green');
    }

    public function getFoldersByStorage($id)
    {
        $ids = filesApp::toIntArray($id);
        if (!$ids) {
            return array();
        }

        $folders = array_fill_keys($ids, array());
        foreach ($this->query("
            SELECT * FROM `{$this->table}`
            WHERE storage_id IN (i:storage_id) AND type = s:type
            ORDER BY storage_id, left_key",
            array('storage_id' => $ids, 'type' => self::TYPE_FOLDER)
        ) as $item)
        {
            $folders[$item['storage_id']][$item['id']] = $item;
        }

        return !is_array($id) ? $folders[(int) $id] : $folders;
    }

    public function getExtMaxLen()
    {
        if ($this->ext_max_len === null) {
            $metadata = $this->getMetadata();
            $ext_max_len = (int)ifset($metadata['ext']['params']);
            if (!$ext_max_len) {
                $ext_max_len = 10;
            }
            $this->ext_max_len = $ext_max_len;
        }
        return $this->ext_max_len;
    }

    /**
     * @return string
     */
    public function getTrashType()
    {
        $csm = new waContactSettingsModel();
        $type = $csm->getOne($this->contact_id, $this->app_id, $this->trash_type_key);
        return $type === self::TRASH_TYPE_PERSONAL ? self::TRASH_TYPE_PERSONAL : self::TRASH_TYPE_ALL;
    }

    /**
     * @param string $type
     */
    public function setTrashType($type)
    {
        $csm = new waContactSettingsModel();
        if ($type === self::TRASH_TYPE_PERSONAL) {
            $csm->set($this->contact_id, $this->app_id, $this->trash_type_key, $type);
        } else {
            $csm->delete($this->contact_id, $this->app_id, $this->trash_type_key);
        }
    }

    public function stubNullSourceForIgnored($sources)
    {
        if ($this->source_ignoring === true) {
            // ignore all
            $ignore_map = array_fill_keys(array_keys($sources), true);
        } else if ($this->source_ignoring === false) {
            // ignore nothing
            $ignore_map = array();
        } else {
            // ignore only chosen
            $ignore_map = array_fill_keys($this->source_ignoring, true);
        }
        foreach ($sources as $source_id => $source) {
            if (!empty($ignore_map[$source_id])) {
                // ignore, insert null stub
                $sources[$source_id] = filesSource::factoryNull();
            }
        }
        return $sources;
    }

    /**
     * @param mixed $value boolean or list of source ids
     */
    public function setSourceIgnoring($value)
    {
        if (is_bool($value)) {
            $this->source_ignoring = $value;
        } else {
            $this->source_ignoring = filesApp::dropNotPositive(filesApp::toIntArray($value));
        }
    }

    /**
     * @param bool $check
     */
    public function setCheckInSync($check)
    {
        $this->check_in_sync = (bool) $check;
    }

    /**
     * @param bool $mode
     */
    public function setDeleteAsyncMode($mode)
    {
        $this->async_operations['delete'] = (bool) $mode;
    }

    /**
     * @param bool $mode
     */
    public function setCopyAsyncMode($mode)
    {
        $this->async_operations['copy'] = (bool) $mode;
    }

    /**
     * @param bool $mode
     */
    public function setMoveAsyncMode($mode)
    {
        $this->async_operations['move'] = (bool) $mode;
    }

    /**
     * @param bool $mode
     * @param null|array $operations
     */
    public function setAsyncMode($mode, $operations = null)
    {
        if ($operations === null) {
            $operations = array('delete', 'copy', 'move');
        }
        foreach ($operations as $operation) {
            $this->async_operations[$operation] = (bool) $mode;
        }
    }

    public function getSourceFiles($source_id, $paths = null, $key = 'id')
    {
        if ($paths !== null) {
            return $this->getByField(array(
                'source_id' => $source_id,
                'source_path' => $paths
            ), $key);
        } else {
            return $this->getByField(array(
                'source_id' => $source_id
            ), $key);
        }
    }

    /**
     * @param array|int $files @see filesApp::typecastFiles method for checking format
     * @return array[]|bool bool
     */
    public function inSync($files)
    {
        $typecast = filesApp::typecastFiles($files);
        $file_id_storage_id_map = array();
        foreach ($typecast['files'] as $file) {
            $source_id = abs((int) ifset($file['source_id']));
            $file_id_storage_id_map[$source_id] = $file['id'];
        }

        $source_ids = array_unique(array_keys($file_id_storage_id_map));
        $sync_sources_map = filesSourceSync::inSync($source_ids);

        $source_sync_map = array_fill_keys($file_id_storage_id_map, false);
        if ($typecast['type'] === 'id' || $typecast['type'] === 'record') {
            $file = reset($typecast['files']);
            return ifset($sync_sources_map[abs((int) ifset($file['source_id']))], false);
        }

        foreach ($sync_sources_map as $source_id => $is_synced) {
            $file_id = $file_id_storage_id_map[$source_id];
            $source_sync_map[$file_id] = $is_synced;
        }
        return $source_sync_map;
    }

    public function getPathInfo($filename)
    {
        return filesApp::pathInfo($filename, $this->getExtMaxLen());
    }


    /**
     * @param $source_ids
     * @return filesSource[]
     */
    private function getSources($source_ids)
    {
        $source_ids = array_unique($source_ids);
        $sources = filesSource::factory($source_ids);
        return $this->stubNullSourceForIgnored($sources);
    }

    /**
     * @param $source_id
     * @return filesSource
     */
    private function getSource($source_id)
    {
        $sources = $this->getSources((array) $source_id);
        return reset($sources);
    }

    private function updateDataByUploadFile($data)
    {
        $file = ifset($data['upload_file']);

        if (filesApp::isRequestFile($file)) {
            $ext = strtolower($file->extension);
            $data = array_merge(array(
                'name' => basename($file->name),
                'ext' => $ext,
                'size' => $file->size,
                'md5_checksum' => md5_file($file->tmp_name)
            ), $data);
        } else if (filesApp::isStream($file)) {
            $stat = fstat($file);
            $info = array();

            if ($stat && is_array($stat) && isset($data['size'])) {
                $info['size'] = $stat['size'];
            }

            $meta = stream_get_meta_data($file);
            if ($meta && is_array($meta) && isset($meta['uri'])) {
                $path_info = filesApp::pathInfo($meta['uri']);
                $info['name'] = $path_info['basename'];
                $info['ext'] = $path_info['ext'];
            }

            $data = array_merge($info, $data);

        }
        return $data;
    }

    private function isDuplicateKeyError($error_code)
    {
        return $error_code == 1062;
    }
}

<?php

abstract class filesSourceProvider
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string Source adapter id (Same as related plugin_id)
     */
    protected $type;

    /**
     * @var filesModel[]
     */
    private static $models = array();

    /**
     * @var array
     */
    protected $info = array();

    /**
     * @var int
     */
    protected $storage_id;

    /**
     * @var int
     */
    protected $folder_id;

    /**
     * @var string
     */
    private $icon;

    /**
     * @var waContact
     */
    private $owner;

    /**
     * @var int
     */
    protected $upload_chunk_size = 5242880;     // 5MB

    protected $options = array();

    /**
     * Is mount was called
     * @var bool
     */
    private $mount = false;

    public function __construct($info = array(), $options = array())
    {
        if (!is_array($info)) {
            $info = array();
        }
        if (!isset($info['id'])) {
            $info['id'] = '';
        }
        if (!isset($info['type'])) {
            $info['type'] = '';
        }
        if (!empty($info['id'])) {
            $this->id = $info['id'];
        }
        if (!empty($info['type'])) {
            $this->type = $info['type'];
        }
        $this->info = $info;
        $this->options = $options;
    }

    public function getId()
    {
        return $this->id !== null ? $this->id : 0;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getContactId()
    {
        $contact_id = isset($this->info['contact_id']) ? $this->info['contact_id'] : 0;
        return (int) $contact_id;
    }

    public function getField($field, $default = null)
    {
        return array_key_exists($field, $this->info) ? $this->info[$field] : $default;
    }

    public function getType()
    {
        return $this->getField('type', '');
    }

    public function setType($type)
    {
        $this->info['type'] = $type;
    }

    /**
     * @param array $params
     * @return int
     */
    public function getUploadChunkSize($params = array())
    {
        $memory_limit = filesApp::inst()->getConfig()->getMemoryLimit();
        return filesApp::toIntegerNumber(min($this->upload_chunk_size, $memory_limit / 2));
    }

    public function setToken($token)
    {
        $this->info['params']['token'] = $token;
        $this->info['params']['token_invalid'] = null;
        self::getSourceParamsModel()->addOne($this->getId(), 'token', $token);
        self::getSourceParamsModel()->delete($this->getId(), 'token_invalid');
    }

    public function getToken()
    {
        return isset($this->info['params']['token']) ? $this->info['params']['token'] : null;
    }

    public function hasValidToken()
    {
        return empty($this->info['params']['token_invalid']) && $this->getToken();
    }

    public function markTokenAsInvalid()
    {
        $datetime = date('Y-m-d H:i:s');
        $this->info['params']['token_invalid'] = $datetime;
        self::getSourceParamsModel()->addOne($this->getId(), 'token_invalid', $datetime);
    }

    public function setSynchronizeDatetime()
    {
        $datetime = date('Y-m-d H:i:s');
        $this->info['synchronize_datetime'] = $datetime;
        self::getSourceModel()->updateById($this->getId(), array(
            'synchronize_datetime' => $datetime
        ));
    }

    public function setPause($until_datetime)
    {
        $this->setParam('pause', $until_datetime);
    }

    public function addPause($seconds)
    {
        $pause = $this->getPause();
        if (!$pause) {
            $time = time();
        } else {
            $time = strtotime($pause);
        }
        $this->setPause(date('Y-m-d H:i:s', $time + $seconds));
    }

    public function unsetPause()
    {
        $this->setParam('pause', null);
    }

    public function getPause()
    {
        return $this->getParam('pause');
    }

    public function inPause()
    {
        $pause = $this->getPause();
        if (!$pause) {
            return false;
        }
        if (intval(strtotime($pause)) < time()) {
            $this->unsetPause();
            return false;
        }
        return true;
    }

    public function getSynchronizeDatetime()
    {
        return $this->getField('synchronize_datetime');
    }

    public function getParams()
    {
        return ifset($this->info['params'], array());
    }

    public function getParam($key)
    {
        return ifset($this->info['params'][$key], null);
    }

    public function setParam($key, $value)
    {
        $this->info['params'][$key] = $value;
        self::getSourceParamsModel()->addOne($this->getId(), $key, $value);
    }

    public function delParam($key)
    {
        $this->setParam($key, null);
    }

    public function delParams($keys)
    {
        foreach ($keys as $key) {
            $this->info['params'][$key] = null;
        }
        self::getSourceParamsModel()->delete($this->getId(), $keys);
    }

    public function addParams($params)
    {
        self::getSourceParamsModel()->add($this->getId(), $params);
        $this->info['params'] = self::getSourceParamsModel()->get($this->getId());
    }

    public function setParams($params)
    {
        self::getSourceParamsModel()->set($this->getId(), $params);
        $this->info['params'] = self::getSourceParamsModel()->get($this->getId());
    }

    public function pauseSync()
    {
        $datetime = date('Y-m-d H:i:s');
        $this->info['params']['sync_paused'] = $datetime;
        self::getSourceParamsModel()->addOne($this->getId(), 'sync_paused', $datetime);
    }

    public function unpauseSync()
    {
        self::getSourceParamsModel()->delete($this->getId(), 'sync_paused');
    }

    public function getStorageId()
    {
        if (!isset($this->info['storage_id']) && !isset($this->info['folder_id'])) {
            return null;
        } else if (isset($this->info['folder_id'])) {
            $fm = new filesFileModel();
            $folder = $fm->getItem($this->info['folder_id'], false);
            $this->info['storage_id'] = $folder['storage_id'];
            self::getSourceModel()->updateById($this->getId(), array(
                'storage_id' => $this->info['storage_id']
            ));
        }
        return (int) $this->info['storage_id'];
    }

    public function getFolderId()
    {
        if (!isset($this->info['folder_id'])) {
            return null;
        }
        return (int) $this->info['folder_id'];
    }

    public function setName($name)
    {
        $this->info['name'] = $name;
    }

    public function getName()
    {
        $name = ifset($this->info['name'], '');
        if (!$name) {
            $name = $this->getDefaultName();
        }
        return $name;
    }

    protected function getDefaultName()
    {
        $default_name = $this->getParam('default_name');
        if (!$default_name) {
            if (is_numeric($this->getId())) {
                $default_name = $this->getType() . '_' . $this->getId();
                $this->setParam('default_name', $default_name);
            } else {
                $default_name = uniqid($this->getType() . '_');
            }
        }
        return $default_name;
    }

    public function getOwner()
    {
        if ($this->owner === null) {
            $contact_id = ifset($this->info['contact_id'], 0);
            $this->owner = new waContact($contact_id);
            if ($contact_id > 0 && !$this->owner->exists()) {
                $this->owner = new waContact(0);
            }
        }
        return $this->owner;
    }

    public function getPath()
    {
        $storage_id = $this->getStorageId();
        $folder_id = $this->getFolderId();

        if (!$folder_id && !$storage_id) {
            return array();
        }

        if ($folder_id) {
            $fm = new filesFileModel();
            return $fm->getPathToFolder($folder_id);
        }

        $sm = new filesStorageModel();
        $storage = $sm->getStorage($storage_id);
        $storage['type'] = 'storage';

        return array($storage);
    }

    public function isMounted($check_existing = true)
    {
        if ($check_existing) {
            return $this->existsFolder() === true || $this->existsStorage() === true;
        } else {
            return $this->getStorageId() || $this->getFolderId();
        }
    }

    private function existsFolder()
    {
        $folder_id = $this->getFolderId();
        if ($folder_id) {
            $fm = new filesFileModel();
            $folder = $fm->getItem($folder_id, false);
            return $folder && $folder['type'] === filesFileModel::TYPE_FOLDER && $folder['storage_id'] > 0;
        }
        return null;
    }

    private function existsStorage()
    {
        $storage_id = $this->getStorageId();
        $folder_id = $this->getFolderId();
        if ($folder_id) {
            $fm = new filesFileModel();
            $folder = $fm->getItem($folder_id, false);
            if ($folder && $folder['type'] === filesFileModel::TYPE_FOLDER && $folder['storage_id'] > 0) {
                $storage_id = $folder['storage_id'];
            }
        }
        if ($storage_id) {
            $sm = new filesStorageModel();
            $storage = $sm->getStorage($storage_id);
            return !!$storage;
        }
        return null;
    }

    /**
     * After save return new updated instance
     * @return filesSourceProvider
     */
    public function save()
    {
        $info = $this->info;
        if (!is_numeric($info['id'])) {
            unset($info['id']);
        }

        // save and clean cache
        $data = self::getSourceModel()->save($info);
        $class_name = get_class($this);
        /**
         * @var filesSourceProvider $new_instance
         */
        $new_instance = new $class_name($data);

        // is mount was called, update storage.source_id and folder.source_id
        if ($this->mount) {
            $storage_id = $this->getStorageId();
            $folder_id = $this->getFolderId();
            if ($storage_id && !$folder_id) {
                $sm = new filesStorageModel();
                $sm->updateById($new_instance->getStorageId(), array(
                    'source_id' => $new_instance->getId()
                ));
            }
            if ($folder_id) {
                $fm = new filesFileModel();
                $fm->updateById($new_instance->getFolderId(), array(
                    'source_id' => $new_instance->getId()
                ));
            }
        }

        return $new_instance;
    }

    /**
     * @return filesSourceModel
     */
    private static function getSourceModel()
    {
        return ifset(self::$models['source'], new filesSourceModel());
    }

    /**
     * @return filesSourceParamsModel
     */
    private static function getSourceParamsModel()
    {
        return ifset(self::$models['source_params'], new filesSourceParamsModel());
    }

    /**
     * Call save methods for real mounting
     * @param $mount
     */
    public function mount($mount)
    {
        $storage_id = (int) ifset($mount['storage_id'], 0);
        $folder_id = (int) ifset($mount['folder_id'], 0);
        if ($storage_id) {
            $this->info['storage_id'] = $storage_id;
        } else if (isset($this->info['storage_id'])) {
            $this->info['storage_id'] = null;
        }
        if ($folder_id) {
            $this->info['folder_id'] = $folder_id;
        } else if (isset($this->info['folder_id'])) {
            $this->info['folder_id'] = null;
        }
        $this->info['create_datetime'] = date('Y-m-d H:i:s');
        $this->mount = true;
    }

    public function delete()
    {
        $source_id = $this->getId();
        if (!is_numeric($source_id)) {
            return false;
        }

        self::getSourceModel()->delete($source_id);

        $this->info['id'] = $this->info['type'];
        $this->id = $this->info['type'];

        return true;
    }

    public function getTitleName()
    {
        return _w('Unknown');
    }

    // HERE BEHAVIOR METHODS
    // MOST OF THESE METHODS SHOULD BE OVERRIDDEN

    public function beforeAdd($params)
    {
        return $params;
    }

    public function afterAdd($params)
    {
        return $params;
    }

    public function beforeReplace($params)
    {
        return $params;
    }

    public function afterReplace($params)
    {
        return $params;
    }

    public function beforeCopy($params)
    {
        return $params;
    }

    public function afterCopy($params)
    {
        return $params;
    }

    public function beforeRename($params)
    {
        return $params;
    }

    public function afterRename($params)
    {
        return $params;
    }

    public function beforeDelete($params)
    {
        return $params;
    }

    public function afterDelete($params)
    {
        return $params;
    }

    public function beforeMove($params)
    {
        return $params;
    }

    public function afterMove($params)
    {
        return $params;
    }

    public function beforeMoveToTrash($params)
    {
        return $params;
    }

    public function afterMoveToTrash($params)
    {
        return $params;
    }

    public function getFilePath($file)
    {
        // empty stub, override it
        return '';
    }

    public function getFilePhotoUrls($file)
    {
        // empty stub, override it
        return array();
    }

    /**
     * @param array|int $file db-record or id
     * @param string $type @see filesSource constants with prefix DOWNLOAD_
     * @param array $options additional options for future
     * @return mixed Depends of @param type.
     */
    public function download($file, $type = filesSource::DOWNLOAD_STDOUT, $options = array())
    {
        // empty stub, override it
    }

    public function downloadChunk($file, $offset, $chunk_size)
    {
        return $offset;
    }

    /**
     * @param array $file
     * @param stream|waRequestFile|string $data
     * @return bool
     */
    public function upload($file, $data)
    {
        // empty stub, override it
        return false;
    }

    public function uploadChunk($file, $offset, $chunk)
    {
        return $offset;
    }

    /**
     * This method called before source-pull process controller start pulling
     */
    public function beforePullingStart()
    {
        // empty stub, override it
    }

    /**
     *
     * This method used by source-pull process controller for first pull files into mounted folder
     * @see filesSourcePullController
     *
     * @param array $progress_info [
     *      'progress' => float
     *      'done' => bool
     * ]
     * It is info map about progress, dependent of realization
     * But some fields has special meaning
     *      'progress' - float in range from 0.0 to 1.0. Default - not passed. Interpret it as 0.0
     *      'done' - bool (empty, not empty), is progress is done. Default - not passed. Interpret is as false
     * It will be passed empty $progress_info on first call of pullChunk
     * Next calls will be received 'info' value from return result, see return
     *
     *
     * Method return info map and list of files.
     * Info map - info about progress, it will be passed to next call of pullChunk, see $progress_info input parameter
     * File is map with 'size', 'path', 'source_path', 'name', 'type' fields
     * @return array [
     *      'list' => [
     *          'size' => float,
     *          'path' => string,
     *          'source_path' => string,
     *          'name' => string,
     *          'type' => string 'file'|'folder'. May be omitted, in this case interpreted as 'file'
     *      ],
     *      'info' => $progress_info map, see input parameter
     */
    public function pullChunk($progress_info = array())
    {
        // empty stub, override it
    }

    /**
     * This method called after source-pull process controller end pulling
     */
    public function afterPullingEnd()
    {
        // empty stub, override it
    }

    public function syncData($options = array())
    {
        return array();
    }

    public function beforePerformCopytask($params)
    {
        return $params;
    }

    public function afterPerformCopytask($params)
    {
        return $params;
    }

    /**
     * @param $params
     * @return bool
     */
    public function isCopytaskPerformed($params)
    {
        $task = $params['task'];
        $offset = $task['offset'];
        if ($params['copytask_file_role'] === 'source') {
            $file = $task['source_file'];
            return $offset >= $file['size'];
        } elseif ($params['copytask_file_role'] === 'target') {
            $file = $task['target_file'];
            return $offset >= $file['size'];
        }
        return false;
    }

    /**
     * @param $params
     * @return void
     */
    public function onCancelCopytask($params)
    {

    }

    /**
     * @return string
     */
    public function getAccountInfoHtml()
    {
        return '';
    }

    /**
     * @return array associative array with fields name, email and etc.
     * Fields 'name', 'email' is required. Other fields depends on realization of source
     */
    public function getAccountInfo()
    {
        return array();
    }

    /**
     * @return bool
     */
    public function hasAccountInfo()
    {
        $info = $this->getAccountInfo();
        $all_empty = true;
        foreach ($info as $field => $value) {
            if (!empty($value)) {
                $all_empty = false;
            }
        }
        return !$all_empty;
    }

    /**
     * @param $params
     * @return null|array Array must have not empty 'path' key value and can have optional 'name' key value
     */
    public function getAttachmentInfo($params)
    {
        return null;
    }

    /**
     * @return filesModel
     */
    public function getFileModel()
    {
        if (empty(self::$models['file'])) {
            self::$models['file'] = new filesFileModel();
            if (is_numeric($this->getId())) {
                self::$models['file']->setSourceIgnoring($this->getId());
            }
        }
        return self::$models['file'];
    }

    public function getSyncDriverClassName()
    {
        $type = $this->getType();
        $class_name = 'files' . filesApp::ucfirst($type) . 'SourceSyncDriver';
        if (class_exists($class_name)) {
            return $class_name;
        } else {
            return 'filesSourceSyncDefaultDriver';
        }
    }


    protected function logException($e)
    {
        $w = date('W');
        $y = date('Y');
        $message = get_class($e) . " - " . $e->getCode() . " - " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        $id = $this->getId();
        $type = $this->getType();
        waLog::log($message, "files/provider/{$type}/{$id}/exceptions/{$y}/{$w}/provider.log");
    }
}

<?php

class filesController extends waViewAction
{
    private $format;


    /**
     * @var filesApp
     */
    protected $app;

    protected $assigns = array();
    protected $response = array();  // for api compatibility
    protected $errors = array();

    protected $app_id = 'files';
    private $need_session = false;
    protected $contact_id;
    private $models;

    public function __construct($params = null)
    {
        $this->getFilesAppInstance();

        parent::__construct($params);

        $refl = new ReflectionClass($this);
        $class_name = $refl->getName();
        if (substr($class_name, -10) === 'Controller') {
            $this->format = 'json';
        } else if (substr($class_name, -6) === 'Action') {
            if (wa()->getEnv() === 'frontend') {
                if (!waRequest::isXMLHttpRequest()) {
                    $this->setLayout(new filesFrontendLayout());
                }
            }
            $this->format = 'html';
        }

        if (filesRights::inst()->canCreateStorage()) {
            $this->createPersistentStorage();
        }
        if (!$this->need_session) {
            $this->getStorage()->close();
        }
        $this->contact_id = $this->getUser()->getId();
    }

    /**
     * @return filesApp
     */
    protected function getFilesAppInstance()
    {
        if ($this->app !== null) {
            return $this->app;
        } else {
            $this->app = filesApp::inst();
            $this->app->setLastActiveTime();
            return $this->app;
        }
    }

    public function assign($args/*($key, $value) || $assigns */)
    {
        $args = func_get_args();
        if (func_num_args() > 1) {
            $key = $args[0];
            $value = $args[1];
            $assigns = array(
                $key => $value
            );
        } else {
            $assigns = $args[0];
        }
        foreach ((array) $assigns as $key => $value) {
            $this->assigns[$key] = $value;
        }
    }

    public function setError($message, $data = array())
    {
        $this->errors[] = array($message, $data);
    }

    /**
     * For Json-controller branch
     * @param null|array $params
     * @return string
     * @throws Exception
     */
    public function run($params = null) {
        try {
            parent::run($params);
        } catch (Exception $e) {
            $this->logException($e);
            throw $e;
        }
        return $this->display();
    }

    private function logException($e)
    {
        $w = date('W');
        $y = date('Y');
        $message = get_class($e) . " - " . $e->getCode() . " - " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        waLog::log($message, "files/exceptions/{$y}/{$w}.log");
    }

    /**
     * @param bool|true $clear_assign
     * @return string
     */
    public function display($clear_assign = true)
    {
        if ($this->format === 'html') {
            $html = $this->displayHtml($clear_assign);
            if (!$this->getRequest()->isXMLHttpRequest() && $this->getResponse()->getHeader('X-Wa-Files-Location')) {
                $this->getResponse()->redirect($this->getResponse()->getHeader('X-Wa-Files-Location'));
            }
            return $html;
        } else if ($this->format === 'json') {
            return $this->displayJson();
        }
        return '';
    }

    private function displayJson()
    {
        $this->getResponse()->addHeader('Content-Type', 'application/json');

        if ($this->getResponse()->getHeader('X-Wa-Files-Location')) {
            $response = array(
                'status' => 'redirect',
                'url' => $this->getResponse()->getHeader('X-Wa-Files-Location')
            );
        } else {
            if (!$this->errors) {
                $response = array('status' => 'ok', 'data' => $this->assigns + $this->response);
            } else {
                $response = array('status' => 'fail', 'errors' => $this->errors);
            }
        }

        $this->getResponse()->sendHeaders();

        $this->setTemplate(wa()->getAppPath() . '/templates/json.html');
        $this->view->assign(array('response' => $response));
        $this->view->display($this->getTemplate());
        return '';
    }

    private function displayHtml($clear_assign = true)
    {
        $this->view->cache($this->cache_time);
        if ($this->cache_time && $this->isCached()) {
            return $this->view->fetch($this->getTemplate(), $this->cache_id);
        } else {
            if (!$this->cache_time && $this->cache_id) {
                $this->view->clearCache($this->getTemplate(), $this->cache_id);
            }
            try {
                $this->execute();
            } catch (Exception $e) {
                $this->logException($e);
                throw $e;
            }
            $this->view->assign($this->assigns);
            $result = $this->view->fetch($this->getTemplate(), $this->cache_id);
            if ($clear_assign) {
                $this->view->clearAllAssign();
            }
            return $result;
        }
    }

    /**
     * Create personal storage for this user
     * if he hasn't any
     */
    private function createPersistentStorage()
    {
        if (!$this->getStorageModel()->getPersistenStorage()) {
            $this->getStorageModel()->createPersistentStorage();
        }
    }


    public function getPageNotFoundError($msg = null, $extra = null)
    {
        return array(
            'msg' => $msg ? $msg : _w('Page not found'),
            'code' => 404,
            'extra' => $extra
        );
    }

    public function getAccessDeniedError($msg = null, $extra = null)
    {
        return filesApp::getAccessDeniedError($msg, $extra);
    }

    public function getInSyncError($msg = null, $extra = null)
    {
        return array(
            'msg' => $msg ? $msg : _w('Sync is in process. No changes available until synchronization finish.'),
            'code' => 400,
            'extra' => $extra
        );
    }

    /**
     * Get url of current action/controller
     * @return string|null
     */
    public function getUrl()
    {
        $class = get_class($this);
        if (preg_match("/{$this->app_id}([A-Z][a-z0-9_]+?)([A-Z][A-Za-z0-9_]+?)?(:?Controller|Action)/", $class, $m)) {
            $module = filesApp::lcfirst($m[1]);
            $action = filesApp::lcfirst($m[2]);
            if ($action) {
                return "?module={$module}&action={$action}";
            } else {
                return "?module={$module}";
            }
        }
        return null;
    }

    /**
     * Make bunch of checks on storage id
     * @param int $id
     * @return true|array
     */
    public function checkStorageId($id)
    {
        $storage = $this->getStorageModel()->getStorage($id);
        if (!$storage) {
            return $this->getPageNotFoundError($storage);
        }
        if (!filesRights::inst()->canReadFilesInStorage($storage['id'])) {
            return $this->getAccessDeniedError(null, $storage);
        }
        return true;
    }

    /**
     * Make bunch of checks on folder id
     * @param int $id
     * @return true|array
     */
    public function checkFolderId($id)
    {
        $folder = $this->getFileModel()->getItem($id, false);
        if (!$folder && $folder['type'] !== filesFileModel::TYPE_FOLDER) {
            return $this->getPageNotFoundError($folder);
        }
        if (!$folder['storage_id']) {
            return $this->getPageNotFoundError($folder);
        }
        if (!filesRights::inst()->canReadFile($folder['id'])) {
            return $this->getAccessDeniedError(null, $folder);
        }
        return true;
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

    /**
     * @return filesCollection
     */
    public function getCollection($hash = '', $options = array())
    {
        return new filesCollection($hash, $options);
    }

    /**
     * @param $options
     * @return filesStatistics
     */
    public function getStatistics($options)
    {
        return new filesStatistics($options);
    }

    /**
     * @return filesConfig
     */
    public function getConfig()
    {
        return $this->getFilesAppInstance()->getConfig();
    }

    /**
     * @return filesRightConfig
     */
    public function getRightConfig()
    {
        return $this->getFilesAppInstance()->getRightConfig();
    }

    public function reportAboutError($error)
    {
        $this->getFilesAppInstance()->reportAboutError($error);
    }

    public function getFileForShareModule($throw_access_denied_exception = true)
    {
        $file_id = $this->getRequest()->request('file_id', 0, waRequest::TYPE_INT);

        $file = $this->getFileModel()->getItem($file_id);
        if (!$file) {
            $this->getFilesAppInstance()->reportAboutError($this->getPageNotFoundError());
        }

        $file['full_access'] = filesRights::inst()->hasFullAccessToFile($file['id']);
        if ($throw_access_denied_exception && !$file['full_access']) {
            $this->getFilesAppInstance()->reportAboutError($this->getAccessDeniedError());
        }

        $frm = $this->getFileRightsModel();
        $file['groups'] = $frm->getJustGroups($file['id']);
        $file['users'] = $frm->getJustUsers($file['id']);

        $sm = $this->getStorageModel();
        $storage = $sm->getStorage($file['storage_id']);
        $storage['groups'] = $sm->getJustGroups($storage['id']);
        $storage['users'] = $sm->getJustUsers($storage['id']);

        $file['storage'] = $storage;

        $file['is_personal'] = filesRights::inst()->isFilesPersonal($file);

        return $file;
    }

    public function isAnySourcePluginInstalled()
    {
        return !!$this->getConfig()->getSourcePlugins();
    }
}

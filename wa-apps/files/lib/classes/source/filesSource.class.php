<?php

/**
 * Class filesSource
 *
 * All that methods is proxy-methods to filesSourceProvider class
 *
 * @method string|int getId()
 * @method array getInfo()
 * @method int getContactId()
 * @method mixed getField(string $field)
 * @method string getType()
 * @method void setType()
 * @method void setToken(string $token)
 * @method string getToken()
 * @method bool hasValidToken()
 * @method void markTokenAsInvalid()
 * @method void setPause($until_datetime)
 * @method void unsetPause()
 * @method string|null getPause()
 * @method void setSynchronizeDatetime()
 * @method string|null getSynchronizeDatetime()
 * @method array getParams()
 * @method string|null getParam($key)
 * @method void setParam(string $key, mixed $value)
 * @method void addParams(array $params)
 * @method void setParams(array $params)
 * @method void delParam(string $key)
 * @method void delParams(array $keys)
 * @method int getStorageId()
 * @method int|null getFolderId()
 * @method void setName()
 * @method string getName()
 * @method waContact getOwner()
 * @method array getPath()
 * @method bool isMounted(bool $check_existing = true)
 * @method mount(array $mount)
 * @method pauseSync()
 * @method unpauseSync()
 * @method string getAccountInfoHtml()
 * @method array getAccountInfo()
 * @method bool hasAccountInfo()
 * @method int getUploadChunkSize(array $params = array())
 * @method array getFilePhotoUrls(array $file)
 *
 * @method bool isCopytaskPerformed(array $params)
 * @method void onCancelCopytask(array $params)
 *
 * @method array beforeAdd(array $params)
 * @method array afterAdd(array $params)
 *
 * @method array beforeReplace(array $params)
 * @method array afterReplace(array $params)
 *
 * @method array beforeCopy(array $params)
 * @method array afterCopy(array $params)
 *
 * @method array beforeRename(array $params)
 * @method array afterRename(array $params)
 *
 * @method array beforeDelete(array $params)
 * @method array afterDelete(array $params)
 *
 * @method array beforeMove(array $params)
 * @method array afterMove(array $params)
 *
 * @method array beforeMoveToTrash(array $params)
 * @method array afterMoveToTrash(array $params)
 *
 * @method string getFilePath(array $file)
 * @method array[]string getFilePhotoUrls(array $file)
 *
 * @method mixed download(array $file, string $type = filesSource::DOWNLOAD_STDOUT, array $options = array())
 * @method bool upload(array $file, stream|waRequestFile|string $data)
 *
 * @method downloadChunk(array $file, int $offset, int $chunk_size)
 * @method uploadChunk(array $file, int $offset, string $chunk)
 *
 * @method beforePullingStart()
 * @method array pullChunk(array $progress_info = array())
 * @method afterPullingEnd()
 * @method array syncData(array $options = array())
 *
 * @method array|null getAttachmentInfo(array $params)
 */
final class filesSource
{
    const DOWNLOAD_STDOUT = 'stdout';
    const DOWNLOAD_FILEPATH = 'filepath';
    const DOWNLOAD_STREAM = 'stream';

    const PROVIDER_TYPE_APP = 'filesSourceAppProvider';
    const PROVIDER_TYPE_NULL = 'filesSourceNullProvider';

    /**
     * @var filesSourcePlugin
     */
    private $plugin;

    /**
     * @var filesModel[]
     */
    private static $models;

    /**
     * @var filesSource[]
     */
    private static $sources = array();

    /**
     * @var filesSourceProvider
     */
    private $provider;

    /**
     * @var bool
     */
    private $in_pause_throw_exception = false;

    /**
     * @var bool
     */
    private $token_invalid_throw_exception = false;

    /**
     * @param string|int|array $id
     * @param array $options
     * @return filesSource|filesSource[]
     */
    public static function factory($id, $options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $ids = array_unique(array_map('trim', (array)$id));

        #get integer ids
        $exists_ids = array_filter(array_map('intval', $ids));
        #and use not cached ids
        $exists_ids = array_diff($exists_ids, array_keys(self::$sources));
        $items = array();

        if ($exists_ids) {

            if ($items = self::getSourceModel()->getById($exists_ids)) {
                foreach ($items as &$item) {
                    $item['params'] = array();
                }
                unset($item);

                foreach (self::getSourceParamsModel()->getByField('source_id', array_keys($items), true) as $param) {
                    //TODO json_decode?
                    $items[$param['source_id']]['params'][$param['name']] = $param['value'];
                }
            }
        }

        $sources = array();
        foreach ($ids as $_id) {

            if ($_id === self::PROVIDER_TYPE_APP) {
                $sources[$_id] = new filesSource(new filesSourceAppProvider());
            } else if ($_id === self::PROVIDER_TYPE_NULL || $_id === null) {
                $sources[$_id] = new filesSource(new filesSourceNullProvider());
            } else if (!isset(self::$sources[$_id])) {
                $info = ifset($items[$_id], array());
                $type = ($info && isset($info['type'])) ? $info['type'] : $_id;
                $info['type'] = $type;


                $plugin = filesSourcePlugin::factory($type);
                if ($plugin) {
                    $provider = $plugin->factoryProvider($type, $info);
                } else {
                    $class_name = self::getClass($type);
                    if (empty($info['id']) && $class_name !== 'filesSourceAppProvider') {
                        $info['id'] = $info['type'];
                    }
                    $provider = new $class_name($info);
                }

                if (!($provider instanceof filesSourceProvider)) {
                    $provider = new filesSourceNullProvider();
                }

                $sources[$_id] = new filesSource($provider);
            } else {
                $sources[$_id] = self::$sources[$_id];
            }

            self::$sources[$_id] = $sources[$_id];
            self::$sources[$_id]->setOptions($options);
        }

        return is_array($id) ? $sources : $sources[$id];
    }

    public function setOptions($options = array())
    {
        $options = (array) $options;
        $this->inPauseThrowException((bool) ifset($options['in_pause_throw_exception']));
        $this->tokenInvalidThrowException((bool) ifset($options['token_invalid_throw_exception']));
    }

    public static function factoryApp()
    {
        return self::factory(self::PROVIDER_TYPE_APP);
    }

    public static function factoryNull()
    {
        return self::factory(self::PROVIDER_TYPE_NULL);
    }

    public function isNull()
    {
        return $this->provider instanceof filesSourceNullProvider;
    }

    public static function factoryByProvider($provider)
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        if ($provider instanceof filesSourceProvider) {
            return new filesSource($provider);
        }
        throw new waException("Illegal provider");
    }

    public function isApp()
    {
        return $this->provider instanceof filesSourceAppProvider;
    }

    public function isPlugin()
    {
        return $this->getPlugin() instanceof filesSourcePlugin;
    }

    /**
     * @return filesSourcePlugin|null
     */
    public function getPlugin()
    {
        if ($this->plugin === null) {
            $plugin = filesSourcePlugin::factory($this->getType());
            $this->plugin = $plugin instanceof filesSourcePlugin ? $plugin : false;
        }
        return $this->plugin instanceof filesSourcePlugin ? $this->plugin : null;
    }

    /**
     * @return null|string
     */
    public function getIconUrl()
    {
        if (!$this->isPlugin()) {
            return null;
        }
        return $this->getPlugin()->getIconUrl();
    }

    public function getSupport()
    {
        if ($this->isPlugin()) {
            $plugin = $this->getPlugin();
            return $plugin->getSupport();
        }
        return self::getAllSupport();
    }

    public static function getAllSupport()
    {
        return array(
            'chunk_download' => true,
            'chunk_upload' => true
        );
    }

    public function isChunkUploadSupport()
    {
        $support = $this->getSupport();
        return ifset($support['chunk_upload']);
    }

    public function isChunkDownloadSupport()
    {
        $support = $this->getSupport();
        return ifset($support['chunk_download']);
    }

    public function isOnDemand()
    {
        return $this->isPlugin() && $this->getPlugin()->isOnDemand();
    }

    /**
     * @param string $type
     * @return string
     */
    private static function getClass($type)
    {
        $class_name = 'filesSourceNullProvider';
        if ($type === self::PROVIDER_TYPE_APP || $type === '0' || $type === 0 || $type === '') {
            $class_name = 'filesSourceAppProvider';
        }

        if ($type) {
            $type = strtolower(trim($type));
            $name = 'files'.filesApp::ucfirst($type).'SourceProvider';
            if (class_exists($name)) {
                $class_name = $name;
            }
        }

        return $class_name;
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
     * filesSource constructor.
     * @param filesSourceProvider $provider
     */
    protected function __construct(filesSourceProvider $provider)
    {
        $this->provider = $provider;
    }

    public function getProviderName()
    {
        return $this->provider->getTitleName();
    }

    /**
     * @return filesSourceProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return filesSource|filesSource[]
     */
    public function save()
    {
        $provider = $this->provider->save();
        unset(self::$sources[$provider->getId()]);
        return self::factory($provider->getId());
    }

    public function delete()
    {
        $id = $this->provider->getId();
        if ($this->provider->delete()) {
            self::$sources[$id] = null;
        }
    }

    public function inSync()
    {
        return filesSourceSync::inSync($this->provider->getId());
    }

    public function beforePerformCopytask($params)
    {
        $orig_files = array(
            'source_file' => $params['task']['source_file'],
            'target_file' => $params['task']['target_file']
        );
        $params = $this->provider->beforePerformCopytask($params);
        $res_files = array(
            'source_file' => $params['task']['source_file'],
            'target_file' => $params['task']['target_file']
        );
        $res_files = $this->restoreImmutableFileFields($res_files, $orig_files);
        $params['task']['source_file'] = $res_files['source_file'];
        $params['task']['target_file'] = $res_files['target_file'];
        return $params;
    }

    public function afterPerformCopytask($params)
    {
        $orig_files = array(
            'source_file' => $params['task']['source_file'],
            'target_file' => $params['task']['target_file']
        );
        $params = $this->provider->afterPerformCopytask($params);
        $res_files = array(
            'source_file' => $params['task']['source_file'],
            'target_file' => $params['task']['target_file']
        );
        $res_files = $this->restoreImmutableFileFields($res_files, $orig_files);
        $params['task']['source_file'] = $res_files['source_file'];
        $params['task']['target_file'] = $res_files['target_file'];
        return $params;
    }

    /**
     * @param null|bool $value
     * @return bool
     */
    public function inPauseThrowException($value = null)
    {
        if ($value === null) {
            return $this->in_pause_throw_exception;
        } else {
            $this->in_pause_throw_exception = (bool) $value;
        }
    }

    /**
     * @param null|bool $value
     * @return bool
     */
    public function tokenInvalidThrowException($value = null)
    {
        if ($value === null) {
            return $this->token_invalid_throw_exception;
        } else {
            $this->token_invalid_throw_exception = (bool) $value;
        }
    }

    /**
     * Proxy all rest methods to under-layer provider
     * @param string $name
     * @param array $arguments
     * @return mixed|null|void
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        $simple_call = true;
        $prefixes = array('before', 'after', 'download', 'upload', 'syncData');
        foreach ($prefixes as $prefix) {
            if (substr($name, 0, strlen($prefix)) === $prefix) {
                $simple_call = false;
                break;
            }
        }

        return $simple_call ? $this->simpleCallProxy($name, $arguments) : $this->callProxyWithHandling($name, $arguments);
    }

    /**
     * Proxy all rest methods to under-layer provider
     * @param string $name
     * @param array $arguments
     * @return mixed|null|void
     * @throws Exception
     */
    private function simpleCallProxy($name, $arguments)
    {
        if (is_callable(array($this->provider, $name))) {
            return call_user_func_array(array($this->provider, $name), $arguments);
        }
        return null;
    }

    /**
     * Proxy all rest methods to under-layer provider
     * @param string $name
     * @param array $arguments
     * @param array $options
     * @return mixed|null|void
     * @throws Exception
     */
    private function callProxyWithHandling($name, $arguments, $options = array())
    {
        if (!is_callable(array($this->provider, $name))) {
            return null;
        }

        if (!$this->inPauseHandling($name)) {
            return null;
        }

        if (!$this->tokenInvalidHandling($name)) {
            return null;
        }

        try {
            if (preg_match('/before(.+)$/', $name, $m)) {
                return $this->beforeOperation($m[1], $arguments);
            }
            return call_user_func_array(array($this->provider, $name), $arguments);
        } catch (filesSourceTokenInvalidException $e) {

            if (ifset($options['token_invalid_try_again']) !== false) {
                $options['token_invalid_try_again'] = false;
                sleep(2);
                return $this->callProxyWithHandling($name, $arguments, $options);
            }

            $this->provider->markTokenAsInvalid();
            $this->provider->unpauseSync();

            if (!$this->tokenInvalidHandling($name)) {
                return null;
            }

        } catch (filesSourceFolderAlreadyExistsException $e) {

            $this->provider->unpauseSync();

            // just ignore, folder already exists, so just keep silence
            $first = reset($arguments);
            if (isset($first['type']) && $first['type'] === filesFileModel::TYPE_FOLDER) {
                return $first;
            }


        } catch (filesSourceRetryLaterException $e) {

            $params = $e->getParams();
            $offset = (int) ifset($params['offset']);
            $offset = $offset > 1 ? $offset : 1;

            if ($offset <= 2 && ifset($options['retry_later_call_again']) !== false) {
                $options['retry_later_call_again'] = false;
                return $this->callProxyWithHandling($name, $arguments, $options);
            }

            $this->provider->setPause(date('Y-m-d H:i:s', strtotime("{$offset} second")));

            if (!$this->inPauseHandling($name)) {
                return null;
            }

        } catch (Exception $e) {

            $this->provider->unpauseSync();

            if ($name === 'syncData') {
                $this->logException($e, $name);
                return;
            }
            throw $e;
        }
    }

    /**
     * @param $name
     * @return bool
     * @throws filesSourceRetryLaterException
     */
    private function inPauseHandling($name)
    {
        if (!$this->provider->inPause()) {
            return true;
        }

        if ($name === 'syncData') {
            return false;
        }

        if ($name === 'download') {
            $this->provider->addPause(3);   // while redirecting pause can be gone, so +3 seconds lag
            if ($this->in_pause_throw_exception || wa()->getEnv() !== 'backend') {
                throw new filesSourceRetryLaterException();
            } else {
                $url = wa()->getAppUrl('files') . '#/source/' . $this->getId();
                wa()->getResponse()->redirect($url);
                return false;
            }
        }

        if ($this->in_pause_throw_exception || wa()->getEnv() !== 'backend') {
            throw new filesSourceRetryLaterException();
        }

        wa()->getResponse()->addHeader('X-Wa-Files-Source-Status', 'in-pause');

        return false;
    }

    private function tokenInvalidHandling($name)
    {
        if (!$this->isPlugin() || $this->provider->hasValidToken()) {
            return true;
        }

        if ($name === 'syncData') {
            return false;
        }

        $is_backend = wa()->getEnv() == 'backend';
        $is_owner = $this->provider->getOwner()->getId() == wa()->getUser()->getId();

        $url = wa()->getAppUrl('files') . '#/source/' . $this->provider->getId();
        if ($name === 'download') {
            if (!$is_backend || !$is_owner || $this->token_invalid_throw_exception) {
                throw new filesSourceRetryLaterException();
            }
            wa()->getResponse()->redirect($url);
            return false;
        }

        if (!$is_backend || $this->token_invalid_throw_exception) {
            throw new filesSourceRetryLaterException();
        }
        wa()->getResponse()->addHeader('X-Wa-Files-Location', $url);

        return false;
    }

    private function beforeOperation($operation, $arguments)
    {
        if (count($arguments) <= 0) {
            return;
        }
        $params = $arguments[0];
        if (!is_array($params)) {
            return $params;
        }

        $method = "before{$operation}";
        if (!method_exists($this->provider, $method)) {
            return $arguments[0];
        }

        $params_files = null;
        $params_file = null;
        if (isset($params['files']) && is_array($params['files'])) {
            $params_files = $params['files'];
        }
        if (isset($params['file']) && is_array($params['file'])) {
            $params_file = $params['file'];
        }

        if (!$params_files && !$params_file) {
            return $params;
        }

        // call provider method
        $res = call_user_func_array(array($this->provider, $method), $arguments);

        if (!empty($res['files'])) {
            $res['files'] = $this->restoreImmutableFileFields($res['files'], $params_files);
        }

        if (!empty($res['file'])) {
            $orig_files = array(
                0 => $params_file
            );
            $res_files = array(
                0 => $res['file']
            );
            $res_files = $this->restoreImmutableFileFields($res_files, $orig_files);
            $res['file'] = $res_files[0];
        }

        return $res;
    }

    private function restoreImmutableFileFields($res_files, $orig_files)
    {
        // restore immutable fields
        $immutable_fields = $this->getImmutableFields();
        foreach ($res_files as $id => &$res_file) {
            if (is_array($res_file) && isset($orig_files[$id])) {
                foreach ($immutable_fields as $field) {
                    if (array_key_exists($field, $orig_files[$id])) {
                        $res_file[$field] = $orig_files[$id][$field];
                    } else {
                        if (array_key_exists($field, $res_file)) {
                            unset($res_file[$field]);
                        }
                    }
                }
            }
        }
        unset($res_file);

        return $res_files;
    }

    public function getImmutableFields()
    {
        return array('id', 'source_id', 'parent_id', 'type', 'depth', 'left_key', 'right_key');
    }

    /**
     * @param array $options
     * @return filesSourceSyncDriver
     */
    public function getSyncDriver($options = array())
    {
        $class_name = $this->provider->getSyncDriverClassName();
        if (class_exists($class_name)) {
            return new $class_name($this, $options);
        } else {
            return new filesSourceSyncDefaultDriver($this, $options);
        }
    }

    private function logException($e, $method_name)
    {
        $w = date('W');
        $y = date('Y');
        $message = get_class($e) . " - " . $e->getCode() . " - " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        waLog::log($message . PHP_EOL, "files/exceptions/{$y}/{$w}/{$method_name}.log");
    }
}

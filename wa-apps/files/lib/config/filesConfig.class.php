<?php

class filesConfig extends waAppConfig
{
    const FILE_TYPE_IMAGE = 'image';
    const FILE_TYPE_TEXT = 'text';

    protected static $max_execution_time;
    protected static $upload_max_filesize;
    protected static $post_max_size;
    protected static $memory_limit;
    protected static $is_ZipArchive_installed;

    /**
     * @var filesRightConfig
     */
    protected $right;
    protected $exts;
    protected $file_types;

    protected $_routes = array();

    public function onCount()
    {
        filesTasksPerformer::perform();

        // send messages from queue
        $messages_queue = new filesMessagesQueueModel();
        $messages_queue->sendAll();

        // delete expired locks
        $lm = new filesLockModel();
        $lm->deleteExpired();

        // show app notice
        $res = null;
        $app_on_count = $this->getAppOnCount();
        if (1 || $app_on_count == 'shared') {

            $last_active_time = filesApp::inst()->getLastActiveTime();

            $groups = filesRights::inst()->getGroupIds();
            $groups_str = "'" . join("','", $groups) . "'";

            $frm = new filesFileRightsModel();
            $sql = "SELECT COUNT(DISTINCT root_file_id) cnt FROM {$frm->getTableName()}
                    WHERE group_id IN({$groups_str}) AND create_datetime > ?";
            $res = $frm->query($sql, $last_active_time)->fetchField('cnt');
        }
        return $res ? $res : null;
    }

    public function checkRights($module, $action)
    {
        $access = parent::checkRights($module, $action);

        switch ($module) {
            case 'source':
                if ($action === 'sync') {
                    $access = true;
                } else {
                    $access = filesRights::inst()->hasAccessToSourceModule();
                }
                break;
            default:
                break;
        }

        return $access;
    }

    public function getRightConfig()
    {
        if ($this->right === null) {
            $this->right = new filesRightConfig();
        }
        return $this->right;
    }

    public function getStorageIcons()
    {
        $icons = array(
            filesStorageModel::ICON_DEFAULT_LIMIT,
            filesStorageModel::ICON_DEFAULT_PERSONAL,
        );
        return array_unique($icons + $this->getOption('storage_icons'));
    }

    public function getFilterIcons()
    {
        return $this->getOption('filter_icons');
    }

    public function getFileTypes($type = null)
    {
        if ($this->file_types === null) {
            $file_types = null;
            foreach (array($this->getConfigPath('data/file_types.php'), $this->getAppPath('lib/config/data/file_types.php')) as $filepath) {
                if (file_exists($filepath)) {
                    $file_types = include($filepath);
                    if (!empty($file_types)) {
                        break;
                    }
                }
            }
            $this->file_types = $file_types;
            foreach ($this->file_types as &$item) {
                $item['name'] = _w($item['name']);
            }
            unset($item);
        }
        return $type === null ? $this->file_types :
            ifset($this->file_types[$type], array(
                'name' => 'Unknown',
                'ext' => array(''),
            ));
    }

    /**
     * @param $type
     * @param $options
     * @param bool $permanently
     */
    public function setFileTypes($type, $options, $permanently = false)
    {
        // load types
        $this->getFileTypes();
        $this->file_types[$type] = $options;
        if ($permanently) {
            waUtils::varExportToFile($this->file_types, $this->getConfigPath('data/file_types.php'));
        }
    }

    public function getExts()
    {
        if ($this->exts === null) {
            $filepath = $this->getAppPath('lib/config/data/ext.php');
            if (file_exists($filepath)) {
                $this->exts = include($filepath);
            }
        }
        return $this->exts;
    }

    public function getOptionsForJs()
    {
        return array(
            'app_id' => 'files',
            'account_name' => wa()->accountName(),
            'hashes_for_change_listener' => $this->getHashesForChangeListener(),
            'locale' => wa()->getLocale()
        );
    }

    public function getFilesPerPage()
    {
        $files_per_page = $this->getOption('files_per_page');
        if (is_numeric($files_per_page)) {
            return (int) $files_per_page;
        } else {
            return 50;
        }
    }

    public function getNewnessExpireTime()
    {
        return $this->get('newness_expire_time', 300);
    }

    public function getPhotoSizes($type = null, $just_values = false)
    {
        $photo_sizes = $this->getOption('photo_sizes');
        if (!$photo_sizes) {
            $photo_sizes = array(
                'file_info' => 750
            );
        }
        $photo_sizes['default'] = 750;
        if ($just_values && $type) {
            return array_values(array_unique($photo_sizes));
        } else {
            return $type === null ? $photo_sizes : ifset($photo_sizes[$type], 0);
        }
    }

    public function getPhotoDefaultSize()
    {
        return $this->getPhotoSizes('default');
    }

    public function getPhotoMainSize()
    {
        return $this->getPhotoFileInfoSize();
    }

    public function getPhotoFileInfoSize()
    {
        return $this->getPhotoSizes('file_info');
    }

    public function getPhotoFileListSmallSize()
    {
        return $this->getPhotoSizes('file_list_small');
    }

    public function getPhotoSidebarSize()
    {
        return $this->getPhotoSizes('sidebar');
    }

// TODO: remove on production?
//    public function getPhotoThumbsOnDemand()
//    {
//        return $this->getOption('photo_thumbs_on_demand');
//    }

    public function getPhotoEnable2x()
    {
        return $this->getOption('photo_enable_2x');
    }

    public function getPhotoMaxSize()
    {
        return $this->getOption('photo_max_size');
    }

    public function getPhotoSharpen()
    {
        return $this->getOption('photo_sharpen');
    }

    public function getPhotoSaveQuality($enable_2x = false)
    {
        if ($enable_2x) {
            $quality = $this->getOption('photo_save_quality');
            if (!$quality) {
                $quality = 90;
            }
        } else {
            $quality = $this->getOption('photo_save_quality_2x');
            if (!$quality) {
                $quality = 70;
            }
        }
        return $quality;
    }

    public function getRouting($route = array(), $dispatch = false)
    {
        $frontend_type = isset($route['frontend_type']) ? $route['frontend_type'] : 0;
        if (!isset($this->_routes[$frontend_type]) || $dispatch) {
            $routes = parent::getRouting($route);
            if ($routes) {
                if (isset($routes[$frontend_type])) {
                    $routes = $routes[$frontend_type];
                } else {
                    $routes = $routes[0];
                }
            }

            /**
             * Extend routing via plugin routes
             * @event routing
             * @param array $routes
             * @return array $routes routes collected for every plugin
             */
            $result = wa()->event(array($this->application, 'routing'), $route);
            $all_plugins_routes = array();
            foreach ($result as $plugin_id => $routing_rules) {
                if ($routing_rules) {
                    $plugin = str_replace('-plugin', '', $plugin_id);
                    foreach ($routing_rules as $url => & $route) {
                        if (!is_array($route)) {
                            list($route_ar['module'], $route_ar['action']) = explode('/', $route);
                            $route = $route_ar;
                        }
                        if (!array_key_exists('plugin', $route)) {
                            $route['plugin'] = $plugin;
                        }
                        $all_plugins_routes[$url] = $route;
                    }
                    unset($route);
                }
            }
            $routes = array_merge($all_plugins_routes, $routes);
            $this->_routes[$frontend_type] = $routes;
        }

        return $this->_routes[$frontend_type];
    }

    public function get($name, $default = null)
    {
        $value = $this->getOption($name);
        return $value !== null ? $value : $default;
    }

    public function getTextFileShowMaxSize()
    {
        return $this->get('text_file_show_max_size', 1000);
    }

    public function getCopyChunkSize()
    {
        return $this->get('copy_chunk_size', 1000000);
    }

    public function copyRetryPause()
    {
        return $this->get('copy_retry_pause', 300);
    }

    /**
     * Returns array of sources available.
     *
     * @return array
     */
    public function getSourcePlugins()
    {
        $plugins = $this->getPlugins();
        foreach ($plugins as $id => $plugin) {
            if (empty($plugin['source'])) {
                unset($plugins[$id]);
            }
        }
        return $plugins;
    }

    public function getExtImgUrl()
    {
        return wa()->getAppStaticUrl('files') . 'img/filetypes/';
    }

    public function getUploadFileNotification()
    {
        return $this->get('upload_file_notification', 'favorite');
    }

    public function getUploadFileNotificationVariants($only_ids = false)
    {
        $variants = array(
            '' => _w('Off'),
            'favorite' => _w('My favorite folders only'),
            'all' => _w('All folders')
        );
        return $only_ids ? array_keys($variants) : $variants;
    }

    public function getAppOnCount()
    {
        return $this->get('app_on_count', 'uploaded_favorite');
    }

    public function getAppOnCountVariants($only_ids = false)
    {
        $variants = array(
            'shared' => _w('New shared files and folders'),
            'uploaded_favorite' => _w('New uploaded files (favorite folders only)'),
            'uploaded_all' => _w('New uploaded files (all folders)')
        );
        return $only_ids ? array_keys($variants) : $variants;
    }

    public function getMessagesQueueSendMaxCount()
    {
        return $this->get('messages_queue_send_max_count', 250);
    }

    public function getMessagesQueueMaxSize()
    {
        return $this->get('messages_queue_max_size', 100000);
    }

    public function getTasksPerRequest()
    {
        return $this->get('tasks_per_request', 20);
    }

    public function getFileLockTimeout()
    {
        return $this->get('file_lock_timeout', 1000);
    }

    public function getSyncTimeout()
    {
        return $this->get('sync_timeout', 300);
    }

    public function explainLogs($logs)
    {
        $logs = parent::explainLogs($logs);

        $app_url = wa()->getConfig()->getBackendUrl(true) . $this->getApplication() . '/';
        $explainer = new filesLogExplainer($logs, array(
            'app_url' => $app_url
        ));

        return $explainer->explain();
    }

    public function getMaxCopyFolders()
    {
        return $this->get('max_copy_folders', 10);
    }

    public function getMaxMoveFolders()
    {
        return $this->get('max_move_folders', 10);
    }

    public function getMaxFilesDownloadInArchive()
    {
        return $this->get('max_files_download_in_archive', 10);
    }

    public function isZipArchiveInstalled()
    {
        if (self::$is_ZipArchive_installed === null) {
            self::$is_ZipArchive_installed = class_exists('ZipArchive');
        }
        return self::$is_ZipArchive_installed;
    }

    public function getMaxExecutionTime($default = null)
    {
        if (self::$max_execution_time === null) {
            self::$max_execution_time = (int) ini_get('max_execution_time');
        }
        if ($default === null) {
            $default = wa()->getEnv() === 'cli' ? 120 : 30;
        }
        return self::$max_execution_time > 0 ? self::$max_execution_time : $default;
    }

    /**
     * @see filesApp::convertToBytes (about overflow of int) and be be careful
     * @return float|int
     */
    public function getUploadMaxFilesize()
    {
        if (self::$upload_max_filesize === null) {
            self::$upload_max_filesize = filesApp::convertToBytes(ini_get('upload_max_filesize'));
        }
        return self::$upload_max_filesize;
    }

    /**
     * @see filesApp::convertToBytes (about overflow of int) and be be careful
     * @return float|int
     */
    public function getPostMaxSize()
    {
        if (self::$post_max_size === null) {
            self::$post_max_size = filesApp::convertToBytes(ini_get('post_max_size'));
        }
        return self::$post_max_size;
    }

    /**
     * @see filesApp::convertToBytes (about overflow of int) and be be careful
     * @return float|int
     */
    public function getMemoryLimit()
    {
        if (self::$memory_limit === null) {
            self::$memory_limit = filesApp::convertToBytes(ini_get('memory_limit'));
        }
        return self::$memory_limit;
    }

    public function export($options)
    {
        $config_files = $this->getConfigPath('config.php');
        waFiles::create($config_files);
        waUtils::varExportToFile($options, $config_files);
    }

    public function getAppUploadMaxFilesSize()
    {
        $sizes[] = (int) $this->getUploadMaxFilesize();
        $sizes[] = 0.8 * $this->getPostMaxSize();
        $memory_limit = (int) $this->getMemoryLimit();
        if ($memory_limit !== -1) {
            $sizes[] = 0.7 * $memory_limit;
        }
        $max_file_size = floor(min($sizes));
        $n = filesApp::toIntegerNumber(log($max_file_size, 2));
        return pow(2, $n);
    }

    public function getHashesForChangeListener()
    {
        return array('', 'all', 'favorite', 'filter', 'folder', 'search', 'storage', 'tag', 'file');
    }

    public function getPluginPath($plugin_id)
    {
        $plugin_path = parent::getPluginPath($plugin_id);
        if (!self::isDebug()) {
            return $plugin_path;
        }

        $path = $this->getConfigPath('plugins.php', true);
        if (!file_exists($path)) {
            return $plugin_path;
        }

        $plugins_config = include($path);
        if (empty($plugins_config[$plugin_id])) {
            return $plugin_path;
        }

        if (!is_array($plugins_config[$plugin_id]) || !isset($plugins_config[$plugin_id]['path'])) {
            return $plugin_path;
        }

        if (!file_exists($plugins_config[$plugin_id]['path'])) {
            return $plugin_path;
        }

        return $plugins_config[$plugin_id]['path'];
    }


}

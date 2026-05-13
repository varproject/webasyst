<?php

class filesInstaller
{
    private $app_id;
    /**
     * @var filesConfig
     */
    private $config;
    /**
     * @var filesRightConfig
     */
    private $right_config;

    public function __construct() {
        $this->app_id = 'files';
        $this->config = filesApp::inst()->getConfig();
        $this->right_config = $this->config->getRightConfig();
    }

    public function installAll()
    {
        $this->createDefaultStorage();
        $this->createDefaultFilters();
        $this->createSupportThumbFiles();
        return true;
    }

    public function createDefaultStorage()
    {
        $storage_model = new filesStorageModel();
        $storage_model->add(array(
            'name' => _w('Common files'),
            'icon' => filesStorageModel::ICON_DEFAULT_LIMIT,
            'type' => filesStorageModel::ACCESS_TYPE_LIMITED
        ));
    }

    public function createDefaultFilters()
    {
        $model = new filesFilterModel();
        $model->add(array(
            'name' => _w('Documents'),
            'icon' => filesFilterModel::ICON_DOCUMENTS,
            'access_type' => filesFilterModel::ACCESS_TYPE_SHARED,
            'conditions' => 'search/file_type=document'
        ));
        $model->add(array(
            'name' => _w('Images'),
            'icon' => filesFilterModel::ICON_IMAGES,
            'access_type' => filesFilterModel::ACCESS_TYPE_SHARED,
            'conditions' => 'search/file_type=image'
        ));
    }

    public function createSupportThumbFiles()
    {
        $path = wa()->getDataPath(null, true, 'files');
        waFiles::copy($this->config->getAppPath('lib/config/data/.htaccess'), $path.'/.htaccess');
        waFiles::write($path.'/thumb.php', '<?php
        $file = realpath(dirname(__FILE__)."/../../../")."/wa-apps/files/lib/config/data/thumb.php";

        if (file_exists($file)) {
            include($file);
        } else {
            header("HTTP/1.0 404 Not Found");
        }
        ');
    }

}
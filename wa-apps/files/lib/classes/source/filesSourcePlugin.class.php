<?php

abstract class filesSourcePlugin extends waPlugin
{
    const TOKEN_RECEIVING_METHOD_AUTO = 'auto';
    const TOKEN_RECEIVING_METHOD_MANUAL = 'manual';

    /**
     * @param $plugin_id
     * @return filesSourcePlugin|null
     */
    public static function factory($plugin_id)
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $plugins = self::getPlugins();
        if (!isset($plugins[$plugin_id])) {
            return null;
        }
        $info = $plugins[$plugin_id];
        if (isset($info['class_name'])) {
            $class_name = $info['class_name'];
        } else {
            $class_name = $info['app_id'] . filesApp::ucfirst($info['id']) . 'Plugin';
        }
        if (!class_exists($class_name)) {
            return null;
        }
        $plugin = new $class_name($info);
        if (!($plugin instanceof filesSourcePlugin)) {
            return null;
        }
        return $plugin;
    }

    public static function getPlugins()
    {
        static $plugins;
        if ($plugins === null) {
            $plugins = wa('files')->getConfig()->getPlugins();
            foreach ($plugins as $id => $plugin) {
                if (empty($plugin['source'])) {
                    unset($plugins[$id]);
                }
            }
        }
        return $plugins;
    }

    /**
     * @param $source_id
     * @param array $options
     * @return string Html form for authorize
     * @throws filesSourceAuthorizeFailedException|Exception
     */
    abstract public function authorizeBegin($source_id, $options = array());

    /**
     * @param array $options
     * @return bool Success status
     * @throws filesSourceAuthorizeFailedException|Exception
     */
    abstract public function authorizeEnd($options = array());

    public function getId()
    {
        return $this->id;
    }

    public function __get($name)
    {
        return $this->getSettings($name);
    }

    public function getIconUrl()
    {
        return $this->getPluginStaticUrl(true) . $this->info['icon'];
    }

    public function getCallbackUrl()
    {
        return self::getCallbackUrlById($this->getId());
    }

    public static function getCallbackUrlById($id)
    {
        return filesApp::getAbsoluteUrl() . '?module=source&action=authorize&authorize_end=1&id=' . $id;
    }

    public function getTokenReceivingMethod()
    {
        return self::TOKEN_RECEIVING_METHOD_AUTO;
    }

    public function getPath()
    {
        return wa('files')->getAppPath('plugins/' . $this->getId() . '/');
    }

    public function getSupport()
    {
        $support = (array) ifset($this->info['support']);
        return array_merge(filesSource::getAllSupport(), $support);
    }

    public static function getAllSupport()
    {
        return array(
            'chunk_download' => true,
            'chunk_upload' => true
        );
    }

    public function isOnDemand()
    {
        return (bool) ifset($this->info['is_on_demand']);
    }

    /**
     * @return bool
     */
    public function areAllRequiredSettingsFilled()
    {
        $config = $this->getSettingsConfig();
        $settings = $this->getSettings();
        foreach ($config as $key => $options) {
            if (!empty($options['required']) && empty($settings[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Never static, cause instance of source tied to current plugin instance
     *
     * @param $type
     * @param array $info
     * @param array $options
     * @return filesSourceProvider
     */
    public function factoryProvider($type, $info, $options = array())
    {
        $name = 'files'.filesApp::ucfirst($type).'SourceProvider';
        if (class_exists($name)) {
            return new $name($info, $options);
        } else {
            return new filesSourceNullProvider();
        }
    }
}

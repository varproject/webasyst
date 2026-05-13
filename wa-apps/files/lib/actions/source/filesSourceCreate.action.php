<?php

class filesSourceCreateAction extends filesController
{
    public function execute()
    {
        $source = $this->getSource();
        if (is_numeric($source->getId())) {
            $this->assign('auth_failed', $this->getAuthFailedMessage($source));
        }
        $this->assign(array(
            'source' => $source,
            'sources' => $this->getSourcePlugins($source),
            'is_any_source_plugin_installed' => $this->isAnySourcePluginInstalled()
        ));
    }

    public function getSource()
    {
        $source = ifset($this->params['source']);
        if ($source instanceof filesSource) {
            return $source;
        } else {
            return filesSource::factoryNull();
        }
    }

    public function getSourcePlugins(filesSource $source)
    {
        $plugins = array();

        // source tuning is not completed?
        if ($source->getId() && is_numeric($source->getId()) && !$source->getToken()) {
            foreach ($this->getConfig()->getSourcePlugins() as $plugin_info) {
                $plugin_id = $plugin_info['id'];
                $plugin_info['enabled'] = $plugin_id == $source->getType();
                $plugins[$plugin_id] = $plugin_info;
            }
            return $plugins;
        }

        foreach ($this->getConfig()->getSourcePlugins() as $plugin_info) {
            $plugin_id = $plugin_info['id'];
            $plugin = filesSourcePlugin::factory($plugin_id);
            $plugin_info['required_settings'] = !$plugin->areAllRequiredSettingsFilled();
            $plugin_info['enabled'] = $plugin->areAllRequiredSettingsFilled();
            $plugins[$plugin_id] = $plugin_info;
        }

        return $plugins;
    }

    public function getAuthFailedMessage(filesSource $source)
    {
        $key = 'source/' . $source->getId() . '/auth_failed';
        $message = $this->getStorage()->get($key);
        $this->getStorage()->del($key);
        return $message;
    }
}

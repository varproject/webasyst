<?php

class filesPluginsActions extends waPluginsActions
{
    public function preExecute()
    {
        $this->shadowed=true;
        $this->is_ajax = false;
        if (!$this->getUser()->isAdmin('files')) {
            throw new waRightsException(_ws('Access denied'));
        }
    }

    public function getTemplatePath($action = null)
    {
        $path = parent::getTemplatePath($action);
        if ($action !== 'settings') {
            $path = parent::getTemplatePath($action);
        } else {

            $is_source_plugin_page = false;
            $plugin_id = waRequest::get('id', null);
            if ($plugin_id) {
                $plugins = $this->getConfig()->getPlugins();
                if (isset($plugins[$plugin_id])) {
                    $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);
                    if (is_object($plugin) && $plugin instanceof filesSourcePlugin) {
                        $is_source_plugin_page = true;
                    }
                }
            }

            if ($is_source_plugin_page) {
                $this->getView()->assign('orig_path', $path);
                $path = $this->getConfig()->getAppPath('templates/actions/plugins/Settings.html');
            }

        }

        return $path;
    }
}

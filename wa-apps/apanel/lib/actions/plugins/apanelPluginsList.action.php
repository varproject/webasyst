<?php

class apanelPluginsListAction extends waViewAction
{
    public function execute()
    {
        $this->checkRights();

        // Подключаем layout Apanel
        $this->setLayout(new apanelBackendLayout());

        // Получаем список всех плагинов
        $plugins = $this->getPluginsList();

        // Состояние плагинов (включены/отключены)
        $plugins_enabled = wa('apanel')->getConfig()->getInfo('plugins') ? true : false;

        $this->view->assign(array(
            'plugins'         => $plugins,
            'plugins_enabled' => $plugins_enabled,
        ));
    }

    protected function getPluginsList()
    {
        $app = wa('apanel');
        $plugins = array();

        $plugins_path = wa()->getAppPath('plugins', 'apanel');
        if (!is_dir($plugins_path)) {
            return $plugins;
        }

        $dir = opendir($plugins_path);
        while (($plugin_id = readdir($dir)) !== false) {
            if ($plugin_id[0] === '.') {
                continue;
            }

            $plugin_config_path = $plugins_path . '/' . $plugin_id . '/lib/config/plugin.php';
            if (!is_file($plugin_config_path)) {
                continue;
            }

            try {
                $plugin = $app->getPlugin($plugin_id);
                if ($plugin) {
                    $plugins[$plugin_id] = array(
                        'id'          => $plugin_id,
                        'name'        => $plugin->getName(),
                        'description' => ifset($plugin->getInfo('description'), ''),
                        'version'     => $plugin->getVersion(),
                        'vendor'      => ifset($plugin->getInfo('vendor'), ''),
                        'icon'        => $this->getPluginIcon($plugin_id, $plugin->getInfo()),
                        'enabled'     => $this->isPluginEnabled($plugin_id),
                    );
                }
            } catch (Exception $e) {
                continue;
            }
        }
        closedir($dir);

        return $plugins;
    }

    protected function isPluginEnabled($plugin_id)
    {
        $plugins_config_path = wa()->getConfig()->getRootPath() . '/wa-config/apps/apanel/plugins.php';
        if (!is_file($plugins_config_path)) {
            return false;
        }

        $enabled = include($plugins_config_path);
        return isset($enabled[$plugin_id]);
    }

    protected function getPluginIcon($plugin_id, $info)
    {
        if (isset($info['icons'][48])) {
            return wa()->getAppUrl('apanel') . 'plugins/' . $plugin_id . '/' . $info['icons'][48];
        } elseif (isset($info['img'])) {
            return wa()->getAppUrl('apanel') . 'plugins/' . $plugin_id . '/' . $info['img'];
        }
        return '';
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}

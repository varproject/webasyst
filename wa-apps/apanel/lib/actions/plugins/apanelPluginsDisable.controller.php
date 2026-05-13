<?php

class apanelPluginsDisableController extends waController
{
    public function execute()
    {
        $this->checkRights();

        if (waRequest::method() !== waRequest::METHOD_POST) {
            throw new waException('Method not allowed', 405);
        }

        $plugin_id = waRequest::param('id', '', waRequest::TYPE_STRING_TRIM);

        if (!$plugin_id || !preg_match('~^[a-z0-9_]+$~i', $plugin_id)) {
            throw new waException('Invalid plugin ID', 400);
        }

        try {
            $plugin = wa('apanel')->getPlugin($plugin_id);
            if (!$plugin) {
                throw new waException('Plugin not found', 404);
            }

            $enabled_plugins = $this->getEnabledPlugins();
            unset($enabled_plugins[$plugin_id]);

            $this->saveEnabledPlugins($enabled_plugins);

            $this->logAction('plugin_disable', array(
                'plugin_id'   => $plugin_id,
                'plugin_name' => $plugin->getName(),
            ));
        } catch (Exception $e) {
            throw new waException($e->getMessage(), 500);
        }

        $this->redirect(wa()->getAppUrl('apanel') . 'plugins/');
    }

    protected function getEnabledPlugins()
    {
        $path = wa()->getConfig()->getRootPath() . '/wa-config/apps/apanel/plugins.php';

        if (!is_file($path)) {
            return array();
        }

        $plugins = include($path);
        return is_array($plugins) ? $plugins : array();
    }

    protected function saveEnabledPlugins($plugins)
    {
        $path = wa()->getConfig()->getRootPath() . '/wa-config/apps/apanel/';

        if (!is_dir($path)) {
            waFiles::create($path);
        }

        $content = "<?php\n\nreturn " . var_export($plugins, true) . ";\n";
        file_put_contents($path . 'plugins.php', $content);

        wa('apanel')->getConfig()->clearCache();
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}

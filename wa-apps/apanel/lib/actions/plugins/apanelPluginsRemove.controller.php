<?php

class apanelPluginsRemoveController extends waController
{
    public function execute()
    {
        $this->checkRights();
        $this->checkPostMethod();

        $plugin_id = $this->getPluginId();
        $plugin = $this->getPlugin($plugin_id);

        $this->removePlugin($plugin_id, $plugin);

        $this->redirect($this->getPluginsUrl());
    }

    protected function checkPostMethod()
    {
        if (waRequest::method() !== waRequest::METHOD_POST) {
            throw new waException('Method not allowed', 405);
        }
    }

    protected function getPluginId()
    {
        $plugin_id = waRequest::param('id', '', waRequest::TYPE_STRING_TRIM);

        if (!$plugin_id || !preg_match('~^[a-z0-9_]+$~i', $plugin_id)) {
            throw new waException('Invalid plugin ID', 400);
        }

        return $plugin_id;
    }

    protected function getPlugin($plugin_id)
    {
        try {
            $plugin = wa('apanel')->getPlugin($plugin_id);
        } catch (Exception $e) {
            throw new waException('Plugin not found', 404);
        }

        if (!$plugin) {
            throw new waException('Plugin not found', 404);
        }

        return $plugin;
    }

    protected function removePlugin($plugin_id, waPlugin $plugin)
    {
        $enabled_plugins = $this->getEnabledPlugins();
        unset($enabled_plugins[$plugin_id]);

        $this->saveEnabledPlugins($enabled_plugins);

        $plugin->uninstall();

        $plugin_path = wa()->getAppPath('plugins/' . $plugin_id, 'apanel');

        if (is_dir($plugin_path)) {
            waFiles::removeDir($plugin_path);
        }

        wa('apanel')->getConfig()->clearCache();
    }

    protected function getEnabledPlugins()
    {
        $path = wa()->getConfig()->getRootPath() . '/wa-config/apps/apanel/plugins.php';

        if (!is_file($path)) {
            return [];
        }

        $plugins = include($path);

        return is_array($plugins) ? $plugins : [];
    }

    protected function saveEnabledPlugins(array $plugins)
    {
        $path = wa()->getConfig()->getRootPath() . '/wa-config/apps/apanel/';

        if (!is_dir($path)) {
            waFiles::create($path);
        }

        $content = "<?php\n\nreturn " . var_export($plugins, true) . ";\n";

        file_put_contents($path . 'plugins.php', $content);

        wa('apanel')->getConfig()->clearCache();
    }

    protected function getPluginsUrl()
    {
        return wa()->getAppUrl('apanel') . 'settings/plugins/';
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}

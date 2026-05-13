<?php

class apanelPluginCatalog
{
    const APP_ID = 'apanel';

    public function getItems()
    {
        $items = [];

        foreach ($this->getInstallerPlugins() as $plugin_id => $plugin) {
            $items[$plugin_id] = $plugin;
        }

        foreach ($this->getLocalPlugins() as $plugin_id => $plugin) {
            if (isset($items[$plugin_id])) {
                $items[$plugin_id] = array_replace($items[$plugin_id], $plugin);
            } else {
                $items[$plugin_id] = $plugin;
            }
        }

        foreach ($items as $plugin_id => &$item) {
            $item = $this->normalizeRuntimeState($plugin_id, $item);
            $item = $this->prepareTableItem($plugin_id, $item);
        }
        unset($item);

        uasort($items, [$this, 'sortItems']);

        return $items;
    }

    public function enable($plugin_id)
    {
        $plugin_id = $this->normalizeRequestPluginId($plugin_id);

        return $this->setStatus($plugin_id, true);
    }

    public function disable($plugin_id)
    {
        $plugin_id = $this->normalizeRequestPluginId($plugin_id);

        return $this->setStatus($plugin_id, false);
    }

    public function remove($plugin_id)
    {
        $plugin_id = $this->normalizeRequestPluginId($plugin_id);

        if (!$this->isInstalled($plugin_id)) {
            throw new waException('Plugin not installed', 404);
        }

        $plugin = $this->getPlugin($plugin_id);

        $this->disable($plugin_id);

        $plugin->uninstall();

        $plugin_path = wa()->getAppPath('plugins/' . $plugin_id, self::APP_ID);

        if (is_dir($plugin_path)) {
            waFiles::delete($plugin_path, true);
        }

        wa(self::APP_ID)->getConfig()->clearCache();

        return true;
    }

    public function getPlugin($plugin_id)
    {
        $plugin_id = $this->normalizeRequestPluginId($plugin_id);

        if (!$this->isInstalled($plugin_id)) {
            throw new waException('Plugin not installed', 404);
        }

        $plugin = wa(self::APP_ID)->getPlugin($plugin_id);

        if (!$plugin) {
            throw new waException('Plugin not found', 404);
        }

        return $plugin;
    }

    public function getPluginInfo($plugin_id)
    {
        $plugin = $this->getPlugin($plugin_id);
        $info = $plugin->getInfo();

        return [
            'id'          => $plugin_id,
            'name'        => $plugin->getName(),
            'description' => $this->stringValue(ifset($info['description'], '')),
            'version'     => $plugin->getVersion(),
            'vendor'      => $this->stringValue(ifset($info['vendor'], '')),
        ];
    }

    public function getSettingsControls($plugin_id)
    {
        $plugin = $this->getPlugin($plugin_id);

        $controls = $plugin->getControls([
            'namespace' => 'settings',
        ]);

        return is_array($controls) ? $controls : [];
    }

    public function saveSettings($plugin_id, array $settings)
    {
        $plugin = $this->getPlugin($plugin_id);

        $plugin->saveSettings($settings);

        wa(self::APP_ID)->getConfig()->clearCache();

        return true;
    }

    public function getInstallUrl($plugin_id)
    {
        $plugin_id = $this->normalizeRequestPluginId($plugin_id);

        $return_url = wa()->getAppUrl(self::APP_ID) . 'settings/plugins/';

        $store_url = wa()->getAppUrl('installer')
            . 'store/plugin/'
            . self::APP_ID . '/'
            . rawurlencode($plugin_id) . '/';

        return $store_url . '?' . http_build_query([
            'in_app' => 1,
            'return_url' => $return_url,
            'go_return_hash_after_installation' => 1,
        ]);
    }

    protected function setStatus($plugin_id, $enabled)
    {
        if (!$this->isInstalled($plugin_id)) {
            throw new waException('Plugin not installed', 404);
        }

        $old_app = wa()->getApp();

        wa('installer', true);

        try {
            $result = installerHelper::pluginSetStatus(self::APP_ID, $plugin_id, (bool) $enabled);
        } catch (Exception $e) {
            wa($old_app, true);
            throw $e;
        }

        wa($old_app, true);

        if ($result !== true) {
            throw new waException(implode("\n", (array) $result), 500);
        }

        wa(self::APP_ID)->getConfig()->clearCache();

        if ($enabled) {
            wa(self::APP_ID)->event('plugin.enable');
        }

        return true;
    }

    protected function getInstallerPlugins()
    {
        $old_app = wa()->getApp();
        $plugins = [];

        try {
            wa('installer', true);

            $apps = installerHelper::getInstaller()->getApps([
                'installed'    => true,
                'requirements' => true,
                'action'       => true,
                'system'       => false,
                'status'       => false,
            ], [
                'extras' => 'plugins',
            ]);

            if (!empty($apps[self::APP_ID]['plugins']) && is_array($apps[self::APP_ID]['plugins'])) {
                foreach ($apps[self::APP_ID]['plugins'] as $plugin_key => $plugin) {
                    $this->appendInstallerPlugin($plugins, $plugin_key, $plugin);
                }
            }

            $extras = installerHelper::getInstaller()->getExtras([self::APP_ID], 'plugins', [
                'local'            => true,
                'status'           => false,
                'installed'        => true,
                'translate_titles' => true,
            ]);

            if (!empty($extras[self::APP_ID]['plugins']) && is_array($extras[self::APP_ID]['plugins'])) {
                foreach ($extras[self::APP_ID]['plugins'] as $plugin_key => $plugin) {
                    $this->appendInstallerPlugin($plugins, $plugin_key, $plugin);
                }
            }
        } catch (Exception $e) {
            waLog::log($e->getMessage(), 'apanel/plugins.log');
        }

        wa($old_app, true);

        return $plugins;
    }

    protected function appendInstallerPlugin(array &$plugins, $plugin_key, $plugin)
    {
        if (!is_array($plugin)) {
            return;
        }

        $plugin_id = $this->normalizeInstallerPluginId($plugin_key, $plugin);

        if ($plugin_id === '') {
            return;
        }

        if (!$this->isCompleteInstallerProduct($plugin_id, $plugin)) {
            return;
        }

        $plugins[$plugin_id] = array_replace(
            ifset($plugins[$plugin_id], []),
            $this->normalizeInstallerPlugin($plugin_id, $plugin)
        );
    }

    protected function normalizeInstallerPlugin($plugin_id, array $plugin)
    {
        return [
            'id'          => $plugin_id,
            'name'        => $this->stringValue(ifset($plugin['name'], $plugin_id)),
            'description' => $this->stringValue(ifset($plugin['description'], ifset($plugin['summary'], ''))),
            'version'     => $this->getInstallerPluginVersion($plugin),
            'vendor'      => $this->stringValue(ifset($plugin['vendor_name'], ifset($plugin['vendor'], ''))),
            'installed'   => false,
            'enabled'     => false,
            'commercial'  => !empty($plugin['commercial']),
            'purchased'   => !empty($plugin['purchased']),
            'applicable'  => array_key_exists('applicable', $plugin) ? !empty($plugin['applicable']) : true,
            'action'      => $this->stringValue(ifset($plugin['action'], 'none')),
            'slug'        => $this->stringValue(ifset($plugin['slug'], self::APP_ID . '/plugins/' . $plugin_id)),
            'source'      => 'installer',
        ];
    }

    protected function getLocalPlugins()
    {
        $plugins = [];
        $plugins_path = wa()->getAppPath('plugins', self::APP_ID);

        if (!is_dir($plugins_path)) {
            return $plugins;
        }

        $dir = opendir($plugins_path);

        if (!$dir) {
            return $plugins;
        }

        while (($plugin_id = readdir($dir)) !== false) {
            if ($plugin_id === '' || $plugin_id[0] === '.') {
                continue;
            }

            if (!preg_match('~^[a-z0-9_]+$~i', $plugin_id)) {
                continue;
            }

            if (!$this->isInstalled($plugin_id)) {
                continue;
            }

            try {
                $plugin = wa(self::APP_ID)->getPlugin($plugin_id);

                if (!$plugin) {
                    continue;
                }

                $info = $plugin->getInfo();

                $plugins[$plugin_id] = [
                    'id'          => $plugin_id,
                    'name'        => $plugin->getName(),
                    'description' => $this->stringValue(ifset($info['description'], '')),
                    'version'     => $plugin->getVersion(),
                    'vendor'      => $this->stringValue(ifset($info['vendor'], '')),
                    'installed'   => true,
                    'enabled'     => false,
                    'commercial'  => false,
                    'purchased'   => false,
                    'applicable'  => true,
                    'action'      => 'none',
                    'slug'        => self::APP_ID . '/plugins/' . $plugin_id,
                    'source'      => 'local',
                ];
            } catch (Exception $e) {
                continue;
            }
        }

        closedir($dir);

        return $plugins;
    }

    protected function normalizeRuntimeState($plugin_id, array $item)
    {
        $installed = $this->isInstalled($plugin_id);
        $enabled = $installed && $this->isEnabled($plugin_id);

        $item['installed'] = $installed;
        $item['enabled'] = $enabled;
        $item['deleted'] = !$installed && !empty($item['source']) && $item['source'] === 'installer';

        return $item;
    }

    protected function prepareTableItem($plugin_id, array $item)
    {
        $installed = !empty($item['installed']);
        $enabled = !empty($item['enabled']);

        $item['id'] = $plugin_id;
        $item['_row_id'] = $plugin_id;
        $item['name'] = $this->stringValue(ifset($item['name'], $plugin_id));
        $item['description'] = $this->stringValue(ifset($item['description'], ''));
        $item['version'] = $this->stringValue(ifset($item['version'], '—'));
        $item['vendor'] = $this->stringValue(ifset($item['vendor'], '—'));
        $item['state_html'] = $this->getStateHtml($item);
        $item['actions_html'] = $this->getActionsHtml($plugin_id, $item);
        $item['sort_weight'] = $this->getSortWeight($item);

        return $item;
    }

    protected function getStateHtml(array $item)
    {
        if (!empty($item['installed']) && !empty($item['enabled'])) {
            return '<span class="text-success">Установлен и включён</span>';
        }

        if (!empty($item['installed'])) {
            return '<span class="text-muted">Установлен, выключен</span>';
        }

        if (!empty($item['commercial']) && empty($item['purchased'])) {
            return '<span class="text-warning">Доступен к покупке</span>';
        }

        return '<span class="text-primary">Доступен к установке</span>';
    }

    protected function getActionsHtml($plugin_id, array $item)
    {
        $app_url = wa()->getAppUrl(self::APP_ID) . 'settings/plugins/';
        $installed = !empty($item['installed']);
        $enabled = !empty($item['enabled']);

        $html = '<div class="d-inline-flex gap-1 justify-content-end align-items-center">';

        if ($installed) {
            $html .= '<a href="' . htmlspecialchars($app_url . '?modal=plugin-settings&id=' . rawurlencode($plugin_id), ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-primary btn-sm" hx-boost="true">';
            $html .= 'Настройки';
            $html .= '</a>';

            if ($enabled) {
                $html .= '<form method="post" action="' . htmlspecialchars($app_url . $plugin_id . '/disable/', ENT_QUOTES, 'UTF-8') . '" class="m-0">';
                $html .= $this->csrfHtml();
                $html .= apanelUi::getControl('button', 'plugin_disable_' . $plugin_id, [
                    'label' => 'Выключить',
                    'class' => 'btn btn-outline-warning btn-sm',
                    'type'  => 'submit',
                ]);
                $html .= '</form>';
            } else {
                $html .= '<form method="post" action="' . htmlspecialchars($app_url . $plugin_id . '/enable/', ENT_QUOTES, 'UTF-8') . '" class="m-0">';
                $html .= $this->csrfHtml();
                $html .= apanelUi::getControl('button', 'plugin_enable_' . $plugin_id, [
                    'label' => 'Включить',
                    'class' => 'btn btn-outline-success btn-sm',
                    'type'  => 'submit',
                ]);
                $html .= '</form>';
            }

            $html .= '<a href="' . htmlspecialchars($app_url . '?modal=plugin-remove&id=' . rawurlencode($plugin_id), ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-danger btn-sm" hx-boost="true">';
            $html .= 'Удалить';
            $html .= '</a>';
        } else {
            $label = (!empty($item['commercial']) && empty($item['purchased'])) ? 'Купить' : 'Установить';

            $html .= '<a href="' . htmlspecialchars($this->getInstallUrl($plugin_id), ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary btn-sm">';
            $html .= htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function isCompleteInstallerProduct($plugin_id, array $plugin)
    {
        if ($this->isInstalled($plugin_id)) {
            return true;
        }

        if (!empty($plugin['installed']) && is_array($plugin['installed'])) {
            return true;
        }

        $name = $this->stringValue(ifset($plugin['name'], ''));
        $description = $this->stringValue(ifset($plugin['description'], ifset($plugin['summary'], '')));
        $version = $this->stringValue(ifset($plugin['version'], ''));
        $vendor = $this->stringValue(ifset($plugin['vendor_name'], ifset($plugin['vendor'], '')));

        if ($name !== '' || $description !== '' || $version !== '' || $vendor !== '') {
            return true;
        }

        if (!empty($plugin['commercial']) || !empty($plugin['purchased'])) {
            return true;
        }

        return false;
    }

    protected function normalizeInstallerPluginId($plugin_key, array $plugin)
    {
        $candidates = [];

        if (!empty($plugin['slug']) && is_scalar($plugin['slug'])) {
            $slug = trim((string) $plugin['slug']);

            if (class_exists('installerHelper')) {
                list($app_id, $ext_id, $type) = installerHelper::parseSlug($slug);

                if ($app_id === self::APP_ID && $type === 'plugin' && $ext_id) {
                    $candidates[] = $ext_id;
                }
            }

            if (preg_match('~^' . preg_quote(self::APP_ID, '~') . '/plugins/([a-z0-9_]+)$~i', $slug, $matches)) {
                $candidates[] = $matches[1];
            }
        }

        foreach (['id', 'plugin_id'] as $field) {
            if (!empty($plugin[$field]) && is_scalar($plugin[$field])) {
                $value = trim((string) $plugin[$field]);

                if (preg_match('~^' . preg_quote(self::APP_ID, '~') . '/plugins/([a-z0-9_]+)$~i', $value, $matches)) {
                    $candidates[] = $matches[1];
                } else {
                    $candidates[] = $value;
                }
            }
        }

        if (is_scalar($plugin_key)) {
            $value = trim((string) $plugin_key);

            if (preg_match('~^' . preg_quote(self::APP_ID, '~') . '/plugins/([a-z0-9_]+)$~i', $value, $matches)) {
                $candidates[] = $matches[1];
            } else {
                $candidates[] = $value;
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if (preg_match('~^[a-z0-9_]+$~i', $candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    protected function getInstallerPluginVersion(array $plugin)
    {
        if (!empty($plugin['installed']) && is_array($plugin['installed']) && isset($plugin['installed']['version'])) {
            return $this->stringValue($plugin['installed']['version']);
        }

        if (isset($plugin['version'])) {
            return $this->stringValue($plugin['version']);
        }

        return '—';
    }

    protected function isInstalled($plugin_id)
    {
        return is_file(wa()->getAppPath('plugins/' . $plugin_id . '/lib/config/plugin.php', self::APP_ID));
    }

    protected function isEnabled($plugin_id)
    {
        $plugins = $this->getEnabledPlugins();

        if (array_key_exists($plugin_id, $plugins)) {
            return !empty($plugins[$plugin_id]);
        }

        return in_array($plugin_id, $plugins, true);
    }

    protected function getEnabledPlugins()
    {
        $path = wa()->getConfig()->getRootPath() . '/wa-config/apps/' . self::APP_ID . '/plugins.php';

        if (!is_file($path)) {
            return [];
        }

        $plugins = include($path);

        return is_array($plugins) ? $plugins : [];
    }

    protected function normalizeRequestPluginId($plugin_id)
    {
        $plugin_id = trim((string) $plugin_id);

        if (!$plugin_id || !preg_match('~^[a-z0-9_]+$~i', $plugin_id)) {
            throw new waException('Invalid plugin ID', 400);
        }

        return $plugin_id;
    }

    protected function sortItems($a, $b)
    {
        $a_weight = $this->getSortWeight($a);
        $b_weight = $this->getSortWeight($b);

        if ($a_weight !== $b_weight) {
            return $a_weight <=> $b_weight;
        }

        return strcasecmp((string) ifset($a['name'], ''), (string) ifset($b['name'], ''));
    }

    protected function getSortWeight(array $item)
    {
        if (!empty($item['installed']) && !empty($item['enabled'])) {
            return 10;
        }

        if (!empty($item['installed'])) {
            return 20;
        }

        if (!empty($item['commercial']) && empty($item['purchased'])) {
            return 40;
        }

        return 30;
    }

    protected function csrfHtml()
    {
        $token = waRequest::cookie('_csrf', '', waRequest::TYPE_STRING_TRIM);

        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    protected function stringValue($value)
    {
        if (is_array($value)) {
            $locale = wa()->getLocale();

            if (isset($value[$locale]) && is_scalar($value[$locale])) {
                return (string) $value[$locale];
            }

            foreach ($value as $item) {
                if (is_scalar($item)) {
                    return (string) $item;
                }
            }

            return '';
        }

        if (is_object($value)) {
            return '';
        }

        return (string) $value;
    }
}

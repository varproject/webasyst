<?php

class apanelPluginsListController extends waViewController
{
    const APP_ID = 'apanel';

    public function execute()
    {
        $this->checkRights();

        $this->setLayout(new apanelBackendLayout());

        $this->layout->assign('sidebar_enabled', false);
        $this->layout->assign('main_body_tree_enabled', false);
        $this->layout->assign('main_body_table_enabled', true);

        $this->prepareModal();
        $this->prepareTable($this->getPluginsList());
    }

    protected function prepareModal()
    {
        $modal = waRequest::get('modal', '', waRequest::TYPE_STRING_TRIM);

        if ($modal !== 'plugin-remove') {
            return;
        }

        $this->layout->assign('backend_modal_page', true);
        $this->executeAction(new apanelPluginsRemoveDialogAction(), 'backend_modal_page');
    }

    protected function prepareTable(array $plugins)
    {
        $this->executeAction(new apanelTableAction([
            'items' => $plugins,

            'columns' => [
                'name' => [
                    'title'   => 'Плагин',
                    'type'    => 'title',
                    'thclass' => 'width-20',
                ],

                'description' => [
                    'title'   => 'Описание',
                    'thclass' => 'width-30',
                ],

                'version' => [
                    'title'   => 'Версия',
                    'thclass' => 'width-8',
                ],

                'vendor' => [
                    'title'   => 'Разработчик',
                    'thclass' => 'width-10',
                ],

                'state_html' => [
                    'title'   => 'Состояние',
                    'type'    => 'html',
                    'thclass' => 'width-10',
                ],

                'actions_html' => [
                    'title'   => 'Действия',
                    'type'    => 'html',
                    'thclass' => 'width-20 text-end',
                    'tdclass' => 'text-end',
                ],
            ],

            'show_checkbox' => false,
            'show_actions'  => false,
            'empty_text'    => 'Плагины для приложения Apanel не найдены.',
        ]), 'main_body_table_items');
    }

    protected function getPluginsList()
    {
        $items = [];

        foreach ($this->getStorePlugins() as $plugin_id => $plugin) {
            $items[$plugin_id] = $plugin;
        }

        foreach ($this->getLocalPlugins() as $plugin_id => $plugin) {
            if (isset($items[$plugin_id])) {
                $items[$plugin_id] = array_replace($items[$plugin_id], $plugin);
            } else {
                $items[$plugin_id] = $plugin;
            }
        }

        uasort($items, function ($a, $b) {
            return strcasecmp((string) ifset($a['name'], ''), (string) ifset($b['name'], ''));
        });

        foreach ($items as $plugin_id => &$item) {
            $item = $this->prepareTableItem($plugin_id, $item);
        }
        unset($item);

        return $items;
    }

    protected function getStorePlugins()
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
                foreach ($apps[self::APP_ID]['plugins'] as $plugin_id => $plugin) {
                    if (!is_array($plugin)) {
                        continue;
                    }

                    $plugin_id = $this->normalizePluginId($plugin_id, $plugin);

                    if ($plugin_id === '') {
                        continue;
                    }

                    $plugins[$plugin_id] = $this->normalizeStorePlugin($plugin_id, $plugin);
                }
            }

            $extras = installerHelper::getInstaller()->getExtras([self::APP_ID], 'plugins', [
                'local'            => true,
                'status'           => false,
                'installed'        => true,
                'translate_titles' => true,
            ]);

            if (!empty($extras[self::APP_ID]['plugins']) && is_array($extras[self::APP_ID]['plugins'])) {
                foreach ($extras[self::APP_ID]['plugins'] as $plugin_id => $plugin) {
                    if (!is_array($plugin)) {
                        continue;
                    }

                    $plugin_id = $this->normalizePluginId($plugin_id, $plugin);

                    if ($plugin_id === '') {
                        continue;
                    }

                    $plugins[$plugin_id] = array_replace(
                        ifset($plugins[$plugin_id], []),
                        $this->normalizeStorePlugin($plugin_id, $plugin)
                    );
                }
            }
        } catch (Exception $e) {
            waLog::log($e->getMessage(), 'apanel/plugins.log');
        }

        wa($old_app, true);

        return $plugins;
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

            $plugin_config_path = $plugins_path . '/' . $plugin_id . '/lib/config/plugin.php';

            if (!is_file($plugin_config_path)) {
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
                    'enabled'     => $this->isPluginEnabled($plugin_id),
                    'source'      => 'local',
                ];
            } catch (Exception $e) {
                continue;
            }
        }

        closedir($dir);

        return $plugins;
    }

    protected function normalizeStorePlugin($plugin_id, array $plugin)
    {
        $installed = !empty($plugin['installed']) || is_dir(wa()->getAppPath('plugins/' . $plugin_id, self::APP_ID));

        return [
            'id'          => $plugin_id,
            'name'        => $this->stringValue(ifset($plugin['name'], $plugin_id)),
            'description' => $this->stringValue(ifset($plugin['description'], ifset($plugin['summary'], ''))),
            'version'     => $this->stringValue(ifset($plugin['installed']['version'], ifset($plugin['version'], ''))),
            'vendor'      => $this->stringValue(ifset($plugin['vendor_name'], ifset($plugin['vendor'], ''))),
            'installed'   => $installed,
            'enabled'     => $installed ? $this->isPluginEnabled($plugin_id) : false,
            'commercial'  => !empty($plugin['commercial']),
            'purchased'   => !empty($plugin['purchased']),
            'applicable'  => array_key_exists('applicable', $plugin) ? !empty($plugin['applicable']) : true,
            'slug'        => $this->stringValue(ifset($plugin['slug'], self::APP_ID . '/plugins/' . $plugin_id)),
            'source'      => 'store',
        ];
    }

    protected function normalizePluginId($plugin_id, array $plugin)
    {
        if (!empty($plugin['id']) && is_scalar($plugin['id'])) {
            $plugin_id = (string) $plugin['id'];
        }

        $plugin_id = trim((string) $plugin_id);

        if (!preg_match('~^[a-z0-9_]+$~i', $plugin_id)) {
            return '';
        }

        return $plugin_id;
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
        $item['state_html'] = $this->getStateHtml($installed, $enabled, $item);
        $item['actions_html'] = $this->getPluginActionsHtml($plugin_id, $item);

        return $item;
    }

    protected function getStateHtml($installed, $enabled, array $item)
    {
        if ($installed && $enabled) {
            return '<span class="text-success">Установлен и включён</span>';
        }

        if ($installed) {
            return '<span class="text-muted">Установлен, выключен</span>';
        }

        if (!empty($item['commercial']) && empty($item['purchased'])) {
            return '<span class="text-warning">Доступен к покупке</span>';
        }

        return '<span class="text-primary">Доступен к установке</span>';
    }

    protected function getPluginActionsHtml($plugin_id, array $item)
    {
        $app_url = wa()->getAppUrl(self::APP_ID) . 'settings/plugins/';
        $installer_plugin_url = wa()->getConfig()->getBackendUrl(true) . 'installer/store/plugin/' . self::APP_ID . '/' . rawurlencode($plugin_id) . '/';

        $installed = !empty($item['installed']);
        $enabled = !empty($item['enabled']);

        $html = '<div class="d-inline-flex gap-1 justify-content-end align-items-center">';

        if ($installed) {
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

            $html .= '<a href="' . htmlspecialchars($installer_plugin_url, ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary btn-sm">';
            $html .= htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function isPluginEnabled($plugin_id)
    {
        $enabled = $this->getEnabledPlugins();

        return isset($enabled[$plugin_id]);
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

    protected function csrfHtml()
    {
        $token = waRequest::cookie('_csrf', '', waRequest::TYPE_STRING_TRIM);

        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    protected function stringValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return (string) $value;
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}

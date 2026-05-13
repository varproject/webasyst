<?php

class apanelPluginsListController extends waViewController
{
    public function execute()
    {
        $this->checkRights();

        $this->setLayout(new apanelBackendLayout());

        // Модуль plugins должен открываться без левого sidebar.
        $this->layout->assign('sidebar_enabled', false);

        // Внутреннее дерево слева тоже не нужно.
        $this->layout->assign('main_body_tree_enabled', false);
        // $this->layout->assign('main_body_table_enabled', true);

        $this->prepareModal();

        if (!wa('apanel')->getConfig()->getInfo('plugins')) {
            waRequest::setParam('message', 'Плагины отключены в конфигурации приложения.');
        }

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
                'icon_html' => [
                    'title'   => '',
                    'type'    => 'html',
                    'thclass' => 'width-2',
                    'tdclass' => 'text-center',
                ],

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
                    'title'   => 'Vendor',
                    'thclass' => 'width-8',
                ],

                'enabled' => [
                    'title'   => 'Статус',
                    'type'    => 'bool',
                    'thclass' => 'width-8',
                ],

                'actions_html' => [
                    'title'   => 'Действия',
                    'type'    => 'html',
                    'thclass' => 'width-15 text-end',
                    'tdclass' => 'text-end',
                ],
            ],

            'show_checkbox' => true,
            'show_actions'  => false,
            'empty_text'    => 'Плагины не найдены.',
        ]), 'main_body_table_items');
    }

    protected function getPluginsList()
    {
        $plugins = [];

        $plugins_path = wa()->getAppPath('plugins', 'apanel');

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
                $plugin = wa('apanel')->getPlugin($plugin_id);

                if (!$plugin) {
                    continue;
                }

                $info = $plugin->getInfo();
                $enabled = $this->isPluginEnabled($plugin_id);

                $plugins[$plugin_id] = [
                    'id'           => $plugin_id,
                    'name'         => $plugin->getName(),
                    'description'  => (string) ifset($info['description'], ''),
                    'version'      => $plugin->getVersion(),
                    'vendor'       => (string) ifset($info['vendor'], ''),
                    'enabled'      => $enabled,
                    'icon_html'    => $this->getPluginIconHtml($plugin, $info),
                    'actions_html' => $this->getPluginActionsHtml($plugin_id, $enabled),
                ];
            } catch (Exception $e) {
                continue;
            }
        }

        closedir($dir);

        return $plugins;
    }

    protected function isPluginEnabled($plugin_id)
    {
        $enabled = $this->getEnabledPlugins();

        return isset($enabled[$plugin_id]);
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

    protected function getPluginIconHtml(waPlugin $plugin, array $info)
    {
        $icon_url = '';

        if (!empty($info['icons'][48])) {
            $icon_url = rtrim($plugin->getPluginStaticUrl(), '/') . '/' . ltrim($info['icons'][48], '/');
        } elseif (!empty($info['img'])) {
            $icon_url = rtrim($plugin->getPluginStaticUrl(), '/') . '/' . ltrim($info['img'], '/');
        }

        if ($icon_url !== '') {
            return '<img src="' . htmlspecialchars($icon_url, ENT_QUOTES, 'UTF-8') . '" alt="" style="width:32px;height:32px;object-fit:contain;">';
        }

        return apanelGetIcon::svg('apanel-icon-settings-outline');
    }

    protected function getPluginActionsHtml($plugin_id, $enabled)
    {
        $app_url = wa()->getAppUrl('apanel') . 'settings/plugins/';
        $plugin_id_escaped = htmlspecialchars($plugin_id, ENT_QUOTES, 'UTF-8');

        $html = '<div class="d-inline-flex gap-1 justify-content-end align-items-center">';

        if ($enabled) {
            $html .= '<form method="post" action="' . htmlspecialchars($app_url . $plugin_id . '/disable/', ENT_QUOTES, 'UTF-8') . '" class="m-0">';
            $html .= $this->csrfHtml();
            $html .= apanelUi::getControl('button', 'plugin_disable_' . $plugin_id, [
                'label' => 'Выключить',
                'class' => 'btn btn-outline-warning btn-sm',
                'type'  => 'submit',
                // 'title' => 'Выключить плагин',
            ]);
            $html .= '</form>';
        } else {
            $html .= '<form method="post" action="' . htmlspecialchars($app_url . $plugin_id . '/enable/', ENT_QUOTES, 'UTF-8') . '" class="m-0">';
            $html .= $this->csrfHtml();
            $html .= apanelUi::getControl('button', 'plugin_enable_' . $plugin_id, [
                'label' => 'Включить',
                'class' => 'btn btn-outline-success btn-sm',
                'type'  => 'submit',
                // 'title' => 'Включить плагин',
            ]);
            $html .= '</form>';
        }

        $html .= '<a href="' . htmlspecialchars($app_url . '?modal=plugin-remove&id=' . rawurlencode($plugin_id), ENT_QUOTES, 'UTF-8') . '" class="btn btn-outline-danger btn-sm" hx-boost="true" title="Удалить плагин">';
        $html .= 'Удалить';
        $html .= '</a>';

        $html .= '</div>';

        return $html;
    }

    protected function csrfHtml()
    {
        $helper = wa()->getView()->getHelper();

        if (method_exists($helper, 'csrf')) {
            return $helper->csrf();
        }

        $token = waRequest::cookie('_csrf', '', waRequest::TYPE_STRING_TRIM);

        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}

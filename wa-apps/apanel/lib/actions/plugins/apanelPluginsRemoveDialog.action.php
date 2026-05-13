<?php

class apanelPluginsRemoveDialogAction extends waViewAction
{
    const APP_ID = 'apanel';

    public function execute()
    {
        $this->checkRights();

        $this->setTemplate('plugins/PluginsRemoveDialog.html', true);

        $plugin_id = waRequest::get('id', '', waRequest::TYPE_STRING_TRIM);

        if (!$plugin_id || !preg_match('~^[a-z0-9_]+$~i', $plugin_id)) {
            throw new waException('Invalid plugin ID', 400);
        }

        try {
            $plugin = wa(self::APP_ID)->getPlugin($plugin_id);
        } catch (Exception $e) {
            throw new waException('Plugin not found', 404);
        }

        if (!$plugin) {
            throw new waException('Plugin not found', 404);
        }

        $info = $plugin->getInfo();

        $description = '';

        if (isset($info['description']) && !is_array($info['description']) && !is_object($info['description'])) {
            $description = (string) $info['description'];
        }

        $this->view->assign([
            'plugin_id'          => $plugin_id,
            'plugin_name'        => $plugin->getName(),
            'plugin_description' => $description,
            'modal_title'        => 'Удаление плагина',
            'modal_size'         => 'modal-md',
            'post_action_url'    => wa()->getAppUrl(self::APP_ID) . 'settings/plugins/' . $plugin_id . '/remove/',
            'close_button_url'   => wa()->getAppUrl(self::APP_ID) . 'settings/plugins/',
        ]);
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}

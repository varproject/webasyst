<?php

class apanelPluginsRemoveDialogAction extends waViewAction
{
    public function execute()
    {
        $this->checkRights();

        $this->setTemplate('plugins/PluginsRemoveDialog.html', true);

        $plugin_id = waRequest::get('id', '', waRequest::TYPE_STRING_TRIM);

        if (!$plugin_id || !preg_match('~^[a-z0-9_]+$~i', $plugin_id)) {
            throw new waException('Invalid plugin ID', 400);
        }

        try {
            $plugin = wa('apanel')->getPlugin($plugin_id);
        } catch (Exception $e) {
            throw new waException('Plugin not found', 404);
        }

        if (!$plugin) {
            throw new waException('Plugin not found', 404);
        }

        $this->view->assign([
            'plugin_id'          => $plugin_id,
            'plugin_name'        => $plugin->getName(),
            'plugin_description' => (string) ifset($plugin->getInfo('description'), ''),
            'modal_title'        => 'Удаление плагина',
            'modal_size'         => 'modal-md',
            'post_action_url'    => wa()->getAppUrl('apanel') . 'settings/plugins/' . $plugin_id . '/remove/',
            'close_button_url'   => wa()->getAppUrl('apanel') . 'settings/plugins/',
            'remove_button_html' => apanelUi::getControl('button', 'plugin_remove_confirm', [
                'label' => 'Удалить',
                'class' => 'btn btn-danger btn-sm',
                'type'  => 'submit',
            ]),
        ]);
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}

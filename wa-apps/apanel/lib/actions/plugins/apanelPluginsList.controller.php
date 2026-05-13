<?php

class apanelPluginsListController extends waViewController
{
    public function execute()
    {
        $this->checkRights();

        $this->setLayout(new apanelBackendLayout());

        $this->layout->assign('sidebar_enabled', false);
        $this->layout->assign('main_body_tree_enabled', false);
        $this->layout->assign('main_body_table_enabled', true);

        $this->prepareModal();
        $this->prepareTable($this->getCatalog()->getItems());
    }

    protected function prepareModal()
    {
        $modal = waRequest::get('modal', '', waRequest::TYPE_STRING_TRIM);

        if ($modal === 'plugin-remove') {
            $this->layout->assign('backend_modal_page', true);
            $this->executeAction(new apanelPluginsRemoveDialogAction(), 'backend_modal_page');
            return;
        }

        if ($modal === 'plugin-settings') {
            $this->layout->assign('backend_modal_page', true);
            $this->executeAction(new apanelPluginsSettingsDialogAction(), 'backend_modal_page');
            return;
        }
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
                    'thclass' => 'width-12',
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

    protected function getCatalog()
    {
        return new apanelPluginCatalog();
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}

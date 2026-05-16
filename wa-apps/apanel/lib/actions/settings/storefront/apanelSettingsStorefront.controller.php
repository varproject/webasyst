<?php

class apanelSettingsStorefrontController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new apanelBackendLayout());
        $this->layout->assign('main_body_tree_enabled', false);

        $this->prepareModal();

        $domain_hash = apanelUrlSegment::get(3);
        $service = new apanelStorefrontSettingsService();
        $items = $service->getDomainStorefronts($domain_hash);

        $domains = $items['domains'] ?? [];
        $routes = reset($items['routes']) ?: [];

        if (empty($domains) || empty($routes)) {
            return waRequest::setParam(
                'message',
                'Не настроен Routing, перейдите в <a href="' . wa_backend_url() . 'site/?list">приложение «Сайт»</a>, чтобы настроить адрес поселения для приложения Apanel.'
            );
        }

        if ($domain_hash !== $items['active_domain_hash']) {
            return apanelRedirect::redirectBack(
                wa()->getAppUrl('apanel') . 'settings/storefront/' . $items['active_domain_hash'] . '/'
            );
        }

        apanelNavigation::insertNode('settings.storefront', $domains);

        $this->prepareTable($routes);

        ////////////////////////////////////

        // dd(apanelSettings::get('ui'));
        $resolver = new apanelStorefrontResolver();

    }

    // Подготовить модальное окно страницы.
    protected function prepareModal()
    {
        $modal = waRequest::get('modal', '', waRequest::TYPE_STRING_TRIM);

        if ($modal !== 'storefront') {
            return;
        }

        $this->layout->assign('backend_modal_page', true);
        $this->executeAction(new apanelSettingsStorefrontDialogAction(), 'backend_modal_page');
    }


    public function prepareTable(array $routes)
    {
        $app_url = '/' . wa()->getRouting()->getCurrentUrl();

        $this->executeAction(new apanelTableAction([
            'items' => $routes,

            'columns' => [
                'route_id' => [
                    'title'   => 'ID',
                    'thclass' => 'width-2',
                ],

                '_name' => [
                    'title'       => 'Название',
                    'type'        => 'title',
                    'thclass'     => 'width-12',
                    'url_pattern' => $app_url . '?modal=storefront&storefront_key=%id%&group=profile',
                ],

                'domain' => [
                    'title'   => 'Домен',
                    'thclass' => 'width-10',
                ],

                'url' => [
                    'title'   => 'Адрес поселения',
                    'thclass' => 'width-10',
                ],

                'full_url' => [
                    'title'   => 'Ссылка на витрину',
                    'thclass' => 'width-15',
                    'type'    => 'href',
                ],

                'plugin_label' => [
                    'title'       => 'Плагин',
                    'type'        => 'title',
                    'thclass'     => 'width-10',
                    'url_pattern' => $app_url . '?modal=storefront&storefront_key=%id%&group=plugin',
                ],

                'screens_label' => [
                    'title'       => 'Экраны',
                    'type'        => 'title',
                    'thclass'     => 'width-10',
                    'url_pattern' => $app_url . '?modal=storefront&storefront_key=%id%&group=screens',
                ],

                'access_label' => [
                    'title'       => 'Доступы',
                    'type'        => 'title',
                    'thclass'     => 'width-10',
                    'url_pattern' => $app_url . '?modal=storefront&storefront_key=%id%&group=access',
                ],

                'auth_label' => [
                    'title'       => 'Авторизация',
                    'type'        => 'title',
                    'thclass'     => 'width-10',
                    'url_pattern' => $app_url . '?modal=storefront&storefront_key=%id%&group=auth',
                ],
                'status' => [
                    'title'   => 'Статус',
                    'thclass' => 'width-8',
                    'type'    => 'bool',
                ],
            ],

            'show_checkbox'      => true,
            'show_actions'       => true,
            'action_url_pattern' => $app_url . '?modal=storefront&storefront_key=%id%&group=plugin',
            'action_item_key'    => 'storefront_key',
            'empty_text'         => 'Для выбранного домена нет поселений приложения Apanel.',
        ]), 'main_body_table_items');
    }
}

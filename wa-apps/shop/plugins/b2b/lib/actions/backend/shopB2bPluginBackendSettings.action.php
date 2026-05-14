<?php

class shopB2bPluginBackendSettingsAction extends waViewAction
{
    /**
     * Внутренняя backend-страница плагина.
     *
     * Здесь можно показывать:
     * - список B2B-каналов;
     * - привязанные поселения;
     * - компании;
     * - пользователей компаний;
     * - правила доступа.
     */
    public function execute()
    {
        if (!wa()->getUser()->isAdmin('shop')) {
            throw new waRightsException(_w('Access denied'));
        }

        $this->setLayout(new shopBackendLayout());

        $channel_model = new shopSalesChannelModel();
        $params_model = new shopSalesChannelParamsModel();

        $channels = $channel_model->getByField('type', 'b2b', true);

        foreach ($channels as &$channel) {
            $channel['params'] = $params_model->get((int) $channel['id']);
            $channel['sales_channel_id'] = 'b2b:' . $channel['id'];
        }
        unset($channel);

        $this->view->assign([
            'channels' => $channels,
            'new_channel_url' => wa('shop')->getAppUrl(null, true) . 'channels/new/b2b/',
        ]);
    }
}

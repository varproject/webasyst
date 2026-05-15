<?php

class shopB2bPluginFrontendAction extends waViewAction
{
    // Frontend-страница B2B-витрины.
    public function execute()
    {
        $channel_id = waRequest::param('b2b_channel_id', 0, waRequest::TYPE_INT);

        if ($channel_id <= 0) {
            throw new waException('B2B-канал не найден.', 404);
        }

        $channel_model  = new shopSalesChannelModel();
        $params_model   = new shopSalesChannelParamsModel();
        $access_service = new shopB2bPluginCustomerAccessService();
        $channel        = $channel_model->getById($channel_id);

        if (!$channel || $channel['type'] !== 'b2b' || empty($channel['status'])) {
            throw new waException('B2B-канал не найден или выключен.', 404);
        }

        $channel['params'] = $params_model->get($channel_id);
        $has_access        = $access_service->canAccess(wa()->getUser()->getId(), $channel['params']);

        if (!$has_access) {
            $this->showAccessDenied($channel);
            return;
        }

        // Ставим после has_access, чтобы игнорировать лояут в блоках
        $this->setLayout(new shopB2bPluginFrontendLayout());

        $this->view->assign([
            'channel'       => $channel,
            'sales_channel' => 'b2b:' . $channel_id,
        ]);
    }

    // Показывает страницу ограничения доступа.
    protected function showAccessDenied(array $channel)
    {
        $block_id = trim((string) ifset($channel, 'params', 'access_denied_block_id', ''));

        $this->setTemplate(wa()->getAppPath('plugins/b2b/templates/actions/frontend/FrontendAccessDenied.html', 'shop'));

        $this->view->assign([
            'channel'                    => $channel,
            'access_denied_block_id'     => $block_id,
            'access_denied_block_params' => [
                'channel'      => $channel,
                'params'       => $channel['params'],
                'contact'      => wa()->getUser(),
                'login_url'    => wa()->getRouteUrl('shop/frontend/my'),
                'register_url' => wa()->getRouteUrl('shop/frontend/signup'),
            ],
        ]);
    }
}

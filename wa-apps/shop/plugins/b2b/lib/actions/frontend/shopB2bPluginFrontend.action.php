<?php

class shopB2bPluginFrontendAction extends waViewAction
{
    // Frontend-страница B2B-портала.
    public function execute()
    {
        $this->setLayout(new shopB2bPluginFrontendLayout());
        $channel_id = waRequest::param('b2b_channel_id', 0, waRequest::TYPE_INT);

        if ($channel_id <= 0) {
            throw new waException('B2B-канал не найден.', 404);
        }

        $channel_model = new shopSalesChannelModel();
        $params_model = new shopSalesChannelParamsModel();

        $channel = $channel_model->getById($channel_id);

        if (!$channel || $channel['type'] !== 'b2b' || empty($channel['status'])) {
            throw new waException('B2B-канал не найден или выключен.', 404);
        }

        $channel['params'] = $params_model->get($channel_id);

        $this->view->assign([
            'channel' => $channel,
            'sales_channel' => 'b2b:' . $channel_id,
        ]);
    }
}

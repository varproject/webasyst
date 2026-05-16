<?php

abstract class shopB2bPluginFrontendBaseAction extends waViewAction
{
    protected array $channel = array();
    protected array $access = array();

    protected function prepareB2b(string $section = ''): void
    {
        $channel_id = waRequest::param('b2b_channel_id', 0, waRequest::TYPE_INT);
        if ($channel_id <= 0) {
            throw new waException('B2B-канал не найден.', 404);
        }

        $access_service = new shopB2bPluginAccessService();
        $this->channel = $access_service->getChannel($channel_id);
        $this->access = $access_service->resolve($channel_id, wa()->getUser()->getId());

        if (empty($this->access['allowed'])) {
            $this->denyB2bAccess();
            return;
        }

        $this->setLayout(new shopB2bPluginFrontendLayout());
        $this->view->assign(array(
            'channel' => $this->channel,
            'channel_id' => $channel_id,
            'b2b_section' => $section,
            'sales_channel' => 'b2b:' . $channel_id,
        ));
    }

    protected function denyB2bAccess(): void
    {
        if (!empty($this->access['requires_auth'])) {
            $this->redirect(wa()->getRouteUrl('shop/frontend/my'));
            return;
        }

        $params = ifset($this->channel, 'params', array());
        $behavior = ifset($this->access, 'behavior', 'page');

        if ($behavior === '404' || $behavior === 'ignore') {
            throw new waException('B2B-канал не найден.', 404);
        }

        if ($behavior === 'redirect') {
            $url = trim((string) ifset($params, 'b2b_access_denied_redirect_url', ''));
            if ($url !== '') {
                $this->redirect($url);
                return;
            }
        }

        $mode = ifset($params, 'b2b_access_denied_page_mode', 'plugin');
        $block_id = trim((string) ifset($params, 'b2b_access_denied_block_id', ''));
        if ($mode !== 'block' || !preg_match('/^[a-z0-9_.-]+$/i', $block_id)) {
            $mode = 'plugin';
            $block_id = '';
        }

        $this->setTemplate(wa()->getAppPath('plugins/b2b/templates/actions/frontend/FrontendAccessDenied.html', 'shop'));
        $this->view->assign(array(
            'channel' => $this->channel,
            'access_denied_page_mode' => $mode,
            'access_denied_block_id' => $block_id,
            'access_denied_reason' => ifset($this->access, 'reason', ''),
            'access_denied_block_params' => array(
                'channel' => $this->channel,
                'params' => $params,
                'contact' => wa()->getUser(),
                'login_url' => wa()->getRouteUrl('shop/frontend/my'),
                'register_url' => wa()->getRouteUrl('shop/frontend/my'),
            ),
        ));
    }
}

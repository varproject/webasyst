<?php

class logsBackendHidePremiumPromoController extends waController
{
    public function execute()
    {
        $feature = waRequest::post('feature', '', waRequest::TYPE_STRING_TRIM);

        if (!in_array($feature, [
            'tracked-email-notifications',
            'bulk-delete',
            'search',
            'sort-mode',
        ])) {
            return;
        }

        $setting = $this->getUser()->getSettings('logs', 'hidden_premium_promos', '');

        try {
            $features = waUtils::jsonDecode($setting, true);
        } catch (Throwable $throwable) {
            $features = [];
        }

        $features[] = $feature;
        $this->getUser()->setSettings('logs', 'hidden_premium_promos', json_encode(array_unique($features)));
    }
}

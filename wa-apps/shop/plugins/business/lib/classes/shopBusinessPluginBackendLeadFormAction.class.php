<?php

abstract class shopBusinessPluginBackendLeadFormAction extends waViewAction
{
    public function execute()
    {
        if (shopBusinessPluginHelper::isDisabledWAID()) {
            $this->view->assign('disabled_waid', true);
            return;
        }

        if (empty($this->params['without_assets'])) {
            $this->view->assign('assets', shopBusinessPluginBackendSettingsAction::getAssets());
        }

        $this->view->assign([
            'is_closable' => !empty($this->params['closable']) || waRequest::get('closable') == 1,
            'is_collapsible' => !empty($this->params['collapsible']) || waRequest::get('collapsible') == 1,
        ]);
    }
}

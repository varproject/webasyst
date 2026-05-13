<?php

class devapiDefaultLayout extends waLayout
{
    public function execute()
    {
        if (waRequest::isMobile()) {
            $path = wa()->getAppPath('templates/layouts/Default.mobile.html');
            if (file_exists($path)) $this->template = $path;
        }

        $this->view->assign([
            'vueFile' => waSystemConfig::isDebug() ? 'vue.global.js' : 'vue.global.prod.js',
            'vueRouterFile' => waSystemConfig::isDebug() ? 'vue-router.global.js' : 'vue-router.global.prod.js',
            'actionButton' =>json_encode(devapiHelper::getVueComponent('actionButton'), 256),
            'backUsers' => json_encode(devapiHelper::getVueComponent('backUsers'), 256),
            'wrlDropdown' => json_encode(devapiHelper::getVueComponent('dropdown'), 256),
            'menuSettings' => json_encode(devapiHelper::getVueComponent('settings', 'menu'), 256),
            'menuSummary' => json_encode(devapiHelper::getVueComponent('summary', 'menu'), 256),
            'menuAccounts' => json_encode(devapiHelper::getVueComponent('accounts', 'menu'), 256),
            'menuReports' => json_encode(devapiHelper::getVueComponent('reports', 'menu'), 256),
            'appVersion' => wa()->getVersion('devapi')
        ]);
    }
}

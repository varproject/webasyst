<?php

class shopBusinessPluginBackendSettingsAction extends waViewAction
{
    public function execute()
    {
        if (shopBusinessPluginHelper::isDisabledWAID()) {
            $this->view->assign('disabled_waid', true);
            return;
        }

        $lead_forms = [];

        $bank_name = waRequest::get('only_form');
        if ($bank_name) {
            $need_form = self::getLeadFormTemplate($bank_name, ['product' => 1]);
            if ($need_form !== null) {
                $lead_forms = [strtolower($bank_name) => $need_form];
            }
        }

        if (!$lead_forms) {
            $lead_forms = [
                'tbank' => [
                    'html' => self::getLeadFormTemplate('TBank'),
                    'is_recommended' => true,
                ],
                'yookassa' => [
                    'html' => self::getLeadFormTemplate('YooKassa'),
                    'is_recommended' => true,
                ],
                'robokassa' => self::getLeadFormTemplate('Robokassa'),
                'alfabank' => self::getLeadFormTemplate('AlfaBank'),
                'modulbank' => self::getLeadFormTemplate('ModulBank'),
                'sberbank' => self::getLeadFormTemplate('SberBank'),
                'tochka' => self::getLeadFormTemplate('Tochka'),
            ];
        }

        $this->view->assign([
            'assets'     => self::getAssets(),
            'lead_forms' => $lead_forms,
            'img_path'   => wa()->getAppStaticUrl().'plugins/business/img',
        ]);
    }

    /**
     * @param string $bank_name TBank, YooKassa, SberBank, ModulBank
     * @param array $params default: ['without_assets' => true]
     * @return string|null
     */
    public static function getLeadFormTemplate($bank_name, $params = [])
    {
        $class_name = "shopBusinessPluginBackendLeadForm{$bank_name}Action";
        if (class_exists($class_name)) {
            if (empty($params['without_assets'])) {
                $params['without_assets'] = true;
            }

            /**
             * @var waViewAction
             */
            $instance = new $class_name($params);

            return $instance->display();
        }

        return null;
    }

    public static function getAssets()
    {
        $wa = wa('shop');
        $plugin_static_url = $wa->getAppStaticUrl().'plugins/business/';
        $version = $wa->getView()->getHelper()->version();

        $css = '<link rel="stylesheet" href="'.$plugin_static_url.'css/business.css?v='.$version.'">';
        $js = '<script type="text/javascript" src="'.$plugin_static_url.'js/business.js?v='.$version.'"></script>';

        return $css . $js;
    }
}

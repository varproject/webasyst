<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiMenuActions extends devapiJsonActions
{

    public function getSummaryAction()
    {
        $accounts = (new devapiAccountModel())->getAll();
        foreach ($accounts as &$account) {
            try {
                $acc = new devapiAccount($account['id']);
                $account['products'] = $acc->getProducts();
            } catch (Exception $e) {
                $account['products'] = false;
            }
        }
        unset($account);
        $account_id = $accounts ? $accounts[0]['id'] : 0;
        $account = new devapiAccount($account_id);
        $settings = new devapiSettings();
        $filters = [
            'limit' => $settings->limit,
            'offset' => 0,
            'list_type' => $settings->list_type,
            'transaction_type' => null,
            'products' => [],
            'period' => ['from' => null, 'to' => null]
        ];
        $options = [
            'transaction_types' => array_merge([['id' => '', 'name' => 'Все типы'], ['id' => 'without_order_payment', 'name' => 'Без учета покупок со счета']], devapiWebasystApi::WA_TRANSACTION_TYPES),
            'products' => $account_id ? self::prepareProducts($account->getProducts()) : [],
            'list_types' => devapiAccountSummary::TRUE_LIST_TYPES,
            'limits' => devapiAccountSummary::TRUE_LIMITS
        ];
        $this->response = [
            'accounts' => $accounts,
            'accountId' => $account_id,
            'filters' => $filters,
            'options' => $options,
            'settings' => $settings
        ];
    }

    public function getAccountsAction()
    {
        $this->response = [
            'accounts' => new devapiAccounts(true)
        ];
    }

    public function getReportsAction()
    {
        $accounts = (new devapiAccountModel())->getAll();
        foreach ($accounts as &$account) {
            try {
                $acc = new devapiAccount($account['id']);
                $account['products'] = $acc->getProducts();
                $account['product_types'] = $acc->getProductTypes();
            } catch (Exception $e) {
                $account['products'] = false;
            }
        }
        unset($account);
        $account_id = $accounts ? $accounts[0]['id'] : 0;
        $account = new devapiAccount($account_id);
        $options = [
            'transaction_types' => devapiWebasystApi::WA_TRANSACTION_TYPES,
            'products' => $account_id ? self::prepareProducts($account->getProducts()) : [],
            'period' => devapiAccountSummary::TRUE_LIST_TYPES,
            'types' => devapiAccountReports::REPORT_TYPES,
            'product_types' => devapiAccountReports::PRODUCT_TYPES
        ];
        $this->response = [
            'accounts' => $accounts,
            'options' => $options
        ];
    }

    public function getSettingsAction()
    {
        if ($isAdmin = wa()->getUser()->isAdmin()) {
            $appSettings = (new waAppSettingsModel())->get('devapi');
            $info = wa()->getAppInfo('devapi');
            if (!isset($appSettings['app_name'])) $appSettings['app_name'] = $info['name'];
            if (!isset($appSettings['app_icon'])) {
                $aIcon = explode('/', $info['icon'][48]);
                $appSettings['app_icon'] = array_pop($aIcon);
            }
            if (!isset($appSettings['helpdesk'])) $appSettings['helpdesk'] = '1';
        } else $appSettings = [];
        $periodicity = false;
        $path = wa()->getAppPath('lib/config/data/periodicity.php');
        $list_types = devapiAccountSummary::TRUE_LIST_TYPES;
        unset($list_types['period']);
        $icons = glob(wa()->getAppPath('img/*48.png'));
        foreach ($icons as &$icon) {
            $aIcon = explode('/', $icon);
            $icon = array_pop($aIcon);
        }
        unset($icon);
        if (file_exists($path)) $periodicity = include($path);
        $this->response = [
            'settings' => new devapiSettings(),
            'appSettings' => $appSettings,
            'accounts' => (new devapiAccountModel())->getAll(),
            'list_types' => $list_types,
            'periodicity' => $periodicity,
            'limits' => devapiAccountSummary::TRUE_LIMITS,
            'icons' => $icons,
            'isAdmin' => $isAdmin,
            'rootPath' => wa()->getConfig()->getRootPath()
        ];
    }

    public static function prepareProducts($products)
    {
        $items = [['id' => '', 'name' => 'Все продукты', 'slug' => 'temp']];
        foreach (devapiHelper::sortArray($products, 'name') as $key => $p) {
            if (!$p['published_version']) continue;
            else {
                $p['id'] = $p['slug'];
                $items[] = $p;
            }
        }
        return $items;
    }
}
<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiBackendActions extends devapiJsonActions
{

    public function updateTransactionsAction()
    {
        if (!$account_id = waRequest::post('account_id')) {
            $this->setError('Отсутствует обязательный параметр account_id');
            return;
        }
        try {
            $account = new devapiAccount($account_id);
            $account->updateTransactions(false);
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
            return;
        }
    }

    public function truncateTransactionsAction()
    {
        if (!$account_id = waRequest::post('account_id')) {
            $this->setError('Отсутствует обязательный параметр account_id');
            return;
        }
        $account = new devapiAccount($account_id);
        $account->truncateTransactions();
    }

    public function updateSummaryAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'filters']);
        if ($this->errors) return;
        try {
            $account = new devapiAccount($post['account_id']);
            $summary = $account->getSummary($post['filters']);
            $products = devapiMenuActions::prepareProducts($account->getProducts());
            $this->response = [
                'summary' => $summary,
                'products' => $products
            ];
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
        }
    }

    public function checkOrderAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'value']);
        if ($this->errors) return;
        try {
            $account = new devapiAccount($post['account_id']);
            $this->response = $account->checkLicense($post['value']);
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
            return;
        }
    }

    public function getOrderInfoDialogAction()
    {
        $account_id = waRequest::post('account_id');
        try {
            $accounts = (new devapiAccountModel())->getAll();
            foreach ($accounts as &$account) {
                try {
                    $acc = new devapiAccount($account['id']);
                    $account['products'] = $acc->getProducts();
                } catch (Exception $e) {
                    $account['products'] = [];
                }
            }
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
            return;
        }
        if (!$account_id && $accounts) $account_id = $accounts[0]['id'];
        $value = waRequest::post('value', '');
        $params = [
            'accounts' => json_encode($accounts),
            'accountId' => $account_id,
            'actionButton' => json_encode(devapiHelper::getVueComponent('actionButton'), 256),
            'value' => $value ? (string)$value : '""',
            'discounts' => devapiWebasystApi::DISCOUNT_TYPES
        ];
        $template = devapiHelper::getVueComponent('orderinfo', null, $params);
        $this->response = $template;
    }

    public function promocodesListAction()
    {
        $accounts = $products = [];
        if ($acc_id = waRequest::post('account_id')) $account_ids = [$acc_id];
        else {
            $accs = (new devapiAccountModel())->getAll();
            $account_ids = array_column($accs, 'id');
        }
        foreach ($account_ids as $account_id) {
            try {
                $acc = new devapiAccount($account_id);
                $account = json_decode(json_encode($acc), true);
                $account['products'] = $acc->getProducts();
                $account['promocodes'] = $acc->getPromocodes();
                $accounts[] = $account;
            } catch (Exception $e) {
            }
        }
        if ($accounts) $acc_id = $accounts[0]['id'];
        try {
            $params = [
                'accounts' => json_encode($accounts),
                'accountId' => $acc_id,
                'actionButton' => json_encode(devapiHelper::getVueComponent('actionButton'), 256)
            ];
            $promocodes = devapiHelper::getVueComponent('promocodes', null, $params);
            $this->response = $promocodes;
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
        }
    }

    public function productsListAction()
    {
        if (!$account_id = waRequest::post('account_id')) {
            throw new waException('Отсутствует обязательный параметр account_id');
        }
        try {
            $acc = new devapiAccount($account_id);
            $products = $acc->getProducts();
            $path = wa()->getAppPath('lib/config/data/extProductData.php');
            if (file_exists($path)) $extData = include($path);
            else $extData = false;
            $params = [
                'products' => json_encode($products),
                'extData' => json_encode($extData),
                'actionButton' => json_encode(devapiHelper::getVueComponent('actionButton'), 256),
                'isRemote' => json_encode($acc->isRemote())
            ];
            $accProducts = devapiHelper::getVueComponent('products', null, $params);
            $this->response = $accProducts;
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
        }
    }

    public function resellerAction()
    {
        if ($acc_id = waRequest::post('account_id')) $account_ids = [$acc_id];
        else {
            $accs = (new devapiAccountModel())->getAll();
            $account_ids = array_column($accs, 'id');
        }
        foreach ($account_ids as $account_id) {
            try {
                $acc = new devapiAccount($account_id);
                $resales = $acc->getResellers();
                break;
            } catch (Exception $e) {
            }
        }
        $params = [
            'resales' => json_encode($resales),
            'actionButton' => json_encode(devapiHelper::getVueComponent('actionButton'), 256)
        ];
        $reseller = devapiHelper::getVueComponent('reseller', null, $params);
        $this->response = $reseller;
    }

    public function createPromocodeAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'promo']);
        if ($this->errors) return;
        try {
            $account = new devapiAccount($post['account_id']);
            $data = $account->createPromocode($post['promo']);
            $this->response = $data;
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
        }
    }

    public function deletePromocodeAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'code']);
        if ($this->errors) return;
        try {
            $account = new devapiAccount($post['account_id']);
            $account->deletePromocode($post['code']);
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
        }
    }

    public function saveAccountAction()
    {
        $post = waRequest::post();
        $post['contact_id'] = wa()->getUser()->getId();
        if (!ifset($post['token'])) unset($post['token']);
        if (!$acc_id = ifset($post['id'])) unset($post['id']);
        try {
            $acc = new devapiAccount($acc_id ?: 0, $post);
            if (isset($post['token'])) {
                try {
                    $acc->getWaData('balance');
                } catch (waException $e) {
                    devapiHelper::setLog($e->getMessage());
                    throw new waException('Некорректный токен');
                }
            }
            $acc->save();
            $this->response = $acc;
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
    }

    public function deleteAccountAction()
    {
        if (!$account_id = waRequest::post('account_id')) {
            $this->setError('Отсутствует обязательный параметр account_id');
            return;
        }
        try {
            $account = new devapiAccount($account_id);
            (new devapiAccountModel())->deleteById($account_id);
            (new devapiTransactionModel())->deleteByField('account_id', $account_id);
            $model = new devapiAccountRemotesModel();
            $remote_ids = $model->select('id')->where('account_id=i:acc_id', ['acc_id' => $account_id])->fetchAll(null, true);
            if ($remote_ids) $model->deleteById($remote_ids);
            $client_ids = [];
            foreach ($remote_ids as $remote_id) {
                $client_ids[] = sprintf('devapi_%s_%s', $account_id, $remote_id);
            }
            if ($client_ids) (new waApiTokensModel())->deleteByField('client_id', $client_ids);
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
    }

    public function saveRemoteAction()
    {
        if (!$data = waRequest::post('remote')) {
            $this->setError('Отсутствует обязательный параметр remote');
            return;
        }
        try {
            $remote = new devapiRemote(ifset($data['id'], 0), ifset($data['account_id'], 0));
            $remote->save($data);
            $this->response = $remote;
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
    }

    public function checkRemoteInfoAction()
    {
        if (!$account_id = waRequest::post('account_id')) {
            $this->setError('Отсутствует обязательный параметр account_id');
            return;
        }
        try {
            $account = new devapiAccount($account_id);
            $data = $account->getWaData('balance');
            $view = wa()->getView();
            $template = wa()->getAppPath('templates/actions/backend/remoteInfo.html');
            foreach ($data as $key => $value) {
                $view->assign($key, $value);
            }
            $view->assign('remote_url', $account->remote_url);
            $this->response = $view->fetch($template);
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
    }

    public function deleteRemoteAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['remote_id', 'account_id']);
        if ($this->errors) return;
        try {
            $remote = new devapiRemote($post['remote_id'], $post['account_id']);
            $remote->delete();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    public function saveTargetAction()
    {
        $data = waRequest::post('target');
        $this->checkRequiredFields($data, ['name', 'url']);
        if ($this->errors) return;
        if ($data['id']) {
            try {
                $target = new devapiAccountCashTarget($data);
                $target->save($data);
            } catch (Exception $e) {
                $this->setError($e->getMessage());
                return;
            }
        } else {
            unset($data['id']);
            $model = new devapiAccountCashTargetsModel();
            if ($model->getByField('url', $data['url'])) {
                $this->setError('Подключение с указанным URL уже существует');
                return;
            }
            $hash_key = uniqid('devapi-cash-');
            wa()->getStorage()->set($hash_key, $data);

            $auth_params = [
                'redirect_uri' => wa()->getUrl(true) . '?action=oauthWa&hash=' . $hash_key,
                'client_id' => devapiHelper::getCashClientId($data['url']),
                'client_name' => sprintf(devapiAccountCashTarget::API_CLIENT_NAME, waRequest::server('http_host')),
                'response_type' => 'code',
                'scope' => 'cash'
            ];
            $target = [
                'hash' => $hash_key,
                'uri' => $data['url'] . '/auth?' . http_build_query($auth_params)
            ];
        }
        $this->response = $target;
    }

    public function preDeleteTargetAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'target_id']);
        if ($this->errors) return;
        $rules = (new devapiAccountCashRulesModel())->getByAccountId($post['account_id']);
        $this->response = array_column($rules, 'name');
    }

    public function deleteTargetAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'target_id']);
        if ($this->errors) return;
        try {
            $target = new devapiAccountCashTarget($post['target_id']);
            $target->delete();
            $account = new devapiAccount($post['account_id']);
            $this->response = $account->getCashRules();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    public function getTargetRuleOptionsAction()
    {
        if (!$target_id = waRequest::post('target_id')) {
            $this->setError('Отсутствует обязательный параметр target_id');
            return;
        }
        try {
            $target = new devapiAccountCashTarget($target_id);
            $options = $target->getOptions();
            $this->response = $options;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    public function saveCashRuleAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['name', 'cash_account']);
        if (!ifset($post['category_income']) && !ifset($post['category_expense'])) {
            $this->setError('Необходимо указать хотя бы одну категорию доходов или затрат');
        }
        if ($this->errors) return;
        $rule = new devapiAccountCashRule($post);
        $rule->save();
        $this->response = $rule;
    }

    public function deleteRuleAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'id']);
        if ($this->errors) return;
        $model = new devapiAccountCashRulesModel();
        $model->deleteByField($post);
    }

    public function sortRulesAction()
    {
        if (!$rules = waRequest::post('rules')) return;
        $model = new devapiAccountCashRulesModel();
        foreach ($rules as $sort => $rule_id) {
            $model->updateById($rule_id, ['sort' => $sort]);
        }
    }

    public function saveSettingsAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['settings']);
        foreach (['telegram', 'announcement', 'max'] as $field) {
            if (!$post['settings'][$field]['enabled']) {
                $post['settings'][$field] = ['enabled' => 0, 'type' => 'me', 'values' => [], 'token' => ''];
                continue;
            }
            if (!in_array($post['settings'][$field]['type'], ['me', 'all']) && !ifset($post['settings'][$field]['values'])) {
                $this->setError(sprintf('Не выбраны получатели уведомлений (%s)', $field));
            }
            if (in_array($field, ['telegram', 'max']) && !trim($post['settings'][$field]['token'])) {
                $this->setError(sprintf('Укажите токен бота %s', ucfirst($field)));
            }
        }
        if ($this->errors) return;
        try {
            $settings = new devapiSettings($post['settings']);
            $settings->save();
            if (wa()->getUser()->isAdmin()) {
                $appSettings = ifset($post['appSettings']);
                foreach (['app_name', 'app_icon', 'helpdesk'] as $field) {
                    $value = ifset($appSettings[$field]);
                    if (!$value && !wa_is_int($value)) continue;
                    (new waAppSettingsModel())->set('devapi', $field, $value);
                }
                foreach (glob(wa()->getConfig()->getPath('cache') . '/config/app*.php') as $file) {
                    waFiles::delete($file);
                }
            }
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
        }
    }

    public function getRemoteTokenAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'remote_id']);
        if ($this->errors) return;
        try {
            $remote = new devapiRemote($post['remote_id'], $post['account_id']);
            $this->response = $remote->getToken();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    public function getRequestHistoryAction()
    {
        if (!$client_id = waRequest::get('contact_id')) {
            $this->setError('тсутствует обязательный параметр client_id');
            return;
        }
        wa('helpdesk');
        $requests = (new helpdeskRequestsCollection([['name' => 'client', 'params' => [$client_id]]]))->getRequests();
        if ($requests) {
            $requests = helpdeskRequest::prepareRequests($requests);
        }
        wa('devapi', true);
        $view = wa()->getView();
        $template = wa()->getAppPath('templates/handlers/helpdesk/history.html');
        $view->assign(['requests' => $requests]);
        $this->response = $view->fetch($template);
    }

    public function generateChartAction()
    {
        $post = waRequest::post();
        $this->checkRequiredFields($post, ['account_id', 'params']);
        if ($this->errors) return;
        try {
            $report = new devapiAccountReports($post['account_id'], $post['params']);
            $this->response = $report;
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
        }
    }

    public function getCsvReportAction()
    {
        $file_path = wa()->getTempPath();
        $file_name = 'report_' . date('Y-m-d_H:i:s') . '.csv';
        $url = wa()->getRootUrl(true) . wa()->getConfig()->getBackendUrl() . '/devapi?action=downloadCsvReport&file=' . $file_name;
        $account_id = waRequest::post('account_id');
        $params = waRequest::post('params');
        $options = waRequest::post('options');
        $options['file'] = $file_name;
        $options['path'] = $file_path;
        try {
            $report = new devapiAccountReports($account_id, $params);
            $report->generateCSV($options);
            $this->response = [
                'url' => $url
            ];
        } catch (Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

    }

    public function getBackendUsersAction()
    {
        $groups = (new waGroupModel())->select('id, name')->where('type="group"')->order('name asc')->fetchAll();
        $collection = new waContactsCollection('/search/is_user=1');
        $users = $collection->getContacts('id,name,photo_url');
        $this->response = [
            'user_id' => wa()->getUser()->getId(),
            'groups' => $groups,
            'users' => array_values($users)
        ];
    }

    public function getUniqueIdAction()
    {
        $this->response = strtoupper(substr(uniqid(), -10));
    }
}
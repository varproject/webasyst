<?php

class cabinetStorefrontActions extends waViewActions
{
    public function preExecute()
    {
        $this->setLayout(new cabinetBackendLayout());
        $this->setTemplate(wa()->getAppPath('templates/actions/backend/main.html', 'cabinet'));
        waRequest::setParam('sidebar_mode', true);

        $app_routes         = wa()->getRouting()->getByApp('cabinet');
        $app_filter_domain  = waRequest::get('domain', null, waRequest::TYPE_STRING_TRIM);
        $app_filter_routes  = $this->flattenRoutingWithDomain($app_routes, $app_filter_domain);

        $this->view->assign([
            'title'             => 'Мои кабинеты',
            'app_routes'        => $app_routes,
            'app_filter_domain' => $app_filter_domain,
            'app_filter_routes' => $app_filter_routes,
        ]);
    }

    public function defaultAction() {}

    /**
     * Преобразует массив роутинга в плоский массив правил
     * с добавлением domain, domain_id и front_url.
     * Если передан $filter_domain — возвращает только правила этого домена.
     *
     * @param array       $arr
     * @param string|null $filter_domain
     * @return array
     */
    public function flattenRoutingWithDomain(array $arr, ?string $filter_domain = null): array
    {
        $result = [];

        foreach ($arr as $domain => $rules) {

            // фильтрация по домену
            if ($filter_domain !== null && $filter_domain !== $domain) {
                continue;
            }

            if (!is_array($rules)) {
                continue;
            }

            // получаем домены с полными данными: [id => ['name' => 'webasyst.loc', ...]]
            wa('site');
            $domains = siteHelper::getDomains(true);

            // определяем domain_id
            $domain_id = null;
            foreach ($domains as $id => $d) {
                if (isset($d['name']) && $d['name'] === $domain) {
                    $domain_id = $id;
                    break;
                }
            }

            // разворот внутреннего массива правил
            $rules = array_reverse($rules, true);

            foreach ($rules as $rule) {
                if (is_array($rule)) {

                    $rule['domain']     = $domain;
                    $rule['domain_id']  = $domain_id;

                    $rule['front_url'] = wa()->getRouting()->getUrl(
                        'cabinet/frontend/',
                        [],
                        true,
                        $domain,
                        $rule['url']
                    );

                    if (empty($rule['_name'])) {
                        $rule['_name'] = wa()->getConfig()
                            ->getAppConfig('cabinet')
                            ->getName();
                    }

                    $result[] = $rule;
                }
            }
        }

        return $result;
    }
}

<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiAccountReports extends devapiEntity
{
    const REPORT_TYPES = [
        'all_products' => 'По всем продуктам',
        'products' => 'По выбранным продуктам...',
        'product_types' => 'По типам продуктов...',
        'transaction_types' => 'По типам транзакций...'
    ];

    const PRODUCT_TYPES = [
        'app' => 'Приложения',
        'plugin' => 'Плагины',
        'shipping' => 'Плагины доставки',
        'payment' => 'Плагины оплаты',
        'sms' => 'SMS-плагины',
        'widget' => 'Виджеты',
        'theme' => 'Темы дизайна'
    ];
    const CHART_TYPES = ['pie', 'bar'];

    private devapiAccount $account;
    private string $period = 'month';
    private array $period_free = ['from' => '', 'to' => ''];
    private string $products = 'all';
    private string $transaction_types = 'all';
    private array $selected = ['transaction_types' => [], 'products' => [], 'product_types' => []];
    private array $slugs = [];
    private ?array $product_types = null;
    private array $query_periods = [];

    protected array $charts = [];

    public function __construct(int $account_id, array $params)
    {
        $this->account = new devapiAccount($account_id);
        foreach ($this->getProperties(ReflectionProperty::IS_PRIVATE) as $property) {
            if (isset($params[$property->name])) {
                $this->{$property->name} = $params[$property->name];
            }
        }
        $this->preparePeriod();
        $this->slugs = $this->getChartProducts();
    }

    private function getGeneralChartData()
    {
        $model = new devapiTransactionModel();
        $wheres = $params = [];
        $wheres[] = 'where account_id=i:account_id';
        $wheres[] = 'datetime between s:from and s:to';
        if ($this->slugs) $wheres[] = 'slug in (s:slugs)';
        switch ($this->transaction_types) {
            case 'plus':
                $transactions = devapiWebasystApi::WA_TRANSACTION_TYPES;
                foreach (['payout', 'cancel', 'buy'] as $field) {
                    unset($transactions[$field]);
                }
                $this->selected['transaction_types'] = array_keys($transactions);
            case 'selected':
                if ($this->selected['transaction_types']) {
                    $wheres[] = 'type in (s:transaction_types)';
                }
                break;
        }
        $where = implode(' and ', $wheres);
        $query = <<<SQL
select slug, count(*) as cnt, sum(amount) as amounts from devapi_transaction $where group by slug;
SQL;
        $params = [
            'account_id' => $this->account->getId(),
            'from' => $this->query_periods['summary']['from'],
            'to' => $this->query_periods['summary']['to'],
            'slugs' => $this->slugs,
            'transaction_types' => ifset($this->selected['transaction_types'], [])
        ];
        $summary = $model->query($query, $params)->fetchAll();
        $summary = $this->convertByProductTypes($summary);
        $periods = [];
        if ($this->query_periods['periods']) {
            $start = false;
            foreach ($this->query_periods['periods'] as $pkey => $period) {
                $params['from'] = $period['from'];
                $params['to'] = $period['to'];
                $items = $model->query($query, $params)->fetchAll();
                if (!$start && !$items) {
                    unset($this->query_periods['periods'][$pkey]);
                } else {
                    $periods[] = $this->convertByProductTypes($items);
                    $start = true;
                }
            }
        }
        return compact('summary', 'periods');
    }

    public function generateCSV(array $options)
    {
        $data = $this->getGeneralChartData();
        $this->prepareChart('bar', $data);
        $data = $this->charts['bar'][$options['period']][$options['variant']];
        $fields = array_merge([array_merge([''], $data->getLabels())], $data->getDataCSV());
        $csv = new devapiCsvWriter($options['path'] . '/' . $options['file']);
        foreach ($fields as $field) {
            $csv->write($field);
        }
        return true;
    }

    private function prepareChart($type, $data)
    {
        $products = $this->getChartProducts(false);
        foreach (['cnt', 'amounts'] as $chType) {
            $datasets = [];
            switch ($type) {
                case 'pie':
                    $labels = array_column($data['summary'], 'slug');
                    foreach ($labels as &$label) {
                        $label = ifset($products[$label]['name'], $label);
                    }
                    unset($label);
                    $dataset = [];
                    array_walk($data['summary'], function ($item) use (&$dataset, $products, $chType) {
                        $dataset[] = $item[$chType];
                    });
                    $datasets[] = ['label' => 'Сводные данные', 'data' => $dataset];
                    break;
                default:
                    $labels = ['Сводные данные'];
                    array_walk($data['summary'], function ($item) use (&$datasets, $products, $chType) {
                        $label = ifset($products[$item['slug']]['name'], $item['slug']);
                        $datasets[] = ['label' => $label, 'data' => [$item[$chType]]];
                    });
                    break;
            }
            $char = new devapiChartSimple(['type' => $type, 'labels' => $labels, 'datasets' => $datasets]);
            $this->charts[$type]['summary'][$chType] = $char;
        }

        if ($data['periods']) {
            if ($this->products === 'types' && $this->product_types === null) {
                $pTypes = [];
                foreach ($this->slugs as $slug) {
                    $pTypes[] = self::PRODUCT_TYPES[$products[$slug]['type']];
                }
                $this->product_types = array_values(array_unique($pTypes));
            }
            $labels = array_column($this->query_periods['periods'], 'label');
            $slugs = $this->products === 'types' ? $this->product_types : $this->slugs;
            foreach (['cnt', 'amounts'] as $chType) {
                $datasets = [];
                foreach ($slugs as $slug) {
                    $dataset = ['label' => ifset($products[$slug]['name'], $slug), 'data' => []];
                    foreach ($data['periods'] as $period) {
                        $idx = array_search($slug, array_column($period, 'slug'));
                        $dataset['data'][] = $idx === false ? 0 : $period[$idx][$chType];
                    }
                    $datasets[] = $dataset;
                }
                foreach ($datasets as $key => $dataset) {
                    if (!array_filter($dataset['data'], function ($val) {
                        return !!$val;
                    })) {
                        unset($datasets[$key]);
                    }
                }
                $char = new devapiChartSimple(['type' => $type, 'labels' => $labels, 'datasets' => $datasets]);
                $this->charts[$type]['periods'][$chType] = $char;
            }
        }

    }

    private function convertByProductTypes($items)
    {
        if ($this->products !== 'types') return $items;
        $products = $this->getChartProducts(false);
        $data = [];
        foreach ($items as $item) {
            $slug = ifset($products[$item['slug']]['type'], 'widget');
            $datum = [
                'slug' => self::PRODUCT_TYPES[$slug],
                'cnt' => ifset($data[$slug]['cnt'], 0) + $item['cnt'],
                'amounts' => ifset($data[$slug]['amounts'], 0) + $item['amounts']
            ];
            $data[$slug] = $datum;
        }
        return array_values($data);
    }

    protected function getChartProducts($slug_only = true, $with_price_only = false)
    {
        $products = [];
        foreach ($this->account->getProducts() as &$product) {
            if (
                !$product['published_version'] ||
                ($with_price_only && !$product['price'])
            ) continue;
            switch ($this->products) {
                case 'types':
                    $aSlug = explode('/', $product['slug']);
                    $ptype = count($aSlug) === 1 ? 'app' : rtrim($aSlug[1], 's');
                    $product['type'] = $ptype;
                    if (!in_array($ptype, $this->selected['product_types'])) continue 2;
                    break;
                case 'selected':
                    if (!in_array($product['slug'], $this->selected['products'])) continue 2;
                    break;
            }
            $products[$product['slug']] = $product;
        }
        unset($product);
        return $slug_only ? array_column($products, 'slug') : $products;
    }

    private function preparePeriod()
    {
        $to = new DateTime();
        $to->setTime(23, 59, 59);
        $from = new DateTime();
        $modifier = null;
        switch ($this->period) {
            case 'two_days':
                $modifier = '-1 day';
                break;
            case 'two_week':
                $from->modify('-7 day');
            case 'week':
                if (!in_array(date('l'), ['Monday', 'Понедельник'])) $modifier = 'previous monday';
                break;
            case 'two_month':
                $from->modify('-1 month');
            case 'month':
                $modifier = 'first day of ' . $from->format('M');
                break;
            case 'quartal':
                $fqMonth = (int)round($from->format('n') / 3, 0) * 3 - 2;
                if ($fqMonth < 10) $fqMonth = '0' . $fqMonth;
                $from = new DateTime($from->format('Y-') . $fqMonth . $from->format('-d'));
                $modifier = sprintf('first day of %s', $from->format('F Y'));
                break;
            case 'year':
                $modifier = 'first day of January ' . $from->format('Y');
                break;
            case 'all':
                if (!$min = (new devapiTransactionModel())->select('min(datetime) as "from"')->fetchField('from')) {
                    $min = '1970-01-01';
                }
                $from = new DateTime($min);
                break;
            case 'period':
                if ($this->period_free['from']) $from = new DateTime($this->period_free['from']);
                else $from = new DateTime('1970-01-01');
                if ($this->period_free['to']) {
                    $to = new DateTime($this->period_free['to']);
                    $to->modify('+1 day');
                }
                break;
        }
        if ($modifier) $from->modify($modifier);
        $from->setTime(0, 0, 0);
        if ($from === $to) $label = $from->format('j F Y');
        else $label = $from->format('Y-m-d') . ' - ' . $to->format('Y-m-d');
        $this->query_periods = [
            'summary' => [
                'label' => $label,
                'from' => $from->format('Y-m-d H:i:s'),
                'to' => $to->format('Y-m-d H:i:s')
            ]
        ];
        $periods = [];
        $interval = $from->diff($to)->format('%a');
        $formers = [
            'y' => ['v' => 365, 'f' => 'year'],
            'm' => ['v' => 31, 'f' => 'month'],
            'a' => ['v' => 1, 'f' => 'day']
        ];
        foreach ($formers as $former => $f) {
            $diff = $interval / $f['v'];
            if ($diff > 1) break;
        }
        if ($diff > 1) {
            if ($former === 'y') {
                $labelFormer = 'Y';
                $from->modify('first day of Jan');
                $diffFormer = 'first day of next year';
            } elseif ($former === 'm') {
                $from->modify('first day of this month');
                $labelFormer = 'M y';
                $diffFormer = 'first day of next month';
            } else {
                $labelFormer = 'j M';
                $diffFormer = 'next day';
            }
            for ($i = 0; $i <= $diff; $i++) {
                $diffDate = new DateTime($from->format('Y-m-d'));
                $diffDate->modify('+' . $i . ' ' . $formers[$former]['f']);
                $periods[] = [
                    'label' => $diffDate->format($labelFormer),
                    'from' => $diffDate->format('Y-m-d 00:00:00'),
                    'to' => $diffDate->modify($diffFormer)->format('Y-m-d 00:00:00')
                ];
            }
        }
        $this->query_periods['periods'] = $periods;
    }

    public function jsonSerialize(): array
    {
        $data = $this->getGeneralChartData();
        foreach (self::CHART_TYPES as $CHART_TYPE) {
            $this->prepareChart($CHART_TYPE, $data);
        }
        $this->charts['table'] = $this->charts['bar'];
        return parent::jsonSerialize();
    }
}
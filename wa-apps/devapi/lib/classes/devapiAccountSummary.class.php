<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiAccountSummary extends devapiEntity
{
    const
        TRUE_LIMITS = [
        20 => '20 записей',
        30 => '30 записей',
        50 => '50 записей',
        100 => '100 записей',
        250 => '250 записей',
        500 => '500 записей',
        0 => 'Все записи'
    ],
        TRUE_LIST_TYPES = [
        'today' => 'За сегодня',
        'two_days' => 'За вчера и сегодня',
        'week' => 'За текущую неделю',
        'two_week' => 'За текущую и предыдущую недели',
        'month' => 'За текущий месяц',
        'two_month' => 'За текущий и предыдущий месяцы',
        'quartal' => 'За текущий квартал',
        'year' => 'За текущий год',
        'all' => 'За все время',
        'period' => 'За период...'
    ];

    public int $offset = 0;
    public int $limit = 30;
    public array $transaction_types = [];
    public array $transaction_no_types = [];
    public array $products = [];
    public string $list_type = 'month';

    protected int $account_id;
    protected array $records = [];
    protected int $count = 0;
    protected float $sum = 0;
    protected array $compares = ['count' => 0, 'sum' => 0];
    protected array $period = ['from' => '', 'to' => ''];

    private devapiTransactionModel $model;

    public function __construct($account_id, $data = [])
    {
        $this->account_id = $account_id;
        $this->model = new devapiTransactionModel();
        foreach ($this->getProperties() as $property) {
            if (isset($data[$property->name])) {
                $this->{$property->name} = $data[$property->name];
            }
        }
        if (!isset(self::TRUE_LIST_TYPES[$this->list_type])) {
            throw new waException('Некорректный параметр list_type: ' . $this->list_type);
        }
        if (ifset($data['transaction_type'])) {
            if ($data['transaction_type'] === 'without_order_payment'){
                $this->transaction_no_types[] = devapiWebasystApi::TRANSACTION_TYPE_ORDER_PAYMENT;
            } else $this->transaction_types[] = $data['transaction_type'];
        }
    }

    public function prepareData()
    {
        $period = $this->getPeriodDates();
        $where = 'account_id=i:account_id';
        $wheres = ['datetime between s:from and s:to'];
        if ($this->transaction_types) $wheres[] = 'type in (s:transaction_types)';
        if ($this->transaction_no_types) $wheres[] = 'type not in (s:transaction_no_types)';
        if ($this->products) $wheres[] = 'slug in (s:slug)';
        $params = [
            'type' => $this->transaction_types,
            'slug' => $this->products,
            'transaction_types' => $this->transaction_types,
            'transaction_no_types' => $this->transaction_no_types,
            'account_id' => $this->account_id,
            'type_payout' => devapiWebasystApi::TRANSACTION_TYPE_PAYOUT,
            'from' => $period['from'],
            'to' => $period['to']
        ];
        if ($wheres) $where .= ' and ' . implode(' and ', $wheres);
        $query_count = <<<SQL
select count(*) as cnt from devapi_transaction where $where order by id desc
SQL;
        $query_sum = <<<SQL
select sum(amount) as summ from devapi_transaction where $where and type!=s:type_payout order by id desc
SQL;
        if ($this->count = $this->model->query($query_count, $params)->fetchField('cnt')) {
            $this->sum = round($this->model->query($query_sum, $params)->fetchField('summ'), 2);
            if (!$this->limit) $this->limit = $this->count;
            $query = <<<SQL
select * from devapi_transaction where $where order by datetime desc limit {$this->limit} offset {$this->offset}
SQL;
            $this->records = $this->model->query($query, $params)->fetchAll();
        }
        if (in_array($this->list_type, ['all', 'period'])) return;
        $params['from'] = $period['compare_from'];
        $params['to'] = $period['compare_to'];
        $this->compares['count'] = (float)$this->model->query($query_count, $params)->fetchField('cnt');
        $this->compares['sum'] = (float)$this->model->query($query_sum, $params)->fetchField('summ');
    }

    private function getPeriodDates($date_string = '')
    {
        $to = new DateTime($date_string);
        $to->setTime(23, 59, 59);
        $from = new DateTime($date_string);
        $modifier = null;
        switch ($this->list_type) {
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
                $fqMonth = $from->format('n') / 3;
                if (!wa_is_int($fqMonth)) $fqMonth = (int) ($fqMonth + 1);
                $fqMonth =  $fqMonth * 3 - 2;
                if ($fqMonth < 10) $fqMonth = '0' . $fqMonth;
                $from = new DateTime($from->format('Y-') . $fqMonth . $from->format('-d'));
                $modifier = sprintf('first day of %s', $from->format('F Y'));
                break;
            case 'year':
                $modifier = 'first day of January ' . $from->format('Y');
                break;
            case 'all':
                $from = new DateTime('1970-01-01');
                break;
            case 'period':
                if ($this->period['from']) $from = new DateTime($this->period['from']);
                else $from = new DateTime('1970-01-01');
                if ($this->period['to']) {
                    $to = new DateTime($this->period['to']);
                    $to->modify('+1 day');
                }
                break;
        }
        if ($modifier) $from->modify($modifier);
        $from->setTime(0, 0, 0);
        $compare_from = new DateTime($from->format('Y-m-d H:i:s'));
        if (!in_array($this->list_type, ['all', 'period'])) {
            $modifiers = [];
            $diff_days = 0;
            switch ($this->list_type) {
                case 'today':
                    $diff_days = 1;
                case 'two_days':
                    if (!$diff_days) $diff_days = 2;
                    $modifiers[] = sprintf('-%s day', $diff_days);
                    break;
                case 'two_week':
                    $modifiers[] = 'first day of last week';
                case 'week':
                    $modifiers[] = 'first day of last week';
                    break;
                case 'two_month':
                    $modifiers[] = 'first day of last month';
                case 'month':
                    $modifiers[] = 'first day of last month';
                    break;
                case 'quartal':
                    $fqMonth = (int)$fqMonth - 3;
                    if ($fqMonth < 0) $fqMonth = 10;
                    if ($fqMonth < 10) $fqMonth = '0' . $fqMonth;
                    $year = $fqMonth == 10 ? ($from->format('Y') - 1) : $from->format('Y');
                    $compare_from = new DateTime(sprintf('%s-%s-%s', $year, $fqMonth, '01'));
                    $compare_to = new DateTime(sprintf('%s-%s-%s', $year, $fqMonth + 2, '01'));
                    $compare_to->modify(sprintf('last day of %s', $compare_to->format('F Y')));
                    break;
                case 'year':
                    $modifiers[] = 'first day of last year';
                    $compare_to = new DateTime($to->format('Y') - 1 . '-' . $to->format('m-d'));
                    if ($compare_to->format('m') !== $to->format('m')) {
                        $compare_to->modify('last day of last month');
                    }
                    break;
            }
            if ($modifiers) {
                foreach ($modifiers as $modifier) {
                    $compare_from->modify($modifier);
                }
            }
            if (!isset($compare_to)) {
                $compare_to = (new DateTime($compare_from->format('Y-m-d')));
                if ($diff_days = $this->getDiffDates($from, $to)) {
                    $compare_to->modify(sprintf('+%s day', $diff_days));
                    if (strpos($this->list_type, 'month') !== false) {
                        if ($this->list_type === 'two_month' && $compare_from->format('d') !== $compare_to->format('d')) {
                            $compare_to = new DateTime($compare_to->format('Y-m-' . $compare_from->format('d')));
                        }
                        if ($from->format('m') === $compare_to->format('m')) {
                            $compare_to->modify('last day of last month');
                        }
                    }
                }
            }
        }
        if (!isset($compare_to)) $compare_to = new DateTime();
        $compare_from->setTime(0, 0, 0);
        $compare_to->setTime(23, 59, 59);
        return [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
            'compare_from' => $compare_from->format('Y-m-d H:i:s'),
            'compare_to' => $compare_to->format('Y-m-d H:i:s')
        ];
    }

    private function getDiffDates(DateTime $from, DateTime $to)
    {
        return (int)(strtotime($to->format('Y-m-d')) - strtotime($from->format('Y-m-d'))) / 86400;
    }


    public function getData()
    {
        return $this->jsonSerialize();
    }
}
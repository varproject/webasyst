<?php

class devapiAccountCashRule extends devapiEntity
{
    protected int $id = 0;
    protected int $account_id = 0;
    protected int $target_id = 0;
    protected string $name = '';
    protected int $sort = 0;

    protected int $break = 1;
    protected string $type = '';
    protected int $cash_account = 0;
    protected int $category_income = 0;
    protected int $category_expense = 0;
    protected string $transaction_type = '';
    protected string $product_slug = '';
    protected array $diff = ['type' => 'full', 'value' => 0, 'diff_type' => '%'];

    protected int $enable = 0;

    private devapiAccountCashRulesModel $model;

    public function __construct($data = [])
    {
        $this->model = new devapiAccountCashRulesModel();
        if (wa_is_int($data)) {
            $data = $this->model->getRuleById($data);
        }
        $this->setData($data);
    }

    public function checkTransaction($transaction)
    {
        if (!$this->enable) return true;
        $template = [
            'account_id' => null, // 2,
            'amount' => null, // '15655',
            'apply_to_all_in_future' => null, // false,
            'category_id' => null, // 2,
            'contractor' => null, // null,
            'contractor_contact' => null, // null,
            'contractor_contact_id' => null, // null,
            'date' => null, // '2022-12-03',
            'description' => null, // 'Прекрасные условия!',
            'id' => null, // null,
            'is_onbadge' => null, // false,
            'is_repeating' => null, // false,
            'is_self_destruct_when_due' => null, // false,
            'repeating_end_after' => null, // null,
            'repeating_end_ondate' => null, // null,
            'repeating_end_type' => null, // 'never',
            'repeating_frequency' => null, // 1,
            'repeating_interval' => null, // 'month',
            'transfer_account_id' => null, // null,
            'transfer_incoming_amount' => null, // '15655'
        ];
        $data = [
            'account_id' => $this->cash_account,
            'amount' => $transaction['amount'],
            'category_id' => $transaction['amount'] >= 0 ? $this->category_income : $this->category_expense,
            'date' => date('Y-m-d', strtotime($transaction['datetime'])),
            'description' => $transaction['comment'] . (isset($transaction['order_id']) ? (PHP_EOL . 'Заказ №' . $transaction['order_id']) : '')
        ];
        $transaction_slug = ifset($transaction['product']['slug']) ?: ifset($transaction['slug']);
        if (
            ($this->product_slug && $this->product_slug !== $transaction_slug) ||
            $this->transaction_type && $this->transaction_type !== $transaction['type']
        ) return true;

        if (!$data['category_id']) return true;
        if (!$target = (new devapiAccountCashTargetsModel())->getByTargetId($this->target_id)) return true;
        $socket = new devapiCashApi();
        $socket->setReceiver($target);
        $socket->addTransaction(array_merge($template, $data));
        return !$this->break;
    }

    public function save(array $data = [])
    {
        if ($data) $this->setData($data);
        $item = $this->prepareDbData();
        $id = $this->model->insert($item, 1);
        if (!$this->id && wa_is_int($id)) $this->id = $id;
    }

    private function prepareDbData()
    {
        $item = $this->model->getEmptyRow();
        if (!$this->id) {
            unset($item['id']);
            $this->sort = $this->model->getNextSort($this->account_id);
        }
        $fields = [];
        $data = $this->jsonSerialize();
        foreach ($this->getProperties() as $property) {
            $field = $property->name;
            if (!isset($data[$field])) continue;
            if (in_array($field, array_keys($item))) {
                $item[$field] = $data[$field];
            } else $fields[$field] = $data[$field];
        }
        $item['data'] = json_encode($fields, JSON_UNESCAPED_UNICODE);
        return $item;
    }

    private function setData(array $data)
    {
        foreach ($this->getProperties() as $property) {
            $field = $property->name;
            if (isset($data[$field])) $this->$field = $data[$field];
            elseif (isset($data['data'][$field])) $this->$field = $data['data'][$field];
        }
    }
}
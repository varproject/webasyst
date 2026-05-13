<?php

class cabinetOrderModel extends waModel
{
    use CabinetModelPrefixFieldsTrait;

    protected $table = 'cabinet_order';


    public function getListWithCounterparty(array $filters = []): array
    {
        $counterparty_model = new cabinetCounterpartyModel();
        $counterparty_table = $counterparty_model->getTableName();

        $sql = "
            SELECT
                o.*,
                " . $this->prefixFields('c', 'counterparty', $counterparty_model) . "
            FROM {$this->getTableName()} AS o
            JOIN {$counterparty_table} AS c
                ON c.id = o.counterparty_id
            WHERE 1
        ";

        $params = [];

        $sql .= " ORDER BY o.create_datetime DESC";

        return $this->query($sql, $params)->fetchAll();
    }
}

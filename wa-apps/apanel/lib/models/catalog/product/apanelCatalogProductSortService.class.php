<?php

/**
 * apanelCatalogProductSortService
 *
 * Назначение:
 * - сортировать товары внутри каталога.
 *
 * Зависимости:
 * - apanelCatalogModel;
 * - apanelCatalogProductModel.
 *
 * Инварианты:
 * - сортировка выполняется только в рамках одного каталога;
 * - список ids должен полностью совпадать с текущим набором товаров каталога;
 * - операция должна быть транзакционной.
 */
class apanelCatalogProductSortService
{
    /**
     * @var apanelCatalogModel
     */
    private $catalog_model;

    /**
     * @var apanelCatalogProductModel
     */
    private $catalog_product_model;

    public function __construct()
    {
        $this->catalog_model         = new apanelCatalogModel();
        $this->catalog_product_model = new apanelCatalogProductModel();
    }

    /**
     * @param int $catalog_id
     * @param array $product_ids
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function sort(int $catalog_id, array $product_ids): void
    {
        $catalog = $this->catalog_model->getById($catalog_id);

        if (!$catalog) {
            throw new waException('Каталог не найден.');
        }

        $product_ids = $this->normalizeIds($product_ids);
        $current_ids = $this->getCurrentProductIds($catalog_id);

        $this->assertSameIdSet($current_ids, $product_ids, 'Передан некорректный набор товаров каталога для сортировки.');

        $this->catalog_product_model->exec('START TRANSACTION');

        try {
            foreach ($product_ids as $index => $product_id) {
                $this->catalog_product_model->updateByField([
                    'product_id' => $product_id,
                    'catalog_id' => $catalog_id,
                ], [
                    'sort_order' => $index * 10,
                ]);
            }

            $this->catalog_product_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->catalog_product_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * @param int $catalog_id
     * @return array
     */
    private function getCurrentProductIds(int $catalog_id): array
    {
        $sql = "SELECT `product_id`
                FROM `apanel_catalog_product`
                WHERE `catalog_id` = i:catalog_id
                ORDER BY `sort_order` ASC, `product_id` ASC";

        $rows = $this->catalog_product_model->query($sql, [
            'catalog_id' => $catalog_id,
        ])->fetchAll();

        $result = [];

        foreach ($rows as $row) {
            $result[] = (int) $row['product_id'];
        }

        return $result;
    }

    /**
     * @param array $expected_ids
     * @param array $actual_ids
     * @param string $message
     * @return void
     * @throws waException
     */
    private function assertSameIdSet(array $expected_ids, array $actual_ids, string $message): void
    {
        $left  = $expected_ids;
        $right = $actual_ids;

        sort($left);
        sort($right);

        if ($left !== $right) {
            throw new waException($message);
        }
    }

    /**
     * @param array $ids
     * @return array
     */
    private function normalizeIds(array $ids): array
    {
        $result = [];

        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id <= 0) {
                continue;
            }

            $result[$id] = $id;
        }

        return array_values($result);
    }
}

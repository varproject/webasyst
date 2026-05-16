<?php

/**
 * apanelCategoryProductSortService
 *
 * Назначение:
 * - сортировать товары внутри конкретной категории каталога.
 *
 * Зависимости:
 * - apanelCategoryModel;
 * - apanelProductCategoriesModel.
 *
 * Инварианты:
 * - сортировка выполняется только в рамках одной категории одного каталога;
 * - category_id должен принадлежать указанному catalog_id;
 * - список ids должен полностью совпадать с текущим набором товаров категории;
 * - операция должна быть транзакционной.
 */
class apanelCategoryProductSortService
{
    /**
     * @var apanelCategoryModel
     */
    private $category_model;

    /**
     * @var apanelProductCategoriesModel
     */
    private $product_categories_model;

    public function __construct()
    {
        $this->category_model           = new apanelCategoryModel();
        $this->product_categories_model = new apanelProductCategoriesModel();
    }

    /**
     * @param int $catalog_id
     * @param int $category_id
     * @param array $product_ids
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function sort(int $catalog_id, int $category_id, array $product_ids): void
    {
        $category = $this->category_model->getByCatalogAndId($catalog_id, $category_id);

        if (!$category) {
            throw new waException('Категория не найдена в этом каталоге.');
        }

        $product_ids = $this->normalizeIds($product_ids);
        $current_ids = $this->getCurrentProductIds($catalog_id, $category_id);

        $this->assertSameIdSet($current_ids, $product_ids, 'Передан некорректный набор товаров категории для сортировки.');

        $this->product_categories_model->exec('START TRANSACTION');

        try {
            foreach ($product_ids as $index => $product_id) {
                $this->product_categories_model->updateByField([
                    'product_id'  => $product_id,
                    'catalog_id'  => $catalog_id,
                    'category_id' => $category_id,
                ], [
                    'sort_order' => $index * 10,
                ]);
            }

            $this->product_categories_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->product_categories_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * @param int $catalog_id
     * @param int $category_id
     * @return array
     */
    private function getCurrentProductIds(int $catalog_id, int $category_id): array
    {
        $sql = "SELECT `product_id`
                FROM `apanel_product_categories`
                WHERE `catalog_id` = i:catalog_id
                    AND `category_id` = i:category_id
                ORDER BY `sort_order` ASC, `product_id` ASC";

        $rows = $this->product_categories_model->query($sql, [
            'catalog_id'  => $catalog_id,
            'category_id' => $category_id,
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

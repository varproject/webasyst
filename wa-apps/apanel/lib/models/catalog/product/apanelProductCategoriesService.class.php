<?php

/**
 * apanelProductCategoriesService
 *
 * Назначение:
 * - заменять набор категорий товара внутри каталога;
 * - добавлять категории товару внутри каталога;
 * - снимать категории с товара внутри каталога.
 *
 * Зависимости:
 * - apanelCategoryModel;
 * - apanelCatalogProductModel;
 * - apanelProductCategoriesModel.
 *
 * Инварианты:
 * - связь товара с каталогом должна существовать заранее;
 * - категория должна принадлежать тому же каталогу;
 * - дубликаты категорий не допускаются;
 * - порядок категорий хранится в sort_order.
 */
class apanelProductCategoriesService
{
    /**
     * @var apanelCategoryModel
     */
    private $category_model;

    /**
     * @var apanelCatalogProductModel
     */
    private $catalog_product_model;

    /**
     * @var apanelProductCategoriesModel
     */
    private $product_categories_model;

    public function __construct()
    {
        $this->category_model           = new apanelCategoryModel();
        $this->catalog_product_model    = new apanelCatalogProductModel();
        $this->product_categories_model = new apanelProductCategoriesModel();
    }

    /**
     * Полностью заменяет список категорий товара внутри каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @param array $category_ids
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function replace(int $product_id, int $catalog_id, array $category_ids): void
    {
        $this->assertCatalogProductExists($product_id, $catalog_id);

        $category_ids = $this->normalizeCategoryIds($category_ids);
        $this->assertCategoryIdsBelongToCatalog($catalog_id, $category_ids);

        $this->product_categories_model->exec('START TRANSACTION');

        try {
            $this->product_categories_model->deleteByProductAndCatalog($product_id, $catalog_id);

            foreach ($category_ids as $index => $category_id) {
                $this->product_categories_model->insert([
                    'product_id'  => $product_id,
                    'catalog_id'  => $catalog_id,
                    'category_id' => $category_id,
                    'sort_order'  => $index * 10,
                ]);
            }

            $this->product_categories_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->product_categories_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Назначает товару дополнительные категории внутри каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @param array $category_ids
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function assign(int $product_id, int $catalog_id, array $category_ids): void
    {
        $this->assertCatalogProductExists($product_id, $catalog_id);

        $category_ids = $this->normalizeCategoryIds($category_ids);
        $this->assertCategoryIdsBelongToCatalog($catalog_id, $category_ids);

        if (!$category_ids) {
            return;
        }

        $current_ids = $this->product_categories_model->getCategoryIds($product_id, $catalog_id);
        $append_ids = [];

        foreach ($category_ids as $category_id) {
            if (!in_array($category_id, $current_ids, true)) {
                $append_ids[] = $category_id;
            }
        }

        if (!$append_ids) {
            return;
        }

        $this->product_categories_model->exec('START TRANSACTION');

        try {
            $offset = count($current_ids);

            foreach ($append_ids as $index => $category_id) {
                $this->product_categories_model->insert([
                    'product_id'  => $product_id,
                    'catalog_id'  => $catalog_id,
                    'category_id' => $category_id,
                    'sort_order'  => ($offset + $index) * 10,
                ]);
            }

            $this->product_categories_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->product_categories_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Снимает категории с товара внутри каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @param array $category_ids
     * @return void
     * @throws waException
     * @throws Exception
     */
    public function remove(int $product_id, int $catalog_id, array $category_ids): void
    {
        $this->assertCatalogProductExists($product_id, $catalog_id);

        $category_ids = $this->normalizeCategoryIds($category_ids);

        if (!$category_ids) {
            return;
        }

        $this->product_categories_model->exec('START TRANSACTION');

        try {
            foreach ($category_ids as $category_id) {
                $this->product_categories_model->deleteByField([
                    'product_id'  => $product_id,
                    'catalog_id'  => $catalog_id,
                    'category_id' => $category_id,
                ]);
            }

            $this->rebuildSortOrder($product_id, $catalog_id);

            $this->product_categories_model->exec('COMMIT');
        } catch (Exception $e) {
            $this->product_categories_model->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * @param int $product_id
     * @param int $catalog_id
     * @return void
     * @throws waException
     */
    private function assertCatalogProductExists(int $product_id, int $catalog_id): void
    {
        $catalog_product = $this->catalog_product_model->getByProductAndCatalog($product_id, $catalog_id);

        if (!$catalog_product) {
            throw new waException('Товар не привязан к каталогу.');
        }
    }

    /**
     * @param int $catalog_id
     * @param array $category_ids
     * @return void
     * @throws waException
     */
    private function assertCategoryIdsBelongToCatalog(int $catalog_id, array $category_ids): void
    {
        foreach ($category_ids as $category_id) {
            $category = $this->category_model->getByCatalogAndId($catalog_id, $category_id);

            if (!$category) {
                throw new waException('Передана категория чужого каталога или категория не существует.');
            }
        }
    }

    /**
     * @param int $product_id
     * @param int $catalog_id
     * @return void
     * @throws Exception
     */
    private function rebuildSortOrder(int $product_id, int $catalog_id): void
    {
        $category_ids = $this->product_categories_model->getCategoryIds($product_id, $catalog_id);

        foreach ($category_ids as $index => $category_id) {
            $this->product_categories_model->updateByField([
                'product_id'  => $product_id,
                'catalog_id'  => $catalog_id,
                'category_id' => $category_id,
            ], [
                'sort_order' => $index * 10,
            ]);
        }
    }

    /**
     * @param array $category_ids
     * @return array
     */
    private function normalizeCategoryIds(array $category_ids): array
    {
        $result = [];

        foreach ($category_ids as $category_id) {
            $category_id = (int) $category_id;

            if ($category_id <= 0) {
                continue;
            }

            $result[$category_id] = $category_id;
        }

        return array_values($result);
    }
}

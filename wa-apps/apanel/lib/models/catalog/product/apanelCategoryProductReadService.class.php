<?php

class apanelCategoryProductReadService
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
     * Возвращает список товаров категории.
     *
     * options:
     * - search
     * - is_active
     * - limit
     * - offset
     *
     * @param int $catalog_id
     * @param int $category_id
     * @param array $options
     * @return array
     * @throws waException
     */
    public function getCategoryProducts(int $catalog_id, int $category_id, array $options = []): array
    {
        $category = $this->category_model->getByCatalogAndId($catalog_id, $category_id);

        if (!$category) {
            // throw new waException('Категория не найдена в этом каталоге.');
            return [];
        }

        $search    = $this->normalizeSearch(array_key_exists('search', $options) ? $options['search'] : '');
        $is_active = $this->normalizeNullableBoolInt(array_key_exists('is_active', $options) ? $options['is_active'] : null);
        $limit     = $this->normalizeLimit(array_key_exists('limit', $options) ? $options['limit'] : 50);
        $offset    = $this->normalizeOffset(array_key_exists('offset', $options) ? $options['offset'] : 0);

        $where = [
            'pc.`catalog_id` = i:catalog_id',
            'pc.`category_id` = i:category_id',
        ];

        $params = [
            'catalog_id'  => $catalog_id,
            'category_id' => $category_id,
            'limit'       => $limit,
            'offset'      => $offset,
        ];

        if ($is_active !== null) {
            $where[] = 'cp.`is_active` = i:is_active';
            $params['is_active'] = $is_active;
        }

        if ($search !== '') {
            $search_like = '%' . $this->product_categories_model->escape($search, 'like') . '%';

            $where[] = "(
                cp.`name` LIKE s:search
                OR p.`code` LIKE s:search
                OR p.`article` LIKE s:search
            )";

            $params['search'] = $search_like;
        }

        $where_sql = implode(' AND ', $where);

        $count_sql = "
            SELECT COUNT(*)
            FROM `apanel_product_categories` pc
                INNER JOIN `apanel_catalog_product` cp
                    ON  cp.`product_id` = pc.`product_id`
                    AND cp.`catalog_id` = pc.`catalog_id`
                INNER JOIN `apanel_product` p
                    ON p.`id` = pc.`product_id`
            WHERE {$where_sql}
        ";

        $total = (int) $this->product_categories_model->query($count_sql, $params)->fetchField();

        $sql = "
            SELECT
                pc.`product_id`,
                pc.`catalog_id`,
                pc.`category_id`,
                pc.`sort_order` AS `category_sort_order`,
                cp.`name`,
                cp.`description`,
                cp.`is_active`,
                cp.`sort_order` AS `catalog_sort_order`,
                cp.`created_contact_id`,
                cp.`updated_contact_id`,
                cp.`created_datetime`,
                cp.`updated_datetime`,
                p.`code`,
                p.`article`
            FROM `apanel_product_categories` pc
                INNER JOIN `apanel_catalog_product` cp
                    ON  cp.`product_id` = pc.`product_id`
                    AND cp.`catalog_id` = pc.`catalog_id`
                INNER JOIN `apanel_product` p
                    ON p.`id` = pc.`product_id`
            WHERE {$where_sql}
            ORDER BY pc.`sort_order` ASC, pc.`product_id` ASC
            LIMIT i:offset, i:limit
        ";

        $items = $this->product_categories_model->query($sql, $params)->fetchAll();

        return [
            'category' => $category,
            'items'    => $items,
            'total'    => $total,
            'limit'    => $limit,
            'offset'   => $offset,
        ];
    }

    /**
     * Возвращает один товар категории.
     *
     * @param int $catalog_id
     * @param int $category_id
     * @param int $product_id
     * @return array|null
     * @throws waException
     */
    public function getCategoryProduct(int $catalog_id, int $category_id, int $product_id): ?array
    {
        $category = $this->category_model->getByCatalogAndId($catalog_id, $category_id);

        if (!$category) {
            throw new waException('Категория не найдена в этом каталоге.');
        }

        $sql = "
            SELECT
                pc.`product_id`,
                pc.`catalog_id`,
                pc.`category_id`,
                pc.`sort_order` AS `category_sort_order`,
                cp.`name`,
                cp.`description`,
                cp.`is_active`,
                cp.`sort_order` AS `catalog_sort_order`,
                cp.`created_contact_id`,
                cp.`updated_contact_id`,
                cp.`created_datetime`,
                cp.`updated_datetime`,
                p.`code`,
                p.`article`
            FROM `apanel_product_categories` pc
                INNER JOIN `apanel_catalog_product` cp
                    ON  cp.`product_id` = pc.`product_id`
                    AND cp.`catalog_id` = pc.`catalog_id`
                INNER JOIN `apanel_product` p
                    ON p.`id` = pc.`product_id`
            WHERE pc.`catalog_id` = i:catalog_id
                AND pc.`category_id` = i:category_id
                AND pc.`product_id` = i:product_id
            LIMIT 1
        ";

        $row = $this->product_categories_model->query($sql, [
            'catalog_id'  => $catalog_id,
            'category_id' => $category_id,
            'product_id'  => $product_id,
        ])->fetchAssoc();

        if (!$row) {
            return null;
        }

        $row['category'] = $category;

        return $row;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function normalizeSearch($value): string
    {
        return trim((string) $value);
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    private function normalizeNullableBoolInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return empty($value) ? 0 : 1;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function normalizeLimit($value): int
    {
        $value = (int) $value;

        if ($value <= 0) {
            return 50;
        }

        if ($value > 500) {
            return 500;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function normalizeOffset($value): int
    {
        $value = (int) $value;

        return $value > 0 ? $value : 0;
    }
}

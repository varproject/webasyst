<?php

class apanelCatalogProductReadService
{
    /**
     * @var apanelCatalogModel
     */
    private $catalog_model;

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
        $this->catalog_model            = new apanelCatalogModel();
        $this->catalog_product_model    = new apanelCatalogProductModel();
        $this->product_categories_model = new apanelProductCategoriesModel();
    }

    /**
     * Возвращает список товаров каталога.
     *
     * options:
     * - search
     * - is_active
     * - limit
     * - offset
     *
     * @param int $catalog_id
     * @param array $options
     * @return array
     * @throws waException
     */
    public function getCatalogProducts(int $catalog_id, array $options = []): array
    {
        $this->assertCatalogExists($catalog_id);

        $search    = $this->normalizeSearch(array_key_exists('search', $options) ? $options['search'] : '');
        $is_active = $this->normalizeNullableBoolInt(array_key_exists('is_active', $options) ? $options['is_active'] : null);
        $limit     = $this->normalizeLimit(array_key_exists('limit', $options) ? $options['limit'] : 50);
        $offset    = $this->normalizeOffset(array_key_exists('offset', $options) ? $options['offset'] : 0);

        $where = [
            'cp.`catalog_id` = i:catalog_id',
        ];

        $params = [
            'catalog_id' => $catalog_id,
            'limit'      => $limit,
            'offset'     => $offset,
        ];

        if ($is_active !== null) {
            $where[] = 'cp.`is_active` = i:is_active';
            $params['is_active'] = $is_active;
        }

        if ($search !== '') {
            $search_like = '%' . $this->catalog_product_model->escape($search, 'like') . '%';

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
            FROM `apanel_catalog_product` cp
                INNER JOIN `apanel_product` p
                    ON p.`id` = cp.`product_id`
            WHERE {$where_sql}
        ";

        $total = (int) $this->catalog_product_model->query($count_sql, $params)->fetchField();

        $sql = "
            SELECT
                cp.`product_id`,
                cp.`catalog_id`,
                cp.`name`,
                cp.`description`,
                cp.`is_active`,
                cp.`sort_order`,
                cp.`created_contact_id`,
                cp.`updated_contact_id`,
                cp.`created_datetime`,
                cp.`updated_datetime`,
                p.`code`,
                p.`article`,
                COUNT(pc.`category_id`) AS `categories_count`
            FROM `apanel_catalog_product` cp
                INNER JOIN `apanel_product` p
                    ON p.`id` = cp.`product_id`
                LEFT JOIN `apanel_product_categories` pc
                    ON  pc.`product_id` = cp.`product_id`
                    AND pc.`catalog_id` = cp.`catalog_id`
            WHERE {$where_sql}
            GROUP BY
                cp.`product_id`,
                cp.`catalog_id`,
                cp.`name`,
                cp.`description`,
                cp.`is_active`,
                cp.`sort_order`,
                cp.`created_contact_id`,
                cp.`updated_contact_id`,
                cp.`created_datetime`,
                cp.`updated_datetime`,
                p.`code`,
                p.`article`
            ORDER BY cp.`sort_order` ASC, cp.`product_id` ASC
            LIMIT i:offset, i:limit
        ";

        $items = $this->catalog_product_model->query($sql, $params)->fetchAll();

        return [
            'items'  => $items,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Возвращает товар в контексте каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @return array|null
     * @throws waException
     */
    public function getCatalogProduct(int $product_id, int $catalog_id): ?array
    {
        $this->assertCatalogExists($catalog_id);

        $sql = "
            SELECT
                cp.`product_id`,
                cp.`catalog_id`,
                cp.`name`,
                cp.`description`,
                cp.`is_active`,
                cp.`sort_order`,
                cp.`created_contact_id`,
                cp.`updated_contact_id`,
                cp.`created_datetime`,
                cp.`updated_datetime`,
                p.`code`,
                p.`article`
            FROM `apanel_catalog_product` cp
                INNER JOIN `apanel_product` p
                    ON p.`id` = cp.`product_id`
            WHERE cp.`product_id` = i:product_id
                AND cp.`catalog_id` = i:catalog_id
            LIMIT 1
        ";

        $row = $this->catalog_product_model->query($sql, [
            'product_id' => $product_id,
            'catalog_id' => $catalog_id,
        ])->fetchAssoc();

        if (!$row) {
            return null;
        }

        $row['category_ids']     = $this->product_categories_model->getCategoryIds($product_id, $catalog_id);
        $row['categories_count'] = count($row['category_ids']);

        return $row;
    }

    /**
     * Возвращает товары каталога, не назначенные ни в одну категорию.
     *
     * options:
     * - search
     * - is_active
     * - limit
     * - offset
     *
     * @param int $catalog_id
     * @param array $options
     * @return array
     * @throws waException
     */
    public function getWithoutCategory(int $catalog_id, array $options = []): array
    {
        $this->assertCatalogExists($catalog_id);

        $search    = $this->normalizeSearch(array_key_exists('search', $options) ? $options['search'] : '');
        $is_active = $this->normalizeNullableBoolInt(array_key_exists('is_active', $options) ? $options['is_active'] : null);
        $limit     = $this->normalizeLimit(array_key_exists('limit', $options) ? $options['limit'] : 50);
        $offset    = $this->normalizeOffset(array_key_exists('offset', $options) ? $options['offset'] : 0);

        $where = [
            'cp.`catalog_id` = i:catalog_id',
            'pc.`product_id` IS NULL',
        ];

        $params = [
            'catalog_id' => $catalog_id,
            'limit'      => $limit,
            'offset'     => $offset,
        ];

        if ($is_active !== null) {
            $where[] = 'cp.`is_active` = i:is_active';
            $params['is_active'] = $is_active;
        }

        if ($search !== '') {
            $search_like = '%' . $this->catalog_product_model->escape($search, 'like') . '%';

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
            FROM `apanel_catalog_product` cp
                INNER JOIN `apanel_product` p
                    ON p.`id` = cp.`product_id`
                LEFT JOIN `apanel_product_categories` pc
                    ON  pc.`product_id` = cp.`product_id`
                    AND pc.`catalog_id` = cp.`catalog_id`
            WHERE {$where_sql}
        ";

        $total = (int) $this->catalog_product_model->query($count_sql, $params)->fetchField();

        $sql = "
            SELECT
                cp.`product_id`,
                cp.`catalog_id`,
                cp.`name`,
                cp.`description`,
                cp.`is_active`,
                cp.`sort_order`,
                cp.`created_contact_id`,
                cp.`updated_contact_id`,
                cp.`created_datetime`,
                cp.`updated_datetime`,
                p.`code`,
                p.`article`,
                0 AS `categories_count`
            FROM `apanel_catalog_product` cp
                INNER JOIN `apanel_product` p
                    ON p.`id` = cp.`product_id`
                LEFT JOIN `apanel_product_categories` pc
                    ON  pc.`product_id` = cp.`product_id`
                    AND pc.`catalog_id` = cp.`catalog_id`
            WHERE {$where_sql}
            ORDER BY cp.`sort_order` ASC, cp.`product_id` ASC
            LIMIT i:offset, i:limit
        ";

        $items = $this->catalog_product_model->query($sql, $params)->fetchAll();

        return [
            'items'  => $items,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * @param int $catalog_id
     * @return void
     * @throws waException
     */
    private function assertCatalogExists(int $catalog_id): void
    {
        $catalog = $this->catalog_model->getById($catalog_id);

        if (!$catalog) {
            throw new waException('Каталог не найден.');
        }
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

<?php

/**
 * apanelProductReadService
 *
 * Назначение:
 * - читать глобальную базу товаров;
 * - возвращать список всех товаров;
 * - возвращать один товар по id;
 * - возвращать товары без привязки к каталогам.
 *
 * Зависимости:
 * - apanelProductModel;
 * - apanelCatalogProductModel.
 *
 * Инварианты:
 * - работает только с глобальной сущностью товара из apanel_product;
 * - не изменяет данные;
 * - не смешивает глобальный товар и локальный контекст товара в каталоге;
 * - количество каталогов считается по apanel_catalog_product.
 *
 * Побочные эффекты:
 * - отсутствуют.
 *
 * Ошибки:
 * - не выбрасывает исключение, если товар не найден, а возвращает null;
 * - ошибки SQL пробрасываются штатным механизмом waModel/waDbException.
 */
class apanelProductReadService
{
    /**
     * @var apanelProductModel
     */
    private $product_model;

    /**
     * @var apanelCatalogProductModel
     */
    private $catalog_product_model;

    public function __construct()
    {
        $this->product_model         = new apanelProductModel();
        $this->catalog_product_model = new apanelCatalogProductModel();
    }

    /**
     * Возвращает список глобальных товаров.
     *
     * Поддерживаемые options:
     * - search
     * - limit
     * - offset
     *
     * @param array $options
     * @return array
     */
    public function getList(array $options = []): array
    {
        $search = $this->normalizeSearch(array_key_exists('search', $options) ? $options['search'] : '');
        $limit  = $this->normalizeLimit(array_key_exists('limit', $options) ? $options['limit'] : 50);
        $offset = $this->normalizeOffset(array_key_exists('offset', $options) ? $options['offset'] : 0);

        $where = [
            '1 = 1',
        ];

        $params = [
            'limit'  => $limit,
            'offset' => $offset,
        ];

        if ($search !== '') {
            $search_like = '%' . $this->product_model->escape($search, 'like') . '%';

            $where[] = "(
                p.`code` LIKE s:search
                OR p.`article` LIKE s:search
            )";

            $params['search'] = $search_like;
        }

        $where_sql = implode(' AND ', $where);

        $count_sql = "
            SELECT COUNT(*)
            FROM `apanel_product` p
            WHERE {$where_sql}
        ";

        $total = (int) $this->product_model->query($count_sql, $params)->fetchField();

        $sql = "
            SELECT
                p.`id`,
                p.`code`,
                p.`article`,
                p.`created_contact_id`,
                p.`updated_contact_id`,
                p.`created_datetime`,
                p.`updated_datetime`,
                COUNT(cp.`catalog_id`) AS `catalogs_count`
            FROM `apanel_product` p
                LEFT JOIN `apanel_catalog_product` cp
                    ON cp.`product_id` = p.`id`
            WHERE {$where_sql}
            GROUP BY
                p.`id`,
                p.`code`,
                p.`article`,
                p.`created_contact_id`,
                p.`updated_contact_id`,
                p.`created_datetime`,
                p.`updated_datetime`
            ORDER BY p.`id` DESC
            LIMIT i:offset, i:limit
        ";

        $items = $this->product_model->query($sql, $params)->fetchAll();

        return [
            'items'  => $items,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Возвращает один глобальный товар по id.
     *
     * @param int $product_id
     * @return array|null
     */
    public function getById(int $product_id): ?array
    {
        $sql = "
            SELECT
                p.`id`,
                p.`code`,
                p.`article`,
                p.`created_contact_id`,
                p.`updated_contact_id`,
                p.`created_datetime`,
                p.`updated_datetime`
            FROM `apanel_product` p
            WHERE p.`id` = i:product_id
            LIMIT 1
        ";

        $row = $this->product_model->query($sql, [
            'product_id' => $product_id,
        ])->fetchAssoc();

        if (!$row) {
            return null;
        }

        $row['catalog_ids']     = $this->getCatalogIds($product_id);
        $row['catalogs_count']  = count($row['catalog_ids']);

        return $row;
    }

    /**
     * Возвращает список глобальных товаров без привязки к каталогам.
     *
     * Поддерживаемые options:
     * - search
     * - limit
     * - offset
     *
     * @param array $options
     * @return array
     */
    public function getWithoutCatalogs(array $options = []): array
    {
        $search = $this->normalizeSearch(array_key_exists('search', $options) ? $options['search'] : '');
        $limit  = $this->normalizeLimit(array_key_exists('limit', $options) ? $options['limit'] : 50);
        $offset = $this->normalizeOffset(array_key_exists('offset', $options) ? $options['offset'] : 0);

        $where = [
            'cp.`product_id` IS NULL',
        ];

        $params = [
            'limit'  => $limit,
            'offset' => $offset,
        ];

        if ($search !== '') {
            $search_like = '%' . $this->product_model->escape($search, 'like') . '%';

            $where[] = "(
                p.`code` LIKE s:search
                OR p.`article` LIKE s:search
            )";

            $params['search'] = $search_like;
        }

        $where_sql = implode(' AND ', $where);

        $count_sql = "
            SELECT COUNT(*)
            FROM `apanel_product` p
                LEFT JOIN `apanel_catalog_product` cp
                    ON cp.`product_id` = p.`id`
            WHERE {$where_sql}
        ";

        $total = (int) $this->product_model->query($count_sql, $params)->fetchField();

        $sql = "
            SELECT
                p.`id`,
                p.`code`,
                p.`article`,
                p.`created_contact_id`,
                p.`updated_contact_id`,
                p.`created_datetime`,
                p.`updated_datetime`,
                0 AS `catalogs_count`
            FROM `apanel_product` p
                LEFT JOIN `apanel_catalog_product` cp
                    ON cp.`product_id` = p.`id`
            WHERE {$where_sql}
            ORDER BY p.`id` DESC
            LIMIT i:offset, i:limit
        ";

        $items = $this->product_model->query($sql, $params)->fetchAll();

        return [
            'items'  => $items,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Возвращает id каталогов, к которым привязан товар.
     *
     * @param int $product_id
     * @return array
     */
    private function getCatalogIds(int $product_id): array
    {
        $sql = "
            SELECT `catalog_id`
            FROM `apanel_catalog_product`
            WHERE `product_id` = i:product_id
            ORDER BY `catalog_id` ASC
        ";

        $rows = $this->catalog_product_model->query($sql, [
            'product_id' => $product_id,
        ])->fetchAll();

        $result = [];

        foreach ($rows as $row) {
            $result[] = (int) $row['catalog_id'];
        }

        return $result;
    }

    /**
     * Нормализует строку поиска.
     *
     * @param mixed $value
     * @return string
     */
    private function normalizeSearch($value): string
    {
        return trim((string) $value);
    }

    /**
     * Нормализует limit.
     *
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
     * Нормализует offset.
     *
     * @param mixed $value
     * @return int
     */
    private function normalizeOffset($value): int
    {
        $value = (int) $value;

        return $value > 0 ? $value : 0;
    }
}

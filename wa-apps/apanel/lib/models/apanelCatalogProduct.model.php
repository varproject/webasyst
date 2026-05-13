<?php

/**
 * apanelCatalogProductModel
 *
 * Назначение:
 * - работать с контекстом товара внутри каталога;
 * - давать сервисному слою точечные выборки по паре product_id+catalog_id;
 * - не реализовывать бизнес-операции привязки/отвязки и массовые сценарии.
 *
 * Зависимости:
 * - waModel.
 *
 * Инварианты:
 * - таблица модели: apanel_catalog_product;
 * - для пары (product_id, catalog_id) существует не более одной записи;
 * - name обязательно на текущем этапе;
 * - is_active по умолчанию равно 1;
 * - поле price отсутствует принципиально.
 *
 * Побочные эффекты:
 * - отсутствуют, кроме стандартных операций чтения/записи waModel.
 *
 * Ошибки:
 * - ошибки SQL пробрасываются штатным механизмом waModel/waDbException.
 */
class apanelCatalogProductModel extends waModel
{
    /**
     * @var string
     */
    protected $table = 'apanel_catalog_product';

    /**
     * Возвращает контекст товара внутри каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @return array<string, mixed>|null
     */
    public function getByProductAndCatalog(int $product_id, int $catalog_id): ?array
    {
        $sql = "SELECT *
                FROM `{$this->table}`
                WHERE `product_id` = i:product_id
                    AND `catalog_id` = i:catalog_id
                LIMIT 1";

        $row = $this->query($sql, [
            'product_id' => $product_id,
            'catalog_id' => $catalog_id,
        ])->fetchAssoc();

        return $row ?: null;
    }

    /**
     * Проверяет существование связи товара с каталогом.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @return bool
     */
    public function exists(int $product_id, int $catalog_id): bool
    {
        return $this->getByProductAndCatalog($product_id, $catalog_id) !== null;
    }

    /**
     * Возвращает количество каталогов, к которым привязан товар.
     *
     * @param int $product_id
     * @return int
     */
    public function countCatalogsByProduct(int $product_id): int
    {
        $sql = "SELECT COUNT(*)
                FROM `{$this->table}`
                WHERE `product_id` = i:product_id";

        return (int) $this->query($sql, [
            'product_id' => $product_id,
        ])->fetchField();
    }

    /**
     * Возвращает список товаров каталога в порядке sort_order.
     *
     * @param int $catalog_id
     * @return array<int, array<string, mixed>>
     */
    public function getByCatalog(int $catalog_id): array
    {
        $sql = "SELECT *
                FROM `{$this->table}`
                WHERE `catalog_id` = i:catalog_id
                ORDER BY `sort_order` ASC, `product_id` ASC";

        return $this->query($sql, [
            'catalog_id' => $catalog_id,
        ])->fetchAll();
    }

    /**
     * Удаляет связь товара с каталогом.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @return bool
     */
    public function deleteByProductAndCatalog(int $product_id, int $catalog_id): bool
    {
        return (bool) $this->deleteByField([
            'product_id' => $product_id,
            'catalog_id' => $catalog_id,
        ]);
    }
}

<?php

/**
 * apanelProductCategoriesModel
 *
 * Назначение:
 * - работать со связями товара и категорий внутри каталога;
 * - отдавать сервисам текущие category_id и выполнять точечное удаление связей;
 * - не реализовывать самостоятельно проверки принадлежности категории каталогу.
 *
 * Зависимости:
 * - waModel.
 *
 * Инварианты:
 * - таблица модели: apanel_product_categories;
 * - связь существует только для уже существующего контекста товара в каталоге;
 * - дубликат пары (product_id, catalog_id, category_id) запрещён;
 * - связь остаётся product-first.
 *
 * Побочные эффекты:
 * - отсутствуют, кроме стандартных операций чтения/записи waModel.
 *
 * Ошибки:
 * - ошибки SQL пробрасываются штатным механизмом waModel/waDbException.
 */
class apanelProductCategoriesModel extends waModel
{
    /**
     * @var string
     */
    protected $table = 'apanel_product_categories';

    /**
     * Возвращает id категорий товара в рамках каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @return array<int, int>
     */
    public function getCategoryIds(int $product_id, int $catalog_id): array
    {
        $sql = "SELECT `category_id`
                FROM `{$this->table}`
                WHERE `product_id` = i:product_id
                    AND `catalog_id` = i:catalog_id
                ORDER BY `sort_order` ASC, `category_id` ASC";

        $rows = $this->query($sql, [
            'product_id' => $product_id,
            'catalog_id' => $catalog_id,
        ])->fetchAll();

        $result = [];

        foreach ($rows as $row) {
            $result[] = (int) $row['category_id'];
        }

        return $result;
    }

    /**
     * Проверяет существование связи товара с категорией внутри каталога.
     *
     * @param int $product_id
     * @param int $catalog_id
     * @param int $category_id
     * @return bool
     */
    public function exists(int $product_id, int $catalog_id, int $category_id): bool
    {
        $sql = "SELECT COUNT(*)
                FROM `{$this->table}`
                WHERE `product_id` = i:product_id
                    AND `catalog_id` = i:catalog_id
                    AND `category_id` = i:category_id";

        return (int) $this->query($sql, [
            'product_id'  => $product_id,
            'catalog_id'  => $catalog_id,
            'category_id' => $category_id,
        ])->fetchField() > 0;
    }

    /**
     * Удаляет все связи товара с категориями в рамках одного каталога.
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

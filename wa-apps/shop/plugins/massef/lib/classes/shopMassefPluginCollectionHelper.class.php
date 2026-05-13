<?php

/**
 * Хелпер коллекции товаров для масс-операций.
 *
 * Наследует shopProductsCollection и предоставляет потоковые (итераторные)
 * методы для безопасной обработки больших выборок, а также стабильную
 * пагинацию с ORDER BY p.id ASC для батч-процессинга.
 * 
 * @author  Petrosian Vagram
 * @since   1.0.0 (2025-11-02)
 */
class shopMassefPluginCollectionHelper extends shopProductsCollection
{
    /**
     * Получить все type_id из коллекции.
     *
     * @return int[] Список уникальных ID типов
     */
    public function getTypeIds()
    {
        $sql = 'SELECT DISTINCT p.type_id ' . $this->getSQL();
        $res = $this->getModel()->query($sql);

        $type_ids = [];
        while ($row = $res->fetch()) {
            if (!empty($row['type_id'])) {
                $type_ids[] = (int)$row['type_id'];
            }
        }

        return array_unique($type_ids);
    }

    /**
     * Потоковая выборка товаров (если нужны поля помимо id).
     * Можно задать $fields (например, "p.id, p.type_id").
     *
     * @param string $fields
     * @return Generator<array<string,int>>
     */
    protected function yieldProducts($fields = 'p.id')
    {
        $model = new shopProductModel();
        $sql = 'SELECT ' . $fields . ' ' . $this->getSQL();

        $res = $model->query($sql);
        while ($row = $res->fetchAssoc()) {
            yield $row;
        }
    }

    /**
     * Потоковая выборка всех product_id.
     * Позволяет безопасно обрабатывать миллионы товаров.
     *
     * @return Generator<int>
     */
    public function yieldProductIds()
    {
        $model = new shopProductModel();
        $sql = 'SELECT p.id ' . $this->getSQL();

        $res = $model->query($sql);
        while ($row = $res->fetch()) {
            yield (int)$row['id'];
        }
    }

    /**
     * Паблик-итератор для пар [id, type_id] без материализации.
     * Вызывает $callback батчами по $batch_size.
     *
     * @param int $batch_size
     * @param callable(array<int,array{id:int,type_id:int}>):void $callback
     * @return void
     */
    public function eachProductIdsAndTypes(int $batch_size, callable $callback): void
    {
        $batch = [];
        foreach ($this->yieldProducts('p.id, p.type_id') as $row) {
            $batch[] = ['id' => (int)$row['id'], 'type_id' => (int)$row['type_id']];
            if (count($batch) >= $batch_size) {
                $callback($batch);
                $batch = [];
            }
        }
        if ($batch) {
            $callback($batch);
        }
    }

    /**
     * Общее количество товаров в коллекции (учитывает фильтры коллекции).
     * DISTINCT защищает от дублей при JOIN'ах внутри getSQL().
     *
     * @return int
     */
    public function countAll(): int
    {
        $model = new shopProductModel();
        $sql = 'SELECT COUNT(DISTINCT p.id) AS cnt ' . $this->getSQL();
        $row = $model->query($sql)->fetchAssoc();
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }

    /**
     * Стабильный поток ID с поддержкой OFFSET/LIMIT и ORDER BY p.id ASC.
     * Используется для предсказуемых батчей и корректного прогресса.
     *
     * @param int $offset
     * @param int $limit
     * @return Generator<int>
     */
    public function yieldProductIdsOrdered(int $offset = 0, int $limit = 300): Generator
    {
        $model = new shopProductModel();
        $sql = 'SELECT p.id ' . $this->getSQL() . ' ORDER BY p.id ASC';
        $sql .= ' LIMIT ' . (int)$offset . ', ' . (int)$limit;

        $res = $model->query($sql);
        while ($row = $res->fetch()) {
            yield (int)$row['id'];
        }
    }
}

<?php

class apanelCategoryModel extends waModel
{
    protected $table = 'apanel_category';

    public function getAllCategoriesByCatalog($catalog_id)
    {
        $cache = new waRuntimeCache('apanel_catalog/getAllCategories');

        $data = $cache->get();
        if ($data !== null) {
            return $data;
        }

        // $data = $this->order('sort ASC')->fetchAll('id');
        $data = $this->where('catalog_id = ?', $catalog_id)
            ->order('sort ASC')
            ->fetchAll('id');

        $cache->set($data);

        return $data;
    }

    public function getCategoriesData($catalog_id, $current_id = null)
    {
        $all_categories = $this->getAllCategoriesByCatalog($catalog_id);

        // Собираем включенные
        $enabled_categories = [];
        foreach ($all_categories as $id => $category) {
            if (!empty($category['is_enabled'])) {
                $enabled_categories[$id] = $category;
            }
        }

        // Определяем дефолтный каталог (текущий из URL или первый активный)
        $active_category = $enabled_categories[$current_id] ?? [];

        return [
            'all'     => $all_categories,
            'enabled' => $enabled_categories,
            'active'  => $active_category,
        ];
    }


    /**
     * Очищает runtime-кэш каталога.
     */
    public function clearRuntimeCache()
    {
        (new waRuntimeCache('apanel_catalog/getAllCategories'))->delete();
    }




























    /**
     * Возвращает все категории каталога.
     *
     * @param int $catalog_id
     * @return array<int, array<string, mixed>>
     */
    public function getByCatalog(int $catalog_id): array
    {
        $sql = "SELECT *
                FROM `{$this->table}`
                WHERE `catalog_id` = i:catalog_id
                ORDER BY `sort` ASC, `id` ASC";

        return $this->query($sql, [
            'catalog_id' => $catalog_id,
        ])->fetchAll('id');
    }

    /**
     * Возвращает дочерние категории внутри каталога.
     *
     * @param int $catalog_id
     * @param int|null $parent_id
     * @return array<int, array<string, mixed>>
     */
    public function getChildren(int $catalog_id, ?int $parent_id = null): array
    {
        $sql = "SELECT *
                FROM `{$this->table}`
                WHERE `catalog_id` = i:catalog_id";

        $params = [
            'catalog_id' => $catalog_id,
        ];

        if ($parent_id === null) {
            $sql .= " AND `parent_id` IS NULL";
        } else {
            $sql .= " AND `parent_id` = i:parent_id";
            $params['parent_id'] = $parent_id;
        }

        $sql .= " ORDER BY `sort` ASC, `id` ASC";

        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Возвращает категорию только если она принадлежит указанному каталогу.
     *
     * @param int $catalog_id
     * @param int $category_id
     * @return array<string, mixed>|null
     */
    public function getByCatalogAndId(int $catalog_id, int $category_id): ?array
    {
        $sql = "SELECT *
                FROM `{$this->table}`
                WHERE `catalog_id` = i:catalog_id
                    AND `id` = i:category_id
                LIMIT 1";

        $row = $this->query($sql, [
            'catalog_id'  => $catalog_id,
            'category_id' => $category_id,
        ])->fetchAssoc();

        return $row ?: null;
    }
}

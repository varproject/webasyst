<?php

class apanelCategoryTreeService
{
    /**
     * @var apanelCatalogModel
     */
    private $catalog_model;

    /**
     * @var apanelCategoryModel
     */
    private $category_model;

    public function __construct()
    {
        $this->catalog_model  = new apanelCatalogModel();
        $this->category_model = new apanelCategoryModel();
    }

    /**
     * Возвращает дерево категорий каталога.
     *
     * Возвращаются только реальные категории из apanel_category.
     * Системные виртуальные узлы "Все товары" и "Без категории"
     * сюда не добавляются.
     *
     * @param int $catalog_id
     * @param bool вернет фильтрованный список в плоском виде
     * @return array
     * @throws waException
     */
    public function getTree(int $catalog_id, bool $flat = false): array
    {
        $catalog = $this->catalog_model->getById($catalog_id);

        if (!$catalog) {
            return [];
        }

        $rows = $this->category_model->getByCatalog($catalog_id);

        if (!$rows) {
            return [];
        }

        if ($flat) {
            return $rows;
        }

        $items_by_parent = [];
        $items_by_id     = [];

        foreach ($rows as $row) {
            $item = $this->prepareItem($row);

            $items_by_id[(int) $item['id']] = $item;

            $parent_key = $item['parent_id'] === null
                ? 0
                : (int) $item['parent_id'];

            if (!isset($items_by_parent[$parent_key])) {
                $items_by_parent[$parent_key] = [];
            }

            $items_by_parent[$parent_key][] = (int) $item['id'];
        }

        return $this->buildBranch(null, 0, $items_by_parent, $items_by_id);
    }

    /**
     * Возвращает плоский список дерева с level.
     *
     * Удобно для select родителя, таблиц и линейного sidebar-рендера.
     *
     * @param int $catalog_id
     * @return array
     * @throws waException
     */
    public function getFlatTree(int $catalog_id): array
    {
        $tree = $this->getTree($catalog_id);

        $result = [];
        $this->flattenTree($tree, $result);

        return $result;
    }

    /**
     * @param array $row
     * @return array
     */
    private function prepareItem(array $row): array
    {
        return [
            'id'                 => (int) $row['id'],
            'catalog_id'         => (int) $row['catalog_id'],
            'parent_id'          => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            'name'               => (string) $row['name'],
            'sort'               => (int) $row['sort'],
            'is_enabled'         => (int) $row['is_enabled'],
            'created_contact_id' => $row['created_contact_id'] !== null ? (int) $row['created_contact_id'] : null,
            'updated_contact_id' => $row['updated_contact_id'] !== null ? (int) $row['updated_contact_id'] : null,
            'created_datetime'   => $row['created_datetime'],
            'updated_datetime'   => $row['updated_datetime'],
            'level'              => 0,
            'has_children'       => false,
            'children_count'     => 0,
            'children'           => [],
        ];
    }

    /**
     * @param int|null $parent_id
     * @param int $level
     * @param array $items_by_parent
     * @param array $items_by_id
     * @return array
     */
    private function buildBranch(?int $parent_id, int $level, array $items_by_parent, array $items_by_id): array
    {
        $parent_key = $parent_id === null ? 0 : $parent_id;

        if (empty($items_by_parent[$parent_key])) {
            return [];
        }

        $result = [];

        foreach ($items_by_parent[$parent_key] as $category_id) {
            if (!isset($items_by_id[$category_id])) {
                continue;
            }

            $item = $items_by_id[$category_id];
            $children = $this->buildBranch($category_id, $level + 1, $items_by_parent, $items_by_id);

            $item['level']          = $level;
            $item['children']       = $children;
            $item['children_count'] = count($children);
            $item['has_children']   = !empty($children);

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array $tree
     * @param array $result
     * @return void
     */
    private function flattenTree(array $tree, array &$result): void
    {
        foreach ($tree as $item) {
            $row = $item;
            unset($row['children']);

            $result[] = $row;

            if (!empty($item['children'])) {
                $this->flattenTree($item['children'], $result);
            }
        }
    }
}

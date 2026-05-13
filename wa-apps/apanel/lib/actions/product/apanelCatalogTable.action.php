<?php

class apanelCatalogTableAction extends apanelBaseTableAction
{
    protected function preExecute()
    {
        $this->setTableId('product');
        $this->setColumns($this->buildColumns());
        $this->setItems($this->buildItems());
    }

    protected function buildColumns()
    {
        return [
            'id' => [
                'title' => '#',
                'width' => '80px',
            ],
            'name' => [
                'title' => 'Название',
            ],
            'code' => [
                'title' => 'Артикул',
                'width' => '180px',
            ],
        ];
    }

    protected function buildItems()
    {
        $seg_id   = filter_var(apanelUrlSegment::get(3), FILTER_VALIDATE_INT);
        $catalogs = (new apanelCatalogModel())->getEnabledCatalogs();

        if (empty($catalogs)) {
            $this->setEmptyText('Каталоги не найдены');
            $this->setPagination(1, 50, 0);
            return [];
        }

        $catalog = isset($catalogs[$seg_id]) ? $catalogs[$seg_id] : (reset($catalogs) ?: []);
        if (empty($catalog['id'])) {
            $this->setEmptyText('Каталог не найден');
            $this->setPagination(1, 50, 0);
            return [];
        }

        $catalog_id = (int)$catalog['id'];

        $search    = trim((string)waRequest::get('search', '', waRequest::TYPE_STRING));
        $is_active = waRequest::get('is_active', '', waRequest::TYPE_STRING);
        $page      = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
        $limit     = 100;
        $offset    = ($page - 1) * $limit;

        $options = [
            'search' => $search,
            'limit'  => $limit,
            'offset' => $offset,
        ];

        if ($is_active !== '') {
            $options['is_active'] = (int)$is_active ? 1 : 0;
        }

        $result = (new apanelCatalogProductReadService())->getCatalogProducts($catalog_id, $options);

        $this->setSearch($search);
        $this->setPagination($page, $limit, (int)($result['total'] ?? 0));
        $this->setSort('name', 'asc');
        $this->setEmptyText('Товары не найдены');
        $this->setFilters([
            'is_active' => $is_active,
        ]);

        return $result['items'] ?? [];
    }
}

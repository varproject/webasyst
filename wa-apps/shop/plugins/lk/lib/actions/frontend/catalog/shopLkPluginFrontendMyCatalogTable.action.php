<?php

class shopLkPluginFrontendMyCatalogTableAction extends waViewAction
{
    public function execute()
    {
        $data = $this->buildTableData();

        $this->layout->assign('main_footer_left_items', $this->buildPagination($data));

        $table_html = shopLkPluginUi::getControl('table', 'catalog', [
            'items'         => $data['items'],
            'columns'       => $this->buildColumns(),
            'show_checkbox' => true,
            'empty_text'    => 'Товары не найдены.',
            'tbody_attrs'   => [
                'id' => 'catalog-table-body',
            ],
        ]);

        $this->view->assign('table_html', $table_html);
    }

    protected function buildColumns()
    {
        return [
            'image' => [
                'title'   => 'Фото',
                'type'    => 'image',
                'thclass' => 'text-nowrap width-3',
                'tdclass' => 'text-nowrap',
            ],
            'code' => [
                'title'   => 'Артикул',
                'thclass' => 'text-nowrap width-10',
                'tdclass' => 'text-nowrap',
            ],
            'name' => [
                'title' => 'Название',
                'type'    => 'title',

            ],
        ];
    }

    protected function buildTableData()
    {
        $filters = $this->getFilters();

        $page   = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
        $limit  = 30;

        $collection = $this->buildCollection($filters);

        $total = $collection->count();
        $pages = max(1, ceil($total / $limit));

        if ($page > $pages) {
            $page = $pages;
        }

        $offset   = ($page - 1) * $limit;
        $products = $collection->getProducts('id,name,sku,sku_id,image_crop_small', $offset, $limit, false);

        // dd($products);

        return [
            'items'   => $this->prepareItems($products),
            'filters' => $filters,
            'page'    => $page,
            'limit'   => $limit,
            'offset'  => $offset,
            'total'   => $total,
            'pages'   => $pages,
        ];
    }

    protected function buildCollection($filters)
    {
        $collection = new shopProductsCollection('', [
            'filters' => false,
            'params'  => false,
        ]);

        $this->applySearch($collection, $filters);
        $this->applyFilters($collection, $filters);

        return $collection;
    }

    protected function applySearch(shopProductsCollection $collection, $filters)
    {
        $q = $this->normalizeSearchQuery(ifset($filters['q'], ''));

        if ($q === '') {
            return;
        }

        $model = new shopProductModel();

        $sku_alias = $collection->addJoin([
            'table' => 'shop_product_skus',
            'type'  => 'LEFT',
            'on'    => ':table.product_id = p.id',
        ]);

        foreach (explode(' ', $q) as $word) {
            $word = trim($word);

            if ($word === '') {
                continue;
            }

            $escaped = $model->escape($word, 'like');

            $where = [];
            $where[] = "p.name LIKE '%{$escaped}%'";
            $where[] = "{$sku_alias}.sku LIKE '%{$escaped}%'";
            $where[] = "{$sku_alias}.name LIKE '%{$escaped}%'";

            $collection->addWhere('(' . implode(' OR ', $where) . ')');
        }

        $collection->groupBy('p.id');
    }

    protected function applyFilters(shopProductsCollection $collection, $filters)
    {
        if (!empty($filters['type_id'])) {
            $collection->addWhere('p.type_id = ' . (int) $filters['type_id']);
        }

        if (!empty($filters['category_id'])) {
            $category_alias = $collection->addJoin('shop_category_products');

            $collection->addWhere($category_alias . '.category_id = ' . (int) $filters['category_id']);
            $collection->groupBy('p.id');
        }
    }

    protected function getFilters()
    {
        return [
            'q'           => trim((string) waRequest::get('q', '', waRequest::TYPE_STRING)),
            'type_id'     => max(0, waRequest::get('type_id', 0, waRequest::TYPE_INT)),
            'category_id' => max(0, waRequest::get('category_id', 0, waRequest::TYPE_INT)),
        ];
    }

    protected function prepareItems($products)
    {
        $items = [];

        foreach ($products as $product) {
            $items[] = [
                'id'   => (int) ifset($product['id'], 0),
                'image' => (string) ifset($product['image_crop_small'], ''),
                'name' => (string) ifset($product['name'], ''),
                'code' => (string) ifset($product['sku'], ''),
            ];
        }

        return $items;
    }

    protected function buildPagination($data)
    {
        if (empty($data['total']) || $data['pages'] <= 1) {
            return '';
        }

        $page  = (int) $data['page'];
        $pages = (int) $data['pages'];

        $html = '<nav class="d-flex align-items-center" aria-label="Пагинация товаров">';
        $html .= '<ul class="pagination pagination-sm mb-0">';

        $html .= $this->buildPaginationItem('Назад', $page - 1, $page <= 1, $data);

        foreach ($this->getPaginationPages($page, $pages) as $p) {
            if ($p === '...') {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                continue;
            }

            $html .= $this->buildPaginationItem($p, $p, false, $data, $p == $page);
        }

        $html .= $this->buildPaginationItem('Вперед', $page + 1, $page >= $pages, $data);

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    protected function buildPaginationItem($label, $page, $disabled, $data, $active = false)
    {
        $class = 'page-item';

        if ($disabled) {
            $class .= ' disabled';
        }

        if ($active) {
            $class .= ' active';
        }

        if ($disabled || $active) {
            return '<li class="' . $class . '"><span class="page-link">' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</span></li>';
        }

        $url = $this->buildPaginationUrl($page, $data['filters']);

        return '<li class="' . $class . '"><a class="page-link" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</a></li>';
    }

    protected function getPaginationPages($page, $pages)
    {
        if ($pages <= 7) {
            return range(1, $pages);
        }

        $result = [1];

        $start = max(2, $page - 2);
        $end   = min($pages - 1, $page + 2);

        if ($start > 2) {
            $result[] = '...';
        }

        for ($i = $start; $i <= $end; $i++) {
            $result[] = $i;
        }

        if ($end < $pages - 1) {
            $result[] = '...';
        }

        $result[] = $pages;

        return $result;
    }

    protected function buildPaginationUrl($page, $filters)
    {
        $params = [];

        foreach ($filters as $key => $value) {
            if ($value !== '' && $value !== 0 && $value !== null) {
                $params[$key] = $value;
            }
        }

        if ($page > 1) {
            $params['page'] = $page;
        }

        return $params ? '?' . http_build_query($params) : '?';
    }

    protected function normalizeSearchQuery($q)
    {
        $q = trim((string) $q);
        $q = preg_replace('~[,;]+~u', ' ', $q);
        $q = preg_replace('~\s+~u', ' ', $q);

        return trim($q);
    }
}

<?php

class apanelCatalogItemListAction extends waViewAction
{
    public function execute()
    {
        $this->setTemplate(wa()->getAppPath('templates/actions/product/BackendCatalogItemList.html', 'apanel'));

        $catalog_id = (int) waRequest::param('catalog_id');

        if ($catalog_id <= 0) {
            throw new waException('Каталог не найден.');
        }

        $catalog_model = new apanelCatalogModel();
        $catalog = $catalog_model->getById($catalog_id);

        if (!$catalog) {
            throw new waException('Каталог не найден.');
        }

        $search    = trim((string) waRequest::get('search', '', waRequest::TYPE_STRING));
        $is_active = waRequest::get('is_active', '', waRequest::TYPE_STRING);
        $page      = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
        $limit     = 50;
        $offset    = ($page - 1) * $limit;

        $read_service = new apanelCatalogProductReadService();
        $tree_service = new apanelCategoryTreeService();

        $options = [
            'search' => $search,
            'limit'  => $limit,
            'offset' => $offset,
        ];

        if ($is_active !== '') {
            $options['is_active'] = (int) $is_active ? 1 : 0;
        }

        $result = $read_service->getCatalogProducts($catalog_id, $options);
        $pages  = (int) ceil($result['total'] / $limit);

        $this->view->assign([
            'catalog'             => $catalog,
            'catalog_id'          => $catalog_id,
            'search'              => $search,
            'is_active'           => $is_active,
            'page'                => $page,
            'limit'               => $limit,
            'pages'               => $pages,
            'total'               => $result['total'],
            'products'            => $result['items'],
            'category_tree'       => $tree_service->getTree($catalog_id),
            'current_sidebar_key' => 'all',
        ]);
    }
}

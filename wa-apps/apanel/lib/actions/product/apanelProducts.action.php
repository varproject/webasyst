<?php

class apanelProductsAction extends waViewAction
{
    public function execute()
    {
        $search = trim((string) waRequest::get('search', '', waRequest::TYPE_STRING));
        $page   = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $service = new apanelProductReadService();

        $result = $service->getList([
            'search' => $search,
            'limit'  => $limit,
            'offset' => $offset,
        ]);

        // dd($result);

        $pages = (int) ceil($result['total'] / $limit);

        // $this->layout->assign('main_body_table_items', $this->getMainBodyTableItems());

        $this->view->assign([
            'search'   => $search,
            'page'     => $page,
            'limit'    => $limit,
            'pages'    => $pages,
            'total'    => $result['total'],
            'products' => $result['items'],
        ]);
    }


    // main_body_table_items
    protected function getMainBodyTableItems($params = null)
    {
        $fields = 'id,  code';
        $allowed_keys = array_map('trim', explode(',', $fields));
        $items = (new apanelProductModel())->select($fields)->fetchAll();

        $columns = [];
        foreach ($allowed_keys as $key) {
            $columns[$key] = [
                'title'   => ucfirst($key),
                'type'    => ($key === 'name') ? 'title' : 'text',
                'visible' => true,
            ];
        }

        return apanelUi::getControl('table', 'products', [
            'items'   => $items,
            'columns' => $columns,
        ]);
    }
}

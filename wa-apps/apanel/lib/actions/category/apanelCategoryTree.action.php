<?php

class apanelCategoryTreeAction extends waViewAction
{
    public function execute()
    {
        $catalog_id = waRequest::param('active_catalog_id', 0);

        if ($catalog_id <= 0) {
            $this->view->assign([
                'has_categories' => false,
                'tree_html'      => '',
            ]);
        }

        $session_category_id    = $this->getStorage()->read("apanel_catalog_{$catalog_id}_category_active_node");
        $current_category_id    = waRequest::get('category_id', $session_category_id, waRequest::TYPE_INT);
        $categories_data        = (new apanelCategoryModel())->getCategoriesData($catalog_id, $current_category_id);
        $enabled_categories     = $categories_data['enabled'] ?? [];
        $category_id            = $categories_data['active']['id'] ?? 0;

        waRequest::setParam('active_category_id', $category_id);

        $tree_html = (new apanelTreeAction([
            'nodes'                 => $enabled_categories,
            'active_id'             => $category_id,
            'session_open_ids_key'  => 'apanel_catalog_' . $catalog_id . '_category_open_ids',
            'session_active_id_key' => 'apanel_catalog_' . $catalog_id . '_category_active_node',
        ]))->display();

        $this->view->assign([
            'has_categories' => !empty($enabled_categories),
            'tree_html'      => $tree_html,
        ]);
    }
}

<?php

class shopB2bPluginChannelCatalogSettingsService extends shopB2bPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        return array(
            'settings' => array(
                'enabled' => ifset($params, 'b2b_catalog_enabled', '1'),
                'url' => ifset($params, 'b2b_catalog_url', 'catalog'),
                'title' => ifset($params, 'b2b_catalog_title', 'Каталог'),
                'category_mode' => ifset($params, 'b2b_catalog_category_mode', 'all'),
                'category_ids' => $this->getIdList(ifset($params, 'b2b_catalog_category_ids', '')),
                'except_category_ids' => $this->getIdList(ifset($params, 'b2b_catalog_except_category_ids', '')),
                'show_empty_categories' => ifset($params, 'b2b_catalog_show_empty_categories', '0'),
                'show_product_count' => ifset($params, 'b2b_catalog_show_product_count', '1'),
                'products_per_page' => ifset($params, 'b2b_catalog_products_per_page', '30'),
                'default_sort' => ifset($params, 'b2b_catalog_default_sort', 'name'),
                'show_filters' => ifset($params, 'b2b_catalog_show_filters', '1'),
                'show_search' => ifset($params, 'b2b_catalog_show_search', '1'),
                'show_prices' => ifset($params, 'b2b_catalog_show_prices', '1'),
                'show_compare_price' => ifset($params, 'b2b_catalog_show_compare_price', '0'),
                'show_stock' => ifset($params, 'b2b_catalog_show_stock', '1'),
            ),
        );
    }

    public function normalize(array $input): array
    {
        $mode = ifset($input, 'b2b_catalog_category_mode', 'all');
        if (!in_array($mode, array('all', 'selected', 'except'), true)) {
            $mode = 'all';
        }

        return array(
            'b2b_catalog_enabled' => $this->getBool(ifset($input, 'b2b_catalog_enabled', 0)),
            'b2b_catalog_url' => $this->normalizeSlug(ifset($input, 'b2b_catalog_url', 'catalog'), 'catalog'),
            'b2b_catalog_title' => trim((string) ifset($input, 'b2b_catalog_title', 'Каталог')),
            'b2b_catalog_category_mode' => $mode,
            'b2b_catalog_category_ids' => $this->encodeIdList(ifset($input, 'b2b_catalog_category_ids', array())),
            'b2b_catalog_except_category_ids' => $this->encodeIdList(ifset($input, 'b2b_catalog_except_category_ids', array())),
            'b2b_catalog_show_empty_categories' => $this->getBool(ifset($input, 'b2b_catalog_show_empty_categories', 0)),
            'b2b_catalog_show_product_count' => $this->getBool(ifset($input, 'b2b_catalog_show_product_count', 1)),
            'b2b_catalog_products_per_page' => max(1, (int) ifset($input, 'b2b_catalog_products_per_page', 30)),
            'b2b_catalog_default_sort' => trim((string) ifset($input, 'b2b_catalog_default_sort', 'name')),
            'b2b_catalog_show_filters' => $this->getBool(ifset($input, 'b2b_catalog_show_filters', 1)),
            'b2b_catalog_show_search' => $this->getBool(ifset($input, 'b2b_catalog_show_search', 1)),
            'b2b_catalog_show_prices' => $this->getBool(ifset($input, 'b2b_catalog_show_prices', 1)),
            'b2b_catalog_show_compare_price' => $this->getBool(ifset($input, 'b2b_catalog_show_compare_price', 0)),
            'b2b_catalog_show_stock' => $this->getBool(ifset($input, 'b2b_catalog_show_stock', 1)),
        );
    }

    public function validate(int $channel_id, array $settings): array
    {
        $errors = array();
        if (ifset($settings, 'b2b_catalog_title', '') === '') {
            $errors[] = array('field' => 'settings[b2b_catalog_title]', 'error_description' => 'Укажите название раздела.');
        }
        if (ifset($settings, 'b2b_catalog_url', '') === '') {
            $errors[] = array('field' => 'settings[b2b_catalog_url]', 'error_description' => 'Укажите URL раздела.');
        }
        if (ifset($settings, 'b2b_catalog_category_mode', 'all') === 'selected' && !$this->getIdList($settings['b2b_catalog_category_ids'])) {
            $errors[] = array('field' => 'settings[b2b_catalog_category_ids]', 'error_description' => 'Выберите категории товаров.');
        }
        if (ifset($settings, 'b2b_catalog_category_mode', 'all') === 'except' && !$this->getIdList($settings['b2b_catalog_except_category_ids'])) {
            $errors[] = array('field' => 'settings[b2b_catalog_except_category_ids]', 'error_description' => 'Выберите исключаемые категории товаров.');
        }
        return $errors;
    }

    public function save(int $channel_id, array $input): void
    {
        $this->updateParams($channel_id, $this->normalize($input));
    }
}

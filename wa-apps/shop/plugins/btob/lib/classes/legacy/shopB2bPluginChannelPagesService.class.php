<?php

class shopBtobPluginChannelPagesService extends shopBtobPluginChannelSettingsService
{
    protected shopBtobPluginChannelPageModel $page_model;

    public function __construct()
    {
        parent::__construct();
        $this->page_model = new shopBtobPluginChannelPageModel();
    }

    public function getViewData(array $channel): array
    {
        $channel_id = (int) ifset($channel, 'id', 0);
        return array(
            'settings' => array('enabled' => 1),
            'pages' => $channel_id ? $this->page_model->select('*')->where('channel_id = i:id', array('id' => $channel_id))->order('sort, id')->fetchAll() : array(),
            'page_sources' => array(
                'own' => 'Своя страница B2B',
                'shop_page' => 'Страница Shop-Script',
                'site_page' => 'Страница Site',
                'site_block' => 'Блок Site',
                'external_url' => 'Ссылка',
            ),
        );
    }

    public function normalizePage(array $input): array
    {
        $source = ifset($input, 'source', 'own');
        if (!in_array($source, array('own', 'shop_page', 'site_page', 'site_block', 'external_url'), true)) {
            $source = 'own';
        }

        $access = ifset($input, 'access_policy', 'inherit');
        if (!in_array($access, array('inherit', 'public', 'authorized', 'restricted'), true)) {
            $access = 'inherit';
        }

        return array(
            'status' => $this->getBool(ifset($input, 'status', 1)),
            'title' => trim((string) ifset($input, 'title', '')),
            'url' => $this->normalizeSlug(ifset($input, 'url', '')),
            'source' => $source,
            'content' => (string) ifset($input, 'content', ''),
            'shop_page_id' => (int) ifset($input, 'shop_page_id', 0),
            'site_page_id' => (int) ifset($input, 'site_page_id', 0),
            'site_block_id' => trim((string) ifset($input, 'site_block_id', '')),
            'external_url' => trim((string) ifset($input, 'external_url', '')),
            'show_in_menu' => $this->getBool(ifset($input, 'show_in_menu', 1)),
            'sort' => (int) ifset($input, 'sort', 0),
            'access_policy' => $access,
        );
    }

    public function validatePage(int $channel_id, array $page, int $page_id = 0): array
    {
        $errors = array();
        if ($page['title'] === '') {
            $errors[] = array('field' => 'page[title]', 'error_description' => 'Укажите название страницы.');
        }
        if ($page['source'] !== 'external_url' && $page['url'] === '') {
            $errors[] = array('field' => 'page[url]', 'error_description' => 'Укажите URL страницы.');
        }
        if ($page['source'] === 'site_block' && !$this->validateBlockId($page['site_block_id'])) {
            $errors[] = array('field' => 'page[site_block_id]', 'error_description' => 'Укажите корректный ID блока Site.');
        }
        if ($page['source'] === 'external_url' && $page['external_url'] === '') {
            $errors[] = array('field' => 'page[external_url]', 'error_description' => 'Укажите ссылку.');
        }
        return $errors;
    }
}

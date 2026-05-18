<?php

class shopBtobPluginChannelBlogSettingsService extends shopBtobPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        return array('settings' => array(
            'enabled' => ifset($params, 'btob_blog_enabled', '0'),
            'url' => ifset($params, 'btob_blog_url', 'blog'),
            'title' => ifset($params, 'btob_blog_title', 'Блог'),
            'source_mode' => ifset($params, 'btob_blog_source_mode', 'blog'),
            'blog_id' => ifset($params, 'btob_blog_blog_id', ''),
            'blog_route_key' => ifset($params, 'btob_blog_route_key', ''),
            'posts_per_page' => ifset($params, 'btob_blog_posts_per_page', '10'),
            'show_author' => ifset($params, 'btob_blog_show_author', '0'),
            'show_datetime' => ifset($params, 'btob_blog_show_datetime', '1'),
            'show_comments' => ifset($params, 'btob_blog_show_comments', '0'),
            'show_in_menu' => ifset($params, 'btob_blog_show_in_menu', '1'),
            'access_policy' => ifset($params, 'btob_blog_access_policy', 'inherit'),
        ));
    }

    public function normalize(array $input): array
    {
        $source = ifset($input, 'btob_blog_source_mode', 'blog');
        if (!in_array($source, array('blog', 'settlement', 'manual'), true)) {
            $source = 'blog';
        }
        $access = ifset($input, 'btob_blog_access_policy', 'inherit');
        if (!in_array($access, array('inherit', 'public', 'authorized', 'restricted'), true)) {
            $access = 'inherit';
        }
        return array(
            'btob_blog_enabled' => $this->getBool(ifset($input, 'btob_blog_enabled', 0)),
            'btob_blog_url' => $this->normalizeSlug(ifset($input, 'btob_blog_url', 'blog'), 'blog'),
            'btob_blog_title' => trim((string) ifset($input, 'btob_blog_title', 'Блог')),
            'btob_blog_source_mode' => $source,
            'btob_blog_blog_id' => (int) ifset($input, 'btob_blog_blog_id', 0),
            'btob_blog_route_key' => trim((string) ifset($input, 'btob_blog_route_key', '')),
            'btob_blog_posts_per_page' => max(1, (int) ifset($input, 'btob_blog_posts_per_page', 10)),
            'btob_blog_show_author' => $this->getBool(ifset($input, 'btob_blog_show_author', 0)),
            'btob_blog_show_datetime' => $this->getBool(ifset($input, 'btob_blog_show_datetime', 1)),
            'btob_blog_show_comments' => $this->getBool(ifset($input, 'btob_blog_show_comments', 0)),
            'btob_blog_show_in_menu' => $this->getBool(ifset($input, 'btob_blog_show_in_menu', 1)),
            'btob_blog_access_policy' => $access,
        );
    }

    public function validate(int $channel_id, array $settings): array
    {
        $errors = array();
        if (!empty($settings['btob_blog_enabled']) && ifset($settings, 'btob_blog_title', '') === '') {
            $errors[] = array('field' => 'settings[btob_blog_title]', 'error_description' => 'Укажите название раздела блога.');
        }
        return $errors;
    }

    public function save(int $channel_id, array $input): void
    {
        $this->updateParams($channel_id, $this->normalize($input));
    }
}

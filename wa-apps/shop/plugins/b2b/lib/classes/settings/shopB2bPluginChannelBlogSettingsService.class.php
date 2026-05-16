<?php

class shopB2bPluginChannelBlogSettingsService extends shopB2bPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        return array('settings' => array(
            'enabled' => ifset($params, 'b2b_blog_enabled', '0'),
            'url' => ifset($params, 'b2b_blog_url', 'blog'),
            'title' => ifset($params, 'b2b_blog_title', 'Блог'),
            'source_mode' => ifset($params, 'b2b_blog_source_mode', 'blog'),
            'blog_id' => ifset($params, 'b2b_blog_blog_id', ''),
            'blog_route_key' => ifset($params, 'b2b_blog_route_key', ''),
            'posts_per_page' => ifset($params, 'b2b_blog_posts_per_page', '10'),
            'show_author' => ifset($params, 'b2b_blog_show_author', '0'),
            'show_datetime' => ifset($params, 'b2b_blog_show_datetime', '1'),
            'show_comments' => ifset($params, 'b2b_blog_show_comments', '0'),
            'show_in_menu' => ifset($params, 'b2b_blog_show_in_menu', '1'),
            'access_policy' => ifset($params, 'b2b_blog_access_policy', 'inherit'),
        ));
    }

    public function normalize(array $input): array
    {
        $source = ifset($input, 'b2b_blog_source_mode', 'blog');
        if (!in_array($source, array('blog', 'settlement', 'manual'), true)) {
            $source = 'blog';
        }
        $access = ifset($input, 'b2b_blog_access_policy', 'inherit');
        if (!in_array($access, array('inherit', 'public', 'authorized', 'restricted'), true)) {
            $access = 'inherit';
        }
        return array(
            'b2b_blog_enabled' => $this->getBool(ifset($input, 'b2b_blog_enabled', 0)),
            'b2b_blog_url' => $this->normalizeSlug(ifset($input, 'b2b_blog_url', 'blog'), 'blog'),
            'b2b_blog_title' => trim((string) ifset($input, 'b2b_blog_title', 'Блог')),
            'b2b_blog_source_mode' => $source,
            'b2b_blog_blog_id' => (int) ifset($input, 'b2b_blog_blog_id', 0),
            'b2b_blog_route_key' => trim((string) ifset($input, 'b2b_blog_route_key', '')),
            'b2b_blog_posts_per_page' => max(1, (int) ifset($input, 'b2b_blog_posts_per_page', 10)),
            'b2b_blog_show_author' => $this->getBool(ifset($input, 'b2b_blog_show_author', 0)),
            'b2b_blog_show_datetime' => $this->getBool(ifset($input, 'b2b_blog_show_datetime', 1)),
            'b2b_blog_show_comments' => $this->getBool(ifset($input, 'b2b_blog_show_comments', 0)),
            'b2b_blog_show_in_menu' => $this->getBool(ifset($input, 'b2b_blog_show_in_menu', 1)),
            'b2b_blog_access_policy' => $access,
        );
    }

    public function validate(int $channel_id, array $settings): array
    {
        $errors = array();
        if (!empty($settings['b2b_blog_enabled']) && ifset($settings, 'b2b_blog_title', '') === '') {
            $errors[] = array('field' => 'settings[b2b_blog_title]', 'error_description' => 'Укажите название раздела блога.');
        }
        return $errors;
    }

    public function save(int $channel_id, array $input): void
    {
        $this->updateParams($channel_id, $this->normalize($input));
    }
}

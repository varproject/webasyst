<?php

class logsBackendNavigationAction extends logsViewAction
{
    const SHOW_PREMIUM_STEP = 3;

    private $backend_url;

    public function __construct()
    {
        parent::__construct();
        $this->backend_url = logsHelper::getLogsBackendUrl(false);
    }

    public function execute()
    {
        $path = waRequest::get('path', '');
        $action = waRequest::get('action', '');

        $back_url = $this->getBackUrl();

        if ($action == 'file') {
            $total_size = filesize(logsHelper::getFullPath($path));
        } else {
            if ($action != 'action') {
                $total_size = logsHelper::getTotalLogsSize();
            }
        }

        if (in_array($action, ['file', 'action', 'actions']) || strlen($path)) {
            if (in_array($action, ['file', 'action'])) {
                $this->view->assign('back', strpos($back_url, $this->backend_url) === 0);
            }

            if (strlen($path)) {
                $breadcrumbs = $this->getBreadcrumbs($path);
            } elseif (in_array($action, ['action', 'actions'])) {
                $breadcrumbs = [
                    [
                        'name' => _w('logs'),
                        'url' => '',
                    ],
                    [
                        'name' => _w('User actions'),
                        'url' => http_build_query([
                            'action' => 'actions',
                        ]),
                    ],
                ];

                if ($action == 'action') {
                    $breadcrumbs[] = [
                        'name' => logsItemAction::getName(waRequest::get('id', '')),
                        'url' => http_build_query([
                            'action' => 'action',
                            'id' => waRequest::get('id', ''),
                        ]),
                    ];
                }
            }

            $this->view->assign('breadcrumbs', $breadcrumbs);
        }

        if (isset($total_size)) {
            $total_size_classes = ['total-size'];

            if (waRequest::get('action', '') == 'file') {
                $total_size_classes[] = 'total-size-file';
            } elseif (logsHelper::isLargeSize($total_size)) {
                $total_size_classes[] = 'total-size-large';
            }

            $total_size_hint = waRequest::get('action', '') == 'file' ? _w('This file’s size') : _w('All log files’ total size');
        }

        $show_search_promo = logsHelper::mustDisplayPremiumPromo('search');
        $show_sort_mode_promo = !$show_search_promo && logsHelper::mustDisplayPremiumPromo('sort-mode');

        $this->view->assign('view_modes', $this->getViewModes());
        $this->view->assign('item_actions', $this->getItemActions());
        $this->view->assign('total_size', isset($total_size) ? logsHelper::formatSize($total_size) : null);
        $this->view->assign('total_size_class', isset($total_size_classes) ? implode(' ', $total_size_classes) : '');
        $this->view->assign('total_size_hint', ifset($total_size_hint));
        $this->view->assign('back_url', $back_url);
        $this->view->assign('is_item_list', !in_array(waRequest::get('action', ''), ['file', 'action']));
        $this->view->assign('product_search_product_name', logsHelper::getProductNameBySlug(waRequest::get('slug', ''), true));
        $this->view->assign('show_search_promo', $show_search_promo);
        $this->view->assign('show_sort_mode_promo', $show_sort_mode_promo);
        $this->view->assign('is_search_mode', is_string(logsHelper::getFileContentsSearchQuery()));
    }

    private function sortViewModes($a, $b)
    {
        if ($a['selected'] != $b['selected']) {
            return $b['selected'] ? 1 : -1;
        } else {
            return $a['sort'] < $b['sort'] ? -1 : 1;
        }
    }

    private function getBreadcrumbs($path)
    {
        $path_parts = explode('/', $path);

        if (!isset($path_parts[0]) || !strlen($path_parts[0])) {
            return false;
        }

        $result = [];
        $result[] = [
            'name' => 'wa-log',
            'url' => '',
        ];

        $item_path = '';
        foreach ($path_parts as $part) {
            $item_path .= $item_path ? '/' . $part : $part;
            $result[] = [
                'name' => $part,
                'url' => http_build_query([
                    'path' => $item_path,
                ]),
            ];
        }

        return $result;
    }

    private function getBackUrl()
    {
        if (in_array(waRequest::get('action', ''), ['file', 'action'])) {
            $back_url = waRequest::cookie('back_url', $this->backend_url);
        } else {
            $path_parts = explode('/', waRequest::get('path', ''));
            array_pop($path_parts);
            $back_url = $path_parts ? '?path=' . implode('/', $path_parts) : $this->backend_url;
        }

        if ($back_url != $this->backend_url) {
            $current_url = wa()->getConfig()->getCurrentUrl();
            $back_url_contents = $current_url_contents = null;
            parse_str(str_replace($this->backend_url, '', $back_url), $back_url_contents);
            parse_str(str_replace($this->backend_url, '', $current_url), $current_url_contents);

            if ($back_url_contents && $current_url_contents) {
                if ($back_url_contents == $current_url_contents) {
                    //same keys & values regardless of order
                    $back_url = $this->backend_url;
                }
            }
        }

        return $back_url;
    }

    private function getViewModes()
    {
        $view_modes = [
            [
                'action' => '',
                'mode'   => 'directory',
                'url'    => '?mode=directory',
                'title'  => _w('By directory'),
                'sort'   => 0,
                'icon'   => logsHelper::getIconClass('folder'),
            ],
            [
                'action' => 'files',
                'mode'   => 'updatetime',
                'url'    => '?action=files&mode=updatetime',
                'title'  => _w('By update time'),
                'sort'   => 1,
                'icon'   => logsHelper::getIconClass('clock'),
            ],
            [
                'action' => 'files',
                'mode'   => 'size',
                'url'    => '?action=files&mode=size',
                'title'  => _w('By file size'),
                'sort'   => 2,
                'icon'   => logsHelper::getIconClass('sort-amount-down'),
            ],
        ];

        foreach ($view_modes as &$view_mode) {
            $view_mode['selected'] = $view_mode['action'] == waRequest::get('action', '') && $view_mode['mode'] == waRequest::get('mode', '')
                || (!waRequest::get('action', '') && $view_mode['mode'] == 'directory');
        }

        usort($view_modes, [$this, 'sortViewModes']);

        return $view_modes;
    }

    private function getItemActions()
    {
        $result = [];

        $this->addShowPremiumAction($result);

        $action = waRequest::get('action', '');
        $path = waRequest::get('path', '');

        $published_items = $this->getPublishedItems($action, $path);

        if ($action == 'file') {
            $result['download'] = [
                'title' => _w('Download'),
                'icon_class' => logsHelper::getIconClass('download'),
                'url' => '?action=download&path=' . waString::escape($path),
                'marker' => true,
            ];
        }

        if (strlen($path)) {
            if ($this->getUser()->getRights($this->getAppId(), 'rename')) {
                $result['rename'] = [
                    'title' => _w('Rename'),
                    'icon_class' => logsHelper::getIconClass('edit'),
                    'data' => [
                        'path' => $path,
                        'redirect' => 1,
                    ],
                ];
            }

            if ($this->getUser()->getRights($this->getAppId(), 'delete_files')) {
                $result['delete'] = [
                    'title' => _w('Delete'),
                    'icon_class' => logsHelper::getIconClass('trash'),
                    'data' => [
                        'path' => $path,
                        'return-url' => $this->getBackUrl(),
                    ],
                ];
            }
        }

        if ($action == 'file') {
            $result['search'] = [
                'title' => _w('Find text in this file'),
                'icon_class' => logsHelper::getIconClass('search'),
                'enabled' => is_string(logsHelper::getFileContentsSearchQuery()),
                'data' => [
                    'path' => $path,
                    'query' => waString::escape(waRequest::get('query', '')),
                    'search-cancel-url' => $this->getFileSearchCancelUrl(),
                ],
            ];

            $tracking_enabled = (new logsTrackedModel())->isTracked($path);
            $tracking_title = $tracking_enabled ? _w('Track changes (enabled)') : _w('Track changes');
            $tracking_title_simple = _w('Track changes');
            $tracking_title_enabled = _w('Track changes (enabled)');

            $result['track'] = [
                'title' => $tracking_title,
                'title_simple' => $tracking_title_simple,
                'title_enabled' => $tracking_title_enabled,
                'icon_class' => logsHelper::getIconClass('flag'),
                'enabled' => $tracking_enabled,
                'marker' => true,
                'data' => [
                    'path' => $path,
                ],
            ];

            if ($this->getUser()->getRights($this->getAppId(), 'publish_files')) {
                $file_published = in_array($path, $published_items);
                $file_published_title = $file_published ? _w('Public link (enabled)') : _w('Public link');
                $file_published_title_simple = _w('Public link');
                $file_published_title_enabled = _w('Public link (enabled)');

                $result['published'] = [
                    'title' => $file_published_title,
                    'title_simple' => $file_published_title_simple,
                    'title_enabled' => $file_published_title_enabled,
                    'icon_class' => logsHelper::getIconClass('globe'),
                    'enabled' => $file_published,
                    'marker' => true,
                    'data' => [
                        'path' => $path,
                    ],
                ];
            }
        }

        if ($this->getUser()->getRights($this->getAppId(), 'view_phpinfo')) {
            $phpinfo_published = in_array('//phpinfo//', $published_items);
            $phpinfo_title = $phpinfo_published ? _w('PHP info (public link enabled)') : _w('PHP info');
            $phpinfo_title_simple = _w('PHP info');
            $phpinfo_title_enabled = _w('PHP info (public link enabled)');

            $result['phpinfo'] = [
                'title' => $phpinfo_title,
                'title_simple' => $phpinfo_title_simple,
                'title_enabled' => $phpinfo_title_enabled,
                'common' => true,
                'icon_class' => logsHelper::getIconClass('info-circle'),
                'enabled' => $phpinfo_published,
            ];
        }

        $result['settings'] = [
            'title' => _w('Settings'),
            'common' => true,
            'icon_class' => logsHelper::getIconClass('cogs'),
            'data' => [
                'hide-data' => json_encode(logsHelper::getHideSetting(null, true)),
            ],
        ];

        return $result;
    }

    private function getPublishedItems($action, $path)
    {
        $published_model = new logsPublishedModel();
        $published_paths = [
            '//phpinfo//',
        ];

        if ($action == 'file') {
            $published_paths[] = $path;
        }

        $result = $published_model
            ->select('path')
            ->where('path IN (s:paths)', [
                'paths' => $published_paths,
            ])
            ->fetchAll(null, true);

        return $result;
    }

    private function addShowPremiumAction(&$config)
    {
        if (logsLicensing::check()->hasPremiumLicense()) {
            return;
        }

        $icon_classes = [logsHelper::getIconClass('star')];

        if (!waRequest::isMobile()) {
            $contact_settings_model = new waContactSettingsModel();
            $times_skipped = (int) $contact_settings_model->getOne($this->getUserId(), $this->getAppId(), 'premium_skipped');

            if ($times_skipped < self::SHOW_PREMIUM_STEP - 1) {
                $icon_classes[] = 'opacity-0';
                $times_skipped++;
            } else {
                $times_skipped = 0;
            }
        }

        $config['premium'] = [
            'title' => _w('More useful features with Logs+ license'),
            'icon_class' => implode(' ', $icon_classes),
        ];

        if (!waRequest::isMobile()) {
            $contact_settings_model->set($this->getUserId(), $this->getAppId(), 'premium_skipped', $times_skipped);
        }
    }

    private function getFileSearchCancelUrl()
    {
        return is_string(logsHelper::getFileContentsSearchQuery())
            ? '?' . http_build_query(array_filter(
                waRequest::get(),
                function ($param) {
                    return in_array($param, ['action', 'path']);
                },
                ARRAY_FILTER_USE_KEY
            ))
            : null;
    }
}

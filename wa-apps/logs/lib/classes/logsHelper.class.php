<?php

class logsHelper
{
    const LOGS_SIZE_NOTIFICATION_LIMIT = 1073741824;    //large is 1GB or more
    const FILE_LIST_MAX_TEXT_LENGTH = 300;
    const SEARCH_HIGHLIGHTING_START = '<mark>';
    const SEARCH_HIGHLIGHTING_END = '</mark>';

    public static function hideData($log)
    {
        if (self::getHideSetting('root_path', true)) {
            $log = str_replace(
                wa()->getConfig()->getRootPath() . DIRECTORY_SEPARATOR,
                '',
                $log
            );
        }

        if (self::getHideSetting('ip', true)) {
            $log = preg_replace(
                '/\b(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\b/',
                'xxx.xxx.xxx.xxx',
                $log
            );
        }

        return $log;
    }

    public static function getHideSetting($key = null, $for_env = false)
    {
        static $values = null;

        if (is_null($values)) {
            $setting = (string) wa()->getSetting('hide');

            if (!strlen($setting)) {
                $values = [
                    'root_path' => ['frontend'],
                    'ip' => ['frontend'],
                ];
            } else {
                $values = json_decode($setting, true);
            }
        }

        if ($key) {
            if (!isset($values[$key])) {
                $values[$key] = [];
            }

            if ($for_env) {
                $result = in_array(wa()->getEnv(), $values[$key]);
            } else {
                $result = $values[$key];
            }
        } else {
            if ($for_env) {
                $result = [];
                foreach ($values as $setting_key => $value) {
                    if (in_array(wa()->getEnv(), $value)) {
                        $result[] = $setting_key;
                    }
                }
                sort($result);
            } else {
                $result = $values;
            }
        }

        return $result;
    }

    public static function getLogsBackendUrl($absolute = true)
    {
        return wa()->getRootUrl($absolute) . wa()->getConfig()->getBackendUrl() . '/logs/';
    }

    public static function getTotalLogsSize()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $files = self::listDir(self::getLogsRootPath(), true);
        $result = 0;

        foreach ($files as $file) {
            $result += filesize(self::getLogsRootPath() . DIRECTORY_SEPARATOR . $file);
        }

        return $result;
    }

    public static function formatSize($size)
    {
        static $locale_decimal_point;

        if (is_null($locale_decimal_point)) {
            $locale_info = waLocale::getInfo(wa()->getLocale());
            $locale_decimal_point = ifset($locale_info['decimal_point'], '.');
        }

        $result = waFiles::formatSize($size, '%0.2f', _w('B,KB,MB,GB'));
        if ($locale_decimal_point != '.') {
            $result = str_replace('.', $locale_decimal_point, $result);
        }
        return $result;
    }

    public static function isLargeSize($value)
    {
        return $value >= self::LOGS_SIZE_NOTIFICATION_LIMIT;
    }

    public static function inCloud()
    {
        return wa()->appExists('hosting') && wa()->getConfig()->getAppConfig('hosting')->getInfo('vendor') == 'webasyst';
    }

    public static function listDir($dir, $recursive = false)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $result = waFiles::listdir($dir, $recursive);

        return array_filter($result, [__CLASS__, 'filterFiles']);
    }

    public static function getFilesByText($query_or_queries, $file_paths = null)
    {
        if (!logsLicensing::check()->hasPremiumLicense()) {
            return;
        }

        $result = [];
        $queries = (array) $query_or_queries;
        $file_paths = $file_paths ? $file_paths : self::listdir(self::getLogsRootPath(), true);

        foreach ($file_paths as $file_path) {
            if (!logsItemFile::check($file_path)) {
                continue;
            }

            $file = @fopen(self::getFullPath($file_path), 'r');

            if (!$file) {
                @fclose($file);
                continue;
            }

            $file_found_lines = [];

            while (!feof($file)) {
                $line = fgets($file, 4096);

                foreach ($queries as $query) {
                    if (mb_strpos(mb_strtolower($line), $query) !== false) {
                        $file_found_lines[$query] = $line;
                    }
                }
            }

            foreach ($file_found_lines as $found_line_query => $found_line) {
                $cut_line = mb_substr($found_line, 0, self::FILE_LIST_MAX_TEXT_LENGTH);

                if (mb_strpos(mb_strtolower($cut_line), $found_line_query) === false) {
                    $cut_line = mb_substr($found_line, 0, mb_strpos($found_line, $found_line_query) + mb_strlen($found_line_query));
                }

                if (mb_strlen($cut_line) < mb_strlen($found_line)) {
                    $cut_line .= '...';
                }

                $result[$found_line_query][$file_path] = self::getHighlightedString($cut_line, $found_line_query);
            }

            fclose($file);
        }

        return is_array($query_or_queries) ? $result : reset($result);
    }

    public static function getAllowedPaths($paths)
    {
        $allowed_paths = array_filter($paths, function ($path) {
            return logsItemFile::check($path);
        });

        return $allowed_paths;
    }

    private static function filterFiles($path)
    {
        return basename($path) != '.htaccess';
    }

    public static function getHighlightedString($string, $query, $replacement = '<mark %s>$1</mark>', $escape = true)
    {
        $style_attribute = in_array(wa()->getEnv(), ['backend', 'frontend'])
            ? 'class="highlighted"'
            : 'style="background-color: #fea;"';

        // waString::escape() is used to return escaped HTML code for display in browser / email client
        return preg_replace(
            sprintf(
                '~(%s)~ui',
                preg_quote($escape ? waString::escape($query) : $query, '~')
            ),
            sprintf($replacement, $style_attribute),
            $escape ? waString::escape($string) : $string
        );
    }

    public static function getQueryHighlightingPattern($query)
    {
        return '~'
            . self::getQueryHighlightingPatternPart(self::SEARCH_HIGHLIGHTING_START)
            . sprintf(
                '(%s)',
                implode('', array_map(function (string $letter) {
                    return sprintf(
                        '(?:%s|%s)',
                        self::getQueryHighlightingPatternPart(mb_strtoupper($letter)),
                        self::getQueryHighlightingPatternPart(mb_strtolower($letter))
                    );
                }, mb_str_split($query)))
            )
            . self::getQueryHighlightingPatternPart(self::SEARCH_HIGHLIGHTING_END)
            . '~';
    }

    private static function getQueryHighlightingPatternPart($string)
    {
        return preg_quote(wa()->getView()->fetch('string:' . sprintf(trim(<<<HTML
            {sprintf('%%s', '%s')|escape:'hexentity'}
        HTML), $string)), '~');
    }

    public static function getQueryHighlightingReplacement()
    {
        return sprintf(
            '%s$1%s',
            self::SEARCH_HIGHLIGHTING_START,
            self::SEARCH_HIGHLIGHTING_END
        );
    }

    /**
     * @param boolean $reverse True turns / to DIRECTORY_SEPARATOR, false turns DIRECTORY_SEPARATOR to /
     */
    public static function normalizePath($path, $reverse = false)
    {
        if (DIRECTORY_SEPARATOR != '/') {
            if ($reverse) {
                return str_replace('/', DIRECTORY_SEPARATOR, $path);
            } else {
                return str_replace(DIRECTORY_SEPARATOR, '/', $path);
            }
        } else {
            return $path;
        }
    }

    public static function log($message)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        waLog::log($message, 'logs/errors.log');
    }

    public static function getFullPath($path)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $full_paths = [];

        if (!isset($full_paths[$path])) {
            $full_paths[$path] = self::getLogsRootPath() . DIRECTORY_SEPARATOR . self::normalizePath($path, true);
        }

        return $full_paths[$path];
    }

    public static function getPathParts($path, $with_logs_root = true)
    {
        $path_parts = explode('/', $path);
        $name = array_pop($path_parts);

        if (is_dir(self::getFullPath($path))) {
            $name .= '/';
        }

        $folder = implode('/', $path_parts);
        $logs_root = $with_logs_root ? 'wa-log/' : '';

        return [
            'folder' => $logs_root . (strlen($folder) ? $folder . '/' : ''),
            'name' => $name,
        ];
    }

    public static function generatePassword()
    {
        return substr(preg_replace('/\W/', '', waString::uuid()), 0, 16);
    }

    public static function redirect()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        wa()->getResponse()->redirect(wa()->getAppUrl());
    }

    public static function getIconClass($icon)
    {
        return 'fas fa-' . $icon;
    }

    public static function getLogsRootPath()
    {
        static $path;

        if (!$path) {
            $path = realpath(wa()->getConfig()->getPath('log'));
        }

        return $path;
    }

    public static function hideCountBadge()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $apps_count = wa()->getStorage()->read('apps-count');
        unset($apps_count['logs']);
        wa()->getStorage()->set('apps-count', $apps_count);
    }

    public static function updateUpdatedFilesBadgeValue()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $updated_files_count = (new logsTrackedModel)->getUpdatedFilesCount();
        $apps_data = wa()->getStorage()->read('apps-count');

        if ($updated_files_count) {
            $apps_data['logs']['count'] = $updated_files_count;
        } else {
            unset($apps_data['logs']);
        }

        wa()->getStorage()->set('apps-count', $apps_data);
    }

    public static function getProductNameBySlug($slug, $with_type_name = false)
    {
        $slug = strval($slug);

        if (!strlen($slug)) {
            return '';
        }

        $type_names = [
            'apps' => _w('“%s” app'),
            'plugins' => _w('“%s” plugin (%s)'),
            'widgets' => _w('“%s” widget (%s)'),
        ];

        $installed_products = self::getInstalledProducts();
        $product = ifset($installed_products, $slug, []);

        if ($product) {
            if ($with_type_name) {
                if ($product['type'] == 'apps') {
                    if ($slug == 'webasyst') {
                        $product_name = _w('Webasyst framework');
                    } else {
                        $product_name = sprintf($type_names[$product['type']], $product['title']);
                    }
                } else {
                    $app_id = $product['app'];

                    if ($app_id == 'wa-plugins/payment') {
                        $app_name = _w('payment');
                    } elseif ($app_id == 'wa-plugins/shipping') {
                        $app_name = _w('shipping');
                    } elseif ($app_id == 'wa-plugins/sms') {
                        $app_name = _w('SMS');
                    } else {
                        $app_name = $installed_products[$app_id]['title'];
                    }

                    $product_name = sprintf($type_names[$product['type']], $product['title'], $app_name);
                }
            } else {
                $product_name = $product['title'];
            }
        }

        return ifset($product_name, '');
    }

    public static function getInstalledProducts()
    {
        static $products;

        if (!$products) {
            wa('installer');

            $apps = installerHelper::getInstaller()->getApps([
                'installed'    => true,
                'requirements' => false,
                'action'       => false,
                'system'       => true,
                'status'       => false,
                'filter'       => [],
            ]);

            $local_apps = wa()->getApps();

            foreach ($apps as $app_id => &$app) {
                if (isset($local_apps[$app_id])) {
                    $app = [
                        'title' => $local_apps[$app_id]['name'],
                        'type' => 'apps',
                    ];
                } else {
                    $app = [
                        'title' => _wd($app_id, $app['name']),
                        'type' => 'apps',
                    ];
                }
            }
            unset($app);

            $products = $apps;
            $products['webasyst'] = [
                'type' => 'apps',
                'title' => _w('Webasyst'),
            ];

            $extras_types = [
                'plugins',
                'widgets',
            ];

            $extras_types_options = [
                'plugins' => [
                    'system' => true,
                ],
            ];

            $extras_options = [
                'local'            => true,
                'status'           => false,
                'installed'        => true,
                'translate_titles' => true,
            ];

            foreach ($extras_types as $extras_type) {
                $app_ids = array_keys($apps);

                $extras = installerHelper::getInstaller()->getExtras(
                    $app_ids,
                    $extras_type,
                    $extras_options + ifset($extras_types_options[$extras_type], [])
                );

                foreach ($extras as $app_id => $extras_batch) {
                    foreach ($extras_batch as $extras_type_entries) {
                        foreach ($extras_type_entries as $extras_type_entry) {
                            if (!empty($extras_type_entry['name'])) {
                                $slug = $extras_type_entry['slug'];
                                $products[$slug] = [
                                    'title' => $extras_type_entry['name'],
                                    'type' => $extras_type,
                                    'app' => $app_id,
                                ];
                            }
                        }
                    }
                }
            }

            $products = array_filter($products, function ($product, $slug) {
                return !($product['type'] == 'apps' && strpos($slug, '/') !== false);
            }, ARRAY_FILTER_USE_BOTH);
        }

        return $products;
    }

    public static function getIconHtml($class)
    {
        return '<i class="' . $class . '"></i>';
    }

    public static function mustDisplayPremiumPromo($feature)
    {
        static $hidden_promos;

        if (is_null($hidden_promos)) {
            try {
                $hidden_promos = waUtils::jsonDecode(
                    wa()->getUser()->getSettings('logs', 'hidden_premium_promos', ''),
                    true
                );
            } catch (Throwable $throwable) {
                $hidden_promos = [];
            }
        }

        return !in_array($feature, $hidden_promos)
            && !logsLicensing::check()->hasPremiumLicense();
    }

    public static function getFileContentsSearchQuery()
    {
        $query = waRequest::get('query', '');

        return (
            strlen(trim($query))
            && wa()->getEnv() == 'backend'
        ) ? $query : null;
    }

    public static function getParamsRemovedUrl(array $params)
    {
        return '?' . http_build_query(array_filter(
            waRequest::get(),
            function ($param) use ($params) {
                return !in_array($param, $params);
            },
            ARRAY_FILTER_USE_KEY
        ));
    }
}

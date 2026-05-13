<?php

class logsProductSlugSearch
{
    public function getFiles($slug, $is_app_addon = false)
    {
        if (!logsLicensing::check()->hasPremiumLicense()) {
            return;
        }

        $result = [];

        foreach ($this->getAllFilesPaths() as $file_path) {
            $is_matching = $this->checkFileBySlugPattern($result, $file_path, $slug);

            if (!$is_matching) {
                $is_matching = $this->checkRootFile($result, $file_path, $slug);
            }

            if (!$is_matching) {
                $this->checkFileByCleanSlug($result, $file_path, $slug, $is_app_addon);
            }

            $this->skipAppAddonsFiles($result, $file_path, $slug, $is_app_addon);
        }

        return $result;
    }

    private function skipAppAddonsFiles(&$result, $file_path, $app_slug, $is_app_addon)
    {
        if (!$is_app_addon && $this->isApp($app_slug)) {
            foreach ($this->getFilePathParts($file_path, $is_app_addon) as $file_path_part) {
                if ($this->isCommonFilePathPart($file_path_part)) {
                    continue;
                }

                if ($this->installedAddonsExist($app_slug, $file_path_part)) {
                    $addons_files = (array) $this->getFiles($file_path_part, $app_slug != 'webasyst');
                    $result = array_diff($result, $addons_files);
                }
            }
        }
    }

    private function isCommonFilePathPart($file_path_part)
    {
        $common_file_path_parts = [
            'plugin',
            'widget',
            'payment',
            'shipping',
            'sms',
        ];

        $result = in_array($file_path_part, $common_file_path_parts);

        return $result;
    }

    private function checkFileByCleanSlug(&$result, $file_path, $slug, $is_app_addon)
    {
        $is_matching = false;

        if (!$this->isAppPlugin($slug) || $this->isCorrectPluginFile($file_path, $is_app_addon, $slug)) {
            if (in_array($this->getCleanSlug($slug), $this->getFileNamePartsFiltered($file_path, $is_app_addon))) {
                $result[] = $file_path;
                $is_matching = true;
            }
        }

        return $is_matching;
    }

    private function getFileNamePartsFiltered($file_path, $is_app_addon)
    {
        $result = array_filter(
            $this->getFileNameParts($file_path, $is_app_addon),
            $this->getFilePathPartsFilterFunction()
        );

        return $result;
    }

    private function isCorrectPluginFile($file_path, $is_app_addon, $slug)
    {
        $file_path_parts = $this->getFilePathParts($file_path, $is_app_addon);

        $result = $this->isAppPlugin($slug) && in_array($this->getAppId($slug), $file_path_parts)
            || $this->isSystemPlugin($slug) && in_array($this->getSystemPluginType($slug), $file_path_parts);

        return $result;
    }

    private function getFilePathParts($file_path, $is_app_addon)
    {
        static $result = [];

        $addon_key = intval($is_app_addon);

        if (!isset($result[$file_path][$addon_key])) {
            $result[$file_path][$is_app_addon] = array_filter(
                array_merge(
                    explode('/', $file_path),
                    $this->getFileNameParts($file_path, $is_app_addon)
                ),
                $this->getFilePathPartsFilterFunction()
            );
        }

        return $result[$file_path][$is_app_addon];
    }

    private function checkRootFile(&$result, $file_path, $slug)
    {
        $is_matching = false;

        if ($this->isRootFile($file_path)) {
            if ($slug == 'webasyst') {
                if (in_array(strtolower($this->getFilePathInfo($file_path, 'filename')), $this->getWebasystSlugs())) {
                    $result[] = $file_path;
                    $is_matching = true;
                }
            } else {
                if (strtolower($this->getFilePathInfo($file_path, 'filename')) == $this->getCleanSlug($slug)
                    && !in_array($this->getCleanSlug($slug), $this->getWebasystSlugs())
                ) {
                    $result[] = $file_path;
                    $is_matching = true;
                }
            }
        }

        return $is_matching;
    }

    private function getFileNameParts($file_path, $is_app_addon)
    {
        static $result = [];

        $addon_key = intval($is_app_addon);

        if (!isset($result[$file_path][$addon_key])) {
            $file_name_parts_regexp = $is_app_addon
                ? '~(\.|(?=[A-Z]))~'
                : '~(\.|-|_|(?=[A-Z]))~';

                $result[$file_path][$is_app_addon] = preg_split($file_name_parts_regexp, $this->getFilePathInfo($file_path, 'filename'));
        }

        return $result[$file_path][$is_app_addon];
    }

    private function isRootFile($file_path)
    {
        static $result = [];

        if (!array_key_exists($file_path, $result)) {
            $result[$file_path] = strpos($this->getFilePathInfo($file_path, 'dirname'), '/') === false;
        }

        return $result[$file_path];
    }

    private function getFilePathInfo($file_path, $part)
    {
        static $result = [];

        if (!array_key_exists($file_path, $result)) {
            $result[$file_path] = pathinfo($file_path);
        }

        return $result[$file_path][$part];
    }

    private function checkFileBySlugPattern(&$result, $file_path, $slug)
    {
        if (preg_match($this->getSlugPattern($slug), $file_path)) {
            $result[] = $file_path;
            $is_matching = true;
        } else {
            $is_matching = false;
        }

        return $is_matching;
    }

    private function getFilePathPartsFilterFunction()
    {
        return function($part) {
            return substr($part, -4, 4) != '.log';
        };
    }

    private function getWebasystSlugs()
    {
        return [
            'error',
            'db',
            'sms',
            'cli',
            'meta_update',
            'signup',
            'wa-installer',
        ];
    }

    private function getAllFilesPaths()
    {
        return logsHelper::listDir(logsHelper::getLogsRootPath(), true);
    }

    private function getSlugPattern($slug)
    {
        static $result = [];

        if (!array_key_exists($slug, $result)) {
            if ($this->isSimpleSlug($slug)) {
                $slug_pattern = '~(^|\/)' . wa_make_pattern($slug) . '(\/|\.log)~';
            } else {
                $slug_versions = [
                    $slug,
                ];

                if ($this->isAppPlugin($slug)) {
                    $slug_versions[] = str_replace('/plugins/', '/', $slug);
                }

                if ($this->isSystemPlugin($slug)) {
                    if ($this->getSystemPluginType($slug) == 'payment') {
                        $slug_versions[] = 'payment/' . $this->getCleanSlug($slug) . 'Payment.log';
                    } elseif ($this->getSystemPluginType($slug) == 'shipping') {
                        $slug_versions[] = 'shipping/' . $this->getCleanSlug($slug) . 'Shipping.log';
                    }
                }

                $slug_pattern = '~(^|\/)(' . implode('|', array_map('wa_make_pattern', $slug_versions)) . ')(\/|\.log)~';
            }

            $result[$slug] = $slug_pattern;
        }

        return $result[$slug];
    }

    private function getSystemPluginType($slug)
    {
        static $result = [];

        if (!array_key_exists($slug, $result)) {
            $result[$slug] = $this->isSystemPlugin($slug) ? $this->getProductSlugPart($slug, 1) : null;
        }

        return $result[$slug];
    }

    private function getAppId($slug)
    {
        static $result = [];

        if (!array_key_exists($slug, $result)) {
            $result[$slug] = $this->isAppPlugin($slug) ? $this->getProductSlugPart($slug, 0) : null;
        }

        return $result[$slug];
    }

    private function isSystemPlugin($slug)
    {
        static $result = [];

        if (!array_key_exists($slug, $result)) {
            $result[$slug] = !$this->isApp($slug) && $this->getProductSlugPart($slug, 0) == 'wa-plugins/';
        }

        return $result[$slug];
    }

    private function isAppPlugin($slug)
    {
        static $result = [];

        if (!array_key_exists($slug, $result)) {
            $result[$slug] = !$this->isApp($slug) && $this->getProductSlugPart($slug, 1) == 'plugins';
        }

        return $result[$slug];
    }

    private function isApp($slug)
    {
        static $result = [];

        if (!array_key_exists($slug, $result)) {
            $result[$slug] = $this->isSimpleSlug($slug);
        }

        return $result[$slug];
    }

    private function getCleanSlug($slug)
    {
        static $result = [];

        if (!array_key_exists($slug, $result)) {
            $result[$slug] = $this->isSimpleSlug($slug) ? $slug : $this->getProductSlugPart($slug, 2);
        }

        return $result[$slug];
    }

    private function isSimpleSlug($slug)
    {
        static $result = [];

        if (!array_key_exists($slug, $result)) {
            $result[$slug] = strpos($slug, '/') === false;
        }

        return $result[$slug];
    }

    private function getProductSlugPart($slug, $part)
    {
        static $result = [];

        if (!isset($result[$slug])) {
            $slug_parts = strpos($slug, '/') === false ? [] : explode('/', $slug);
            $result[$slug] = $slug_parts;
        }

        return ifset($result, $slug, $part, null);
    }

    private function installedAddonsExist($app_slug, $clean_slug)
    {
        static $result = [
            'webasyst' => [],
            'apps' => [],
        ];

        $apps_key = $app_slug == 'webasyst' ? 'webasyst' : 'apps';

        if (!isset($result[$apps_key][$clean_slug])) {
            $installed_products = logsHelper::getInstalledProducts();

            $result[$apps_key][$clean_slug] = array_filter(
                $installed_products,
                function($product_slug) use ($app_slug, $clean_slug) {
                    $is_app = strpos($product_slug, '/') === false;

                    return $app_slug == 'webasyst'
                        ? $is_app && $clean_slug == $product_slug && $product_slug != 'webasyst'
                        : !$is_app && $clean_slug == $this->getProductSlugPart($product_slug, 2);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return count($result[$apps_key][$clean_slug]) > 0;
    }
}

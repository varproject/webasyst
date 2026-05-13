<?php

class devapiHelper
{
    const APP_ID = 'devapi';

    public static function setLog($message, $type = 'error', $data = null, $source = true)
    {
        if ($source) {
            if (is_object($source)) {
                $message = $message . PHP_EOL . $source->getTraceAsString();
            } elseif (function_exists('debug_backtrace')) {
                $bt = debug_backtrace(1)[0];
                $source = $bt['file'] . ' (' . $bt['line'] . ')' . ($message ? PHP_EOL . '------------------' : '');
                $message = $source . PHP_EOL . $message;
            }
        }
        $file = sprintf('%s/%s', self::APP_ID, self::APP_ID) . ($type ? ('.' . $type) : '') . '.log';
        waLog::log($message, $file);
        if ($data) {
            waLog::dump($data, $file);
        }
    }


    public static function getVueComponent($name, $subfolder = null, $params = [])
    {
        $path = null;
        $view = wa()->getView();
        if (waRequest::isMobile()) {
            $path = sprintf('templates/components/%s%s%s.mobile.vue', $subfolder ? $subfolder . '/' : '', $name . '/', $name);
            if (!file_exists(wa()->getAppPath($path))) $path = null;
        }
        if (!$path) {
            $path = sprintf('templates/components/%s%s%s.vue', $subfolder ? $subfolder . '/' : '', $name . '/', $name);
        }
        $template = wa()->getAppPath($path);
        if ($params) $view->assign($params);
        switch ($name) {
            case 'settings':
                $apps = wa()->getApps();
                $view->assign('appHelpdesk', isset($apps['helpdesk']) ? 1 : 0);
                break;
            case 'accounts':
                $transactionTypes = devapiWebasystApi::WA_TRANSACTION_TYPES;
                unset($transactionTypes['period']);
                $templates = [
                    'remote' => new devapiRemote(),
                    'target' => new devapiAccountCashTarget(),
                    'rule' => new devapiAccountCashRule()
                ];
                $view->assign([
                    'templates' => json_encode($templates),
                    'transactionTypes' => json_encode($transactionTypes)
                ]);
                break;
            case 'products':
                $prices = include(wa()->getAppPath('lib/config/data/waPrices.php'));
                $view->assign('prices', json_encode($prices));
                break;
        }
        return $view->fetch($template);
    }

    public static function setAnnouncement($message, $user_ids)
    {
        $params = [
            'app_id' => 'ozonstat',
            'text' => $message,
            'datetime' => date('Y-m-d H:i:s')
        ];
        try {
            (new waAnnouncementModel())->insert($params);
        } catch (Exception $e) {
            self::setLog($e->getMessage());
        }
    }

    public static function saveConfig($config)
    {
        return waUtils::varExportToFile($config, wa()->getConfig()->getConfigPath('config.php'));
    }

    public static function sortArray($data, $field, $index = 'asc', $with_keys = false)
    {
        if (!$data) return $data;
        $result = array();
        foreach ($data as $key => $value) {
            $tmp_arr[$key] = $value[$field];
        }
        if ($index == 'asc') {
            asort($tmp_arr, SORT_LOCALE_STRING);
        } else {
            arsort($tmp_arr, SORT_LOCALE_STRING);
        }

        $keys = array_keys($tmp_arr);
        foreach ($keys as $key) {
            if ($with_keys) {
                $result[$key] = $data[$key];
            } else {
                $result[] = $data[$key];
            }
        }
        return $result;
    }

    public static function getCashClientId(string $url)
    {
        return 'devapi-cash-' . hash('crc32', $url);
    }

    public static function transliterate($str, $strict = true)
    {
        $str = preg_replace('/\s+/u', '-', $str);
        if ($str) {
            foreach (waLocale::getAll() as $lang) {
                $str = waLocale::transliterate($str, $lang);
            }
        }
        $str = preg_replace('/[^a-zA-Z0-9_-]+/', '', $str);
        if ($strict && !strlen($str)) {
            $str = date('Ymd');
        }
        return strtolower($str);
    }
}
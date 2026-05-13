<?php

final class shopLkPluginRedirect
{
    /**
     * Выполняет редирект назад.
     *
     * Варианты вызова:
     * - redirectBack(true)             => на текущий URI без GET-параметров
     * - redirectBack('/target/url')    => на конкретный URL
     * - redirectBack()                 => цепочка Referer -> AppUrl
     *
     * @param string|bool $target Исходный URL или флаг очистки текущего URI
     * @return void
     */
    public static function redirectBack($target = ''): void
    {
        $return_url = '';

        if ($target === true) {
            // Если передан true, берем текущий путь без параметров
            $return_url = (string) parse_url((string) waRequest::server('REQUEST_URI', ''), PHP_URL_PATH);
        } elseif (is_string($target)) {
            // Если передана строка, используем её
            $return_url = $target;
        }

        // 1. Нормализуем URL
        $target_url = self::resolveTargetUrl($return_url);

        // 2. Fallback на Referer, если URL пустой (не был передан или был некорректным)
        if ($target_url === '') {
            $target_url = self::resolveTargetUrl((string) waRequest::server('HTTP_REFERER', ''));
        }

        // 3. Валидация безопасности и защита от бесконечного цикла
        if (!self::isLocalUrl($target_url) || self::isCurrentUrl($target_url)) {
            $target_url = wa()->getAppUrl('shop');
        }

        wa()->getResponse()->redirect($target_url);
    }

    /**
     * Нормализует переданный URL.
     *
     * Правила:
     * - protocol-relative URL сбрасываются (возвращается пустая строка);
     * - абсолютный URL возвращается как есть;
     * - абсолютный путь от домена возвращается как есть;
     * - относительный путь преобразуется в путь от URL приложения.
     *
     * @param string $url
     * @return string
     */
    private static function resolveTargetUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        // Превентивная защита от protocol-relative URL на этапе нормализации
        if (strpos($url, '//') === 0) {
            return '';
        }

        if (isset($url[0]) && $url[0] === '/') {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return '';
        }

        if (isset($parts['scheme'])) {
            return $url;
        }

        return wa()->getAppUrl('shop') . ltrim($url, '/');
    }

    /**
     * Проверяет, что URL является внутренним и безопасным.
     *
     * Разрешаются:
     * - относительные URL;
     * - абсолютные пути;
     * - абсолютные http/https URL только текущего домена (определяется через роутинг Webasyst).
     *
     * @param string $url
     * @return bool
     */
    private static function isLocalUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        // Двойная проверка на protocol-relative
        if (strpos($url, '//') === 0) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme'])) {
            $scheme = strtolower((string) $parts['scheme']);

            if (!in_array($scheme, ['http', 'https'], true)) {
                return false;
            }
        }

        if (!isset($parts['host'])) {
            return true; // Локальный путь без указания хоста считаем безопасным
        }

        $target_host = strtolower((string) $parts['host']);

        // Используем API Webasyst для надежного получения текущего домена
        // Это корректно работает с алиасами и мультидоменностью
        $current_domain = strtolower((string) wa()->getRouting()->getDomain());

        // Очистка от портов на всякий случай
        $current_domain = preg_replace('/:\d+$/', '', $current_domain);
        $target_host    = preg_replace('/:\d+$/', '', $target_host);

        return $current_domain !== '' && $target_host === $current_domain;
    }

    /**
     * Проверяет, ведет ли URL на текущую страницу.
     *
     * Сравнение выполняется по path + ?query.
     *
     * @param string $url
     * @return bool
     */
    private static function isCurrentUrl(string $url): bool
    {
        $current_url = self::normalizeComparableUrl((string) waRequest::server('REQUEST_URI', ''));
        $target_url  = self::normalizeComparableUrl($url);

        return $current_url !== '' && $current_url === $target_url;
    }

    /**
     * Нормализует URL для сравнения:
     * path + ?query
     *
     * @param string $url
     * @return string
     */
    private static function normalizeComparableUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return '';
        }

        $path  = isset($parts['path']) ? (string) $parts['path'] : '';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return $path . $query;
    }
}

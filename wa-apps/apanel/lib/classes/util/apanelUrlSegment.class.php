<?php

/**
 * apanelUrlSegment
 *
 * Назначение:
 * - извлекать сегменты запроса в универсальном формате;
 * - автоматически работать в двух режимах:
 *   1) Web/API: разбирать URL после префикса приложения;
 *   2) CLI/Cron: разбирать аргументы командной строки.
 *
 * Зависимости:
 * - waRequest;
 * - waSystem;
 * - ifset().
 *
 * Инварианты:
 * - результат all() кешируется в пределах одного запроса;
 * - get() работает с позициями 1..N;
 * - asDot() возвращает сегменты через точку;
 * - asString() возвращает сегменты через слеш.
 *
 * Побочные эффекты:
 * - в web-режиме использует REQUEST_URI;
 * - в CLI-режиме использует $_SERVER['argv'].
 *
 * Ошибки:
 * - если контекст не позволяет корректно определить сегменты, возвращается пустой массив;
 * - при недоступности URL приложения возвращается пустой массив.
 */
final class apanelUrlSegment
{
    public const APP_ID = 'apanel';

    /**
     * Внутренний кеш вычисленных сегментов.
     *
     * @var array<int, string>|null
     */
    private static ?array $cache = null;

    /**
     * Возвращает сегмент по позиции.
     *
     * Позиции начинаются с 1.
     *
     * @param int $position
     * @param string|null $default
     * @return string|null
     */
    public static function get(int $position, ?string $default = null): ?string
    {
        if ($position < 1) {
            return $default;
        }

        $segments = self::all();

        return $segments[$position - 1] ?? $default;
    }

    /**
     * Возвращает сегмент как целое число.
     * 
     * Если сегмент не является целым числом или отсутствует, 
     * возвращает $default.
     *
     * @param int $position Позиция (с 1)
     * @param int|null $default Значение по умолчанию
     * @return int|null
     */
    public static function getInt(int $position, ?int $default = null): ?int
    {
        // Позиции меньше 1 невалидны по условию
        if ($position < 1) {
            return $default;
        }

        $segments = self::all();
        $value = $segments[$position - 1] ?? null;

        // Если сегмента нет, сразу возвращаем дефолт
        if ($value === null) {
            return $default;
        }

        // Строго проверяем, что в строке только целое число
        $validated = filter_var($value, FILTER_VALIDATE_INT);

        // Если валидация не прошла, filter_var вернет false
        return ($validated !== false) ? $validated : $default;
    }



    /**
     * Возвращает все сегменты запроса.
     *
     * Поведение:
     * - CLI: пропускает системные аргументы и возвращает хвост argv;
     * - Web: возвращает сегменты URL после префикса приложения.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (PHP_SAPI === 'cli') {
            $segments = array_slice(ifset($_SERVER['argv'], []), 2);

            self::$cache = array_values(array_filter(array_map(static function ($value) {
                return (string) $value;
            }, $segments), static function ($value) {
                return $value !== '';
            }));

            return self::$cache;
        }

        $request_uri = (string) waRequest::server('REQUEST_URI', '');
        $path_only   = (string) parse_url($request_uri, PHP_URL_PATH);

        if ($path_only === '') {
            return self::$cache = [];
        }

        try {
            $app_url = wa()->getAppUrl(self::APP_ID);
        } catch (Exception $e) {
            return self::$cache = [];
        }

        $prefix = (string) parse_url((string) $app_url, PHP_URL_PATH);

        if ($prefix === '') {
            return self::$cache = [];
        }

        $prefix     = rtrim($prefix, '/') . '/';
        $prefix_len = strlen($prefix);

        if (strncmp($path_only, $prefix, $prefix_len) !== 0) {
            return self::$cache = [];
        }

        $relative = trim(substr($path_only, $prefix_len), '/');

        if ($relative === '') {
            return self::$cache = [];
        }

        self::$cache = array_values(array_filter(array_map(static function ($value) {
            return rawurldecode((string) $value);
        }, explode('/', $relative)), static function ($value) {
            return $value !== '';
        }));

        return self::$cache;
    }

    /**
     * Возвращает все сегменты в dot-формате.
     *
     * @return string
     */
    public static function asDot(): string
    {
        return implode('.', self::all());
    }

    /**
     * Возвращает все сегменты в snace-формате.
     *
     * @return string
     */
    public static function asSnace(): string
    {
        return implode('_', self::all());
    }

    /**
     * Возвращает все сегменты как строку пути.
     *
     * @param bool $with_trailing_slash
     * @return string
     */
    public static function asString(bool $with_trailing_slash = true): string
    {
        $path = implode('/', self::all());

        if ($path === '') {
            return '';
        }

        return $with_trailing_slash ? $path . '/' : $path;
    }

    /**
     * Сбрасывает внутренний кеш.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$cache = null;
    }
}

<?php

final class shopLkPluginStringCase
{
    /**
     * Преобразовывает строку в camelCase.
     *
     * Примеры:
     * - category_is_open      => categoryIsOpen
     * - category.is.open      => categoryIsOpen
     * - category-is/open      => categoryIsOpen
     * - CategoryIsOpen        => categoryIsOpen
     * - category-is-open      => categoryIsOpen
     * - category is open      => categoryIsOpen
     *
     * @param string $string
     * @param bool $drop_numeric_suffix Удалять ли хвост вида "_15".
     * @return string
     */
    public static function toCamelCase(string $string, bool $drop_numeric_suffix = false): string
    {
        $words = self::normalizeWords($string, $drop_numeric_suffix);

        if (empty($words)) {
            return '';
        }

        $first = array_shift($words);

        return $first . implode('', array_map('ucfirst', $words));
    }

    /**
     * Преобразовывает строку в PascalCase.
     *
     * @param string $string
     * @param bool $drop_numeric_suffix Удалять ли хвост вида "_15".
     * @return string
     */
    public static function toPascalCase(string $string, bool $drop_numeric_suffix = false): string
    {
        $words = self::normalizeWords($string, $drop_numeric_suffix);

        if (empty($words)) {
            return '';
        }

        return implode('', array_map('ucfirst', $words));
    }

    /**
     * Преобразовывает строку в snake_case.
     *
     * @param string $string
     * @param bool $drop_numeric_suffix Удалять ли хвост вида "_15".
     * @return string
     */
    public static function toSnakeCase(string $string, bool $drop_numeric_suffix = false): string
    {
        $words = self::normalizeWords($string, $drop_numeric_suffix);

        if (empty($words)) {
            return '';
        }

        return implode('_', $words);
    }

    /**
     * Преобразовывает строку в kebab-case.
     *
     * @param string $string
     * @param bool $drop_numeric_suffix Удалять ли хвост вида "_15".
     * @return string
     */
    public static function toKebabCase(string $string, bool $drop_numeric_suffix = false): string
    {
        $words = self::normalizeWords($string, $drop_numeric_suffix);

        if (empty($words)) {
            return '';
        }

        return implode('-', $words);
    }

    /**
     * Преобразовывает строку в UPPER_SNAKE_CASE.
     *
     * @param string $string
     * @param bool $drop_numeric_suffix Удалять ли хвост вида "_15".
     * @return string
     */
    public static function toUpperSnakeCase(string $string, bool $drop_numeric_suffix = false): string
    {
        $snake = self::toSnakeCase($string, $drop_numeric_suffix);

        if ($snake === '') {
            return '';
        }

        return strtoupper($snake);
    }

    /**
     * Преобразовывает строку в Title Case.
     *
     * @param string $string
     * @param bool $drop_numeric_suffix Удалять ли хвост вида "_15".
     * @return string
     */
    public static function toTitleCase(string $string, bool $drop_numeric_suffix = false): string
    {
        $words = self::normalizeWords($string, $drop_numeric_suffix);

        if (empty($words)) {
            return '';
        }

        return implode(' ', array_map('ucfirst', $words));
    }

    /**
     * Преобразовывает строку к виду:
     * - первый символ в верхнем регистре;
     * - остальные символы в нижнем регистре;
     * - исходная структура строки сохраняется.
     *
     * Примеры:
     * - A_ТУТ_ЛЮБАЯ_СТРОКА => A_тут_любая_строка
     * - user-TYPE          => User-type
     * - ЭЛЕМЕНТ_123        => Элемент_123
     *
     * @param string $string
     * @return string
     */
    public static function toCapitalizedCase(string $string): string
    {
        $string = trim($string);

        if ($string === '') {
            return '';
        }

        $string = mb_strtolower($string, 'UTF-8');
 
        return mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr($string, 1, mb_strlen($string, 'UTF-8'), 'UTF-8');
    }

    /**
     * Нормализует входную строку в массив слов нижнего регистра.
     *
     * Поддерживаемые входные форматы:
     * - snake_case
     * - kebab-case
     * - camelCase
     * - PascalCase
     * - строки с пробелами
     * - смешанные строки с разделителями "_", "-", ".", "/", "\".
     *
     * @param string $string
     * @param bool $drop_numeric_suffix
     * @return array<int, string>
     */
    protected static function normalizeWords(string $string, bool $drop_numeric_suffix = false): array
    {
        $string = trim($string);

        if ($string === '') {
            return [];
        }

        if ($drop_numeric_suffix) {
            $string = preg_replace('/(?:[_\-\.\s\/\\\\]+)\d+$/', '', $string);
        }

        $string = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $string);
        $string = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $string);
        $string = str_replace(['_', '-', '.', '/', '\\'], ' ', $string);
        $string = waLocale::transliterate($string);
        $string = mb_strtolower($string, 'UTF-8');
        $string = preg_replace('/[^a-z0-9\s]+/', ' ', $string);
        $string = preg_replace('/\s+/', ' ', trim($string));

        if ($string === '') {
            return [];
        }

        return explode(' ', $string);
    }
}

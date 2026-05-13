<?php

/**
 * apanelGetIcon
 *
 * Назначение:
 * - возвращать HTML иконки в одном из трёх форматов:
 *   1) raw SVG;
 *   2) SVG-иконка из спрайта;
 *   3) font-иконка через тег <i>.
 *
 * Зависимости:
 * - waString;
 * - waSystem;
 * - DOMDocument;
 * - ifset().
 *
 * Инварианты:
 * - публичные методы всегда возвращают строку;
 * - svg() работает только со спрайтом приложения;
 * - rawSvg() возвращает только разрешённый SVG-код;
 * - class, size и sprite path нормализуются перед вставкой в HTML.
 *
 * Побочные эффекты:
 * - обращается к static URL приложения для построения href спрайта;
 * - выполняет парсинг SVG через DOMDocument.
 *
 * Ошибки:
 * - некорректные входные значения приводят к возврату пустой строки;
 * - PHP Error/TypeError не скрываются.
 */
final class apanelGetIcon
{
    protected const DEFAULT_SPRITE = 'img/icon-sprite.svg';
    protected const DEFAULT_WIDTH  = '24';
    protected const DEFAULT_HEIGHT = '24';

    /**
     * Возвращает HTML иконки с автоматическим определением типа.
     *
     * Поддерживаемые варианты:
     * - raw SVG-код;
     * - идентификатор SVG-символа из спрайта;
     * - class font-иконки или HTML <i class=""></i>.
     *
     * @param string $icon
     * @param array<string, mixed> $params
     * @return string
     */
    public static function html(string $icon, array $params = []): string
    {
        $icon = trim($icon);

        if (empty($icon)) {
            return '';
        }

        if (stripos($icon, '<svg') !== false) {
            return self::rawSvg($icon);
        }

        if (strpos($icon, '<') === false && strpos($icon, 'apanel-icon') !== false) {
            return self::svg($icon, $params);
        }

        return self::font($icon, $params);
    }

    /**
     * Возвращает SVG-иконку из спрайта.
     *
     * Поддерживаемые параметры:
     * - sprite => путь к SVG-спрайту относительно static;
     * - width  => ширина;
     * - height => высота;
     * - class  => дополнительные CSS-классы.
     *
     * @param string $symbol_id
     * @param array<string, mixed> $params
     * @return string
     */
    public static function svg(string $symbol_id, array $params = []): string
    {
        $symbol_id = self::sanitizeSymbolId($symbol_id);

        if (empty($symbol_id)) {
            return '';
        }

        $static_url = wa('apanel')->getAppStaticUrl('apanel', false);
        $sprite     = self::sanitizeSpritePath(ifset($params['sprite'], self::DEFAULT_SPRITE));
        $width      = self::sanitizeSize(ifset($params['width'], self::DEFAULT_WIDTH), self::DEFAULT_WIDTH);
        $height     = self::sanitizeSize(ifset($params['height'], self::DEFAULT_HEIGHT), self::DEFAULT_HEIGHT);
        $class      = self::sanitizeClass(ifset($params['class'], ''));

        $class_attr = trim('apanel-icon svg ' . $class);
        $href       = $static_url . $sprite . '#' . $symbol_id;

        return
            '<svg class="' . waString::escape($class_attr) . '" width="' . waString::escape($width) . '" height="' . waString::escape($height) . '">' .
            '<use href="' . waString::escape($href) . '"></use>' .
            '</svg>';
    }

    /**
     * Возвращает font-иконку в виде тега <i>.
     *
     * Поддерживаемые входные значения:
     * - class-строка;
     * - HTML <i class=""></i>.
     *
     * @param string $icon
     * @param array<string, mixed> $params
     * @return string
     */
    public static function font(string $icon, array $params = []): string
    {
        $class = self::extractFontClass($icon);

        if (empty($class)) {
            return '';
        }

        $extra_class = self::sanitizeClass(ifset($params['class'], ''));

        if (!empty($extra_class)) {
            $class .= ' ' . $extra_class;
        }

        return '<i class="' . waString::escape($class) . '"></i>';
    }

    /**
     * Возвращает безопасный raw SVG.
     *
     * @param string $svg
     * @return string
     */
    public static function rawSvg(string $svg): string
    {
        return self::sanitizeSvg($svg);
    }

    /**
     * Извлекает и нормализует CSS-класс font-иконки.
     *
     * Поддерживаемые входные значения:
     * - class-строка;
     * - HTML <i class=""></i>.
     *
     * @param string $icon
     * @return string
     */
    protected static function extractFontClass(string $icon): string
    {
        $icon = trim($icon);

        if (empty($icon)) {
            return '';
        }

        if (stripos($icon, '<i') !== false) {
            if (preg_match('/class=["\']([^"\']+)["\']/', $icon, $matches)) {
                return self::sanitizeClass($matches[1]);
            }

            return '';
        }

        if (strpos($icon, '<') !== false) {
            return '';
        }

        return self::sanitizeClass($icon);
    }

    /**
     * Возвращает безопасный SVG-код по whitelist-разбору DOM.
     *
     * Разрешаются только базовые SVG-теги и атрибуты, достаточные для иконок.
     *
     * @param string $svg
     * @return string
     */
    protected static function sanitizeSvg(string $svg): string
    {
        $svg = trim($svg);

        if (empty($svg)) {
            return '';
        }

        $doc  = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);

        $loaded = $doc->loadHTML(
            '<?xml encoding="utf-8" ?><div>' . $svg . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$loaded) {
            return '';
        }

        $allowed_tags = array_flip([
            'svg',
            'g',
            'path',
            'circle',
            'rect',
            'polyline',
            'line',
            'polygon',
            'ellipse',
            'defs',
            'symbol',
            'use',
        ]);

        $allowed_attrs = array_flip([
            'class',
            'viewbox',
            'width',
            'height',
            'fill',
            'stroke',
            'stroke-width',
            'd',
            'points',
            'x',
            'y',
            'cx',
            'cy',
            'r',
            'rx',
            'ry',
            'xmlns',
            'href',
            'xlink:href',
        ]);

        foreach (iterator_to_array($doc->getElementsByTagName('*')) as $node) {
            $tag = strtolower($node->nodeName);

            if ($tag === 'div') {
                continue;
            }

            if (!isset($allowed_tags[$tag])) {
                $node->parentNode->removeChild($node);
                continue;
            }

            if (!$node->hasAttributes()) {
                continue;
            }

            $remove = [];

            foreach ($node->attributes as $attr) {
                $name  = strtolower($attr->name);
                $value = trim($attr->value);

                if (!isset($allowed_attrs[$name])) {
                    $remove[] = $attr->name;
                    continue;
                }

                if (strpos($name, 'on') === 0) {
                    $remove[] = $attr->name;
                    continue;
                }

                if ($name === 'href' || $name === 'xlink:href') {
                    $clean = preg_replace('/[\x00-\x1F\x7F\s]+/', '', $value);

                    if (
                        stripos($clean, 'javascript:') === 0 ||
                        stripos($clean, 'data:') === 0 ||
                        stripos($clean, 'vbscript:') === 0
                    ) {
                        $remove[] = $attr->name;
                    }
                }
            }

            foreach ($remove as $attr_name) {
                $node->removeAttribute($attr_name);
            }
        }

        $wrapper = $doc->getElementsByTagName('div')->item(0);

        if (!$wrapper) {
            return '';
        }

        $result = '';

        foreach ($wrapper->childNodes as $child) {
            if (strtolower($child->nodeName) === 'svg') {
                $result .= $doc->saveHTML($child);
            }
        }

        return $result;
    }

    /**
     * Нормализует CSS-классы.
     *
     * @param string $class
     * @return string
     */
    protected static function sanitizeClass(string $class): string
    {
        $class = preg_replace('/[^a-zA-Z0-9_\-\s]/', ' ', $class);
        $class = preg_replace('/\s+/', ' ', trim($class));

        return (string) $class;
    }

    /**
     * Нормализует идентификатор SVG-символа.
     *
     * @param string $symbol_id
     * @return string
     */
    protected static function sanitizeSymbolId(string $symbol_id): string
    {
        $symbol_id = trim($symbol_id);
        $symbol_id = preg_replace('/[^a-zA-Z0-9_\-:.]/', '', $symbol_id);

        return (string) $symbol_id;
    }

    /**
     * Нормализует размер SVG-атрибута.
     *
     * Поддерживаются значения:
     * - 24
     * - 24px
     * - 1em
     * - 1rem
     * - 100%
     *
     * @param string $size
     * @param string $default
     * @return string
     */
    protected static function sanitizeSize(string $size, string $default): string
    {
        $size = trim($size);

        if (empty($size)) {
            return $default;
        }

        if (!preg_match('/^\d+(px|em|rem|%)?$/i', $size)) {
            return $default;
        }

        return $size;
    }

    /**
     * Нормализует путь к SVG-спрайту.
     *
     * Разрешается только относительный путь внутри static-каталога приложения.
     *
     * @param string $path
     * @return string
     */
    protected static function sanitizeSpritePath(string $path): string
    {
        $path = trim($path);

        if (empty($path)) {
            return self::DEFAULT_SPRITE;
        }

        if (strpos($path, '..') !== false || preg_match('~^(https?:)?//~i', $path)) {
            return self::DEFAULT_SPRITE;
        }

        return ltrim($path, '/');
    }
}

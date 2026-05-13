<?php

final class shopLkPluginSlugify
{
    /**
     * Генерирует безопасный slug из строки.
     *
     * Правила:
     * - выполняется транслитерация через waLocale;
     * - строка переводится в нижний регистр;
     * - все символы, кроме a-z и 0-9, заменяются на разделитель;
     * - если результат начинается с цифры, добавляется префикс "n";
     * - итоговая длина ограничивается параметром limit.
     *
     * @param string $str Исходная строка.
     * @param bool $strict Если true, при пустом результате возвращается fallback-значение.
     * @param int $limit Максимальная длина результата.
     * @param string $separator Разделитель слов: "-" для URL, "_" для кодов.
     * @return string
     */
    public static function generate(string $str, bool $strict = true, int $limit = 255, string $separator = '-'): string
    {
        $str       = trim($str);
        $separator = $separator === '_' ? '_' : '-';
        $fallback  = 'entity' . $separator . date('Ymd');

        if ($str === '') {
            return $strict ? $fallback : '';
        }

        if ($limit < 1) {
            $limit = 255;
        }

        $str = waLocale::transliterate($str);
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[^a-z0-9]+/', $separator, $str);
        $str = trim((string) $str, $separator);

        if ($str !== '' && preg_match('/^\d/', $str)) {
            $str = 'n' . $separator . $str;
        }

        if (mb_strlen($str, 'UTF-8') > $limit) {
            $str = mb_substr($str, 0, $limit, 'UTF-8');
            $str = rtrim($str, $separator);
        }

        if ($str === '') {
            return $strict ? $fallback : '';
        }

        return $str;
    }
}

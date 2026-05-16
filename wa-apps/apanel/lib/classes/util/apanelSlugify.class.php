<?php

/**
 * apanelSlugify
 *
 * Назначение:
 * - генерировать безопасный slug из произвольной строки;
 * - приводить строку к формату из символов a-z, 0-9 и разделителя;
 * - использоваться для кодов, URL-частей и системных идентификаторов.
 *
 * Зависимости:
 * - waLocale;
 * - mbstring.
 *
 * Инварианты:
 * - результат содержит только a-z, 0-9 и разделитель "-" или "_";
 * - если slug начинается с цифры, добавляется префикс "n";
 * - при strict=true пустой результат заменяется fallback-значением;
 * - длина результата не превышает limit.
 *
 * Побочные эффекты:
 * - использует текущую локаль Webasyst для транслитерации.
 *
 * Ошибки:
 * - исключения не перехватываются;
 * - при пустой строке или пустом результате метод возвращает fallback либо пустую строку по контракту strict.
 */
final class apanelSlugify
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

<?php

trait CabinetModelPrefixFieldsTrait
{
    /**
     * Генерирует список полей таблицы в формате:
     *   alias.field AS prefix_field
     * или, если $prefix === '', просто:
     *   alias.field
     *
     * @param string        $alias   SQL-алиас таблицы ('o', 'c', ...)
     * @param string|null   $prefix  Префикс для имён полей:
     *                                - null  → использовать $alias (o_id)
     *                                - ''    → без AS, оставить имена как есть
     *                                - 'c'   → c_id, c_name, ...
     * @param waModel|null  $model   Модель, чьи поля использовать (по умолчанию $this)
     * @return string
     */
    public function prefixFields(string $alias, ?string $prefix = null, $model = null): string
    {
        if (!$model instanceof waModel) {
            $model = $this;
        }

        $fields = $model->getMetadata();
        $out    = [];

        // Режим БЕЗ префикса: просто alias.field
        if ($prefix === '') {
            foreach ($fields as $field => $info) {
                $out[] = "{$alias}.{$field}";
            }

            return implode(",\n                ", $out);
        }

        // Обычный режим: alias.field AS prefix_field
        $prefix = $prefix ?? $alias;

        foreach ($fields as $field => $info) {
            $out[] = "{$alias}.{$field} AS {$prefix}_{$field}";
        }

        return implode(",\n                ", $out);
    }
}

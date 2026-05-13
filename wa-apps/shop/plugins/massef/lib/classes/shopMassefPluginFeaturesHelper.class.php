<?php

/**
 * Хелпер для массовой работы с характеристиками товаров.
 *
 * Задачи:
 *  - потоковая загрузка фич по типам товаров
 *  - исключение служебных «divider»
 *  - подгрузка значений через shopFeatureModel::getValues()
 *  - поддержка составных (2D/3D) фич
 *  - нормализация значений (Boolean/Range/Dimension) и единиц измерения
 *  - подготовка структуры для шаблона формы
 *
 * Оптимизирован для больших объёмов (50k+ фич).
 * 
 * @author  Petrosian Vagram
 * @since   1.0.0 (2025-11-02)
 */
class shopMassefPluginFeaturesHelper
{
    /**
     * Загружает все характеристики с их значениями и единицами измерения.
     *
     * @param array<int>|int|null $type_ids          Список ID типов (или null — все)
     * @param string              $status            Статус ('public'|'private'|'all')
     * @param int                 $limit_per_feature Лимит значений на одну фичу
     * @param bool                $normalize         Нормализовать значения и добавить единицы
     * @return array                                 Ассоц. массив фич, подготовленный для шаблона
     */
    public function getAllFeaturesWithValues($type_ids = null, $status = 'public', $limit_per_feature = 200, $normalize = true)
    {
        $feature_model      = new shopFeatureModel();
        $type_feature_model = new shopTypeFeaturesModel();

        $type_ids   = array_filter((array)$type_ids, fn($v) => (int)$v > 0);
        $batch_size = 500;
        $offset     = 0;
        $all_features = [];

        // === 1. Получаем список ID фичей для указанных типов ===
        if ($type_ids) {
            $feature_ids = $type_feature_model
                ->select('DISTINCT feature_id')
                ->where('type_id IN (' . implode(',', array_map('intval', $type_ids)) . ')')
                ->fetchAll(null, true);
        } else {
            $feature_ids = $feature_model
                ->select('id')
                ->fetchAll(null, true);
        }

        if (!$feature_ids) {
            return [];
        }

        $feature_ids = array_values($feature_ids);
        $total = count($feature_ids);

        // === 2. Потоковая загрузка фичей пакетами ===
        while ($offset < $total) {
            $batch_ids = array_slice($feature_ids, $offset, $batch_size);

            // Загружаем данные по фичам, исключая type = 'divider'
            $sql = "SELECT * FROM shop_feature 
                WHERE id IN (" . implode(',', $batch_ids) . ")
                AND type != 'divider'";

            if ($status !== 'all') {
                $sql .= " AND status = s:status";
            }

            $features = $feature_model->query($sql, ['status' => $status])->fetchAll('code');
            if (!$features) {
                $offset += $batch_size;
                continue;
            }

            // === 3. Подгружаем значения фичей (оптимизированный метод Webasyst) ===
            $features = $feature_model->getValues($features, $limit_per_feature > 0 ? $limit_per_feature : null);

            // === 4. Подгружаем дочерние 2D/3D фичи (и тут исключаем divider) ===
            $parent_ids = array_column($features, 'id');
            if ($parent_ids) {
                $childs = $feature_model
                    ->select('*')
                    ->where('parent_id IN (' . implode(',', $parent_ids) . ')')
                    ->where("type != 'divider'")
                    ->fetchAll('code');

                if ($childs) {
                    $childs = $feature_model->getValues($childs, $limit_per_feature ?: null);
                    $features += $childs;
                }
            }

            // === 5. Преобразуем значения в читаемый формат ===
            if ($normalize) {
                $features = $this->normalizeFeatureValues($features);
            }

            // === 6. Добавляем в общий результат ===
            $all_features += $features;

            unset($features, $childs, $batch_ids);
            gc_collect_cycles();

            $offset += $batch_size;
        }

        // === 7. Форматируем перед возвратом ===
        return $this->formatForForm($all_features);
    }

    /**
     * Нормализация значений характеристик:
     *  - Boolean → «Да»/«Нет»
     *  - Range   → «begin–end {unit}», извлечение единиц измерения
     *  - Dimension → «value {unit/unit_name}», полный набор доступных units
     *  - Очистка пустых/служебных значений
     *
     * @param array $features
     * @return array
     */
    protected function normalizeFeatureValues(array $features): array
    {
        foreach ($features as &$feature) {
            $normalized = [];
            $unit = '';
            $unit_name = '';
            $units = [];

            foreach ($feature['values'] as $key => $v) {

                // Простые текстовые / числовые значения
                if (is_string($v) || is_numeric($v)) {
                    $val = trim((string)$v);
                    if ($val !== '') $normalized[$key] = $val;
                    continue;
                }

                // Булевы значения
                if ($v instanceof shopBooleanValue) {
                    $value = $this->extractPrivate($v, 'value');
                    $normalized[$key] = $value ? 'Да' : 'Нет';
                    continue;
                }

                // Диапазоны (range)
                if ($v instanceof shopRangeValue) {
                    $begin = $this->extractPrivate($v, 'begin');
                    $end   = $this->extractPrivate($v, 'end');

                    if ($begin instanceof shopDimensionValue) {
                        $unit = $this->extractPrivate($begin, 'unit');
                        $unit_name = $this->extractPrivate($begin, 'unit_name');
                        $units_raw = $this->extractPrivate($begin, 'units');

                        if (is_array($units_raw)) {
                            foreach ($units_raw as $u) {
                                $units[] = [
                                    'value' => $u['value'] ?? '',
                                    'label' => $u['title'] ?? '',
                                ];
                            }
                        }
                    }

                    $begin_val = $this->extractPrivate($begin, 'value');
                    $end_val   = $this->extractPrivate($end, 'value');
                    $unit_str  = $unit_name ? " {$unit_name}" : ($unit ?: '');

                    if ($begin_val !== null && $end_val !== null && $begin_val != $end_val) {
                        $normalized[$key] = "{$begin_val}–{$end_val}{$unit_str}";
                    } elseif ($begin_val !== null) {
                        $normalized[$key] = "{$begin_val}{$unit_str}";
                    }
                    continue;
                }

                // Измерения (dimension.*)
                if ($v instanceof shopDimensionValue) {
                    $val  = $this->extractPrivate($v, 'value');
                    $unit = $this->extractPrivate($v, 'unit');
                    $unit_name = $this->extractPrivate($v, 'unit_name');
                    $units_raw = $this->extractPrivate($v, 'units');

                    if (is_array($units_raw)) {
                        foreach ($units_raw as $u) {
                            $units[] = [
                                'value' => $u['value'] ?? '',
                                'label' => $u['title'] ?? '',
                            ];
                        }
                    }

                    $normalized[$key] = trim($val . ' ' . ($unit_name ?: $unit));
                    continue;
                }

                // Fallback для прочих объектов
                if (is_object($v) && property_exists($v, 'value')) {
                    $normalized[$key] = (string)$v->value;
                } else {
                    $normalized[$key] = (string)$v;
                }
            }

            // Если единицы не определены — пробуем определить по типу
            if (!$unit && preg_match('/dimension\./', $feature['type'])) {
                $unit = $feature['default_unit'] ?? '';
            }

            // Дополнительно тянем возможные единицы измерения через shopDimension
            if (preg_match('/(?:dimension|range)\.([a-z_]+)/', $feature['type'], $m)) {
                $dimension_type = $m[1];
                if (class_exists('shopDimension')) {
                    $units_raw = shopDimension::getUnits($dimension_type);
                    if (is_array($units_raw)) {
                        foreach ($units_raw as $u) {
                            $units[] = [
                                'value' => $u['value'] ?? '',
                                'label' => $u['title'] ?? '',
                            ];
                        }
                    }
                }
            }

            // Если unit_name пуст, пробуем достать его из shopDimension по коду unit
            if ($unit && !$unit_name && preg_match('/(?:dimension|range)\.([a-z_]+)/', $feature['type'], $m)) {
                $dimension_type = $m[1];
                if (class_exists('shopDimension')) {
                    $units_map = shopDimension::getUnits($dimension_type);
                    if (!empty($units_map[$unit]['title'])) {
                        $unit_name = $units_map[$unit]['title'];
                    }
                }
            }

            $feature['unit'] = $unit;
            $feature['unit_name'] = $unit_name;

            // Убираем дубли единиц по value
            if ($units) {
                $seen = [];
                $unique_units = [];
                foreach ($units as $u) {
                    if (!in_array($u['value'], $seen, true)) {
                        $seen[] = $u['value'];
                        $unique_units[] = $u;
                    }
                }
                $feature['units'] = $unique_units;
            } else {
                $feature['units'] = [];
            }

            $feature['values'] = array_values(array_filter($normalized));
        }

        unset($feature);
        return $features;
    }

    /**
     * Подготовка структуры для шаблона формы:
     *  - типы boolean/range/text/number → соответствующие виджеты
     *  - selectable → select/checkbox-группа (multiple)
     *  - составные 2D/3D → group с дочерними полями
     *
     * @param array $features Нормализованные фичи
     * @return array Структура полей формы
     */
    protected function formatForForm(array $features): array
    {
        $result = [];
        $children_map = [];

        // Сначала группируем дочерние (для 2D/3D)
        foreach ($features as $f) {
            if (!empty($f['parent_id'])) {
                $children_map[$f['parent_id']][] = $f;
            }
        }

        foreach ($features as $f) {
            if (!empty($f['parent_id'])) continue;

            $type = $f['type'];
            $format = 'text';
            $multiple = false;
            $options = [];

            // boolean
            if ($type === 'boolean') {
                $format = 'checkbox';
                $options = [
                    ['value' => 1, 'label' => 'Да'],
                    ['value' => 0, 'label' => 'Нет'],
                ];
            }
            // range
            elseif (str_starts_with($type, 'range.')) {
                $format = 'range';
            }
            // text
            elseif (str_contains($type, 'varchar') || str_contains($type, 'text')) {
                $format = 'text';
            }
            // numeric
            elseif (str_contains($type, 'double') || str_contains($type, 'dimension')) {
                $format = 'number';
            }

            // selectable (списки)
            if (!empty($f['selectable'])) {
                $format = 'select';
                $multiple = (bool)$f['multiple'];
                foreach ($f['values'] as $val) {
                    $options[] = ['value' => $val, 'label' => $val];
                }
            }

            // Групповые 2D/3D фичи
            if (preg_match('/^[23]d\./', $type)) {
                $format = 'group';
                $fields = [];
                $axis_labels = ['X', 'Y', 'Z'];

                if (!empty($children_map[$f['id']])) {
                    foreach ($children_map[$f['id']] as $i => $child) {
                        $fields[] = [
                            'id'         => $child['id'],
                            'code'       => $child['code'],
                            'label'      => $axis_labels[$i] ?? ('Value ' . ($i + 1)),
                            'type'       => $child['type'],
                            'format'     => 'number',
                        ];
                    }
                }

                $result[] = [
                    'id'         => $f['id'],
                    'code'       => $f['code'],
                    'name'       => $f['name'],
                    'type'       => $f['type'],
                    'format'     => 'group',
                    'fields'     => $fields,
                    'unit'       => $f['unit'] ?? '',
                    'unit_name'  => $f['unit_name'] ?? '',
                    'units'      => $f['units'] ?? [],
                ];
                continue;
            }

            // Обычные типы
            $result[] = [
                'id'         => $f['id'],
                'code'       => $f['code'],
                'name'       => $f['name'],
                'type'       => $f['type'],
                'format'     => $format,
                'multiple'   => $multiple,
                'options'    => $options,
                'values'     => $f['values'],
                'unit'       => $f['unit'],
                'unit_name'  => $f['unit_name'],
                'units'      => $f['units'],
            ];
        }

        return $result;
    }

    /**
     * Безопасное извлечение приватного свойства из объекта (Reflection fallback).
     *
     * @param mixed  $object   Источник
     * @param string $property Имя приватного свойства
     * @return mixed|null
     */
    private function extractPrivate($object, string $property)
    {
        if (!is_object($object)) return null;

        if (property_exists($object, $property)) {
            return $object->$property ?? null;
        }

        try {
            $ref = new ReflectionClass($object);
            if ($ref->hasProperty($property)) {
                $prop = $ref->getProperty($property);
                $prop->setAccessible(true);
                return $prop->getValue($object);
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }
}

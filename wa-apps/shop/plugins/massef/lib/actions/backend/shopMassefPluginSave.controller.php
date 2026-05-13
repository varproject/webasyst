<?php

/**
 * Сохранение характеристик без прогресса (одним запросом).
 *
 * Поддерживает:
 *  - очистку значений по clear[code]=1 (c учётом составных 2D/3D фич)
 *  - безопасное сохранение только «непустых» значений (sanitizeFeatures)
 *  - проверку прав (admin либо type.%)
 *
 * По завершении выполняется redirect на referer.
 * 
 * @author  Petrosian Vagram
 * @since   1.0.0 (2025-11-02)
 * 
 */
class shopMassefPluginSaveController extends waController
{
    /** @var string */
    protected $products_hash;

    /** @var shopMassefPluginCollectionHelper */
    protected $collection_helper;

    /** @var true|array Права: true=админ, иначе массив type.% */
    protected $rights;

    /**
     * Инициализация прав, products_hash и хелпера коллекции.
     *
     * @return void
     * @throws waException
     */
    public function preExecute()
    {
        // права
        $user = wa()->getUser();
        if ($user->isAdmin('shop')) {
            $this->rights = true;
        } else {
            $this->rights = $user->getRights('shop', 'type.%');
        }

        // хэш коллекции
        $this->products_hash = waRequest::post('products_hash', '', waRequest::TYPE_STRING);
        if (!$this->products_hash) {
            throw new waException('Не передан products_hash', 400);
        }

        // helper
        $this->collection_helper = new shopMassefPluginCollectionHelper($this->products_hash);
    }

    /**
     * Основной поток:
     *  1) очистка значений по clear[]
     *  2) фильтрация «пустых» значений и сохранение
     *  3) redirect на referer
     *
     * @return void
     */
    public function execute()
    {
        // Сырые данные с фронта
        $features_raw = waRequest::post('features', [], waRequest::TYPE_ARRAY);
        $clear        = waRequest::post('clear',    [], waRequest::TYPE_ARRAY);
        $referer      = waRequest::server('HTTP_REFERER', '', waRequest::TYPE_STRING);

        // 1) Очистки (строго по clear[])
        if ($clear) {
            $this->clearFeatures($clear);
        }

        // 2) Сохранение только непустых значений по затронутым кодам
        $features = $this->sanitizeFeatures($features_raw);

        if (!$features) {
            $this->redirect($referer);
        }

        $this->saveFeatures($features);
        $this->redirect($referer);
    }

    /**
     * Удаление значений характеристик, перечисленных в $clear[code] => 1.
     * Принимает как части 2D/3D (code.0), так и «родителя» 2D/3D (без .0/.1/.2),
     * расширяя удаление на все части.
     *
     * Удаление происходит на уровне product (sku_id = NULL).
     *
     * @param array $clear code => 1
     * @return void
     */
    protected function clearFeatures(array $clear)
    {
        $feature_model = new shopFeatureModel();

        // 1) Фичи по пришедшим кодам
        $features_by_code = $feature_model->getByCode(array_keys($clear), 'code');

        // 2) Расширить для композитов
        $expanded_codes = $clear; // code => 1
        foreach ($features_by_code as $code => $f) {

            // Пришла часть: добавить всех "братьев"
            if (!empty($f['parent_id'])) {
                $siblings = $feature_model->getByField('parent_id', (int)$f['parent_id'], 'code');
                foreach ($siblings as $s_code => $_) {
                    $expanded_codes[$s_code] = 1;
                }
                continue;
            }

            // Пришёл родитель 2d/3d: добавить всех детей и убрать родителя
            if (!empty($f['type']) && preg_match('~^[23]d\.~', $f['type'])) {
                $children = $feature_model->getByField('parent_id', (int)$f['id'], 'code');
                foreach ($children as $c_code => $_) {
                    $expanded_codes[$c_code] = 1;
                }
                unset($expanded_codes[$code]);
            }
        }

        // 3) Удалить по feature_id (product-level, sku_id = NULL)
        $features = $feature_model->getByCode(array_keys($expanded_codes));
        if (!$features) {
            return;
        }

        $feature_ids = array_map(static function ($f) {
            return (int)$f['id'];
        }, $features);
        $pf_model    = new shopProductFeaturesModel();

        $this->collection_helper->eachProductIdsAndTypes(500, function ($batch) use ($pf_model, $feature_ids) {
            $product_ids = [];

            foreach ($batch as $p) {
                $type_id = (int)$p['type_id'];
                if ($this->rights === true || !empty($this->rights[$type_id])) {
                    $product_ids[] = (int)$p['id'];
                }
            }

            if ($product_ids) {
                $pf_model->deleteByField([
                    'product_id' => $product_ids,
                    'feature_id' => $feature_ids,
                    'sku_id'     => null,
                ]);
            }
        });
    }

    /**
     * Сохраняет только «непустые» значения фич для всех товаров коллекции,
     * с учётом прав доступа (admin либо type.%).
     *
     * @param array $features Подготовленные значения (после sanitizeFeatures)
     * @return void
     */
    protected function saveFeatures(array $features)
    {
        $updated = 0;
        $errors  = 0;

        $this->collection_helper->eachProductIdsAndTypes(100, function ($batch) use ($features, &$updated, &$errors) {

            foreach ($batch as $p) {
                $pid     = (int)$p['id'];
                $type_id = (int)$p['type_id'];

                if ($this->rights !== true && empty($this->rights[$type_id])) {
                    continue;
                }

                try {
                    $product = new shopProduct($pid);

                    // Ядро корректно разберёт тип/единицы/2D/3D и перезапишет ТОЛЬКО эти коды
                    $product->save(['features' => $features], true);

                    $updated++;
                } catch (Exception $e) {
                    $errors++;
                    waLog::log("Massef save error pid={$pid}: " . $e->getMessage(), 'massef.features.error.log');
                }
            }
        });

        // waLog::log("massef: features saved={$updated}, errors={$errors}", 'massef.features.log');
    }

    /**
     * Санитизация входных данных features:
     * отбрасываются пустые значения, чтобы не провоцировать удаление ядром.
     * Возвращаются только реально затронутые коды.
     *
     * Пример исключаемых значений:
     *  - '' (пустая строка), null
     *  - ['value' => ''] (пустое значение)
     *  - [] / ['value'=>[]] / ['0'=>'', '1'=>''] (полностью пустые наборы)
     *
     * Поддерживаются диапазоны: ['value' => ['begin'=>..., 'end'| 'enf'=>...], 'unit'=>...]
     *
     * @param array $features
     * @return array
     */
    protected function sanitizeFeatures(array $features): array
    {
        $out = [];

        foreach ($features as $code => $val) {

            // 1) Не-массив — просто игнорим пустые строки/NULL
            if (!is_array($val)) {
                if ($val === '' || $val === null) {
                    continue;
                }
                $out[$code] = $val;
                continue;
            }

            // 2) Диапазон: ожидаем ['value' => ['begin'=>..., 'enf'|'end'=>...], 'unit' => ...]
            if (isset($val['value']) && is_array($val['value']) && (array_key_exists('begin', $val['value']) || array_key_exists('end', $val['value']) || array_key_exists('enf', $val['value']))) {

                // поддерживаются и 'end', и 'enf'
                $begin = $val['value']['begin'] ?? null;
                $end   = array_key_exists('enf', $val['value']) ? $val['value']['enf'] : ($val['value']['end'] ?? null);

                // считаем пустыми только '' и null, но НЕ '0'
                $is_begin_empty = ($begin === '' || $begin === null);
                $is_end_empty   = ($end   === '' || $end   === null);

                if ($is_begin_empty && $is_end_empty) {
                    // обе границы пустые — диапазон не шлём вовсе
                    continue;
                }

                // собираем обратно, не меняя схему ключей
                $value = ['begin' => $begin];
                if (array_key_exists('enf', $val['value'])) {
                    $value['enf'] = $end;
                } else {
                    $value['end'] = $end;
                }

                $item = ['value' => $value];
                if (isset($val['unit']) && $val['unit'] !== '' && $val['unit'] !== null) {
                    $item['unit'] = $val['unit'];
                }

                $out[$code] = $item;
                continue;
            }

            // 3) Общий случай с ключом 'value' (единицы измерения, 1d и т.п.)
            if (array_key_exists('value', $val)) {
                $v = $val['value'];
                if ($v === '' || $v === null) {
                    continue; // пусто — не шлём
                }
                $out[$code] = $val;
                continue;
            }

            // 4) Множественные/произвольные массивы — чистим пустые элементы
            $clean = [];
            foreach ($val as $k => $v) {
                if (is_array($v)) {
                    if (array_key_exists('value', $v)) {
                        if ($v['value'] === '' || $v['value'] === null) {
                            continue;
                        }
                        $clean[$k] = $v;
                    } else {
                        // неизвестная форма — оставляем только непустые скаляры
                        $vv = array_filter($v, static function ($x) {
                            return !($x === '' || $x === null);
                        });
                        if ($vv) {
                            $clean[$k] = $vv;
                        }
                    }
                } else {
                    if ($v === '' || $v === null) {
                        continue;
                    }
                    $clean[$k] = $v;
                }
            }

            if ($clean) {
                $out[$code] = $clean;
            }
        }

        return $out;
    }
}

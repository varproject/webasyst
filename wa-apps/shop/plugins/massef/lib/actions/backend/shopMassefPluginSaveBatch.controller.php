<?php

/**
 * Batch-сохранение характеристик с прогрессом и тайм-бюджетом.
 *
 * ВХОД (POST):
 * - products_hash (string) — хэш коллекции товаров (обязательно)
 * - features[] (array)     — значения характеристик (как в обычном save)
 * - clear[code]=1          — коды характеристик для очистки (опционально)
 * - offset (int)           — смещение курсора по упорядоченному списку товаров
 * - limit  (int)           — запрошенный размер батча (клиентский «потолок»)
 * - total  (int, опц.)     — заранее посчитанный total (чтобы не делать COUNT каждый раз)
 *
 * ВЫХОД (JSON через waJsonController):
 * {
 *   status: "ok",
 *   data: {
 *     done: bool,
 *     next_offset: int|null,
 *     processed: int,     // сколько позиций реально "съели" из потока в этом вызове
 *     done_count: int,    // offset+processed, ограниченный total
 *     progress: float,    // 0..100
 *     total: int
 *   }
 * }
 *
 * Особенности:
 * - Сервер сам режет входной батч «внутренними мини-батчами» и прерывается по тайм-бюджету,
 *   возвращая частичный прогресс (без обязательных правок фронта).
 * - OFFSET всегда увеличивается на число «позиций потока», а не на число реально сохранённых товаров.
 *   Это важно для предсказуемого курсора и совпадает с текущей логикой.
 *
 * @author  Petrosian Vagram
 * @since   1.0.0 (2025-11-02)
 */
class shopMassefPluginSaveBatchController extends waJsonController
{
    /** @var string */
    protected $products_hash;

    /** @var shopMassefPluginCollectionHelper */
    protected $collection_helper;

    /** @var array|true Права: true=админ, либо массив type.% */
    protected $rights;

    /** @var int внутренняя доразбивка на мини-батчи при сохранении */
    protected $inner_chunk = 100;

    /** @var int тайм-бюджет в миллисекундах на один HTTP-вызов */
    protected $time_budget_ms = 0;

    public function preExecute()
    {
        wa('shop');

        // Права
        $user = wa()->getUser();
        $this->rights = $user->isAdmin('shop') || $user->getRights('shop', 'type.%');

        // Хэш обязателен
        $this->products_hash = waRequest::post('products_hash', '', waRequest::TYPE_STRING);
        if (!$this->products_hash) {
            throw new waException('Не передан products_hash', 400);
        }

        // Коллекция
        $this->collection_helper = new shopMassefPluginCollectionHelper($this->products_hash);

        // Тайм-бюджет: берём аккуратный «safe» по max_execution_time, без требований к фронту
        $this->time_budget_ms = $this->detectTimeBudgetMs();
    }

    public function execute()
    {
        // Параметры батча
        $offset = waRequest::post('offset', 0, waRequest::TYPE_INT);
        $limit  = waRequest::post('limit',  300, waRequest::TYPE_INT);
        if ($limit <= 0) {
            $limit = 300;
        }

        // Данные формы
        $features_raw = waRequest::post('features', [], waRequest::TYPE_ARRAY);
        $clear_raw    = waRequest::post('clear',   [], waRequest::TYPE_ARRAY);

        // total: если фронт не передал — считаем (да, на миллионах это дорогая операция; фронт МОЖЕТ передавать total)
        $total = waRequest::post('total', 0, waRequest::TYPE_INT);
        if ($total <= 0) {
            $total = (int) $this->collection_helper->countAll();
        }

        // Пустая коллекция — сразу «готово»
        if ($total === 0) {
            $this->response = [
                'done'        => true,
                'next_offset' => null,
                'processed'   => 0,
                'done_count'  => 0,
                'progress'    => 100,
                'total'       => 0,
            ];
            return;
        }

        // Текущий «запрошенный» батч позиций потока
        $ids = iterator_to_array($this->collection_helper->yieldProductIdsOrdered($offset, $limit));
        if (!$ids) {
            $done_count = min($offset, $total);
            $this->response = [
                'done'        => true,
                'next_offset' => null,
                'processed'   => 0,
                'done_count'  => $done_count,
                'progress'    => round(($done_count / $total) * 100, 2),
                'total'       => $total,
            ];
            return;
        }

        $t0 = microtime(true);
        $consumed = 0;       // сколько позиций потока «съели» в ЭТОМ вызове
        $pm = new shopProductModel();

        // Сразу подготовим «feature_ids для очистки» (если есть clear) — один раз на вызов
        $clear_feature_ids = $this->expandClearToFeatureIds($clear_raw);

        // Санитизация значений — один раз на вызов
        $features = $this->sanitizeFeatures($features_raw);

        // Бежим по входному батчу мини-блоками (to reduce memory + следим за тайм-бюджетом)
        $i = 0;
        $ids_count = count($ids);

        while ($i < $ids_count) {
            // Внутренний мини-батч
            $chunk = array_slice($ids, $i, $this->inner_chunk);
            $i += count($chunk);
            $consumed += count($chunk); // это — «позиции потока», не путать с количеством реально сохранённых

            // За один запрос достанем type_id для фильтра по правам
            $allowed_map = $this->fetchAllowedProductsMap($pm, $chunk);

            // Очистка product-level фич по текущему мини-батчу
            if ($clear_feature_ids) {
                $this->deleteProductFeatures($clear_feature_ids, array_keys($allowed_map));
            }

            // Сохранение значений по текущему мини-батчу
            if ($features) {
                $this->saveFeatures($features, array_keys($allowed_map));
            }

            // Проверка тайм-бюджета — аккуратно выходим, отдав частичный прогресс
            if ($this->isTimeBudgetExceeded($t0)) {
                break;
            }
        }

        // Прогресс и курсор
        $done_count = min($offset + $consumed, $total);
        $next_offset = ($done_count < $total) ? $done_count : null;
        $progress = round(($done_count / $total) * 100, 2);

        $this->response = [
            'done'        => ($next_offset === null),
            'next_offset' => $next_offset,
            'processed'   => $consumed,
            'done_count'  => $done_count,
            'progress'    => $progress,
            'total'       => $total,
        ];
    }

    /**
     * Подбор тайм-бюджета на один HTTP-вызов.
     * Стараемся занять лишь часть max_execution_time, чтобы не упираться в таймауты,
     * и при этом не делать слишком короткие «тики».
     */
    protected function detectTimeBudgetMs(): int
    {
        $max_exec = (int) ini_get('max_execution_time'); // секунды; 0 — без ограничений
        if ($max_exec <= 0) {
            // «неизвестно/без лимита»: берём аккуратную константу
            return 1200; // ~1.2 секунды
        }
        // Берём около 60% от лимита, но в пределах [800ms; 5000ms]
        $ms = (int) floor($max_exec * 1000 * 0.6);
        if ($ms < 800) {
            $ms = 800;
        }
        if ($ms > 5000) {
            $ms = 5000;
        }
        return $ms;
    }

    protected function isTimeBudgetExceeded(float $t0): bool
    {
        $elapsed_ms = (microtime(true) - $t0) * 1000.0;
        return ($elapsed_ms >= $this->time_budget_ms);
    }

    /**
     * Превращает clear[code]=1 в массив feature_id[], учитывая 2D/3D и «родителей/части».
     * @param array $clear_raw
     * @return int[] feature_id[]
     */
    protected function expandClearToFeatureIds(array $clear_raw): array
    {
        if (!$clear_raw) {
            return [];
        }

        $feature_model = new shopFeatureModel();
        $features_by_code = $feature_model->getByCode(array_keys($clear_raw), 'code');

        // Расширяем: если часть — добавляем «братьев»; если родитель 2D/3D — заменяем на детей
        $expanded_codes = $clear_raw;
        foreach ($features_by_code as $code => $f) {

            if (!empty($f['parent_id'])) {
                // Пришла часть — добавим всех братьев
                $siblings = $feature_model->getByField('parent_id', (int) $f['parent_id'], 'code');
                foreach ($siblings as $s_code => $_) {
                    $expanded_codes[$s_code] = 1;
                }
                continue;
            }

            if (!empty($f['type']) && preg_match('~^[23]d\.~', $f['type'])) {
                // Пришёл родитель 2D/3D — заменяем на всех детей
                $children = $feature_model->getByField('parent_id', (int) $f['id'], 'code');
                foreach ($children as $c_code => $_) {
                    $expanded_codes[$c_code] = 1;
                }
                unset($expanded_codes[$code]);
            }
        }

        $features = $feature_model->getByCode(array_keys($expanded_codes));
        if (!$features) {
            return [];
        }

        $feature_ids = [];
        foreach ($features as $f) {
            $feature_ids[] = (int) $f['id'];
        }
        return $feature_ids;
    }

    /**
     * Возвращает карту разрешённых товаров: [product_id => type_id], учитывая права.
     * За один SQL по мини-батчу.
     */
    protected function fetchAllowedProductsMap(shopProductModel $pm, array $product_ids): array
    {
        if (!$product_ids) {
            return [];
        }
        $ids = implode(',', array_map('intval', $product_ids));
        $rows = $pm->query("SELECT id, type_id FROM shop_product WHERE id IN ({$ids})")->fetchAll();
        if (!$rows) {
            return [];
        }

        $allowed = [];
        if ($this->rights === true) {
            foreach ($rows as $r) {
                $allowed[(int) $r['id']] = (int) $r['type_id'];
            }
            return $allowed;
        }

        foreach ($rows as $r) {
            $type_id = (int) $r['type_id'];
            if (!empty($this->rights[$type_id])) {
                $allowed[(int) $r['id']] = $type_id;
            }
        }
        return $allowed;
    }

    /**
     * Удаление product-level значений характеристик для заданных товаров.
     * @param int[] $feature_ids
     * @param int[] $product_ids_allowed
     */
    protected function deleteProductFeatures(array $feature_ids, array $product_ids_allowed): void
    {
        if (!$feature_ids || !$product_ids_allowed) {
            return;
        }
        $pfm = new shopProductFeaturesModel();
        $pfm->deleteByField([
            'product_id' => $product_ids_allowed,
            'feature_id' => $feature_ids,
            'sku_id'     => null,
        ]);
    }

    /**
     * Сохранение «непустых» значений фич для разрешённых товаров.
     * @param array $features
     * @param int[] $product_ids_allowed
     */
    protected function saveFeatures(array $features, array $product_ids_allowed): void
    {
        if (!$features || !$product_ids_allowed) {
            return;
        }

        foreach ($product_ids_allowed as $pid) {
            try {
                $product = new shopProduct((int) $pid);
                // ядро корректно распарсит типы/единицы/2D/3D и перезапишет ТОЛЬКО переданные коды
                $product->save(['features' => $features], true);
            } catch (Exception $e) {
                waLog::log("massef batch save error pid={$pid}: " . $e->getMessage(), 'massef.features.error.log');
            }
        }
    }

    /**
     * Санитизация features: отбрасываем пустые значения.
     * Поддерживаем и 'end', и 'enf' (как в обычном контроллере).
     */
    protected function sanitizeFeatures(array $features): array
    {
        $out = [];

        foreach ($features as $code => $val) {

            // Не-массив — игнорируем '', null
            if (!is_array($val)) {
                if ($val === '' || $val === null) {
                    continue;
                }
                $out[$code] = $val;
                continue;
            }

            // range.*: value.begin / value.end | value.enf
            if (
                isset($val['value']) && is_array($val['value']) &&
                (array_key_exists('begin', $val['value']) || array_key_exists('end', $val['value']) || array_key_exists('enf', $val['value']))
            ) {

                $begin = $val['value']['begin'] ?? null;
                $end   = array_key_exists('enf', $val['value']) ? $val['value']['enf'] : ($val['value']['end'] ?? null);

                $is_begin_empty = ($begin === '' || $begin === null);
                $is_end_empty   = ($end   === '' || $end   === null);

                if ($is_begin_empty && $is_end_empty) {
                    continue;
                }

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

            // общий случай с ключом 'value'
            if (array_key_exists('value', $val)) {
                $v = $val['value'];
                if ($v === '' || $v === null) {
                    continue;
                }
                $out[$code] = $val;
                continue;
            }

            // произвольные массивы — чистим пустые элементы
            $clean = [];
            foreach ($val as $k => $v) {
                if (is_array($v)) {
                    if (array_key_exists('value', $v)) {
                        if ($v['value'] === '' || $v['value'] === null) {
                            continue;
                        }
                        $clean[$k] = $v;
                    } else {
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

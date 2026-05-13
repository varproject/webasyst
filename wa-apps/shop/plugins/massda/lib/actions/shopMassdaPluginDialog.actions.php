<?php

/**
 * Диалог массовой очистки артикулов (SKU).
 *
 * Основные функции:
 * - формирование коллекции товаров, требующих очистки;
 * - вывод стартового экрана с расчётом количеств;
 * - запуск интерфейса прогресса;
 * - пошаговая пакетная обработка выбранных товаров;
 * - поддержка флага сохранения названия главного артикула;
 * - адаптация размера пакета под тайм-бюджет;
 * - финализация и очистка рабочих переменных в storage;
 * - логирование всех этапов.
 *
 * Логика работы:
 * 1) defaultAction()
 *    — подготавливает стартовые данные;
 *    — считает выбранные товары и товары на обработку;
 *    — очищает старое состояние и сохраняет новое в storage.
 *
 * 2) runAction()
 *    — запускает экран прогресса;
 *    — принимает флаг article_name из формы;
 *    — передаёт его дальше в шаблон Process.
 *
 * 3) processAction()
 *    — получает текущее состояние из storage;
 *    — берёт очередную партию товаров;
 *    — вызывает cleaner;
 *    — пересчитывает прогресс;
 *    — завершает процесс, если товаров больше не осталось.
 */
class shopMassdaPluginDialogActions extends waViewActions
{
    protected $products_hash;

    protected $delete_limit = 10;
    protected $time_budget_ms = 1200;

    protected $start = true;
    protected $is_finished = false;

    protected $percent = 0;
    protected $processed = 0;

    protected $remaining_count = 0;
    protected $total_count = 0;

    /** @var shopProductsCollection */
    protected $products_collection;

    /** @var shopMassdaPluginCleaner */
    protected $cleaner;

    protected $log_file = 'massda.log';


    /**
     * Предварительная инициализация контроллера.
     *
     * Выполняет:
     * - получение хеша коллекции;
     * - формирование основной коллекции товаров;
     * - создание cleaner для пакетной обработки;
     * - определение тайм-бюджета обработки.
     *
     * @return void
     */
    public function preExecute()
    {
        $this->products_hash = waRequest::post('products_hash', '', waRequest::TYPE_STRING);

        $this->products_collection = $this->getProductsCollection();
        $this->cleaner = new shopMassdaPluginCleaner();
        $this->time_budget_ms = $this->detectTimeBudgetMs();
    }

    /**
     * Стартовый экран диалога.
     *
     * Выполняет:
     * - очистку мусора из прошлой сессии;
     * - пересчёт количества выбранных товаров;
     * - определение количества товаров, которые нужно обработать;
     * - инициализацию всех переменных прогресса;
     * - передачу данных в шаблон.
     *
     * @return void
     */
    public function defaultAction()
    {
        $storage = $this->getStorage();

        // Очищаем прошлые данные, если они остались
        $this->clearStorage();

        // Количество ВСЕХ выбранных товаров (сырой хеш)
        $raw_collection = new shopProductsCollection($this->products_hash);
        $products_selected_count = $raw_collection->count();

        // Количество товаров, действительно требующих обработки
        $products_to_process_count = $this->products_collection->count();

        // Сохраняем состояние
        $storage->write('massda_selected_total',      $products_selected_count);
        $storage->write('massda_initial_to_process',  $products_to_process_count);
        $storage->write('massda_total',               $products_to_process_count);
        $storage->write('massda_remaining',           $products_to_process_count);
        $storage->write('massda_delete_limit',        $this->delete_limit);

        // Передаём в шаблон
        $this->view->assign([
            'products_hash'             => $this->products_hash,
            'products_selected_count'   => $products_selected_count,
            'products_to_process_count' => $products_to_process_count,
        ]);

        $this->setDialogTemplate('DialogDefault');
    }

    protected function setDialogTemplate($name)
    {
        if (wa()->whichUI('shop') === '1.3') {
            $this->setTemplate('plugins/massda/templates/actions/actions-legacy/dialog/' . $name . '.html');
        } else {
            $this->setTemplate('plugins/massda/templates/actions/dialog/' . $name . '.html');
        }
    }

    /**
     * Запуск интерфейса прогресса.
     *
     * Показывает:
     * - сколько выбрано товаров;
     * - сколько будет обработано;
     * - состояние первого экрана прогресса;
     * - флаг article_name, который должен пройти через весь процесс.
     *
     * @return void
     */
    public function runAction()
    {
        $storage = $this->getStorage();

        $products_selected_count   = (int) $storage->read('massda_selected_total');
        $products_to_process_count = (int) $storage->read('massda_initial_to_process');
        $total                     = (int) $storage->read('massda_total');
        $article_name              = waRequest::post('article_name', 0, waRequest::TYPE_INT);

        // Логируем запуск обработки
        try {
            waLog::log(sprintf(
                "START: selected=%d process=%d hash=%s article_name=%d",
                $products_selected_count,
                $products_to_process_count,
                $this->products_hash,
                $article_name
            ), $this->log_file);
        } catch (Exception $e) {
        }

        // Выводим первый экран прогресса
        $this->view->assign([
            'products_hash'             => $this->products_hash,
            'start'                     => true,
            'is_finished'               => false,
            'percent'                   => 0,
            'processed'                 => 0,
            'total'                     => $total,
            'article_name'              => $article_name,
            'products_selected_count'   => $products_selected_count,
            'products_to_process_count' => $products_to_process_count,
        ]);

        // $this->setTemplate('Process');
        $this->setDialogTemplate('DialogProcess');
    }


    /**
     * Основной шаг обработки.
     *
     * Выполняет:
     * - получение текущего состояния из storage;
     * - получение флага article_name из POST;
     * - выборку партии товаров (offset = 0);
     * - вызов cleaner для обработки SKU;
     * - логирование ошибок;
     * - пересчёт оставшегося количества;
     * - адаптацию размера пакета под среднее время;
     * - расчёт прогресса и возврат промежуточного состояния;
     * - завершение процесса, если товаров не осталось.
     *
     * @return void
     * @throws Exception
     */
    public function processAction()
    {
        $storage = $this->getStorage();

        $this->remaining_count     = (int) $storage->read('massda_remaining');
        $this->total_count         = (int) $storage->read('massda_total');
        $products_to_process_count = (int) $storage->read('massda_initial_to_process');
        $products_selected_count   = (int) $storage->read('massda_selected_total');
        $article_name              = waRequest::post('article_name', 0, waRequest::TYPE_INT);

        $this->delete_limit = (int) $storage->read('massda_delete_limit');
        if ($this->delete_limit <= 0) {
            $this->delete_limit = 10;
        }

        // Всё обработано?
        if ($this->remaining_count <= 0) {
            $this->finishProcess($products_to_process_count, $products_selected_count, $article_name);
            return;
        }

        // Получение партии товаров
        $products    = $this->products_collection->getProducts('id', 0, $this->delete_limit);
        $product_ids = array_keys($products);

        if ($product_ids) {
            $t0 = microtime(true);

            // Пакетная обработка
            try {
                $this->cleaner->clean($product_ids, $article_name);
            } catch (Exception $e) {

                // Пишем ошибку в лог
                try {
                    waLog::log(sprintf(
                        "ERR: %s ids=%s",
                        $e->getMessage(),
                        implode(',', $product_ids)
                    ), $this->log_file);
                } catch (Exception $e2) {
                }

                throw $e;
            }

            $cnt        = count($product_ids);
            $elapsed_ms = (microtime(true) - $t0) * 1000;

            // Обновление количества оставшихся товаров
            $this->remaining_count -= $cnt;
            if ($this->remaining_count < 0) {
                $this->remaining_count = 0;
            }
            $storage->write('massda_remaining', $this->remaining_count);

            // Адаптивный размер следующего пакета
            $avg_ms = $elapsed_ms / $cnt;
            if ($avg_ms < 1) {
                $avg_ms = 1;
            }

            $new_limit = (int) floor($this->time_budget_ms / $avg_ms);
            if ($new_limit < 1) {
                $new_limit = 1;
            }
            if ($new_limit > 200) {
                $new_limit = 200;
            }

            $storage->write('massda_delete_limit', $new_limit);
        }

        // Прогресс
        $this->processed = $this->total_count - $this->remaining_count;
        $this->percent   = ($this->total_count > 0)
            ? (int) floor($this->processed / $this->total_count * 100)
            : 100;

        // Проверяем завершение
        if ($this->remaining_count <= 0) {
            $this->finishProcess($products_to_process_count, $products_selected_count, $article_name);
            return;
        }

        // Промежуточные данные
        $this->view->assign([
            'products_hash'             => $this->products_hash,
            'start'                     => false,
            'is_finished'               => false,
            'percent'                   => $this->percent,
            'processed'                 => $this->processed,
            'total'                     => $this->total_count,
            'article_name'              => $article_name,
            'products_selected_count'   => $products_selected_count,
            'products_to_process_count' => $products_to_process_count,
        ]);

        // $this->setTemplate('Process');
        $this->setDialogTemplate('DialogProcess');

    }


    /**
     * Завершение обработки.
     *
     * Выполняет:
     * - финальное логирование;
     * - вывод итогового состояния в шаблон;
     * - очистку всех переменных в storage.
     *
     * @param int $initial_to_process
     * @param int $selected_total
     * @param int $article_name
     * @return void
     */
    private function finishProcess($initial_to_process, $selected_total, $article_name = 0)
    {
        $this->is_finished = true;
        $this->percent     = 100;

        try {
            waLog::log(sprintf(
                "FINISH: processed=%d selected=%d hash=%s article_name=%d",
                $this->total_count,
                $selected_total,
                $this->products_hash,
                $article_name
            ), $this->log_file);
        } catch (Exception $e) {
        }

        // Передача финальных данных
        $this->view->assign([
            'products_hash'             => $this->products_hash,
            'start'                     => false,
            'is_finished'               => true,
            'percent'                   => 100,
            'processed'                 => $this->total_count,
            'total'                     => $this->total_count,
            'article_name'              => $article_name,
            'products_selected_count'   => $selected_total,
            'products_to_process_count' => $initial_to_process,
        ]);

        // $this->setTemplate('Process');
        $this->setDialogTemplate('DialogProcess');


        // Очищаем storage после завершения
        $this->clearStorage();
    }


    /**
     * Очистка всех рабочих переменных плагина из storage.
     *
     * Вызывается:
     * - перед началом нового процесса;
     * - после завершения обработки.
     *
     * @return void
     */
    private function clearStorage()
    {
        $storage = $this->getStorage();

        $storage->remove('massda_remaining');
        $storage->remove('massda_total');
        $storage->remove('massda_initial_to_process');
        $storage->remove('massda_delete_limit');
        $storage->remove('massda_selected_total');
    }


    /**
     * Формирование коллекции товаров с фильтром multiple_sku.
     *
     * Выполняет:
     * - кеширование списка товаров с параметром multiple_sku;
     * - создание коллекции на основе хеша пользователя;
     * - фильтрацию по условиям:
     *      - p.sku_count > 1
     *      - p.id в списке multiple_sku
     *
     * @return shopProductsCollection
     */
    private function getProductsCollection()
    {
        $cache = new waRuntimeCache('shop.massda.multiple_sku_ids');

        if ($cache->isCached()) {
            $ids = $cache->get();
        } else {
            $m = new shopProductParamsModel();

            $ids = $m->select('product_id')
                ->where("name='multiple_sku' AND value='1'")
                ->fetchAll(null, true);

            $ids = array_map('intval', $ids);

            $cache->set($ids);
        }

        $collection = new shopProductsCollection($this->products_hash);

        $where = "p.sku_count > 1";
        if (!empty($ids)) {
            $where .= " OR p.id IN (" . implode(',', $ids) . ")";
        }

        $collection->addWhere("(" . $where . ")");

        return $collection;
    }


    /**
     * Определяет тайм-бюджет одного HTTP-тика.
     *
     * Принцип:
     * - берём ~60% от max_execution_time;
     * - ограничиваем диапазоном 800–5000 мс.
     *
     * @return int
     */
    protected function detectTimeBudgetMs()
    {
        $max_exec = (int) ini_get('max_execution_time');
        if ($max_exec <= 0) {
            return 1200;
        }

        $ms = (int) floor($max_exec * 1000 * 0.6);
        if ($ms < 800) {
            $ms = 800;
        }
        if ($ms > 5000) {
            $ms = 5000;
        }

        return $ms;
    }
}

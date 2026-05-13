<?php

/**
 * Очистка артикулов (SKU) у товаров.
 *
 * Функции очистки:
 *  - Удаляет все SKU, кроме основного (shop_product.sku_id).
 *  - Очищает name и фичи основного SKU.
 *  - Удаляет selectable-фичи товара.
 *  - Переводит товар в одиночный режим (multiple_sku=0, sku_type=0, sku_count=1).
 *  - Запускает финальный пересчёт через shopProductModel::correct().
 *
 * Работает совместно с контроллером, который ограничивает размер порции
 * и контролирует тайм-бюджет одного HTTP-вызова.
 *
 * @author Petrosian Vagram
 * @since  1.1.0 (2025-11-22)
 */
class shopMassdaPluginCleaner
{
    /**
     * Очищает все лишние SKU у переданного списка товаров.
     *
     * Алгоритм:
     *   1. Нормализует массив product_ids.
     *   2. Загружает товар и определяет основной SKU.
     *   3. Удаляет остальные SKU.
     *   4. Очищает name и фичи основного SKU.
     *   5. Сбрасывает selectable-фичи.
     *   6. Переводит товар в режим single-SKU.
     *   7. Запускает корректировку товара через shopProductModel::correct().
     *
     * @param int[] $product_ids Массив ID товаров для обработки
     * @return void
     */
    public function clean(array $product_ids, $is_delete_article_name = false)
    {
        if (!$product_ids) {
            return;
        }

        wa('shop');

        // Нормализуем ID
        $product_ids = array_map('intval', $product_ids);
        $product_ids = array_values(array_unique(array_filter($product_ids)));

        if (!$product_ids) {
            return;
        }

        $pm    = new shopProductModel();
        $sm    = new shopProductSkusModel();
        $pfm   = new shopProductFeaturesModel();
        $pfsel = new shopProductFeaturesSelectableModel();
        $ppm   = new shopProductParamsModel();

        foreach ($product_ids as $pid) {
            // Берём товар
            $product_row = $pm->getById($pid);
            if (!$product_row || empty($product_row['sku_id'])) {
                continue;
            }

            $main_sku_id = (int) $product_row['sku_id'];

            // Все SKU товара, ключ — ID SKU
            $skus = $sm->getByField('product_id', $pid, 'id');
            if (!$skus || empty($skus[$main_sku_id])) {
                continue;
            }

            // 1. Удаляем все SKU, кроме основного
            foreach ($skus as $sid => $sku_row) {
                if ((int) $sid !== $main_sku_id) {
                    // Используем delete модели, как в старом классе
                    $sm->delete($sid);
                }
            }

            // 2. Чистим name и фичи основного SKU
            if (!$is_delete_article_name) {
                $sm->updateById($main_sku_id, [
                    'name' => ''
                ]);
            }

            // Удаляем фичи, привязанные к SKU
            $pfm->deleteByField('sku_id', $main_sku_id);

            // Повторно заберём текущие данные SKU для setData()
            $main_sku = $sm->getById($main_sku_id);
            if (!$main_sku) {
                continue;
            }

            // Минимальный набор для setData:
            $main_sku['features'] = [];
            $main_sku['stock']    = [];

            try {
                // 3. Работаем через shopProduct и setData
                $product = new shopProduct($pid);

                // Обновляем данные основного SKU
                $sm->setData($product, [
                    $main_sku_id => $main_sku
                ]);

                // 4. Убираем selectable-фичи целиком
                $pfsel->deleteByField(['product_id' => $pid]);

                // 5. Переводим товар в одиночный режим
                // Берём все текущие параметры товара
                $params = $ppm->get($pid);

                // Принудительно переводим multiple_sku в 0, не трогая остальные
                $params['multiple_sku'] = '0';

                // Сохраняем полный набор параметров обратно
                $ppm->set($pid, $params);

                $pm->updateById($pid, [
                    'sku_type'  => 0,
                    'sku_count' => 1,
                ]);

                // 6. Финальный пересчёт (цены, остатков, кешей и т.д.)
                $pm->correct($pid);
            } catch (Exception $e) {
                waLog::log('Ошибка очистки товара, с id: ' . $pid . ': ' . $e->getMessage(), 'massda-error.log');
            }
        }
    }
}

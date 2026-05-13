<?php

/**
 * Диалог массового редактирования характеристик.
 *
 * Получает коллекцию товаров по products_hash, считает их количество без
 * материализации выборки, загружает список характеристик, нормализует их и
 * передаёт данные в шаблон диалога.
 *
 * В шаблон пробрасываются:
 *  - products_hash  (string)
 *  - products_count (int)
 *  - features       (array) нормализованный список фич для формы
 * 
 * @author  Petrosian Vagram
 * @since   1.0.0 (2025-11-02)
 */
class shopMassefPluginDialogAction extends waViewAction
{
    /** @var string */
    protected $products_hash;

    /** @var shopMassefPluginCollectionHelper */
    protected $collection_helper;

    /** @var shopMassefPluginFeaturesHelper */
    protected $feature_helper;

    /** @var array Пустой ответ для случаев без коллекции/фич */
    protected $empty_result = [
        'products_hash'  => '',
        'products_count' => 0,
        'features'       => [],
    ];

    /**
     * Инициализация хелперов и products_hash.
     * Если хэш не передан — сразу отдаётся пустой шаблон.
     *
     * @return void
     */
    public function preExecute()
    {
        $this->products_hash = waRequest::post('products_hash', '', waRequest::TYPE_STRING);

        // Если хэш пуст — возвращаем пустой шаблон
        if (empty($this->products_hash)) {
            return $this->view->assign($this->empty_result);
        }

        // Инициализация хелперов
        $this->collection_helper = new shopMassefPluginCollectionHelper($this->products_hash);
        $this->feature_helper    = new shopMassefPluginFeaturesHelper();
    }

    /**
     * Сбор данных для диалога:
     * 1) типы товаров коллекции
     * 2) «потоковый» подсчёт количества товаров
     * 3) загрузка и нормализация характеристик
     * 4) проброс в шаблон
     *
     * @return void
     */
    public function execute()
    {
        // 1. Получаем типы товаров
        $type_ids = $this->collection_helper->getTypeIds();
        if (empty($type_ids)) {
            return $this->view->assign($this->empty_result);
        }

        // 2. Считаем товары — потоково, без загрузки в память
        $products_count = 0;
        foreach ($this->collection_helper->yieldProductIds() as $id) {
            $products_count++;
        }

        if ($products_count === 0) {
            return $this->view->assign($this->empty_result);
        }

        // 3. Загружаем характеристики
        $normalized_features = $this->feature_helper->getAllFeaturesWithValues($type_ids, 'all', 200, true);

        if (empty($normalized_features)) {
            return $this->view->assign($this->empty_result);
        }

        // 4. Передаём данные в шаблон
        $this->view->assign([
            'products_hash'  => $this->products_hash,
            'products_count' => $products_count,
            'features'       => $normalized_features,
        ]);
    }
}

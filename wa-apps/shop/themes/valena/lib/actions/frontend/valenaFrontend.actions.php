<?php

class valenaFrontendActions extends waViewActions
{
    protected $category_model;
    protected $product_model;

    protected $category = null;
    protected $product = null;
    protected $products = [];

    protected function getCategoryModel()
    {
        if (!$this->category_model) {
            $this->category_model = new shopCategoryModel();
        }
        return $this->category_model;
    }

    protected function getProductModel()
    {
        if (!$this->product_model) {
            $this->product_model = new shopProductModel();
        }
        return $this->product_model;
    }

    public function preExecute()
    {
        wa('shop');
        $this->setTemplate('Main');
        $this->setLayout(new valenaFrontendLayout());


        $categories = $this->getCategories();

        $this->view->assign([
            'categories' => $categories,
        ]);
    }



    public function defaultAction()
    {
        $category_model = $this->getCategoryModel();
        $product_model  = $this->getProductModel();

        // ===============================
        // 1) ПАРАМЕТРЫ ИЗ ROUTING (готовые!)
        // ===============================
        $category_id = waRequest::param('category_id', 0, waRequest::TYPE_INT);
        $product_id  = waRequest::param('product_id', 0, waRequest::TYPE_INT);


        // ===============================
        // 2) ЗАГРУЗКА КАТЕГОРИИ И ТОВАРА
        // ===============================
        if ($category_id > 0) {
            $this->category = $category_model->getById($category_id);
        }

        if ($product_id > 0) {
            $this->product = $product_model->getById($product_id);

            // если продукт загружен — категория должна быть основной категорией товара
            if ($this->product && empty($this->category)) {
                $this->category = $category_model->getById($this->product['category_id']);
            }
        }

        // dd($this->category);


        // ===============================
        // 3) ФИЛЬТР ЦВЕТА (логика + сессия)
        // ===============================

        $session_color_feature_id = (int)$this->getStorage()->read('valena_color_feature_id');
        $session_color_value_id   = (int)$this->getStorage()->read('valena_color_value_id');

        $req_color_feature_id = waRequest::get('color_feature_id', null, waRequest::TYPE_INT);
        $req_color_value_id   = waRequest::get('color_value_id', null, waRequest::TYPE_INT);

        // итоговые значения
        $color_feature_id = $session_color_feature_id;
        $color_value_id   = $session_color_value_id;

        // если пришло новое значение — обновляем и сессию
        if ($req_color_feature_id !== null) {
            $color_feature_id = $req_color_feature_id;
            $this->getStorage()->write('valena_color_feature_id', $color_feature_id);
        }

        if ($req_color_value_id !== null) {
            $color_value_id = $req_color_value_id;
            $this->getStorage()->write('valena_color_value_id', $color_value_id);
        }


        // ===============================
        // 4) ЗАГРУЗКА СПИСКА ТОВАРОВ КАТЕГОРИИ
        // ===============================
        $this->products = [];

        if (!empty($this->category['id'])) {

            $this->products = $this->getFilteredProducts([
                'category_id'      => $this->category['id'],
                'color_feature_id' => $color_feature_id,
                'color_value_id'   => $color_value_id,
            ]);
        }

        // dd($this->products);

        // ===============================
        // 5) РЕНДЕР
        // ===============================
        $this->view->assign([
            'category' => $this->category,
            'products' => $this->products,
            'product'  => $this->product,

            // чтобы в сайдбаре подсвечивать выбранный цвет:
            'active_color_feature_id' => $color_feature_id,
            'active_color_value_id'   => $color_value_id,
        ]);
    }



    public function homeAction()
    {
        // // строим дерево
        // $category_tree = $this->getCategories();

        // // Характеристика цвета
        // $colors = (new shopFeatureValuesColorModel)->getAll('id');
        // // dd($colors);


        // // Обовляем активный фильтр цвета в сессии
        // $session_color_feature_id = $this->getStorage()->read('valena_color_feature_id');
        // $session_color_value_id = $this->getStorage()->read('valena_color_value_id');
        // $valena_color_feature_id = waRequest::get('color_feature_id', $session_color_feature_id, waRequest::TYPE_STRING_TRIM);
        // $valena_color_value_id = waRequest::get('color_value_id', $session_color_value_id, waRequest::TYPE_STRING_TRIM);
        // $this->getStorage()->write('valena_color_feature_id', $valena_color_feature_id);
        // $this->getStorage()->write('valena_color_value_id', $valena_color_value_id);

        // // Удаляем из сессии, при переходе на др. страницу, если нет выбранной категории
        // $category_id = waRequest::param('category_id', '', waRequest::TYPE_STRING_TRIM);
        // if (!$category_id) {
        //     $this->getStorage()->remove('valena_color_feature_id');
        //     $this->getStorage()->remove('valena_color_value_id');
        // }

        // $this->view->assign([
        //     'colors' => $colors,
        //     'categories' => $category_tree,
        //     'active_color_value_id' => $this->getStorage()->read('valena_color_value_id'),
        // ]);
    }





    protected function getCategories()
    {
        // Модель категории
        $category_model = $this->getCategoryModel();
        $table = $category_model->getTableName();

        $sql = "
            SELECT 
                cat.left_key,
                cat.id,
                cat.right_key,
                cat.depth,
                cat.parent_id,
                cat.name,
                cat.meta_title,
                cat.meta_keywords,
                cat.meta_description,
                cat.type,
                cat.url,
                cat.full_url,
                cat.count,
                cat.description,
                cat.filter,
                cat.sort_products,
                cat.include_sub_categories,
                cat.status,
                cp.name  AS param_name,
                cp.value AS param_value
            FROM {$table} AS cat
            LEFT JOIN shop_category_params AS cp
                ON cp.category_id = cat.id
            ORDER BY cat.left_key
        ";

        $rows = $category_model->query($sql)->fetchAll();
        $categories = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];

            // Инициализация категории, если ещё не добавили
            if (!isset($categories[$id])) {
                $categories[$id] = $row;
                $categories[$id]['params'] = [];
            }

            // Добавляем параметр, если есть
            if ($row['param_name'] !== null) {
                $categories[$id]['params'][$row['param_name']] = $row['param_value'];
            }

            // Эти технические поля в конечном массиве не нужны
            unset($categories[$id]['param_name'], $categories[$id]['param_value']);
        }

        $categories_tree = $category_model->buildNestedTree($categories);
        dd($categories_tree);
        return $categories_tree;
    }










    /**
     * ФИЛЬТР ТОВАРОВ
     */
    protected function getFilteredProducts(array $filters = [])
    {
        $product_model  = $this->getProductModel();
        $category_model = $this->getCategoryModel();

        $sql = "
            SELECT
                p.id,
                p.name,
                p.url,
                p.price,
                p.currency,
                p.compare_price,
                p.count,
                p.image_id,
                p.ext,
                s.sku
            FROM shop_product AS p
            LEFT JOIN shop_product_skus AS s
                ON s.product_id = p.id
            AND s.sort = 1
        ";

        $where       = [];
        $params      = [];
        $ordered_ids = []; // порядок товаров из shopProductsCollection

        /**
         * Фильтр категории
         *
         * 1) Для любой категории (stat/dyn) забираем порядок через shopProductsCollection('category/<id>').
         * 2) Для динамической (type = 1) — набор товаров берём ТОЛЬКО из коллекции (WHERE p.id IN (...)).
         * 3) Для статической (type = 0) — оставляем твою старую логику через shop_category_products,
         *    а порядок берём из $ordered_ids (ORDER BY FIELD(p.id,...)).
         */
        if (!empty($filters['category_id'])) {
            $category_id = (int)$filters['category_id'];
            $category    = $category_model->getById($category_id);

            if ($category) {
                // Всегда вытаскиваем порядок из коллекции категории
                $collection = new shopProductsCollection('category/' . $category_id);
                $rows       = $collection->getProducts('id'); // без лимита, только id-шники

                if (!$rows) {
                    // В категории ничего нет — сразу пусто
                    return [];
                }

                foreach ($rows as $row) {
                    if (!empty($row['id'])) {
                        $ordered_ids[] = (int)$row['id'];
                    }
                }

                if (!$ordered_ids) {
                    return [];
                }

                // Динамическая категория: ограничиваем выборку по списку id
                if ((int)$category['type'] === 1) {
                    $where[]                      = 'p.id IN (i:dynamic_product_ids)';
                    $params['dynamic_product_ids'] = $ordered_ids;
                } else {
                    // Статическая категория: оставляем старую логику через shop_category_products
                    $sql .= "
                    LEFT JOIN shop_category_products AS cp
                        ON cp.product_id = p.id
                ";
                    $where[]               = 'cp.category_id = i:category_id';
                    $params['category_id'] = $category_id;
                    // Порядок для неё всё равно возьмём из $ordered_ids в ORDER BY FIELD()
                }
            }
        }

        // Фильтр цвета
        if (!empty($filters['color_feature_id']) && !empty($filters['color_value_id'])) {
            $sql .= "
                JOIN shop_product_features AS pf
                    ON pf.product_id = p.id
                AND pf.feature_id = i:color_feature_id
            ";
            $where[]                    = 'pf.feature_value_id = i:color_value_id';
            $params['color_feature_id'] = (int)$filters['color_feature_id'];
            $params['color_value_id']   = (int)$filters['color_value_id'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= "
            GROUP BY p.id
        ";

        // Сортировка:
        // если есть $ordered_ids — сортируем как в системе через FIELD(p.id,...),
        // иначе — старый дефолт p.id ASC.
        if ($ordered_ids) {
            $sql .= "
            ORDER BY FIELD(p.id, " . implode(',', $ordered_ids) . ")
        ";
        } else {
            $sql .= "
            ORDER BY p.id ASC
        ";
        }

        return $product_model->query($sql, $params)->fetchAll('id');
    }
}

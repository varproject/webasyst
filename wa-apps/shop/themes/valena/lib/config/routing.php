<?php

$current_url = wa()->getRouting()->getCurrentUrl();
$uri = parse_url($current_url, PHP_URL_PATH);
$uri = trim((string)$uri, '/');
$uri = rawurldecode($uri);
$uri = preg_replace('~[^a-zA-Z0-9\-_\/]+~u', '', $uri);


wa('shop');

$segments = $uri === '' ? [] : explode('/', $uri);
$slug = end($segments);


// Если slug пустой — это главная
if ($slug === false || $slug === '') {
  return [
    '*' => [
      'module' => 'frontend',
      'action' => 'home'
    ]
  ];
}

$category_model = new shopCategoryModel();
$product_model  = new shopProductModel();

/**
 * 1. Пробуем найти категорию по URL
 */
$category_id = (int)$category_model
  ->select('id')
  ->where("url = s:url", ['url' => $slug])
  ->fetchField();

/**
 * 2. Если это не категория — пробуем товар
 */
$product_id = 0;
$product_cat_id = 0;

if (!$category_id) {
  $product = $product_model
    ->select('id, category_id')
    ->where("url = s:url", ['url' => $slug])
    ->fetchAssoc();

  if (!empty($product['id'])) {
    $product_id = (int)$product['id'];
    $product_cat_id = (int)$product['category_id'];
  }
}

/**
 * 3. Возвращаем маршруты
 */
if ($category_id > 0) {
  return [
    '<category_id>/' => [
      'module'      => 'frontend',
      'action'      => 'default',
      'category_id' => $category_id,
    ]
  ];
}

if ($product_id > 0) {
  return [
    '<product_id>/' => [
      'module'      => 'frontend',
      'action'      => 'default',
      'product_id'  => $product_id,
      'category_id' => $product_cat_id,
    ]
  ];
}

return [
  '*' => [
    'module' => 'frontend',
    'action' => 'home',
  ]
];

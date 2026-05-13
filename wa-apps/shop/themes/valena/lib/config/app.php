<?php

// Дополнительный файл с кофигурацией
if (!function_exists('dd')) {
  function dd($array)
  {
    echo '<pre>';
    print_r($array);
    echo '</pre>';
  }
}


return array(
  'name' => 'Valena Shop',
  'icon' =>
  array(
    48 => 'img/valena48.png',
    96 => 'img/valena96.png',
  ),
  'version' => '1.0.0',
  'vendor' => '123456',
  'ui' => '2.0',
  'frontend' => true,
);

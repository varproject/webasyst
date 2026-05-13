<?php

/*Загружаем исходные данные в таблицу переменных*/
$model = new waModel();

//Предопределенная стилизация для popup окон
$data = array(
    array("name" => "banner", "title" => "Баннер", "width" => 800, "height" => 260),
    array("name" => "icon", "title" => "Иконка", "width" => 50, "height" => 50),
    array("name" => "image", "title" => "Изображение", "width" => 200, "height" => 0),
);

foreach($data as $item){
    $model->query("REPLACE `shop_skcatimage_groups` (`name`, `title`, `width`, `height`) VALUES ('{$item["name"]}', '{$item["title"]}', {$item["width"]}, {$item["height"]})");
}

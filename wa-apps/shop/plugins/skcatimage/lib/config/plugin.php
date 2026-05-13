<?php
return array(
    'name' => "Изображения для категории",
    'version' => '1.11.0',
    'description' => "Привязка изображений любых размеров к категории товаров",
    'img' => 'img/skcatimage.png',
    'shop_settings' => true,
    'handlers' => array(
        'backend_category_dialog' => 'addInputsFile',
        'backend_prod_category_dialog' => 'addInputsFileNew',
        'frontend_head' => 'addMarkupImage',
    ),
    'vendor' => 1039853,
);
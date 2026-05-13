<?php

/*Удаляем таблицы плагина*/
$model = new waModel();
$model->query('DROP TABLE IF EXISTS `shop_skcatimage_groups`');
$model->query('DROP TABLE IF EXISTS `shop_skcatimage_data`');

waFiles::delete(wa()->getDataPath("skcatimage/", true, 'shop'), false);
waFiles::delete(wa()->getDataPath("skcatimage/", false, 'shop'), false);
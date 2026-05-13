<?php

$model = new waModel();

try{
    $sql = "ALTER TABLE `shop_skcatimage_data` ADD `query` VARCHAR(64) NOT NULL DEFAULT '' AFTER `size`;";

    $model->query($sql);

}catch(waDbException $e){ }

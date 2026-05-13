<?php

class shopSkcatimageCategoryNew extends shopSkcatimageCategoryAbstract{

    public function __construct($category_id){
        parent::__construct($category_id);
    }

    public function getContent(){

        $data_images = $this->getDataImages();

        $view = wa()->getView();

        $view->assign("shop_skcatimage_groups", $this->groups);
        $view->assign("shop_skcatimage_data", $data_images);

        $init = array(
            "url" => wa()->getAppUrl('shop'),
            "category_id" => $this->category_id,
        );
        $view->assign("shop_skcatimage_init", $init);

        $path = wa()->getAppPath() . "/plugins/{$this->plugin_id}/templates/actions/backend/";
        $view->assign("shop_skcatimage_path", $path);

        return $view->fetch($path . "inputsCategoryNew.html");

    }

}
<?php

class shopSkcatimagePlugin extends shopPlugin{

    public $pluginID = "skcatimage";

    public function addInputsFileNew($params){

        if(!isset($params["category"]["id"]) || empty($params["category"]["id"])){
            return array();
        }

        $categoryLoader = new shopSkcatimageCategoryNew($params["category"]["id"]);

        if(!$categoryLoader->isActive()){
            return array();
        }

         return array(
             "top" => $categoryLoader->getContent(),
         );


    }

    public function addInputsFile($category){

        if(empty($category["id"])){
            return "";
        }

        $categoryLoader = new shopSkcatimageCategoryOld($category["id"]);

        if(!$categoryLoader->isActive()){
            return "";
        }

        return $categoryLoader->getContent();

    }

    public function addMarkupImage( $category ){

        $plugin_id = $this->pluginID;

        $settings = wa("shop")->getPlugin($plugin_id)->getSettings();

        if(!$settings["status"] || !$settings["is_markup"] || !$settings["markup_id"]){
            return "";
        }

        $params = wa()->getRequest()->param();
        if(!isset($params["module"]) || $params["module"] != "frontend"){
            return "";
        }


        if(!isset($params["action"]) || $params["action"] != "category"){
            return "";
        }

        $view = wa()->getView();
        $categoryData = $view->getVars("category");

        if(empty($categoryData) || !is_array($categoryData) ){
            return "";
        }

        $modelData = new shopSkcatimageDataModel();
        $image = $modelData->getByField(array("category_id" => $categoryData["id"], "group_name" => $settings["markup_id"]));

        if($image){
            $urlImage = wa()->getConfig()->getHostUrl() .  wa()->getDataUrl("skcatimage/{$categoryData["id"]}/", true, 'shop') . $image["name"];
            return "<meta property='og:image' content='{$urlImage}'>";
        }

    }

    public static function clearCache(){

        $cache = new waSerializeCache('shopSkcatimage', 3600, 'shop/skcatimage');
        $cache->delete();

    }

}

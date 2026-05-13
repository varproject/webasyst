<?php

abstract class shopSkcatimageCategoryAbstract{

    protected $plugin_id = "skcatimage";

    protected $is_active = false;

    protected $settings = array();

    protected $category_id;

    protected $groups = array();

    public function __construct($category_id){

        $this->category_id = $category_id;

        $this->settings = wa("shop")->getPlugin($this->plugin_id)->getSettings();

        $groupsModel = new shopSkcatimageGroupsModel();
        $this->groups = $groupsModel->getAll();

        if(!empty($this->settings["status"]) ||  empty($this->groups)){
            $this->is_active = true;
        }
    }

    protected function getDataImages(){

        $dataModel = new shopSkcatimageDataModel();
        $data = $dataModel->query("SELECT * FROM shop_skcatimage_data WHERE category_id = i:category_id", array("category_id" => $this->category_id))->fetchAll();
        $data_images = array();
        if(!empty($data)){
            foreach($data as $item){
                $data_images[$item["group_name"]] = $item;
                $data_images[$item["group_name"]]["url"] = wa()->getDataUrl("skcatimage/{$this->category_id}/", true, 'shop') . $item["name"];
            }
        }

        return $data_images;
    }

    public function isActive(){
        return $this->is_active;
    }

}
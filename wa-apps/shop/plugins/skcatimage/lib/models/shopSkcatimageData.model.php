<?php

class shopSkcatimageDataModel extends waModel{

    protected $table = 'shop_skcatimage_data';

    public function getAllImages(){

        $data = $this->getAll();

        $dataReturn = array();

        $cdn_url = "";

        $cdn = new waCdn();

        if(!empty($cdn->count())){
            $cdn_url = $cdn->getRandom();
        }

        if(!empty($data)){
            foreach($data as $item){
                $dataReturn[$item["category_id"]][$item["group_name"]] = $cdn_url . wa()->getDataUrl("skcatimage/{$item["category_id"]}/", true, 'shop') . $item["name"];
                if(!empty($item["query"])){
                    $dataReturn[$item["category_id"]][$item["group_name"]] .= "?" . $item["query"];
                }
            }
        }

        return $dataReturn;

    }

}

<?php

class shopSkcatimagePluginBackendGroupsSaveController extends waJsonController{


    public function execute(){

        $post = wa()->getRequest()->post();

        $data = array();
        $error = array();
        $repeat = array_count_values($post["name"]);

        foreach($post as $key => $item){
            foreach($item as $id => $value){
                $value = trim($value);
                if(($key == "name" || $key == "title") && !$value){
                    $error[$id][$key] = "Введите значение";
                    continue;
                }

                if($key == "name"){
                    if(!preg_match("#^[a-z0-9_]+$#", $value)){
                        $error[$id]["name"] = "Допустимые символы a-z, 0-9 и _";
                        continue;
                    }
                    if($repeat[$value] > 1){
                        $error[$id]["name"] = "Повтор идентификаторов недопустим.";
                    }
                }
                $data[$id][$key] = $value;
            }
        }

        if($error){
            $this->errors["input"] = $error;
            return false;
        }

        $groupsModel = new shopSkcatimageGroupsModel();
        $groupsModel->truncate();
        foreach($data as $item){
            $groupsModel->insert($item);
        }

        shopSkcatimagePlugin::clearCache();

        return true;

    }


}
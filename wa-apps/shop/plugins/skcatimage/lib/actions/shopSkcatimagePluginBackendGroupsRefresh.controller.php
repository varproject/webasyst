<?php

/**
 * Класс контроллер по обновлению триграмм поиска
 */
class shopSkcatimagePluginBackendGroupsRefreshController extends waLongActionController{

    public function execute(){
        try{
            parent::execute();
        }
        catch(waException $ex){
            if($ex->getCode() == '302'){
                echo json_encode(array('warning' => $ex->getMessage()));
            }else{
                echo json_encode(array('error' => $ex->getMessage()));
            }
        }
    }

    protected function init(){

        $groupName = waRequest::post("groupName");

        $dataModel = new shopSkcatimageDataModel();
        $max = $dataModel->countByField(array("group_name" => $groupName));

        $settings = wa("shop")->getPlugin("skcatimage")->getSettings();

        $this->data["current"] = 0;
        $this->data["max"] = $max;
        $this->data["group"] = $groupName;
        $this->data["is_2x"] = (int)$settings["is_2x"];
        $this->data["step"] = $this->data["is_2x"] ? 5 : 10;
        $this->data["query_string"] = (int)$settings["query_string"];
        $this->data["error"] = "";

    }

    protected function info(){

        if($this->data['error']){
            $response = array("error" => $this->data['error']);

            echo json_encode($response);

        }else{
            $response = array('processId' => $this->processId, 'ready' => $this->isDone());
            $response["group"] = $this->data["group"];
            $response["current"] = $this->data["current"];
            $response["max"] = $this->data["max"];

            if($this->data["max"] > 0){
                $response["progress"] = (int)(100 - (($this->data["max"] - $this->data["current"]) / $this->data["max"] * 100));
                if($response["progress"] > 100){
                    $response["progress"] = 100;
                }
            }else{
                $response["progress"] = 100;
            }


            echo json_encode($response);
        }

    }

    protected function isDone(){

        return $this->data['error'] || ($this->data["current"] >= $this->data["max"]);

    }

    protected function step(){

        if($this->data["current"] < $this->data["max"]){

            $groupModel = new shopSkcatimageGroupsModel();
            $group = $groupModel->getByField(array("name" => $this->data["group"]));

            if(!$group){
                $this->data['error'] = "Идентификатор группы не найден";
            }

            $dataModel = new shopSkcatimageDataModel();
            $group_name = $dataModel->escape($group["name"]);
            $current = $this->data["current"];
            $step = $this->data["step"];

            $images = $dataModel->query("SELECT * FROM shop_skcatimage_data WHERE group_name = '{$group_name}' LIMIT {$current}, {$step}")->fetchAll();

            foreach($images as $image){

                if($image["type"] == "image/svg+xml"){
                    continue;
                }

                try{
                    $protectedPath = wa()->getDataPath("skcatimage/{$image["category_id"]}/", false, 'shop');
                    $publicPath = wa()->getDataPath("skcatimage/{$image["category_id"]}/", true, 'shop');

                    $imageObject = waImage::factory($protectedPath . $image["name"]);

                    if($group["width"] || $group["height"]){
                        $imageObject = shopSkcatimageResize::generateThumb($protectedPath . $image["name"], $group["width"], $group["height"]);
                    }

                    $imageObject->save($publicPath . $image["name"]);

                    $name_2x = shopSkcatimageResize::getName2X($image["name"]);
                    if($this->data["is_2x"]){

                        $group_2x = array();
                        $imageObject = waImage::factory($protectedPath . $image["name"]);

                        if($group["width"] || $group["height"]){
                            $group_2x["width"] = $group["width"] * 2;
                            $group_2x["height"] = $group["height"] * 2;
                            $imageObject = shopSkcatimageResize::generateThumb($protectedPath . $image["name"], $group_2x["width"], $group_2x["height"]);
                        }

                        $imageObject->save($publicPath . $name_2x);

                    }else{
                        waFiles::delete($publicPath . $name_2x);
                    }

                }
                catch(waException $e){
                    continue;
                }

            }

            $this->data["current"] += $this->data["step"];
        }

    }

    protected function finish($filename){

        $this->info();

        $groupModel = new shopSkcatimageGroupsModel();
        $group = $groupModel->getByField(array("name" => $this->data["group"]));

        if($group){
            $dataModel = new shopSkcatimageDataModel();
            $group_name = $dataModel->escape($group["name"]);
            $query = "";
            if($this->data["query_string"]){
                $query = time();
            }
            $dataModel->updateByField("group_name", $group_name, array("query" => $query));
        }

        shopSkcatimagePlugin::clearCache();

        if($this->getRequest()->post('cleanup')){
            return true;
        }
        return false;

    }

    private function error($message){
        waLog::log($message, 'shop/plugins/skcatimage.log');
        $this->data['error'] = $message;
    }

}
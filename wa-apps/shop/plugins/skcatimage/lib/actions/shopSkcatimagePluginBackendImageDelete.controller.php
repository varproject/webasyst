<?php

class shopSkcatimagePluginBackendImageDeleteController extends waJsonController{


    public function execute(){

        $plugin_id = "skcatimage";

        $group_name = waRequest::post("group_name");
        $category_id = waRequest::post("category_id");

        if(!$group_name || !$category_id){
            return false;
        }

        $dataModel = new shopSkcatimageDataModel();
        $data = $dataModel->getByField(array("group_name" => $group_name, "category_id" => $category_id));
        $dataModel->deleteByField(array("group_name" => $group_name, "category_id" => $category_id));

        $publicPath = wa()->getDataPath("skcatimage/{$category_id}/", true, 'shop');
        $protectedPath = wa()->getDataPath("skcatimage/{$category_id}/", false, 'shop');

        $name_2x = shopSkcatimageResize::getName2X($data["name"]);

        waFiles::delete($publicPath . $data["name"]);
        waFiles::delete($publicPath . $name_2x);
        waFiles::delete($protectedPath . $data["name"]);

        $view = wa()->getView();
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}/templates/actions/backend/";

        $groupsModel = new shopSkcatimageGroupsModel();
        $group = $groupsModel->getByField("name", $group_name);
        $view->assign("group", $group);
        $data["html"] = $view->fetch($path . "inputsCategoryRowEmpty.html");

        shopSkcatimagePlugin::clearCache();

        $this->response = $data;

        return true;

    }


}
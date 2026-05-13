<?php

/**
 * Класс экшен для управления настройками плагина
 */
class shopSkcatimagePluginSettingsAction extends waViewAction{

    /**
     * Запрос данных для страницы настроек
     */
    public function execute(){

        $plugin_id = "skcatimage";

        /*Настройки плагина*/
        $vars = array();

        $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);
        $namespace = wa()->getApp() . '_' . $plugin_id;

        $params = array();
        $params['id'] = $plugin_id;
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

        $settings_controls = $plugin->getControls($params);
        $this->getResponse()->setTitle(_w(sprintf('Plugin %s settings', $plugin->getName())));

        $vars['plugin_info'] = array(
            'name' => $plugin->getName()
        );
        $vars['plugin_id'] = $plugin_id;
        $vars['settings_controls'] = $settings_controls;
        $vars['settings'] = $plugin->getSettings();

        $groupsModel = new shopSkcatimageGroupsModel();
        $vars["groups"] = $groupsModel->getAll();

        $dataMax = $groupsModel->query("SELECT max(id) as max_id FROM shop_skcatimage_groups")->fetchAssoc();
        $vars["shop_skcatimage_config"] = array(
            "pathToApp" => wa()->getAppUrl('shop'),
            "max_id" => $dataMax["max_id"],
        );

        $this->view->assign($vars);

        $this->view->assign('shop_plugin_url', wa("shop")->getPlugin($plugin_id)->getPluginStaticUrl());

    }

}

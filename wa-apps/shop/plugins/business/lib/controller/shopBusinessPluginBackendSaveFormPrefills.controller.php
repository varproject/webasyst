<?php

class shopBusinessPluginBackendSaveFormPrefillsController extends waJsonController
{
    public function execute()
    {
        $post = waRequest::post();
        if (!$post) {
            return;
        }

        $fields = $this->sanitizeFields($post);

        $this->savePrefills($fields);
        $this->getResponse()->setStatus(204);
    }

    private function savePrefills($fields)
    {
        $settingsModel = new waAppSettingsModel();

        foreach ($fields as $name => $val) {
            $settingsModel->set('shop', $name, $val);
        }
    }

    private function sanitizeFields($fields)
    {
        foreach ($fields as $name => $val) {
            if (!shopBusinessPluginBackendGetFormPrefillsController::validIdPrefix($name)) {
                unset($fields[$name]);
            }
        }

        return $fields;
    }
}

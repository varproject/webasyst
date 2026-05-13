<?php

class shopBusinessPluginBackendGetFormPrefillsController extends waJsonController
{
    private const COMMON_FIELD_IDS = ['phone', 'email', 'country'];
    private const PREFIX_FIELD_ID = 'business_';

    public function execute()
    {
        $this->response = $this->getFormPrefills();
    }

    /**
     * @return array [ [id => value], ..., [n] ]
     */
    private function getFormPrefills()
    {
        $settingsModel = new waAppSettingsModel();
        $settings = $settingsModel->getByField('app_id', 'shop', true);

        $fields_data = [];
        foreach ($settings as $setting) {
            $field_id = $setting['name'];

            if (self::validIdPrefix($field_id) || self::validCommonId($field_id)) {
                $fields_data[$field_id] = $setting['value'];
            }
        }

        if (!empty($fields_data['phone'])) {
            $fields_data['phone'] = preg_replace('/[^+|\d+]/', '', $fields_data['phone']);
        }

        return $fields_data;
    }

    public static function validIdPrefix($field_id) {
        $prefix = self::PREFIX_FIELD_ID;
        $length = strlen($prefix);

        return substr($field_id, 0, $length) === $prefix;
    }

    public static function validCommonId($field_id) {
        return in_array($field_id, self::COMMON_FIELD_IDS);
    }
}

<?php

class shopBusinessPluginTBankApiCheckPhoneController extends waJsonController
{
    public function execute()
    {
        $phone_number = waRequest::get('phoneNumber');
        if (!$phone_number) {
            throw new JsonException('Необходимо указать номер телефона');
        }

        if (shopBusinessPluginHelper::isDisabledWAID()) {
            throw new JsonException('Необходимо подключить Webasyst ID');
        }

        $service_api = new waServicesApi();
        $res = $service_api->serviceCall('BANK_PARTNER',
            ['phone_number' => $phone_number],
            waNet::METHOD_POST, ['request_format' => waNet::FORMAT_JSON],
            'tbank_checkphone', false);

        if (ifset($res['status']) >= 300) {
            $errors = [
                'error' => ifset($res['response']['error']),
                'error_description' => ifset($res['response']['error_description'])
            ];
            if ($errors['error'] === 'system_error') {
                shopBusinessPluginHelper::attachErrorHTML($errors);
            }
            $this->errors = $errors;
            return;
        }

        $permissions = ifset($res, 'response', 'result', 'permissions', null);
        if (empty($permissions)) {
            return;
        }

        switch ($permissions) {
            case 'ANY':
            case 'PROVEN_ONLY':
                $this->response = [
                    'permissions' => 'ANY',
                    'html' => '<i class="fa fa-check-circle text-yellow"></i>'
                ];
                break;
            case 'PROHIBITED':
                $this->response = [
                    'permissions' => 'PROHIBITED',
                    'html' => '<div class="state-caution-hint">По данному номеру телефона нельзя создать заявку</div>'
                ];
                break;
            default:
                break;
        }
    }

}

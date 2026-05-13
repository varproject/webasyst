<?php

class shopBusinessPluginTBankApiCreateApplicationController extends waJsonController
{
    public function execute()
    {
        if (shopBusinessPluginHelper::isDisabledWAID()) {
            throw new JsonException('Необходимо подключить Webasyst ID');
        }

        $data = waRequest::post();
        if ($data) {
            $data['source'] = 'Федеральные партнеры';
            $data['subsource'] = 'API';
            if (empty($data['temperature'])) {
                $data['temperature'] = 'HOT';
            }

            if ($data['product'] === 'Интернет-эквайринг') {
                $data['additionalFields'] = ['utmSource' => 'sales_sme'];
            }
        }

        $service_api = new waServicesApi();
        $res = $service_api->serviceCall('BANK_PARTNER', $data,
            waNet::METHOD_POST, ['request_format' => waNet::FORMAT_JSON],
            'tbank_application', false);

        if (ifset($res['status']) >= 300) {
            $errors = [
                'error' => ifset($res['response']['error']),
                'error_description' => ifset($res['response']['error_description']),
                'required_fields' => ifset($res['response']['required_fields'])
            ];
            if ($errors['error'] === 'system_error') {
                shopBusinessPluginHelper::attachErrorHTML($errors);
            }
            $this->errors = $errors;
            return;
        }


        if (!empty($res['response']['success'])) {
            $res['response']['message_html'] = <<<HTML
            <div class="custom-mt-16">
                <i class="fas fa-check-circle text-green" style="font-size:5rem;"></i>
                <h3>Заявка принята</h3>
                <p class="small">Заявка отправлена в Т-Банк. Пожалуйста, ожидайте ответа представителей банка.</p>
            </div>
HTML;
        }

        $this->response = $res['response'];
    }

}

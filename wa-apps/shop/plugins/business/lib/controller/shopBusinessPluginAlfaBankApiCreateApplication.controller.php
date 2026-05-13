<?php
class shopBusinessPluginAlfaBankApiCreateApplicationController extends waJsonController
{
    public function execute()
    {
        if (shopBusinessPluginHelper::isDisabledWAID()) {
            throw new JsonException('Необходимо подключить Webasyst ID');
        }

        $post = waRequest::post();
        if (!$post || !is_array($post)) {
            throw new JsonException('400 Bad Request');
        }

        if (!empty($post['productInfo'])) {
            $post['productInfo'] = array_values($post['productInfo']);
        }

        $service_api = new waServicesApi();
        $res = $service_api->serviceCall('BANK_PARTNER', $post,
            waNet::METHOD_POST, ['request_format' => waNet::FORMAT_JSON],
            'alfabank_application', false);

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

        if (!empty($res['response']['id'])) {
            $res['response']['message_html'] = <<<HTML
            <div class="custom-mt-16">
                <i class="fas fa-check-circle text-green" style="font-size:5rem;"></i>
                <h3>Заявка принята</h3>
                <p class="small">Заявка отправлена в Альфа-Банк. Пожалуйста, ожидайте ответа представителей банка.</p>
            </div>
HTML;
        }

        $this->response = $res['response'];
    }
}

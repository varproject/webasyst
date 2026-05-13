<?php

class shopBusinessPluginYookassaApiCreateApplicationController extends waJsonController
{
    public function execute()
    {
        if (shopBusinessPluginHelper::isDisabledWAID()) {
            throw new JsonException('Необходимо подключить Webasyst ID');
        }

        $post = waRequest::post();
        /*
        //TODO: пока передавать фото паспорта не будем
        $request = [];
        if (!empty($post['passportMain'])) {
            $request['passportMain'] = $post['passportMain'];
            unset($post['passportMain']);
        }
        $request['request'] = $post;
        */
        $request = $post;
        $request['personalDataProcessingConsent'] = true;

        $service_api = new waServicesApi();
        $res = $service_api->serviceCall('BANK_PARTNER', $request,
            waNet::METHOD_POST, ['request_format' => waNet::FORMAT_JSON],
            'yookassa_application', false);

        if (ifset($res['status']) >= 300) {
            $errors = [
                'error' => ifset($res['response']['error']),
                'error_description' => ifset($res['response']['error_description']),
                'required_fields' => ifset($res['response']['required_fields']),
            ];
            if ($errors['error'] === 'system_error') {
                shopBusinessPluginHelper::attachErrorHTML($errors);
            }
            $this->errors = $errors;
            return;
        }

        $response = $res['response'];

        // handle response
        if (!empty($response['status'])) {
            switch ($response['status']) {
                case 'PENDING':
                    $response['message_html'] = <<<HTML
            <div class="custom-mt-16">
                <i class="fas fa-clock text-gray" style="font-size:5rem;"></i>
                <h3>Подтвердите заявку по ссылке ниже</h3>
            </div>
HTML;
                    break;
                case 'BOARDING':
                    $response['message_html'] = <<<HTML
            <div class="custom-mt-16">
                <i class="fas fa-clock text-yellow" style="font-size:5rem;"></i>
                <h3>Идет подключение...</h3>
            </div>
HTML;
                    break;
                case 'SUCCEEDED':
                    $response['message_html'] = <<<HTML
            <div class="custom-mt-16">
                <i class="fas fa-check-circle text-green" style="font-size:5rem;"></i>
                <h3>Магазин подключен, можно принимать платежи</h3>
            </div>
HTML;
                    break;
                case 'REJECTED':
                    if (strtoupper(ifempty($response['rejectionReason'], '')) === 'COUNTERPARTYALREADYEXISTS') {
                        $response['message_html'] = <<<HTML
                        <div class="custom-mt-16">
                            <i class="fas fa-check-circle text-blue" style="font-size:5rem;"></i>
                            <h3>Вы уже зарегистрированы в ЮKassa</h3>
                        </div>
HTML;
                    } else {
                        $response['message_html'] = <<<HTML
                        <div class="custom-mt-16">
                            <i class="fas fa-times-circle text-orange" style="font-size:5rem;"></i>
                            <h3>Отказано в подключении</h3>
                        </div>
HTML;
                    }
                    break;
                case 'EXPIRED':
                    $response['message_html'] = <<<HTML
            <div class="custom-mt-16">
                <i class="far fa-calendar-times text-brown" style="font-size:5rem;"></i>
                <h3>Заявка истекла</h3>
            </div>
HTML;
                    break;
            }
        }

        $this->response = $response;
    }
}

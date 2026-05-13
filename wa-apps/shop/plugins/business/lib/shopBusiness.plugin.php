<?php

class shopBusinessPlugin extends shopPlugin
{
    /**
     * @throws waRightsException
     * @throws waException
     */
    public function backendSettingsSidebar()
    {
        if ($this->isInvalidPermission()) {
            return;
        }

        return [
            'sidebar_top_li' =>
                '<li><a href="?action=settings#/business/">
                    <span class="icon"><i class="fas fa-university"></i></span>
                    <span class="shop-sidebar-hidden">' . _wp('Business') . '</span>
                </a></li>
                <script>
                    if (!$.settings) { $.settings = {}; }
                    $.settings.businessPreLoad = function (params) {
                        let params_str = "";
                        if (params) {
                            params = params.split("/");
                            params_str += params[0] ? "&only_form=" + params[0] : "";
                            params_str += params[1] ? "&product=" + params[1] : "";
                        }
                        this.load("?plugin=business&action=settings" + params_str);
                        return true;
                    };
                </script>',
        ];
    }

    public function handleBackendSettingsPayments()
    {
        if ($this->isInvalidPermission()) {
            return;
        }

        $after_h1_content =
        '<div class="small semibold custom-mb-24">'.
            'Рекомендуем для онлайна и оплаты на сайте: '.
            '<a href="?action=settings#/business/TBank/">Т-Кассу</a>, '.
            '<a href="?action=settings#/business/AlfaBank/">Альфа-банк</a>, '.
            '<a href="?action=settings#/business/YooKassa/">ЮКассу</a>, '.
            '<a href="?action=settings#/business/Robokassa/">Робокассу</a><br>'.
            'Для офлайна и мобильной кассы: '.
            '<a href="?action=settings#/business/ModulBank/">Модулькассу</a>, '.
            '<a href="?action=settings#/business/TBank/Аренда торговый эквайринг/">aQsi 5</a>'.
        '</div>';

        $script_id = uniqid('business-payment-script-');
        $html = <<<HTML
<script id="{$script_id}">
    $(function () {
        const header_hint = $('.s-settings-payment-header-hint');
        header_hint.find('p:first').addClass('custom-mb-0');
        header_hint.append('{$after_h1_content}');
    });
</script>
HTML;

        echo $html;
    }

    public function handleBackendSettingsPaymentSetup()
    {
        $plugin_id = waRequest::get('plugin_id');
        if ($this->isInvalidPermission() || !$plugin_id || shopBusinessPluginHelper::isDisabledWAID()) {
            return;
        }

        $payment_id = $plugin_id;
        $is_first_add = true;
        if (wa_is_int($plugin_id)) {
            $plugin = shopPayment::getPlugin(null, (int)$plugin_id);
            $payment_id = $plugin->getId();
            $is_first_add = false;
        }

        $payment_id_to_bank_name = [
            'tinkoff'     => ['form' => 'TBank', 'product' => 'Интернет-эквайринг', 'verification_field' => 'terminal_key'],
            'yandexkassa' => ['form' => 'YooKassa',  'verification_field' => 'shop_id'],
            'modulkassa'  => ['form' => 'ModulBank'],
            'aqsi'        => ['form' => 'TBank', 'product' => 'Аренда торговый эквайринг'],
            'idalfabank'  => ['form' => 'AlfaBank'],
            'robokassa'   => ['form' => 'Robokassa'],
        ];
        if (empty($payment_id_to_bank_name[$payment_id])) {
            return;
        }

        if (!$is_first_add) {
            $verification_field = ifset($payment_id_to_bank_name[$payment_id]['verification_field']);
            if ($verification_field) {
                $verification_val = shopPayment::getPlugin(null, $plugin_id)->getSettings($verification_field);
                if ($verification_val) {
                    return;
                }
            } else {
                $status = shopPayment::getPluginInfo($plugin_id)['status'];
                if ($status == 1) {
                    return;
                }
            }
        }

        $params = http_build_query([
            'action' => 'LeadForm'.$payment_id_to_bank_name[$payment_id]['form'],
            'product' => ifempty($payment_id_to_bank_name[$payment_id], 'product', ''),
            'collapsible' => 1,
        ]);
        $loading_text = _wp('Loading...');
        echo <<<HTML
            <style>
                #s-settings-payment-setup > form .field { transition: opacity .2s; }
                #s-settings-payment-setup.is-overlay > form .field { opacity: 0.5; }
            </style>
            <script>
                $(function () {
                    const payment_setup_wrapper = $('#s-settings-payment-setup');
                    const wrapper = $('<div class="s-business-lead-form__wrapper" />');

                    payment_setup_wrapper.addClass('is-overlay');
                    wrapper.append('{$loading_text} <i class="fas fa-spinner fa-spin"></i>');
                    $.get('?plugin=business&{$params}', function (html) {
                        wrapper.empty().append(html);
                        $('#s-settings-payment-setup').prepend(wrapper);

                        payment_setup_wrapper.find('.js-hide-business-lead-form,.js-collapse-business-lead-form').one('click', function () {
                            payment_setup_wrapper.removeClass('is-overlay');
                            return true;
                        });
                        payment_setup_wrapper.find('> form :input').one('focus change', function (e) {
                            payment_setup_wrapper.removeClass('is-overlay');
                            return true;
                        });
                    });
                });
            </script>
HTML;

    }

    public function getSettingsDisclaimerHtml()
    {
        return '
            <p><button href="javascript:void(0)" class="js-business-go-offers button green larger rounded">Смотреть предложения партнеров</button></p>
            <script>
                $(function () {
                    $(".js-business-go-offers").one("click", function () {
                        if (typeof $.wa.switchToNewUI === "function") {
                            $.wa.switchToNewUI();
                        }
                        location.href = "?action=settings#/business/";
                    });
                });
            </script>
        ';

    }

    private function isInvalidPermission()
    {
        return wa()->whichUI() == '1.3' || wa()->getApp() !== 'shop';
    }
}

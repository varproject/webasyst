<?php

return [
    'name' => 'Business',
    'description' => /*_wp*/('Special offers from Webasyst partners'),
    'vendor' => 'webasyst',
    'version' => '1.1.2',
//    'shop_settings' => true,
    'handlers' => [
        'backend_settings' => 'backendSettingsSidebar',
        'controller_after.shopSettingsPaymentAction' => 'handleBackendSettingsPayments',
        'controller_after.shopSettingsPaymentSetupAction' => 'handleBackendSettingsPaymentSetup',
    ],
    'ui' => '2.0',
];
//EOF

<?php

class shopLkPluginFrontendAuthController extends waViewController
{
    // Единая точка входа для login/signup/forgotpassword/setpassword
    public function execute()
    {
        $this->setLayout(new shopLkPluginFrontendAuthLayout());

        // Если auth-страница открыта не через каноническое поселение, переносим на него
        shopLkPluginNavigation::redirectAuthToCanonical();

        $segment = shopLkPluginUrlSegment::get(1);

        switch ($segment) {
            case 'signup':
                $this->executeAction(new shopLkPluginFrontendSignupAction());
                break;

            case 'forgotpassword':
            case 'setpassword':
                $this->executeAction(new shopLkPluginFrontendForgotAction());
                break;

            case 'login':
            default:
                $this->executeAction(new shopLkPluginFrontendLoginAction());
                break;
        }
    }
}

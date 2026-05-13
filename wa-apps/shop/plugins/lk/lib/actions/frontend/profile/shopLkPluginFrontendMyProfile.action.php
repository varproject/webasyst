<?php

class shopLkPluginFrontendMyProfileAction extends waMyProfileAction
{
    public function execute()
    {
        parent::execute();

        $save_button_html = '
            <button type="submit" form="lk-profile-form" id="lk-profile-submit" class="btn btn-success btn-sm" disabled>
                Сохранить
            </button>
        ';

        $this->layout->assign('main_toolbar_enabled', false);
        $this->layout->assign('main_footer_left_items', [
            $save_button_html,
        ]);
        $this->layout->assign('main_footer_right_items', []);

        $this->view->assign([
            'plugin_static_url'  => wa('shop')->getPlugin('lk')->getPluginStaticUrl(),
            'profile_action_url' => wa()->getConfig()->getRequestUrl(false, true),
            'profile_back_url'   => wa()->getRouteUrl('shop/frontend/my'),
        ]);
    }

    protected function getForm()
    {
        $form = parent::getForm();
        // dd($form);

        $order = [
            'photo',
            'lastname',
            'firstname',
            'middlename',
            'phone',
            'email',
            'birthday',
            'sex',
            // 'im',
            // 'socialnetwork',
            'locale',
            'timezone',
            // 'address',
            'password',
            'password_confirm',
        ];

        $fields = [];

        foreach ($order as $id) {
            if (isset($form->fields[$id])) {
                $fields[$id] = $form->fields[$id];
            }
        }

        $form->fields = $fields;

        return $form;
    }
}

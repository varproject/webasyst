<?php

class shopB2bPluginFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->view->assign(array(
            'b2b_static_url' => wa('shop')->getPlugin('b2b')->getPluginStaticUrl(),
            'b2b_user' => wa()->getUser(),
        ));
    }
}

<?php

class shopBtobPluginFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->view->assign(array(
            'btob_static_url' => wa('shop')->getPlugin('btob')->getPluginStaticUrl(),
            'btob_user' => wa()->getUser(),
        ));
    }
}

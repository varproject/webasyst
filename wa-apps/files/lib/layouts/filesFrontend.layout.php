<?php

class filesFrontendLayout extends waLayout
{
    public function execute()
    {
        /**
         * Include plugins js and css
         * @event frontend_assets
         * @return array[string]string $return[%plugin_id%] Extra head tag content
         */
        $frontend_assets = wa('files')->event('frontend_assets');
        $this->assign('frontend_assets', $frontend_assets);

        $this->setThemeTemplate('index.html');
    }
}

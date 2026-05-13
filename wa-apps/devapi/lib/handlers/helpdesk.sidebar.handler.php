<?php

/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiHelpdeskSidebarHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $js = '';
        if ((new waAppSettingsModel())->get('devapi', 'helpdesk')) {
            $wa_url = wa()->getConfig()->getRootUrl();
            $js = <<<JS
<script>
$.wa.loadSources( [
            {
                id: 'devapi-css',
                type: 'css',
                uri: '{$wa_url}wa-apps/devapi/css/devapi.css'
            }
        ] );
</script>
JS;
        }
        return $js;
    }
}
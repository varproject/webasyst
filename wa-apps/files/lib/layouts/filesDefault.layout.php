<?php

class filesDefaultLayout extends waLayout
{
    public function execute()
    {
        $this->executeAction('sidebar', new filesBackendSidebarAction());
        $this->executeAction('uploader', new filesUploadAction());
        $this->assign('js_options', filesApp::inst()->getConfig()->getOptionsForJs());

        $sm = new filesSourceModel();
        $this->assign('source_count', $sm->countAllSources());

        /**
         * Include plugins js and css
         * @event backend_assets
         * @return array[string]string $return[%plugin_id%] Extra head tag content
         */
        $backend_assets = wa('files')->event('backend_assets');
        $this->assign('backend_assets', $backend_assets);

        /**
         * Custom HTML blocks from plugins
         * @event backend_layout_blocks
         * @return array[string]string $return[%plugin_id%]
         */
        $params = array(
            'layout' => $this
        );
        $backend_layout_blocks = wa('files')->event('backend_layout', $params);
        $this->assign('backend_layout_blocks', $backend_layout_blocks);
    }
}

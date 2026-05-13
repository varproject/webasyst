<?php

class logsFrontendNavigationAction extends waViewAction
{
    public function execute()
    {
        $actions = [];

        switch (waRequest::param('action')) {
            case 'fileView':
                $download_url = wa()->getRouteUrl('/frontend/fileDownload', [
                    'hash' => waRequest::param('hash'),
                    'path' => waRequest::param('path'),
                ]);

                $actions['download'] = [
                    'icon_class' => logsHelper::getIconClass('download'),
                    'url' => $download_url,
                    'title' => _w('Download')
                ];

                break;
        }

        $path_parts = [
            'folder' => 'wa-log/',
            'name' => waRequest::param('path'),
        ];

        $this->view->assign('actions', $actions);
        $this->view->assign('item', $path_parts);
    }
}

<?php

class logsBackendController extends waViewController
{
    public function execute()
    {
        if (!waRequest::get()) {
            // app's home page

            if (
                logsLicensing::check()->hasPremiumLicense()
                && $this->getUser()->getSettings($this->getAppId(), 'remember_sort_mode')
            ) {
                $sort_mode = (string) $this->getUser()->getSettings($this->getAppId(), 'sort_mode');
            }

            if (!empty($sort_mode)) {
                // sort mode is applied & remembered

                $redirect_url_params = $sort_mode == 'directory'
                    ? [
                        'mode' => $sort_mode,
                    ]
                    : [
                        'mode' => $sort_mode,
                        'action' => 'files',
                    ];

                $this->getResponse()->redirect('?' . http_build_query($redirect_url_params));
            } else {
                // remembering setting not saved yet

                if (strpos(waRequest::server('HTTP_REFERER'), logsHelper::getLogsBackendUrl()) !== 0) {
                    // first app access

                    $this->redirect('?action=files&mode=updatetime');
                } else {
                    // repeated access from within the app
                    $this->executeAction(new logsBackendDirectoryAction());
                }
            }
        } else {
            // otherwise view a directory according to the GET request
            $this->executeAction(new logsBackendDirectoryAction());
        }
    }
}

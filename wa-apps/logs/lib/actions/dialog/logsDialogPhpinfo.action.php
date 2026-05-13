<?php

class logsDialogPhpinfoAction extends waViewAction
{
    public function execute()
    {
        try {
            if (!function_exists('phpinfo')) {
                throw new Exception(_w('Function <tt>phpinfo()</tt>, used to display the PHP configuration, is not available on your server.'));
            }

            if (!$this->getRights('view_phpinfo')) {
                throw new Exception(_w('You have no access rights to view the PHP configuration.'));
            }

            $can_publish_phpinfo = $this->getRights('publish_phpinfo');

            if ($can_publish_phpinfo) {
                $this->view->assign('can_publish_phpinfo', $can_publish_phpinfo);

                if ((bool) wa()->getRouting()->getByApp($this->getAppId())) {
                    $published_data = (new logsPublishedModel())->getByField([
                        'path' => '//phpinfo//',
                    ]);

                    $published_url = $published_data ? wa()->getRouteUrl('logs/frontend/phpinfo', [
                        'hash' => $published_data['hash'],
                    ], true) : '';

                    $this->view->assign('published', $published_data);
                    $this->view->assign('url', $published_url);
                } else {
                    $this->view->assign(
                        'controls_error',
                        sprintf_wp(
                            'Add a rule for Logs in the <a %s>Site</a> app’s settings to create a public link.',
                            sprintf(
                                'href="%s"',
                                wa()->getAppUrl('site')
                            )
                        )
                    );
                }
            }
        } catch (Exception $e) {
            $this->view->assign('error', logsHelper::getIconHtml('fas fa-exclamation-triangle') . ' ' . $e->getMessage());
        }
    }
}

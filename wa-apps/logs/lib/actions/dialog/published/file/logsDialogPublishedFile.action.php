<?php

class logsDialogPublishedFileAction extends waViewAction
{
    public function execute()
    {
        $path = waRequest::get('path', '');

        try {
            if (!$this->getRights('publish_files')) {
                throw new Exception(_w('You have no access rights to publish or unpublish log files.'));
            }

            $routing_exists = (bool) wa()->getRouting()->getByApp($this->getAppId());

            if (!$routing_exists) {
                throw new Exception(
                    sprintf_wp(
                        'Add a rule for Logs in the <a %s>Site</a> app’s settings to create a public link.',
                        sprintf(
                            'href="%s"',
                            wa()->getAppUrl('site') . 'settings/'
                        )
                    )
                );
            }

            $published_model = new logsPublishedModel();
            $published_file = $published_model->getByField([
                'path' => $path,
            ]);

            $url = $published_file ? wa()->getRouteUrl(
                'logs/frontend/fileView',
                [
                    'hash' => $published_file['hash'],
                    'path' => $path,
                ],
                true
            ) : '';

            $controls = [
                'status' => [
                    'value' => [
                        'control_type' => waHtmlControl::CHECKBOX,
                        'value' => 1,
                        'checked' => !empty($published_file),
                        'class' => 'published-status-ibutton',
                        'id' => 'published-status-selector-checkbox',
                        'data' => [
                            'path' => $path,
                        ],
                    ],
                ],
                'url' => [
                    'value' => [
                        'control_type' => waHtmlControl::INPUT,
                        'value' => $url,
                        'disabled' => true,
                        'class' => 'published-url long',
                        'control_wrapper' => '%s%s%s' . ' <a href="' . $url . '" target="_blank"><i class="fas fa-external-link-alt"></i></a>',
                    ],
                ],
                'password' => [
                    'path' => [
                        'control_type' => waHtmlControl::HIDDEN,
                        'value' => $path,
                    ],
                    'value' => [
                        'control_type' => waHtmlControl::INPUT,
                        'value' => ifset($published_file, 'password', ''),
                        'readonly' => true,
                        'class' => 'auto-copy cursor-pointer published-password-value',
                        'data' => [
                            'loc-copied' => _w('Copied to clipboard.'),
                        ],
                    ],
                ],
            ];

            foreach ($controls as &$group_controls) {
                foreach ($group_controls as $control_name => &$control) {
                    $control = waHtmlControl::getControl($control['control_type'], $control_name, $control);
                }
            }
            unset($group_controls, $control);

            $this->view->assign('controls', $controls);
            $this->view->assign('published_file', $published_file);
        } catch (Throwable $throwable) {
            $this->view->assign('error', logsHelper::getIconHtml('fas fa-exclamation-triangle') . ' ' . $throwable->getMessage());
        }

        $this->view->assign('file', logsHelper::getPathParts($path));
    }
}

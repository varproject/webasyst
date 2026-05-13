<?php

class logsDialogTrackedFileAction extends waViewAction
{
    public function execute()
    {
        $path = waRequest::get('path', '');

        try {
            $tracked_model = new logsTrackedModel();
            $tracked_file = $tracked_model->getByField([
                'contact_id' => $this->getUserId(),
                'path' => $path,
            ]);

            $controls = [
                'status' => [
                    'control_type' => waHtmlControl::CHECKBOX,
                    'value' => 1,
                    'checked' => !empty($tracked_file),
                    'class' => 'tracked-status-ibutton',
                    'id' => 'tracked-status-selector-checkbox',
                    'data' => [
                        'path' => $path,
                    ],
                ],
                'hint' => [
                    'control_type' => waHtmlControl::HELP,
                    'value' => 'asd',
                ],
            ];

            foreach ($controls as $control_name => &$control) {
                $control = waHtmlControl::getControl($control['control_type'], $control_name, $control);
            }
            unset($control);

            $this->view->assign('controls', $controls);
            $this->view->assign('tracked_file', $tracked_file);
            $this->view->assign('show_email_notifications_promo', logsHelper::mustDisplayPremiumPromo('tracked-email-notifications'));
        } catch (Exception $e) {
            $this->view->assign('error', $e->getMessage());
        }

        $this->view->assign('file', logsHelper::getPathParts($path));
    }
}

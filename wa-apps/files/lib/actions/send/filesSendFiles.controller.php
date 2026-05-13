<?php

class filesSendFilesController extends filesController
{

    public function execute()
    {
        $data = wa()->getRequest()->request('data', null, waRequest::TYPE_ARRAY);
        if (!$data || empty($data['files'])) {
            throw new waException('Empty post data');
        }
        $file_ids = $data['files'];
        if (!is_array($file_ids)) {
            $file_ids = preg_split('/\s*,\s*/', $file_ids);
        }
        $to = trim($data['to']);

        $parser = new waMailAddressParser($to);

        try {
            $mail_addresses = $parser->parse();
        } catch (waException $e) {
            $this->setError($e->getMessage(), 'to');
            return false;
        }
        if (!$mail_addresses) {
            $this->setError(_w('Please specify valid email address'), 'to');
            return false;
        }
        $validator = new waEmailValidator();
        $to = array();
        foreach ($mail_addresses as $e) {
            if (!$validator->isValid($e['email'])) {
                $this->setError(_w('Invalid email address') . ': ' . $e['email'], 'to');
                return false;
            }
            $to[$e['email']] = $e['name'];
        }

        $col = new filesCollection('list/' . implode(',', $file_ids), array(
            'check_rights' => true,
            'workup' => false,
            'filter' => array(
                'type' => filesFileModel::TYPE_FILE
            )
        ));
        $files = $col->getItems('*', 0, count($file_ids));

        $subject = ifset($data['subject']);
        $content = ifset($data['text']);

        $message = array(
            'subject' => $subject,
            'content' => $content,
            'address' => $to,
            'files' => $files
        );
        $queue_model = new filesMessagesQueueModel();
        $queue_model->pushMessage($message);

        $this->assign(array(
            'success' => true
        ));
        return true;
    }

}

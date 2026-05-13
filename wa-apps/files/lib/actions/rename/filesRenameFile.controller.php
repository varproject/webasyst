<?php

class filesRenameFileController extends filesController
{

    public function execute()
    {
        $file = $this->getFile();

        if ($this->getFileModel()->inSync($file)) {
            $sync_err = $this->getInSyncError();
            $this->errors['sync'] = array(
                'name' => 'sync',
                'msg' => $sync_err['msg']
            );
            return;
        }

        $name = $this->getName();
        $errors = $this->validate($name);
        if (!empty($errors)) {
            $this->errors = $errors;
            return;
        }

        if ($file['name'] === $name) {
            $this->assign(array(
                'file' => $file
            ));
            return;
        }

        if ($this->getFileModel()->rename($file['id'], $name)) {

            /**
             * Rename file or folder
             * @event file_rename
             * @params array[string]int $params['id']
             * @params array[string]string $params['old_name']
             * @params array[string]string $params['new_name']
             */
            $params = array('id' => $file['id'], 'old_name' => $file['name'], 'new_name' => $name);
            wa()->event('file_rename', $params);

            $file = $this->getFileModel()->getItem($file['id'], false, true);

            $this->logAction('rename', $file['id']);

            $this->assign(array(
                'file' => $file
            ));
        }
    }

    public function validate($name)
    {
        $errors = array();

        if (strlen($name) <= 0) {
            $errors[] = array(
                'name' => 'name',
                'msg' => _w('This field is required')
            );
        }

        $banned_symbols = $this->getFileModel()->getBannedSymbols();
        if (preg_match($banned_symbols, $name)) {
            $errors[] = array(
                'name' => 'name',
                'msg' => _w('There are a forbidden symbols')
            );
        }

        return $errors;
    }

    /**
     * @return array|bool
     * @throws waException
     * @throws waRightsException
     */
    public function getFile()
    {
        $file_id = wa()->getRequest()->post('id');
        $file = $this->getFileModel()->getItem($file_id);
        if (!$file) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
        }
        $files = array($file_id => $file);
        $allowed = filesRights::inst()->dropUnallowedToMove($files);
        if (!$allowed) {
            filesApp::inst()->reportAboutError($this->getAccessDeniedError());
        }
        return $file;
    }

    public function getName()
    {
        return wa()->getRequest()->post('name', '', waRequest::TYPE_STRING_TRIM);
    }

}

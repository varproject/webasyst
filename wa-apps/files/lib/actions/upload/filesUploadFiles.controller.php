<?php

class filesUploadFilesController extends filesController
{

    public function execute()
    {
        $this->getStorage()->close();

        // work with folder and storage
        $folder_id = $this->getFolderId();
        $storage_id = $this->getStorageId();
        $source_id = $this->getSourceId($storage_id, $folder_id);

        if (!$folder_id && !$storage_id) {
            $storage = $this->getStorageModel()->getPersistenStorage();
            if (!$storage) {
                $this->reportAboutError($this->getAccessDeniedError());
            }
            $storage_id = $storage['id'];
        }

        $files = array();

        foreach ($this->getFiles($storage_id, $folder_id) as $file_info) {

            $file = null;
            $replace = 0;
            if (!is_array($file_info)) {
                $file = $file_info;
            } else if (isset($file_info['file'])) {
                $file = $file_info['file'];
                $replace = ifset($file_info['replace'], 0);
            }

            if (!$file) {
                $files[] = array(
                    'name' => $file_info['name'],
                    'error' => ifset($file_info['error'], 'Unknown error')
                );
            } else if ($file->error_code != UPLOAD_ERR_OK) {
                $files[] = array(
                    'name' => $file->name,
                    'error' => $file->error
                );
            } else {
                try {
                    $files[] = $this->save(
                        $file,
                        array(
                            'parent_id' => $folder_id,
                            'storage_id' => $storage_id,
                            'source_id' => $source_id
                        ),
                        $replace
                    );
                } catch (Exception $e) {
                    $files[] = array(
                        'name' => $file->name,
                        'error' => $e->getMessage()
                    );
                }
            }
        }

        if ($files) {
            $ids = array();
            foreach ($files as $f) {
                if (isset($f['id'])) {
                    $ids[] = $f['id'];
                }
                /**
                 * @event file_upload
                 */
                wa()->event('file_upload', $f);
            }
            $this->logAction('files_upload', join(',', $ids));
        }

        $this->assign(array(
            'files' => $files,
            'folder_id' => $folder_id,
            'storage_id' => $storage_id
        ));
    }

    public function getFiles($storage_id, $folder_id)
    {
        if (wa()->getRequest()->server('HTTP_X_FILE_NAME')) {
            $name = wa()->getRequest()->server('HTTP_X_FILE_NAME');
            $size = wa()->getRequest()->server('HTTP_X_FILE_SIZE');

            $safe_name = trim(preg_replace('~[^a-z\.]~', '', waLocale::transliterate($name)), ". \n\t\r");
            $safe_name || ($safe_name = uniqid('p'));
            $file_path = wa()->getTempPath($this->getAppId() . '/upload/') . $safe_name;

            $append_file = is_file($file_path) && $size > filesize($file_path);
            clearstatcache();
            file_put_contents(
                $file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0
            );
            $file = new waRequestFile(array(
                'name' => $name,
                'type' => wa()->getRequest()->server('HTTP_X_FILE_TYPE'),
                'size' => $size,
                'tmp_name' => $file_path,
                'error' => UPLOAD_ERR_OK
            ));
            $files = array($file);
        } else {
            $files = array();
            foreach (wa()->getRequest()->file('files') as $file) {
                $files[] = $file;
            }
        }

        $file_names = array();
        foreach ($files as $file) {
            $file_names[] = $file->name;
        }

        /**
         * @var string solve
         * @see method phpdoc for clarifying situation
         */
        $solve = $this->getSolve();

        /**
         * @var array Array of upload files that has name conflict
         */
        $conflict_files = $this->getFileModel()->getFilesByNames($file_names, $storage_id, $folder_id);
        if ($conflict_files) {
            foreach ($conflict_files as $file_info) {
                $conflict_name = $file_info['name'];
                foreach ($files as &$file) {
                    if ($file->name === $conflict_name) {
                        if ($solve === 'exclude') {
                            $file = array(
                                'name' => $file->name,
                                'error' => _w('Excluded from upload because of name conflict.')
                            );
                        } else if ($solve === 'replace') {
                            $file = array(
                                'file' => $file,
                                'replace' => $file_info['id']
                            );
                        } else {
                            // case rename is default solve behavior implemented in model method
                            // so nothing to do
                        }
                    }
                }
                unset($file);
            }
        }

        return $files;
    }

    public function dropFilesByNames($files, $names)
    {
        foreach ($files as $file_id => $file) {
            if ($file['type'] === filesFileModel::TYPE_FILE && in_array($file['name'], $names)) {
                unset($files[$file_id]);
            }
        }
        return $files;
    }

    protected function save(waRequestFile $file, $data, $replace)
    {
        // check in copy process
        if ($replace) {
            $in_copy_process = $this->getFileModel()->select('in_copy_process')->where('id = ?', $replace)->fetchField();
            if ($in_copy_process) {
                $replace = false;
            }
        }

        // check files lock
        if ($replace) {
            $lm = new filesLockModel();
            $res = $lm->sliceOffLocked(array($replace), filesLockModel::RESOURCE_TYPE_FILE, filesLockModel::SCOPE_EXCLUSIVE);
            if (empty($res)) {
                $replace = false;
            }
        }

        if ($replace) {
            $data['id'] = $replace;
            $id = $this->getFileModel()->replaceFile($data, $file);
        } else {

            if ($data['parent_id']) {
                $folder = $this->getFileModel()->getFolder($data['parent_id']);
                $source_id = $folder['source_id'];
            } else {
                $storage = $this->getStorageModel()->getStorage($data['storage_id']);
                $source_id = $storage['source_id'];
            }
            $data['source_id'] = $source_id;

            $id = $this->getFileModel()->addFile($data, $file);
        }
        if (!$id) {
            throw new waException(_w("Saving error"));
        }
        $file = $this->getFileModel()->getFile($id);
        return $file;
    }

    public function getFolderId()
    {
        $folder_id = wa()->getRequest()->post('folder_id', 0, waRequest::TYPE_INT);
        if ($folder_id && filesRights::inst()->canAddNewFilesIntoFolder($folder_id) && !$this->getFileModel()->inSync($folder_id)) {
            return $folder_id;
        }
        return 0;
    }

    public function getStorageId()
    {
        $storage_id = wa()->getRequest()->post('storage_id', null, waRequest::TYPE_INT);
        if ($storage_id && filesRights::inst()->canAddNewFilesIntoStorage($storage_id) && !$this->getStorageModel()->inSync($storage_id)) {
            return $storage_id;
        }
        return 0;
    }

    public function getSourceId($storage_id, $folder_id)
    {
        $source_id = 0;
        $folder = $this->getFileModel()->getFolder($folder_id);
        if ($folder) {
            $storage = $this->getStorageModel()->getById($folder['storage_id']);
        } else {
            $storage = $this->getStorageModel()->getById($storage_id);
        }
        if ($storage) {
            $source_id = $storage['source_id'];
        }
        return $source_id;
    }

    /**
     *
     * Get solve behavior
     *
     * Solve by three options: exclude, replace, rename
     * If exclude - we just ignore that upload files that has name conflict
     * If replace - call later instead of addFile replaceFile method
     * If rename - just call addFile. Rename is default solve behavior implemented in model method
     *
     * @return string solve
     */
    public function getSolve()
    {
        return wa()->getRequest()->post('solve', '', waRequest::TYPE_STRING_TRIM);
    }

    public function display($clear_assign = true)
    {
        $this->getResponse()->sendHeaders();
        echo json_encode($this->assigns);
    }

}

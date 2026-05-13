<?php

class filesMoveFilesController extends filesController
{
    protected $start_time;
    protected $max_execution_time;
    protected $process_id;
    protected $max_time_per_one_external_file = 10;
    protected $storage_id;
    protected $folder_id;

    public function __construct($params = null)
    {
        $this->start_time = time();
        $this->max_execution_time = $this->getConfig()->getMaxExecutionTime();

        parent::__construct($params);

        $copytask = new filesCopytaskModel();
        $this->process_id = $copytask->generateProcessId();

        // get destination place and check it
        $folder_id = $this->getFolderId();
        $storage_id = $this->getStorageId();
        $error = $this->checkDestination($storage_id, $folder_id);
        if ($error) {
            $this->setError($error['msg']);
            return;
        }

        $this->storage_id = $storage_id;
        $this->folder_id = $folder_id;
    }

    public function execute()
    {
        // get files and find out is there conflict
        $res = $this->getFiles();

        // unallowed to move files exist, terminate action and send response about it
        if ($res['unallowed_count'] > 0) {
            $this->assign(array(
                'unallowed_exist' => true
            ));
            return;
        }

        $files = $res['allowed'];

        $conflict = $this->getFileModel()->getConflictFiles(
            $files,
            $this->storage_id,
            $this->folder_id
        );
        $solve = $this->getSolve();
        if ($conflict && !in_array($solve, array('replace', 'exclude', 'rename'))) {
            $this->assign(array(
                'files_conflict' => true,
                'files' => $files
            ));
            return;
        }

        // separate to 2 groups: ones that do not require external api calls, ones that do
        // name on group - light and tough
        // treat them differently
        $res = $this->separate($files);
        $light_files = $res['light_files'];
        $tough_files = $res['tough_files'];

        $res_success = true;
        $moved_files = array();

        try {
            if ($light_files) {
                $success = $this->moveLightItems($light_files);
                if ($success) {
                    $moved_files = array_merge($moved_files, array_keys($light_files));
                }
                $res_success = $res_success && $success;
            }
            if ($tough_files) {
                $success = $this->moveToughItems($tough_files);
                if ($success) {
                    $moved_files = array_merge($moved_files, array_keys($light_files));
                }
                $res_success = $res_success && $success;
            }
        } catch (filesException $e) {
            $this->errors = $e->getMessage();
        }

        $action_id = $this->isRestore() ? 'restore' : 'move';
        if ($moved_files) {
            $this->logAction($action_id, join(',', $moved_files));
        }

        $res = array(
            'success' => $res_success,
            'count' => count($moved_files)
        );

        $queue = new filesTasksQueueModel();
        $copytask = new filesCopytaskModel();
        $count = $queue->countByField('process_id', $this->process_id) +
            $copytask->countByField('process_id', $this->process_id);
        if ($count > 0) {
            $res['process_id'] = $this->process_id;
        }

        $this->assign($res);
    }

    public function getFiles()
    {
        $file_ids = wa()->getRequest()->request('file_id', null, waRequest::TYPE_ARRAY_INT);
        if (!$file_ids) {
            return array();
        }
        $files = $this->getFileModel()->getById($file_ids);
        if ($this->isRestore()) {
            $allowed = filesRights::inst()->dropHasNotAnyAccess($files);
        } else {
            $allowed = filesRights::inst()->dropUnallowedToMove($files);
        }

        $allowed = $this->dropInSync($allowed);

        return array(
            'allowed' => $allowed,
            'unallowed_count' => count($files) - count($allowed)
        );
    }

    public function dropInSync($files)
    {
        $sync_map = $this->getFileModel()->inSync($files);
        foreach ($files as $id => $file) {
            if (!empty($sync_map[$id])) {
                unset($files[$id]);
            }
        }
        return $files;
    }

    public function isRestore()
    {
        return wa()->getRequest()->request('restore');
    }

    public function solveByReplace($conflict)
    {
        if (!filesRights::inst()->canReplaceFiles($conflict['dst_conflict_files'])) {
            return $this->getAccessDeniedError();
        }
        $file_to_delete = array();
        foreach (array_keys($conflict['dst_conflict_files']) as $file_id) {
            if (isset($conflict['src_conflict_files'][$file_id])) {
                unset($conflict['src_conflict_files'][$file_id]);
            } else {
                $file_to_delete[] = $file_id;
            }
        }

        // check in_copy_process
        $in_copy_process = $this->getFileModel()->select('in_copy_process, id')->where('id IN (:ids)', array(
            'ids' => $file_to_delete
        ))->fetchAll('id', true);

        // drop in_copy_process files
        foreach ($file_to_delete as $index => $file_id) {
            if (!empty($in_copy_process[$file_id])) {
                unset($file_to_delete[$index]);
            }
        }

        // slice off locked files too
        $lm = new filesLockModel();
        $file_to_delete = $lm->sliceOffLocked($file_to_delete, filesLockModel::RESOURCE_TYPE_FILE, filesLockModel::SCOPE_EXCLUSIVE);

        if ($file_to_delete) {
            $this->getFileModel()->delete($file_to_delete);
        }
        if (!$conflict['src_conflict_files']) {
            return array('msg' => _w("Files from the same place"));
        }
        return null;
    }

    public function getExcludeNames($conflict)
    {
        return filesApp::getFieldValues($conflict['src_conflict_files'], 'name');
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

    public function getSolve()
    {
        return wa()->getRequest()->post('solve', '', waRequest::TYPE_STRING_TRIM);
    }

    public function getFolderId()
    {
        return wa()->getRequest()->post('folder_id', 0, waRequest::TYPE_INT);
    }

    public function getStorageId()
    {
        return wa()->getRequest()->post('storage_id', 0, waRequest::TYPE_INT);
    }

    public function checkDestination($storage_id, $folder_id)
    {
        if (!$storage_id && !$folder_id) {
            return $this->getPageNotFoundError(_w('Choose destination folder or storage'));
        }
        if (!$folder_id) {
            if (!filesRights::inst()->canAddNewFilesIntoStorage($storage_id)) {
                return $this->getAccessDeniedError();
            }
        } else {
            if (!filesRights::inst()->canAddNewFilesIntoFolder($folder_id)) {
                return $this->getAccessDeniedError();
            }
        }
        return null;
    }

    public function isCheckCountFirst()
    {
        return wa()->getRequest()->request('check_count_first');
    }

    protected function separate($files)
    {
        if ($this->folder_id) {
            $folder = $this->getFileModel()->getFolder($this->folder_id);
            $source_id = ifset($folder, 'source_id', 0);
        } else {
            $storage = $this->getStorageModel()->getStorage($this->storage_id);
            $source_id = ifset($storage, 'source_id', 0);
        }

        if ($source_id > 0) {
            return array('light_files' => array(), 'tough_files' => $files);
        }

        $light_files = array();
        $tough_files = array();

        foreach ($files as $file) {
            if ($file['source_id'] > 0) {
                $tough_files[$file['id']] = $file;
            } else {
                $light_files[$file['id']] = $file;
            }
        }
        return array('light_files' => $light_files, 'tough_files' => $tough_files);
    }

    protected function moveLightItems($files)
    {
        $threshold = 250;
        $usual = array_slice($files, 0, $threshold, true);
        $async = array_slice($files, $threshold, null, true);
        return $this->moveUsualAndAsyncGroups($usual, $async);
    }

    protected function moveToughItems($items)
    {
        $files = array();
        $folders = array();
        foreach ($items as $item) {
            if ($item['type'] === filesFileModel::TYPE_FILE) {
                $files[$item['id']] = $item;
            } else {
                $folders[$item['id']] = $item;
            }
        }
        $success = true;
        $success = $success && $this->moveToughFiles($files);
        $success = $success && $this->moveToughFolders($folders);
        return $success;
    }

    /**
     * @param $files
     * @param bool $is_async
     * @return array
     */
    protected function move($files, $is_async = false)
    {
        $conflict = $this->getFileModel()->getConflictFiles(
            $files,
            $this->storage_id,
            $this->folder_id
        );

        // is there name conflict?
        $solve = null;
        if ($conflict) {
            // how to solve name conflict
            $solve = $this->getSolve();
            if ($solve === 'exclude') {
                $files = $this->dropFilesByNames($files, $this->getExcludeNames($conflict));
            } else if ($solve === 'replace') {
                $error = $this->solveByReplace($conflict);
                if ($error) {
                    $this->setError($error['msg']);
                    return false;
                }
            }
        }

        $options = array('process_id' => $this->process_id);
        if ($is_async) {
            $options['is_async'] = true;
            $options['async_params'] = array(
                'replace' => $solve === 'replace',
                'restore' => !!$this->isRestore()
            );
        }
        $res = $this->getFileModel()->moveItems(
            array_keys($files),
            $this->storage_id,
            $this->folder_id,
            $options
        );
        return $res ? true : false;
    }

    protected function moveToughFiles($files)
    {
        $elapsed_time = time() - $this->start_time;
        $rest_time = $this->max_execution_time - $elapsed_time;
        $max_time_per_one_file = 10;    // because of execution remote call
        $threshold = intval($rest_time / $max_time_per_one_file);
        return $this->moveUsualAndAsyncGroups(
            array_slice($files, 0, $threshold, true),
            array_slice($files, $threshold, null, true)
        );
    }

    protected function moveToughFolders($folders)
    {
        $fm = $this->getFileModel();

        $elapsed_time = time() - $this->start_time;
        $rest_time = $this->max_execution_time - $elapsed_time;
        $max_time_per_one_file = 10;    // because of execution remote call
        $threshold = intval($rest_time / $max_time_per_one_file);

        $folders_ids = array_keys($folders);

        $counters = $fm->countChildren($folders_ids, true);
        asort($counters);

        $total_count = 0;
        $usual = array();
        $async = array();
        foreach ($counters as $folder_id => $child_folders_count) {
            $folder = $folders[$folder_id];
            $count = $folder['count'] + $child_folders_count + 1;
            $total_count += $count;
            if ($total_count <= $threshold) {
                $usual[$folder_id] = $folders[$folder_id];
            } else {
                $async[$folder_id] = $folders[$folder_id];
            }
        }
        return $this->moveUsualAndAsyncGroups($usual, $async);
    }

    /**
     * @param $usual
     * @param $async
     * @return bool
     */
    protected function moveUsualAndAsyncGroups($usual, $async)
    {
        $success = true;
        if ($usual) {
            $success = $success && $this->move($usual);
        }
        if ($async) {
            $success = $success && $this->move($async, true);
        }
        return $success;
    }
}

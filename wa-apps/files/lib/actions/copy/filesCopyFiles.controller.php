<?php

class filesCopyFilesController extends filesController
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
        $file_ids = $this->getFiles();
        $files = $this->getFileModel()->getById($file_ids);

        // separate to 2 groups: ones that do not require external api calls, ones that do
        // name on group - light and tough
        // treat them differently
        $res = $this->separate($files);
        $light_files = $res['light_files'];
        $tough_files = $res['tough_files'];

        try {
            if ($light_files) {
                $light_res = $this->copyLightItems($light_files);
            }
            if ($tough_files) {
                $tough_res = $this->copyToughItems($tough_files);
            }

            $res = $this->mergeCopyResults(
                !empty($light_res) ? $light_res : array(),
                !empty($tough_res) ? $tough_res : array()
            );

            $this->logAction('copy', join(',', $res['file_ids']));

            $queue = new filesTasksQueueModel();
            $copytask = new filesCopytaskModel();
            $queue_count = $queue->countByField('process_id', $this->process_id);
            $copytask_count = $copytask->countByField('process_id', $this->process_id);
            $count = $queue_count + $copytask_count;
            if ($count > 0) {
                $res['process_id'] = $this->process_id;
            }

            $this->assign($res);

        } catch (filesException $e) {
            $this->errors = $e->getMessage();
        }
    }

    public function getFiles()
    {
        $file_ids = wa()->getRequest()->request('file_id', array(), waRequest::TYPE_ARRAY_INT);
        if (!$file_ids) {
            return array();
        }
        $allowed = filesRights::inst()->dropUnallowedToCopy($file_ids);
        return array_slice($allowed, 0, 500, true);
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
            return $this->getPageNotFoundError();
        }
        if (!$folder_id) {
            if (!filesRights::inst()->canAddNewFilesIntoStorage($storage_id)) {
                return $this->getAccessDeniedError();
            }
            if ($this->getStorageModel()->inSync($storage_id)) {
                return $this->getInSyncError();
            }
        } else {
            if (!filesRights::inst()->canAddNewFilesIntoFolder($folder_id)) {
                return $this->getAccessDeniedError();
            }
            if ($this->getFileModel()->inSync($folder_id)) {
                return $this->getInSyncError();
            }
        }
        return null;
    }

    protected function separate($files)
    {
        if ($this->folder_id) {
            $folder = $this->getFileModel()->getFolder($this->folder_id);
            $source_id = $folder['source_id'];
        } else {
            $storage = $this->getStorageModel()->getStorage($this->storage_id);
            $source_id = $storage['source_id'];
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

    protected function copyLightItems($files)
    {
        $file_ids = array_keys($files);

        //TODO: first slice make usual, last slice make async
        $res = $this->copy($file_ids, count($file_ids) > 250);

        if (!is_array($res)) {
            $res = array(
                'files_count' => 0,
                'folders_count' => 0,
                'count' => 0,
            );
        }

        $res['file_ids'] = $file_ids;

        return $res;
    }

    protected function copyToughItems($items)
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
        $files_res = $this->copyToughFiles($files);
        $folders_res = $this->copyToughFolders($folders);
        return $this->mergeCopyResults(
            !empty($files_res) ? $files_res : array(),
            !empty($folders_res) ? $folders_res : array()
        );
    }

    protected function copyToughFiles($files)
    {
        $elapsed_time = time() - $this->start_time;
        $rest_time = $this->max_execution_time - $elapsed_time;
        $max_time_per_one_file = 10;    // because of execution remote call
        $threshold = intval($rest_time / $max_time_per_one_file);
        $file_ids = array_keys($files);
        return $this->copyUsualAndAsyncGroups(
            array_slice($file_ids, 0, $threshold),
            array_slice($file_ids, $threshold)
        );
    }

    protected function copyToughFolders($folders)
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
                $usual[] = $folder_id;
            } else {
                $async[] = $folder_id;
            }
        }
        return $this->copyUsualAndAsyncGroups($usual, $async);
    }

    protected function mergeCopyResults($res1, $res2)
    {
        $result = array(
            'files_count' => 0,
            'folders_count' => 0,
            'count' => 0,
            'file_ids' => array()
        );
        foreach (func_get_args() as $res) {
            if (is_array($res)) {
                $result['files_count'] += (int)ifset($res['files_count']);
                $result['folders_count'] += (int)ifset($res['folders_count']);
                $result['count'] += (int)ifset($res['count']);
                $result['file_ids'] = array_merge(
                    $result['file_ids'],
                    (array) ifset($res['file_ids'], array())
                );
            }
        }
        return $result;
    }

    protected function copy($ids, $is_async = false)
    {
        $options = array('process_id' => $this->process_id);
        $options['is_async'] = $is_async;
        return $this->getFileModel()->copy(
            $ids,
            $this->storage_id,
            $this->folder_id,
            $options
        );
    }

    protected function copyUsualAndAsyncGroups($usual, $async)
    {
        if ($usual) {
            $usual_res = $this->copy($usual);
        }
        if ($async) {
            $async_res = $this->copy($async, true);
        }

        $result = $this->mergeCopyResults(
            !empty($usual_res) ? $usual_res : array(),
            !empty($async_res) ? $async_res : array()
        );
        $result['file_ids'] = array_merge(
            !empty($usual_res) ? $usual : array(),
            !empty($async_res) ? $async : array()
        );

        return $result;
    }
}

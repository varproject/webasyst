<?php

class filesTasksPerformer
{
    /**
     * @var filesTasksQueueModel
     */
    protected $queue;

    /**
     * @var filesFileModel
     */
    protected $fm;

    /**
     * @var string|null
     */
    protected $process_id;

    /**
     * @var int
     */
    protected $contact_id;

    /**
     * @var int
     */
    protected $number_of_tasks;

    /**
     * @var int
     */
    protected $max_execution_time;

    /**
     * @var int
     */
    protected $execution_start;

    /**
     * @var array
     */
    protected $options;

    public function __construct($options = array())
    {
        $this->queue = new filesTasksQueueModel();
        $this->number_of_tasks = (int) ifset($options['number_of_tasks']);
        if (!isset($options['number_of_tasks']) || $this->number_of_tasks < 0) {
            $this->number_of_tasks = filesApp::inst()->getConfig()->getTasksPerRequest();
        }

        if (!empty($options['max_execution_time'])) {
            $this->max_execution_time = (int) $options['max_execution_time'];
        } else {
            $this->max_execution_time = filesApp::inst()->getConfig()->getMaxExecutionTime(120);
        }
        if ($this->max_execution_time <= 0) {
            $this->max_execution_time = 120;
        }

        $this->execution_start = time();

        $this->process_id = ifset($options['process_id']);

        $contact_id = (int) ifset($options['contact_id']);
        if ($contact_id > 0) {
            $this->contact_id = $contact_id;
        } else {
            $this->contact_id = wa()->getUser()->getId();
        }

        $this->options = $options;
    }

    public static function perform($options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $performer = new self($options);
        return $performer->execute();
    }

    public function execute()
    {
        /**
         * @event start_perform_tasks
         */
        wa('files')->event('start_perform_tasks');

        $result = array();

        $count = $this->process_id ?
            $this->queue->countByField(array('process_id' => $this->process_id)) :
            $this->queue->countAll();

        if ($count <= 0) {
            return $result;
        }

        $number = 0;

        $tasks = ifset($this->options['prior_tasks'], array());
        foreach ($tasks as $task) {
            if (is_array($task) && isset($task['id'])) {
                $task_id = (int) $task['id'];
            } else {
                $task_id = (int) $task;
            }
            $result[$task_id] = $this->executeOne($task);
            $number += 1;
        }

        $options = array(
            'pause' => $this->max_execution_time,
        );
        if ($this->process_id) {
            $options['process_id'] = $this->process_id;
        }
        if ($this->contact_id > 0) {
            $options['contact_id'] = $this->contact_id;
        }

        for (; $number < $this->number_of_tasks; $number += 1) {
            $task = $this->queue->getNextTask($options);
            if ($task) {
                $result[$task['id']] = $this->executeOne($task);
            }
        }

        return $result;
    }

    /**
     * @param int|array $task
     * @return null
     */
    protected function executeOne($task)
    {
        if (time() - $this->execution_start >= $this->max_execution_time - 10) {
            return null;
        }
        if (!is_array($task)) {
            $task = $this->queue->getTaskById($task, array(
                'pause' => $this->max_execution_time
            ));
        } else {
            $task = array_merge($task, $this->queue->lockTask($task['id']));
        }
        if (!$task) {
            return null;
        }
        if ($task['operation'] === filesTasksQueueModel::OPERATION_DELETE) {
            return $this->executeDelete($task);
        }
        if ($task['operation'] === filesTasksQueueModel::OPERATION_COPY) {
            return $this->executeCopy($task);
        }
        if ($task['operation'] === filesTasksQueueModel::OPERATION_MOVE) {
            return $this->executeMove($task);
        }
    }

    protected function executeDelete($task)
    {
        $fm = $this->getFileModel();
        $file = $fm->getById($task['file_id']);
        if (!$file) {
            $this->queue->deleteLock($file['id']);
            $this->queue->delete($task['id']);
            return;
        }

        if (!$this->waitWhileInSync($file['id'])) {
            return false;
        }

        $res = false;

        try {
            $this->queue->deleteLock($file['id']);
            $res = $this->getFileModel()->delete(
                $file['id'],
                array(
                    'source_options' => array(
                        'in_pause_throw_exception' => true,
                        'token_invalid_throw_exception' => true
                    )
                )
            );
        } catch (Exception $e) {
        }

        if (!$res) {
            $file = $fm->getById($file['id']);
            if ($file) {
                $this->queue->setLock($file['id']);
                return false;
            }
        }

        $this->queue->delete($task['id']);
        return true;

    }

    protected function executeCopy($task)
    {
        $fm = $this->getFileModel();
        $item = $fm->getById($task['file_id']);
        if (!$item) {
            $this->queue->deleteLock($item['id']);
            $this->queue->delete($task['id']);
            return;
        }

        if (!$this->waitWhileInSync($task['file_id'])) {
            return;
        }

        $is_folder = $item['type'] === filesFileModel::TYPE_FOLDER;

        $this->queue->deleteLock($task['file_id']);

        $res = $fm->copy(
            $task['file_id'],
            $task['storage_id'],
            $task['parent_id'],
            array(
                'ignore_children' => $is_folder,
                'process_id' => $task['process_id']
            )
        );

        if ($is_folder) {
            $clone_id = $res['track_ids'][$item['id']];
            $this->queue->updateByField(
                array('parent_task_id' => $task['id']),
                array('parent_id' => $clone_id)
            );
        }

        $this->queue->delete($task['id']);

        return $res;
    }

    protected function executeMove($task)
    {
        $item = $this->getFileModel()->getById($task['file_id']);
        if (!$item) {
            $this->queue->deleteLock($item['id']);
            $this->queue->delete($task['id']);
            return;
        }

        if (!$this->waitWhileInSync($task['file_id'])) {
            return;
        }


        $conflict = $this->getFileModel()->getConflictFiles(
            array($item['id'] => $item),
            $task['storage_id'],
            $task['parent_id']
        );

        if ($conflict && $task['replace']) {
            $error = $this->solveByReplace($conflict);
            if ($error) {
                return null;
            }
        }

        $is_folder = $item['type'] === filesFileModel::TYPE_FOLDER;
        $this->queue->deleteLock($task['file_id']);
        $res = $this->getFileModel()->moveItems(
            $task['file_id'],
            $task['storage_id'],
            $task['parent_id'],
            array(
                'ignore_children' => $is_folder,
                'process_id' => $task['process_id'],
                'ignore_held_move_folders' => true
            )
        );

        if ($is_folder) {
            $clone_id = $res['track_ids'][$item['id']];
            $this->queue->updateByField(
                array('parent_task_id' => $task['id']),
                array('parent_id' => $clone_id)
            );
        }

        $this->queue->delete($task['id']);

        return $res;
    }

    protected function solveByReplace($conflict)
    {
        if (!filesRights::inst()->canReplaceFiles($conflict['dst_conflict_files'])) {
            return filesApp::getAccessDeniedError();
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

    /**
     * @return filesTasksQueueModel
     */
    public function getQueue()
    {
        return $this->queue;
    }

    public function cancel()
    {
        if ($this->process_id) {
            $this->queue->deleteByProcessId($this->process_id);
        }
    }

    protected function waitWhileInSync($file_id, $timeout = 5)
    {
        $start = time();
        while ($this->getFileModel()->inSync($file_id)) {
            if (time() - $start >= $timeout) {
                return false;
            }
            sleep(1);
        }
        return true;
    }

    protected function deleteTask($task)
    {
        $this->queue->delete($task['id']);
        if ($task['file_id']) {
            $this->getFileModel()->delete($task['file_id']);
        }
    }

    protected function getFileModel()
    {
        if (!$this->fm) {
            $this->fm = new filesFileModel();
            $this->fm->setCheckInSync(true);
            $this->fm->setDeleteAsyncMode(false);
            $this->fm->setCopyAsyncMode(false);
            $this->fm->setMoveAsyncMode(false);
        }
        return $this->fm;
    }
}

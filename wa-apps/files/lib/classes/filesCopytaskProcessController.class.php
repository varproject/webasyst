<?php

class filesCopytaskProcessController extends waLongActionController
{
    /**
     * @var filesCopytask
     */
    protected $copytask;

    /**
     * @var filesTasksPerformer
     */
    protected $tasks_performer;

    /**
     * @var filesCopytaskModel
     */
    protected $copytask_model;

    protected $max_execution_time;

    public function __construct()
    {
        $this->copytask_model = new filesCopytaskModel();

        $this->tasks_performer = new filesTasksPerformer(array(
            'number_of_tasks' => 1,
            'process_id' => $this->getCopytaskProcessId(),
        ));

        $this->copytask = new filesCopytask(1, array(
            'process_id' => $this->getCopytaskProcessId(),
            'max_execution_time' => $this->getMaxExecutionTime(),
            'max_steps' => 10,
            'not_delete_held_move_folders' => get_class($this) === 'filesMoveProcessController'
        ));

        if ($this->isCancelCopytask()) {
            $this->data['process'] = 100;
            $this->tasks_performer->cancel();
            $this->copytask->cancel();
        }
    }

    protected function getMaxExecutionTime()
    {
        return $this->_max_exec_time = filesApp::inst()->getConfig()->getMaxExecutionTime();
    }

    protected function initEnv()
    {
        parent::initEnv();
        $this->_max_exec_time = $this->getMaxExecutionTime();
    }

    /**
     * Initializes new process.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     */
    protected function init()
    {
        $this->data = array(
            'phase' => '',
            'tasks_queue_info' => array(
                'total' => 0,
                'progress' => 0,
                'done' => false
            ),
            'progress_size_info' => array(
                'total' => 0,
                'size' => 0,
                'progress' => 0,
            ),
            'progress_count_info' => array(
                'total' => 0,
                'count' => 0,
                'progress' => 0,
            ),
            'progress' => 0,
            'timestamp' => time()
        );
        $this->initTasksPhase();
    }

    protected function initTasksPhase()
    {
        $process_id = $this->getCopytaskProcessId();
        $tasks_queue_count = $this->tasks_performer->getQueue()->countByField(array(
            'process_id' => $process_id
        ));
        $this->data['tasks_queue_info']['total'] = $tasks_queue_count;
    }

    protected function isCopytaskPhaseInited()
    {
        return $this->data['phase'] === 'copytask';
    }

    protected function initCopytaskPhase()
    {
        $process_id = $this->getCopytaskProcessId();
        $total_size = $this->copytask_model->calcTotalSizeOfFilesByProcessId($process_id);
        $total_count = $this->copytask_model->countByField(array(
            'process_id' => $process_id
        ));
        $this->data['progress_size_info']['total'] = $total_size;
        $this->data['progress_count_info']['total'] = $total_count;
        $this->data['phase'] = 'copytask';
    }

    protected function getCopytaskProcessId()
    {
        if (!array_key_exists('copytask_process_id', $this->data)) {
            $this->data['copytask_process_id'] = (int) wa()->getRequest()->post('copytask_process_id');
        }
        return $this->data['copytask_process_id'];
    }

    protected function isCancelCopytask()
    {
        if (!array_key_exists('cancel_copytask', $this->data)) {
            $this->data['cancel_copytask'] = (int) wa()->getRequest()->post('cancel_copytask');
        }
        return $this->data['cancel_copytask'];
    }


    /**
     * Checks if there is any more work for $this->step() to do.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     *
     * $this->getStorage() session is already closed.
     *
     * @return boolean whether all the work is done
     */
    protected function isDone()
    {
        return $this->data['progress'] >= 100;
    }

    protected function isDoneTasksPhase()
    {
        return $this->data['tasks_queue_info']['done'];
    }

    /**
     * Performs a small piece of work.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     * Should never take longer than 3-5 seconds (10-15% of max_execution_time).
     * It is safe to make very short steps: they are batched into longer packs between saves.
     *
     * $this->getStorage() session is already closed.
     * @return boolean false to end this Runner and call info(); true to continue.
     */
    protected function step()
    {
        if (!$this->isDone() && !$this->isCancelCopytask()) {
            if (!$this->isDoneTasksPhase()) {
                $this->tasks_performer->execute();
                $this->calcProgressPercentages();
            } else {
                $this->copytask->execute();
                $this->calcProgressPercentages();
            }
        }
    }

    protected function calcProgressPercentages()
    {
        $process_id = $this->getCopytaskProcessId();

        if (!$this->isDoneTasksPhase()) {
            $rest_tasks_count = $this->tasks_performer->getQueue()->countByField(array(
                'process_id' => $process_id
            ));
            $processed_count = $this->data['tasks_queue_info']['total'] - $rest_tasks_count;
            if ($rest_tasks_count <= 0) {
                $this->data['tasks_queue_info']['progress'] = 100;
            } else {
                $this->data['tasks_queue_info']['progress'] = floatval($processed_count / $this->data['tasks_queue_info']['total']) * 100;
            }

            $this->data['tasks_queue_info']['done'] = $this->data['tasks_queue_info']['progress'] >= 100;

            if (!$this->data['tasks_queue_info']['done']) {
                $this->data['progress'] = $this->data['tasks_queue_info']['progress'] / 10;
            }

            return;
        }

        if (!$this->isCopytaskPhaseInited()) {
            $this->initCopytaskPhase();
        }

        $rest_total_size = $this->copytask_model->calcTotalSizeOfFilesByProcessId($process_id);
        $offset_sum = $this->copytask_model->calcOffsetSumByProcessId($process_id);
        $processed_size = $this->data['progress_size_info']['total'] - ($rest_total_size - $offset_sum);

        $rest_total_count = $this->copytask_model->countByField(array(
            'process_id' => $process_id
        ));
        $processed_count = $this->data['progress_count_info']['total'] - $rest_total_count;

        // calculate progress for size
        $this->data['progress_size_info']['size'] = $processed_size;
        if ($this->data['progress_size_info']['total'] <= 0) {
            $this->data['progress_size_info']['progress'] = 99;
        } else {
            $this->data['progress_size_info']['progress'] = floatval($processed_size / $this->data['progress_size_info']['total']) * 99;
        }

        // calculate progress for files count
        $this->data['progress_count_info']['count'] = $processed_count;
        if ($this->data['progress_count_info']['total'] <= 0) {
            $this->data['progress_count_info']['progress'] = 99;
        } else {
            $this->data['progress_count_info']['progress'] = floatval($processed_count / $this->data['progress_count_info']['total']) * 99;
        }

        $progress = max(
            $this->data['progress'],
            $this->data['progress_size_info']['progress'],
            $this->data['progress_count_info']['progress']
        );
        if ($progress <= 0) {
            $progress = 0;
        }

        $progress = floatval(min(max($progress, $this->data['progress']), 99));

        // almost done, wait 'held' folders
        if ($rest_total_count <= 0) {
            if (get_class($this) === 'filesMoveProcessController') {
                $fm = new filesFileModel();
                if ($fm->countByField(array(
                    'in_copy_process' => $process_id,
                    'type' => filesFileModel::TYPE_FOLDER
                )) <= 0) {
                    $progress = 100;
                } else {
                    $fm->deleteHeldMoveFolders();
                }
            } else {
                $progress = 100;
            }
        }

        $this->data['progress'] = $progress;
    }

    /**
     * Called when $this->isDone() is true
     * $this->data is read-only, $this->fd is not available.
     *
     * $this->getStorage() session is already closed.
     *
     * @param $filename string full path to resulting file
     * @return boolean true to delete all process files; false to be able to access process again.
     */
    protected function finish($filename)
    {
        return true;
    }

    /** Called by a Messenger when the Runner is still alive, or when a Runner
     * exited voluntarily, but isDone() is still false.
     *
     * This function must send $this->processId to browser to allow user to continue.
     *
     * $this->data is read-only. $this->fd is not available.
     */
    protected function info()
    {
        $this->calcProgressPercentages();

        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $response = array(
            'time'      => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId' => $this->processId,
            'progress'  => $this->data['progress'],
            'ready'     => $this->isDone(),
            'data' => $this->data
        );
        if ($response['ready']) {
            $response['progress'] = 100.00;
        }

        $response['progress_str'] = str_replace(',', '.', sprintf('%0.3f%%', $response['progress']));

        if ($this->getRequest()->post('cleanup')) {
            $response['report'] = $this->report();
        }

        echo json_encode($response);
    }

    protected function report()
    {
        return _w('All files have been copied');
    }
}

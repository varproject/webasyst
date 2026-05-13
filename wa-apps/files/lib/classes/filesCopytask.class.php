<?php

class filesCopytask
{
    /**
     * @var filesFileModel
     */
    private $model;

    /**
     * @var filesCopytaskModel
     */
    protected $copytask;

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
    protected $max_steps;

    /**
     * @var int
     */
    protected $steps_passed;

    /**
     * @var int
     */
    protected $execution_start;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var int
     */
    protected $chunk_size = '5M';

    protected $threshold_chunk_size = '30M';

    public function __construct($number_of_tasks = 1, $options = array()) {
        $this->model = new filesFileModel();
        $this->copytask = new filesCopytaskModel();
        $this->number_of_tasks = $number_of_tasks;

        // simple upload or partial (chunk) upload
        $this->threshold_chunk_size = filesApp::convertToBytes($this->threshold_chunk_size);
        $this->threshold_chunk_size = min($this->threshold_chunk_size, $this->getConfig()->getAppUploadMaxFilesSize());

        // default chunk_size
        $this->chunk_size = filesApp::convertToBytes($this->chunk_size);

        // we can override by options
        if (!empty($options['chunk_size'])) {
            $this->chunk_size = filesApp::toIntegerNumber($options['chunk_size']);
        }

        if ($this->chunk_size <= 0) {
            throw new filesException("Chunk size must be greater 0");
        }

        if (!empty($options['max_execution_time'])) {
            $this->max_execution_time = (int) $options['max_execution_time'];
        } else {
            $this->max_execution_time = filesApp::inst()->getConfig()->getMaxExecutionTime();
        }

        if (isset($options['max_steps']) && wa_is_int($options['max_steps'])) {
            $this->max_steps = (int) $options['max_steps'];
        }

        $this->execution_start = time();

        $this->steps_passed = 0;

        $this->options = $options;
    }

    /**
     * @param int $number_of_tasks
     * @param array $options
     * @return null|void
     */
    public static function perform($number_of_tasks = 1, $options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $copytask = new self($number_of_tasks, $options);
        return $copytask->execute();
    }

    public function execute()
    {
        /**
         * @event start_copy_tasks
         */
        wa('files')->event('start_copy_tasks');
        for ($i = 0; $i < $this->number_of_tasks; $i += 1) {
            $this->executeOne();
        }
    }

    public function cancel()
    {
        $process_id = ifset($this->options['process_id']);
        if ($process_id) {

            $file_ids = array();
            $tasks = $this->copytask->getByField('process_id', $process_id, true);
            foreach ($tasks as $task) {
                $file_ids[] = $task['source_id'];
                $file_ids[] = $task['target_id'];
            }
            $files = $this->model->getById($file_ids);

            $source_ids = array();
            foreach ($tasks as &$task) {
                $source_file_id = $task['source_id'];
                $target_file_id = $task['target_id'];
                $source_file = $files[$source_file_id];
                $target_file = $files[$target_file_id];
                $task['source_file'] = $source_file;
                $task['target_file'] = $target_file;
                $source_ids[] = $source_file['source_id'];
                $source_ids[] = $target_file['source_id'];
            }
            unset($task);

            /**
             * @var filesSource[] $sources
             */
            $sources = filesSource::factory($source_ids);
            /**
             * @var filesSource $null
             */
            $null = filesSource::factoryNull();
            foreach ($tasks as $task) {
                /**
                 * @var filesSource $source_source
                 */
                $source_source = ifset($sources[$task['source_file']['source_id']], $null);
                $params = array(
                    'copytask_file_role' => 'source',
                    'task' => $task
                );
                $source_source->onCancelCopytask($params);

                /**
                 * @var filesSource $target_source
                 */
                $target_source = ifset($sources[$task['target_file']['source_id']], $null);
                $params = array(
                    'copytask_file_role' => 'target',
                    'task' => $task
                );
                $target_source->onCancelCopytask($params);
            }

            $this->copytask->deleteByProcessId($process_id);
        }
    }

    protected function getConfig()
    {
        return filesApp::inst()->getConfig();
    }

    protected function executeOne()
    {
        if ($this->copytask->countAll() <= 0) {
            return;
        }
        $this->copytask->prolongLocks();
        $task = $this->getTask();
        if (!$task) {
            return;
        }
        $res = $this->performTask($task);
        $this->clearTaskResources($task);
        return $res;
    }

    protected function getTask()
    {
        $process_id = ifset($this->options['process_id']);
        $task = $this->copytask->getTask($this->max_execution_time, $process_id);
        if (!$task) {
            return null;
        }

        $retries_threshold = 5;

        // seems like we stuck, was not working at all
        if ($task['retries'] > $retries_threshold && $task['offset'] == 0) {
            $this->deleteCopytask($task);
            return null;
        }

        $this->copytask->incrementRetriesOfTask($task['target_id']);

        $date = date('Y-m-d H:i:s');
        $this->copytask->updateById($task['target_id'], array( 'process_datetime' => $date ));
        $task['process_datetime'] = $date;

        $src_file = $this->model->getFile($task['source_id']);
        if (!$src_file) {
            $this->deleteCopytask($task);
            return null;
        }

        $trg_file = $this->model->getFile($task['target_id']);
        if (!$trg_file) {
            $this->deleteCopytask($task);
            return null;
        }

        $task['target_file'] = $trg_file;
        $task['source_file'] = $src_file;

        $source_options = array(
            'in_pause_throw_exception' => true,
            'token_invalid_throw_exception' => true
        );
        $task['source_file']['source'] = filesSource::factory($task['source_file']['source_id'], $source_options);
        $task['target_file']['source'] = filesSource::factory($task['target_file']['source_id'], $source_options);

        // One more heuristic - to prevent infinitive stuck-ing
        // Size of all done work is in theory must be equal size of one work multiplied by current number of tries ($task['retries'])
        // But in real world some attempts (tries) could be not go well - e.g. source response filesSourceRetryLaterException or filesSourceTokenInvalidException
        // So play it safe buy taking into account retries_threshold coefficient
        $chunk_size = $this->getChunkSize($task);

        // ideal (theoretical) work
        $ideal_number_of_tries = intval($src_file['size'] / $chunk_size);

        // need extra analyse (will check task.offset)
        $tolerant_number_of_tries = $ideal_number_of_tries + $retries_threshold;

        // definitely should kill the task
        $not_tolerant_number_of_tries = $ideal_number_of_tries + ($retries_threshold * 3);

        if ($task['retries'] > $tolerant_number_of_tries) {

            // ok, seems like we did work hard, but still not done
            // hmm...ok, lets try look in offset (actual progress of work)
            if ($task['offset'] < ($src_file['size'] / 2)) {
                // didn't do even half of all must be done work
                // looks like we stuck - delete task
                $this->deleteCopytask($task);
                return null;
            }

        } elseif ($task['retries'] > $not_tolerant_number_of_tries) {
            // task did stuck - delete it
            $this->deleteCopytask($task);
            return null;
        }

        $params = array(
            'copytask_file_role' => 'source',
            'task' => $task
        );

        $params = $task['source_file']['source']->beforePerformCopytask($params);
        if (!$params) {
            $this->deleteCopytask($task);
            return null;
        }

        $params = array(
            'copytask_file_role' => 'target',
            'task' => $task
        );
        $params = $task['target_file']['source']->beforePerformCopytask($params);

        if (!$params) {
            $this->deleteCopytask($task);
            return null;
        }
        $task['target_file'] = $params['task']['target_file'];

        return $task;
    }

    protected function clearTaskResources($task)
    {
        $this->copytask->unlockTask($task['target_id']);
    }

    protected function isTaskCompleted($task)
    {
        $params = array(
            'copytask_file_role' => 'source',
            'task' => $task
        );
        $is_source_performed = $task['source_file']['source']->isCopytaskPerformed($params);

        $params = array(
            'copytask_file_role' => 'target',
            'task' => $task
        );
        $is_target_performed = $task['target_file']['source']->isCopytaskPerformed($params);

        return $is_source_performed && $is_target_performed;
    }

    protected function getFile($id, $check_exist = true)
    {
        $file = $this->model->getFile($id);

        if ($check_exist && file_exists($file['path'])) {
            $file['exists'] = true;
        } else {
            $file['exists'] = false;
        }

        $file['filesize'] = 0;
        if ($file['exists']) {
            clearstatcache();
            $file['filesize'] = filesApp::toIntegerNumber(@filesize($file['path']));
        }

        return $file;
    }

    protected function performTask($task)
    {
        if ($task['source_file']['size'] <= $this->threshold_chunk_size) {
            return $this->performTaskSimple($task);
        } else {
            return $this->performTaskPartial($task);
        }
    }

    protected function performTaskPartial($task)
    {
        $support = $task['source_file']['source']->isChunkDownloadSupport() && $task['target_file']['source']->isChunkUploadSupport();
        if (!$support) {
            $this->deleteCopytask($task);
            return;
        }

        $complete = $this->isTaskCompleted($task);

        // run copy process
        while (!$complete) {

            $res = $this->performOneChunkOfTask($task);
            if (!$res) {
                break;
            }

            $task = $res;
            $complete = $this->isTaskCompleted($task);
            if ($complete) {
                break;
            }

            $this->steps_passed += 1;
            if (wa_is_int($this->max_steps) && $this->steps_passed >= $this->max_steps) {
                break;
            }

            $executed_time = time() - $this->execution_start;
            if ($executed_time >= $this->max_execution_time - 5) {
                break;
            }

            sleep(1);
        }

        if ($complete) {
            $this->onCompleteCopyTask($task);
        }

        return $complete;
    }

    protected function performTaskSimple($task)
    {
        $executed_time = time() - $this->execution_start;
        if ($executed_time >= $this->max_execution_time - 10) {
            return false;
        }

        $res = false;
        try {
            $stream = $task['source_file']['source']->download($task['source_file'], filesSource::DOWNLOAD_STREAM);
            $res = $task['target_file']['source']->upload($task['target_file'], $stream);
        } catch (filesSourceRetryLaterException $e) {
            $this->logException($e);
            return false;
        } catch (filesSourceTokenInvalidException $e) {
            $this->logException($e);
            return false;
        } catch (Exception $e) {
            $this->logException($e);
        }

        if (!$res) {
            $this->deleteCopytask($task);
        } else {
            $task['target_file'] = array_merge($task['target_file'], $res);
            $this->onCompleteCopyTask($task, 'simple');
        }
        return $res;
    }

    protected function performOneChunkOfTask($task)
    {
        // limited by target_source capacity upload data
        $chunk_size = $this->getChunkSize($task);

        try {
            $chunk = $task['source_file']['source']->downloadChunk($task['source_file'], $task['offset'], $chunk_size);
        } catch (filesSourceRetryLaterException $e) {
            $this->logException($e);
            return false;
        } catch (filesSourceTokenInvalidException $e) {
            $this->logException($e);
            return false;
        } catch (Exception $e) {
            $this->logException($e);
            $this->deleteCopytask($task);
            return false;
        }

        try {
            $task['offset'] = $task['target_file']['source']->uploadChunk($task['target_file'], $task['offset'], $chunk);
        } catch (filesSourceRetryLaterException $e) {
            $this->logException($e);
            return false;
        } catch (Exception $e) {
            $this->logException($e);
            $this->deleteCopytask($task);
            return false;
        }

        // save offset
        $this->copytask->updateById($task['target_id'], array( 'offset' => $task['offset'] ));

        return $task;
    }

    protected function getChunkSize($task)
    {
        return min(
            $this->chunk_size,
            $task['target_file']['source']->getUploadChunkSize(array(
                'file' => $task['target_file']
            ))
        );
    }

    protected function onCompleteCopyTask($task, $type = 'partial')
    {
        // afterPerformCopytask hook called only for partial upload
        if ($type === 'partial') {
            $params = array(
                'copytask_file_role' => 'source',
                'task' => $task
            );
            $params = $task['source_file']['source']->afterPerformCopytask($params);

            // source method afterPerformCopytask is allowed to change target_file,
            // because we can apply some info (for example 'size')
            // but it is not allowed change source file - because source_file is source, it must be hold "old"
            $task['target_file'] = $params['task']['target_file'];

            $params = array(
                'copytask_file_role' => 'target',
                'task' => $task
            );
            $params = $task['target_file']['source']->afterPerformCopytask($params);

            // source method afterPerformCopytask is allowed to change target_file,
            // because we can apply some info (for example 'size')
            // but it is not allowed change source file - because source_file is source, it must be hold "old"
            $task['target_file'] = $params['task']['target_file'];
        }

        // own copytask changes
        $task['target_file']['update_datetime'] = date('Y-m-d H:i:s');
        $task['target_file']['in_copy_process'] = 0;

        // old version on target file
        $file = $this->model->getById($task['target_file']['id']);

        // calculate what to update
        $update = array();
        foreach ($file as $field => $orig_value) {
            if ($task['target_file'][$field] != $orig_value) {
                $update[$field] = $task['target_file'][$field];
            }
        }

        // so, update
        if ($update) {
            $this->model->updateById($file['id'], $update);
            $file = array_merge($file, $update);
        }

        // mean copytask for moving file - so delete source file
        if ($task['is_move']) {

            // check if file corrupted while copy

            $source_file = $this->model->getById($task['source_file']['id']);

            $corrupted = ($source_file['md5_checksum'] !== null && $file['md5_checksum'] !== null && $source_file['md5_checksum'] != $file['md5_checksum'])
                            || $source_file['size'] != $file['size'];

            $tasks_count = $this->copytask->countByField(array('process_id' => $task['process_id']));

            // delete first copytask to release locks
            $this->copytask->delete($task['target_file']['id']);

            // delete source file
            if (!$corrupted) {
                $this->deleteSourceFile($task);
            } else {
                $this->model->updateById($task['source_file']['id'], array('in_copy_process' => 0));
            }

            // delete (or reset) 'held' folders, see description of method
            if ($tasks_count <= 1 && empty($this->options['not_delete_held_move_folders'])) {
                if (!$corrupted) {
                    $this->model->deleteHeldMoveFolders();
                } else {
                    $this->model->resetHeldMoveFolders();
                }
            }

        } else {
            $this->copytask->delete($task['target_file']['id']);
        }

    }

    protected function deleteCopytask($task)
    {
        $this->copytask->delete($task['target_id']);
        $this->model->delete($task['target_id']);
        $this->model->updateById($task['source_id'], array(
            'in_copy_process' => 0
        ));
    }

    protected function deleteSourceFile($task)
    {
        if ($task['source_file']['source']->isApp()) {
            $this->model->delete($task['source_file']['id']);
        } else {
            $this->model->delete($task['source_file']['id'], array('is_async' => true));
        }
    }

    protected function logException($e)
    {
        $w = date('W');
        $y = date('Y');
        $message = get_class($e) . " - " . $e->getCode() . " - " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        waLog::log($message, "files/exceptions/copytask/{$y}/{$w}.log");
    }
}

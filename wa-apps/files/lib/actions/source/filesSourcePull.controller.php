<?php

class filesSourcePullController extends waLongActionController
{
    /**
     * @var filesFileModel
     */
    private $fm;

    /**
     * @var filesSource
     */
    private $source;

    /**
     * @var array
     */
    private $storage;

    /**
     * @var array
     */
    private $folder;

    protected function preExecute()
    {
        $this->getResponse()->addHeader('Content-type', 'application/json');
        $this->getResponse()->sendHeaders();
    }

    public function execute()
    {
        try {
            parent::execute();
        } catch (waException $e) {
            if ($e->getCode() == '302') {
                echo json_encode(array('warning' => $e->getMessage()));
            } else {
                echo json_encode(array('error' => $e->getMessage()));
            }
            $w = date('W');
            $y = date('Y');
            $message = get_class($e) . " - " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
            waLog::log($message, "files/exceptions/{$y}/{$w}.log");
        }
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
        $this->info();
        $source = $this->getSource();
        $source->unpauseSync();
        if ($this->getRequest()->post('cleanup')) {
            return true;
        }
        return false;
    }

    /**
     * Initializes new process.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     */
    protected function init()
    {
        $this->initData();
        $source = $this->getSource();
        $source->beforePullingStart();
    }

    protected function initData()
    {
        $this->data = $this->data + array(
            'timestamp' => time(),

            // info about whole progress
            'progress_info' => array(
                'progress' => 0,    // 0-100, % of done work
                'done' => false,    // true|false, is work done
            ),

            // info about pulling files from remote source
            'pull_progress_info' => array(
                'progress' => 0,    // 0-100, % of done work
                'done' => false,    // true|false, is work done
            ),

            // info about handling pulled files
            'handle_progress_info' => array(
                'progress' => 0,            // 0-100, % of done work
                'total_count' => 0,         // int, total count of files
                'count' => 0,               // int, count of processed files
                'done' => false,            // true|false, is work done
            )
        );
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
        return $this->data['progress_info']['progress'] >= 100 || $this->data['progress_info']['done'];
    }

    private function isDonePullingChunks()
    {
        return $this->data['pull_progress_info']['progress'] >= 100 || $this->data['pull_progress_info']['done'];
    }

    protected function doneAllWork()
    {
        $this->data['progress_info']['done'] = true;
    }

    /**
     * @param array $progress_info
     * @throws filesException
     */
    protected function pullChunk($progress_info = array())
    {
        // progress info only fo pulling
        $source = $this->getSource();
        $pull_result = $source->pullChunk($progress_info);
        $this->data['pull_progress_info'] = $pull_result['info'];

        $sync = new filesSourceSync($source);
        $sync->append($pull_result['list']);

        // all progress info
        $progress = $this->data['pull_progress_info']['progress'];
        $progress /= 4; // suppose that quarter of work is done
        $this->data['progress_info']['progress'] = $progress;
    }

    public function handleNextChunk()
    {
        $source = $this->getSource();

        $sync = new filesSourceSync($source, array(
            'pull' => true
        ));

        // first time initialization
        if ($this->data['handle_progress_info']['total_count'] === 0) {
            $this->data['handle_progress_info']['total_count'] = $sync->getTotalCount();
        }

        $res = $sync->process($this->data['handle_progress_info']);

        // save accumulated count
        $this->data['handle_progress_info']['count'] += $res['count'];

        // result count <= 0 - handle process done
        if ($res['count'] <= 0) {
            $this->data['handle_progress_info']['progress'] = 100;
            $this->data['handle_progress_info']['done'] = true;
        } else {
            $count = $this->data['handle_progress_info']['count'];
            $total_count = $this->data['handle_progress_info']['total_count'];
            $progress = $this->data['handle_progress_info']['progress'];
            $progress = min(
                100,
                max(
                    round(100 * ($count / $total_count), 2),
                    $progress
                )
            );
            $this->data['handle_progress_info']['progress'] = $progress;
        }

        // save other fields from result, for next call of sync->process
        foreach ($res as $field => $value) {
            // 'count', 'progress'... must not be suddenly overwritten in handle_progress_info structure
            if (!in_array($field, array('count', 'progress'))) {
                $this->data['handle_progress_info'][$field] = $value;
            }
        }

        // all progress info
        $progress = $this->data['handle_progress_info']['progress'];
        // 25% - means that quarter of work is done, because all data is pulled already
        // thus 75% is rest of work corresponding to handle pulled data
        $progress *= 0.75;
        $this->data['progress_info']['progress'] = 25 + $progress;
        $this->data['progress_info']['done'] = $this->data['handle_progress_info']['done'];

        return true;
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
        $source = $this->getSource();
        $source->pauseSync();
        if (!$this->isDonePullingChunks()) {
            $this->pullChunk($this->data['pull_progress_info']);
        } else {
            $this->handleNextChunk();
        }
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
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $response = array(
            'time'      => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId' => $this->processId,
            'progress'  => 0.0,
            'ready'     => $this->isDone(),
            'data' => $this->data
        );
        if ($response['ready']) {

            $source = $this->getSource();
            $source->afterPullingEnd();

            $response['progress'] = 100.00;
            $sm = new filesSourceModel();
            $response['source_count'] = $sm->countAllSources();

        } else {
            $response['progress'] = $this->data['progress_info']['progress'];
        }

        $response['progress_str'] = str_replace(',', '.', sprintf('%0.3f%%', $response['progress']));

        if ($this->getRequest()->post('cleanup')) {
            $response['report'] = $this->report();
        }

        echo json_encode($response);
    }

    protected function report()
    {
        return _w('All files from remote source have been pulled');
    }

    /**
     * @return filesSource
     * @throws filesException
     */
    public function getSource()
    {
        if ($this->source === null) {
            $source_id = wa()->getRequest()->post('id');
            $source = filesSource::factory($source_id);
            if (!$source || !is_numeric($source->getId())) {
                throw new filesException(_w('Source not found'));
            }
            $this->source = $source;
        }
        return $this->source;
    }

    /**
     * @return filesFileModel
     */
    public function getFileModel()
    {
        if ($this->fm === null) {
            $this->fm = new filesFileModel();
        }
        return $this->fm;
    }

    /**
     * @throws filesException
     * @return array
     */
    public function getFolder()
    {
        if ($this->folder === null) {
            $source = $this->getSource();
            $folder_id = $source->getFolderId();
            if (!$folder_id) {
                $folder = $this->getFileModel()->getEmptyRow();
                $folder['id'] = 0;
                $folder['type'] = filesFileModel::TYPE_FOLDER;
            } else {
                $folder = $this->getFileModel()->getItem($folder_id, false);
                if (!$folder || $folder['type'] !== filesFileModel::TYPE_FOLDER) {
                    throw new filesException(_w("Folder not found"));
                }
            }
            $this->folder = $folder;
        }
        return $this->folder;
    }

    /**
     * @return array
     * @throws filesException
     */
    public function getFilesStorage()
    {
        if ($this->storage === null) {
            $source = $this->getSource();
            $folder_id = $source->getFolderId();
            if ($folder_id) {
                $folder = $this->getFolder();
                $storage_id = $folder['storage_id'];
            } else {
                $storage_id = $source->getStorageId();
            }
            $sm = new filesStorageModel();
            $storage = $sm->getStorage($storage_id);
            if (!$storage) {
                throw new filesException(_w("Storage not found"));
            }
            $this->storage = $storage;
        }
        return $this->storage;
    }
}



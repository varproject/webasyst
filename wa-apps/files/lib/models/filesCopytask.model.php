<?php

class filesCopytaskModel extends filesModel implements filesFileRelatedInterface
{
    protected $table = 'files_copytask';
    protected $id = 'target_id';

    const MIN_PROCESS_ID = 100;

    public function add($source_id, $target_id, $process_id = 0, $is_move = false)
    {
        $data = array(
            'source_id' => $source_id,
            'target_id' => $target_id,
        );
        $data['create_datetime'] = date('Y-m-d H:i:s');
        $process_id = (int) $process_id;
        if ($process_id !== 0) {
            $data['process_id'] = $process_id;
        }
        $data['is_move'] = $is_move ? 1 : 0;
        $this->setLock(array($source_id, $target_id));
        return $this->insert($data);
    }

    public function generateProcessId()
    {
        $process_id = $this->generateRandomProcessId();
        if (!$this->select('*')->where('process_id = ?', $process_id)->fetchField()) {
            return $process_id;
        } else {
            return $this->generateRandomProcessId();
        }
    }

    private function generateRandomProcessId()
    {
        $min_id = filesCopytaskModel::MIN_PROCESS_ID;      // id < $min_id reserved for future for potentially special using
        $max_id = pow(2, 31) - 1;

        // generate random process id
        list($usec, $sec) = explode(' ', microtime());
        srand((float) $sec + ((float) $usec * 100000));
        $random_process_id = rand($min_id, $max_id);
        return (string) $random_process_id;
    }


    public function delete($id)
    {
        $items = $this->getById(filesApp::toIntArray($id));
        if (!$items) {
            return;
        }
        $this->deleteById(array_keys($items));
        $lock_file_ids = array();
        foreach ($items as $item) {
            $lock_file_ids[] = $item['source_id'];
            $lock_file_ids[] = $item['target_id'];
        }
        $this->deleteLock($lock_file_ids);
    }

    public function deleteByProcessId($process_id)
    {
        $process_ids = filesApp::toIntArray($process_id);
        $items = $this->getByField(array('process_id' => $process_ids), $this->id);
        if (!$items) {
            return;
        }
        $this->deleteById(array_keys($items));
        $lock_file_ids = array();
        foreach ($items as $item) {
            $lock_file_ids[] = $item['source_id'];
            $lock_file_ids[] = $item['target_id'];
        }
        $this->deleteLock($lock_file_ids);

        $this->getFileModel()->delete(array_keys($items));
    }

    public function getTask($pause = null, $process_id = null)
    {
        if ($pause === null) {
            $pause = (int) ini_get('max_execution_time');
            if ($pause < 0) {
                $pause = 120;
            }
        }
        $pause = (int) $pause;
        $lock = md5(uniqid(str_replace('::', '', __METHOD__)));
        $lock_expired_datetime = date('Y-m-d H:i:s', time() + $pause);
        $now_datetime = date('Y-m-d H:i:s');

        $where = array();
        if ($process_id) {
            $process_id = (int) $process_id;
            $where[] = "ct.process_id = '{$process_id}'";
        }

        $join = "LEFT JOIN `files_tasks_queue` tq ON tq.process_id = ct.process_id";
        $where[] = "tq.process_id IS NULL";

        $where = $where ? " AND " . join(" AND ", $where) : '';

        $sql = "
            UPDATE {$this->table} t JOIN (
                SELECT DISTINCT ct.target_id
                FROM {$this->table} ct
                {$join}
                WHERE (ct.`lock` IS NULL OR ct.`lock_expired_datetime` IS NULL OR ct.`lock_expired_datetime` < '{$now_datetime}') {$where}  
                ORDER BY ct.retries, ct.create_datetime
                LIMIT 0, 1
            ) r ON t.target_id = r.target_id
            SET t.`lock` = '{$lock}', t.`lock_expired_datetime` = '{$lock_expired_datetime}'";
        $this->exec($sql);

        return $this->getByField('lock', $lock);
    }

    public function unlockTask($id)
    {
        $this->updateById($id, array('lock' => null, 'lock_expired_datetime' => null));
    }

    public function incrementRetriesOfTask($id)
    {
        $sql = "UPDATE {$this->table}
            SET retries = retries + 1
            WHERE {$this->id} = i:id";
        $this->exec($sql, array('id' => $id));
    }

    /**
     * @param int|array[int] $file_id
     */
    private function setLock($file_id)
    {
        $this->getLockModel()->set(
            $file_id,
            array(
                'contact_id' => 0,
                'scope' => filesLockModel::SCOPE_EXCLUSIVE
            ),
            filesLockModel::RESOURCE_TYPE_FILE
        );
    }

    /**
     * @param int|array[int] $file_id
     */
    private function deleteLock($file_id)
    {
        $this->getLockModel()->delete($file_id);
    }

    /**
     * Event method called when file(s) is deleting
     * @param array[int]|int $file_id
     */
    public function onDeleteFile($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);

        // if its target ids
        $this->delete($file_ids);

        // if its source ids
        $target_ids = $this->select('target_id')->where($this->getWhereByField(array(
            'source_id' => $file_ids
        )))->fetchAll(null, true);
        $this->delete($target_ids);
    }

    public function prolongLocks()
    {
        $this->getLockModel()->prolongForCopytask();
    }

    public function getRelatedFields()
    {
        return array('source_id', 'target_id');
    }

    public function calcTotalSizeOfFilesByProcessId($process_id)
    {
        $sql = "SELECT SUM(f.size) AS size  
                FROM `files_copytask` ct 
                JOIN `files_file` f ON f.id = ct.source_id
                WHERE ct.process_id = ?";
        return $this->query($sql, $process_id)->fetchField();
    }

    public function calcOffsetSumByProcessId($process_id)
    {
        $sql = "SELECT SUM(offset) FROM `files_copytask` WHERE process_id = ?";
        return $this->query($sql, $process_id)->fetchField();
    }

    public function getFileTasks($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $file_ids = filesApp::dropNotPositive($file_ids);
        $file_ids = array_unique($file_ids);
        if (!$file_ids) {
            return array();
        }

        $tasks = $this->select('*')->where('target_id IN(:ids) OR source_id IN(:ids)', array(
            'ids' => $file_ids
        ))->fetchAll();

        $source_file_ids = array();
        $target_file_ids = array();
        foreach ($tasks as $task) {
            $source_file_ids[] = $task['source_id'];
            $target_file_ids[] = $task['target_id'];
        }

        $sizes = array();
        $fm = new filesFileModel();
        if ($source_file_ids) {
            $sizes = $fm->select('id,size')->where('id IN (:ids)', array(
                'ids' => $source_file_ids
            ))->fetchAll('id', true);
        }

        $source_source_ids = array();
        if ($source_file_ids) {
            $source_source_ids = $fm->select('id,source_id')->where('id IN (:ids)', array(
                'ids' => $source_file_ids
            ))->fetchAll('id', true);
        }

        $source_sources = array();
        if ($source_source_ids) {
            $source_sources = filesSource::factory($source_source_ids);
        }

        $source_sources_info = array();
        foreach ($source_source_ids as $file_id => $source_id) {
            if (isset($source_sources[$source_id])) {
                $source_sources_info[$file_id] = array(
                    'id' => $source_id,
                    'icon_url' => $source_sources[$source_id]->getIconUrl(),
                    'name' => $source_sources[$source_id]->getIconUrl(),
                    'provider_name' => $source_sources[$source_id]->getProviderName(),
                );
            }
        }

        $target_source_ids = array();
        if ($target_file_ids) {
            $target_source_ids = $fm->select('id,source_id')->where('id IN (:ids)', array(
                'ids' => $target_file_ids,
            ))->fetchAll('id', true);
        }

        $target_sources = array();
        if ($target_source_ids) {
            $target_sources = filesSource::factory($target_source_ids);
        }

        $target_sources_info = array();
        foreach ($target_source_ids as $target_id => $source_id) {
            if (isset($target_sources[$source_id])) {
                $target_sources_info[$target_id] = array(
                    'id' => $source_id,
                    'icon_url' => $target_sources[$source_id]->getIconUrl(),
                    'name' => $target_sources[$source_id]->getName(),
                    'provider_name' => $target_sources[$source_id]->getProviderName(),
                );
            }
        }

        $all_tasks = array();
        foreach ($tasks as $task) {
            $source_file_ids[] = $task['source_id'];

            $source_file_size = 0;
            if (isset($sizes[$task['source_id']])) {
                $source_file_size = filesApp::toIntegerNumber($sizes[$task['source_id']]);
            }

            $progress = 0.0;
            if ($source_file_size > 0) {
                $progress = $task['offset'] / $source_file_size;
            }

            $task['progress'] = $progress * 100;

            $task['target_source_info'] = array();
            if (isset($target_sources_info[$task['target_id']])) {
                $task['target_source_info'] = $target_sources_info[$task['target_id']];
            }

            $task['source_source_info'] = array();
            if (isset($source_sources_info[$task['source_id']])) {
                $task['source_source_info'] = $source_sources_info[$task['source_id']];
            }

            $all_tasks[$task['target_id']]['as_target'][$task['process_id']] = $task;
            $all_tasks[$task['source_id']]['as_source'][$task['process_id']] = $task;
        }

        return $all_tasks;
    }

}

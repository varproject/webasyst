<?php

class filesTasksQueueModel extends filesModel implements filesFileRelatedInterface
{
    protected $id = 'id';
    protected $table = 'files_tasks_queue';

    const OPERATION_MOVE = 'move';
    const OPERATION_COPY = 'copy';
    const OPERATION_DELETE = 'delete';

    const MIN_PROCESS_ID = 100;

    public function addToDelete($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $res = array_fill_keys($file_ids, 0);
        $file_ids = filesApp::dropNotPositive($file_ids);
        foreach ($file_ids as $file_id) {
            $res[$file_id] = $this->add(self::OPERATION_DELETE, $file_id);
        }
        if ($file_ids) {
            $this->getFileModel()->exec("
                UPDATE `files_file` 
                SET storage_id = IF(storage_id > 0, -storage_id, storage_id)
                WHERE id IN (:ids)
            ", array('ids' => $file_ids));
        }
        if (is_array($file_id)) {
            return $res;
        }
        return ifset($res[(int) $file_id], 0);
    }

    public function addToCopy($file_id, $storage_id = 0, $parent_id = 0, $params = array())
    {
        $params['storage_id'] = $storage_id;
        $params['parent_id'] = $parent_id;
        return $this->addToCopyOrMove($file_id, self::OPERATION_COPY, $params);
    }

    public function addToMove($file_id, $storage_id = 0, $parent_id = 0, $params = array())
    {
        $params['storage_id'] = $storage_id;
        $params['parent_id'] = $parent_id;
        return $this->addToCopyOrMove($file_id, self::OPERATION_MOVE, $params);
    }

    public function getNextTask($options = array())
    {
        $pause = ifset($options['pause']);
        $where = '';
        if (!empty($options['process_id'])) {
            $where = $this->getWhereByField('process_id', $options['process_id'], 'q');
        }
        return $this->getTaskByWhere($pause, true, $where);
    }

    public function getTaskById($id, $options = array())
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }
        return $this->getTaskByWhere(ifset($options['pause']), ifset($options['topology']), $this->getWhereByField('id', $id, 'q'));
    }

    public function delete($id)
    {
        $items = $this->getById(filesApp::toIntArray($id));
        if (!$items) {
            return;
        }
        $file_ids = filesApp::getFieldValues($items, 'file_id');
        $this->deleteLock($file_ids);
        $this->deleteById(array_keys($items));
    }

    public function deleteByProcessId($process_id)
    {
        $items = $this->getByField(array('process_id' => $process_id));
        if (!$items) {
            return;
        }
        $file_ids = filesApp::getFieldValues($items, 'file_id');
        $this->deleteLock($file_ids);
        $this->deleteById(array_keys($items));
    }

    public function lockTask($task_id, $pause = null)
    {
        $pause = (int) $pause;
        if ($pause <= 0) {
            $pause = $this->getConfig()->getMaxExecutionTime();
        }

        $lock = md5(uniqid(str_replace('::', '', __METHOD__)));
        $lock_expired_datetime = date('Y-m-d H:i:s', time() + $pause);

        $this->updateById($task_id, array(
            'lock' => $lock,
            'lock_expired_datetime' => $lock_expired_datetime
        ));

        return $this->getById($task_id);
    }

    public function getRelatedFields()
    {
        return array('file_id');
    }

    public function getOperations()
    {
        return array_values($this->getConstantsByPrefix('OPERATION_'));
    }

    /**
     * Event method called when file(s) is deleting
     * @param array[int]|int $file_id
     */
    public function onDeleteFile($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);

        $ids = $this->select('id')->where("file_id IN(:ids)",
            array(
                'ids' => $file_ids
            )
        )->fetchAll(null, true);

        if ($ids) {
            $this->delete($ids);
        }
    }

    /**
     * @param int|array[int] $file_id
     */
    public function setLock($file_id)
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
    public function deleteLock($file_id)
    {
        $this->getLockModel()->delete($file_id);
    }

    protected function addToCopyOrMove($file_id, $operation, $params = array())
    {
        $file_ids = filesApp::toIntArray($file_id);
        $res = array_fill_keys($file_ids, 0);
        foreach (filesApp::dropNotPositive($file_ids) as $file_id) {
            $res[$file_id] = $this->add(
                $operation,
                $file_id,
                $params
            );
        }
        if (is_array($file_id)) {
            return $res;
        }
        return ifset($res[(int) $file_id], 0);
    }

    protected function getTaskByWhere($pause = null, $topology = true, $where = '', $contact_id = null)
    {
        $pause = (int) $pause;
        if ($pause <= 0) {
            $pause = $this->getConfig()->getMaxExecutionTime();
        }

        $lock = md5(uniqid(str_replace('::', '', __METHOD__)));
        $lock_expired_datetime = date('Y-m-d H:i:s', time() + $pause);
        $now_datetime = date('Y-m-d H:i:s');

        $join = '';

        if ($topology) {
            $join = "LEFT JOIN `{$this->table}` AS pq ON pq.id = q.parent_task_id";
            if ($where) {
                $where .= ' AND ';
            }
            $where .= 'pq.id IS NULL';
        }

        if ($where) {
            $where = ' AND ' . $where;
        }

        $order_by = '';
        $contact_id = (int) $contact_id;
        if ($contact_id > 0) {
            $order_by = ", IF(q.`contact_id` = '{$contact_id}', 0, 1)";
        }

        $sql = "
            UPDATE {$this->table} t JOIN (
                SELECT q.id
                FROM `{$this->table}` AS q
                {$join}
                WHERE (q.lock IS NULL OR q.lock_expired_datetime IS NULL OR q.lock_expired_datetime < '{$now_datetime}') {$where}  
                ORDER BY q.`create_datetime` DESC, q.`id` DESC {$order_by}
                LIMIT 0, 1
            ) r ON t.id = r.id
            SET t.`lock` = '{$lock}', t.`lock_expired_datetime` = '{$lock_expired_datetime}'";

        $this->exec($sql);
        return $this->getByField('lock', $lock);
    }

    protected function add($operation, $file_id, $params = array())
    {
        if (!in_array($operation, $this->getOperations())) {
            return false;
        }
        $file_id = (int) $file_id;
        if ($file_id <= 0) {
            return false;
        }

        $data = array(
            'file_id' => $file_id,
            'contact_id' => $this->contact_id,
            'operation' => $operation,
            'create_datetime' => date('Y-m-d H:i:s'),
        );

        if (isset($params['parent_id'])) {
            $data['parent_id'] = (int) $params['parent_id'];
        }

        if (isset($params['storage_id'])) {
            $data['storage_id'] = (int) $params['storage_id'];
        }

        if (isset($params['parent_task_id'])) {
            $data['parent_task_id'] = (int) $params['parent_task_id'];
            if ($data['parent_task_id'] <= 0) {
                $data['parent_task_id'] = null;
            }
        }

        if (isset($params['process_id'])) {
            $process_id = (int) $params['process_id'];
            if ($process_id !== 0) {
                $data['process_id'] = $process_id;
            }
        }

        if (isset($params['replace'])) {
            $data['replace'] = $params['replace'] ? 1 : 0;
        }

        if (isset($params['restore'])) {
            $data['restore'] = $params['restore'] ? 1 : 0;
        }

        $this->setLock($file_id);

        return $this->insert($data);
    }
}

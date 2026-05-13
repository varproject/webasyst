<?php

class filesSourceSyncModel extends filesModel
{
    const TYPE_ADD_FILE = 'file';
    const TYPE_ADD_FOLDER = 'folder';
    const TYPE_DELETE = 'delete';
    const TYPE_MOVE = 'move';
    const TYPE_RENAME = 'rename';

    protected $table = 'files_source_sync';

    public function append($source_id, $items)
    {
        $types = $this->getAvailableTypes();
        $types_map = array_fill_keys($types, true);

        $inserts = array();

        foreach ($items as $item) {

            if (!isset($item['type']) && !isset($types_map[$item['type']])) {
                continue;
            }

            $insert = array(
                'source_id' => $source_id,
                'source_inner_id' => null,
                'source_path' => null,
                'type' => '',
                'name' => '',
                'size' => 0
            );

            foreach ($item as $field => $value) {
                if ($this->fieldExists($field) && $field !== 'id') {
                    $insert[$field] = $value;
                    unset($item[$field]);
                }
            }

            if (array_key_exists('id', $item)) {
                if (!empty($item['id']) && empty($item['source_inner_id'])) {
                    $insert['source_inner_id'] = $item['id'];
                }
                unset($item['id']);
            } else if (array_key_exists('source_inner_id', $item)) {
                if (!empty($item['source_inner_id'])) {
                    $insert['source_inner_id'] = $item['source_inner_id'];
                }
                unset($item['source_inner_id']);
            }

            if (array_key_exists('path', $item)) {
                if (!empty($item['path']) && empty($insert['source_path'])) {
                    $insert['source_path'] = $item['path'];
                }
                unset($item['path']);
            }


            if (empty($insert['source_path'])) {
                $insert['source_path'] = '/';
            }

            $insert['uid'] = md5(__METHOD__ . serialize($insert));;
            $insert['datetime'] = date('Y-m-d H:i:s');

            // rest of fields goto params
            if ($item) {
                $insert['params'] = array();
                foreach ($item as $key => $value) {
                    if (!array_key_exists($key, $insert) && $value !== null) {
                        $insert['params'][$key] = $value;
                    }
                }
            }

            $inserts[$insert['uid']] = $insert;
        }

        $this->insertItems($inserts);

        $this->clearExpired();
    }

    public function count($source_id)
    {
        return $this->countByField(array('source_id' => $source_id, 'slice_id' => null));
    }

    public function sliceFirst($source_id, $count, $clean_right_away = true)
    {
        $source_id = (int) $source_id;
        if ($source_id <= 0) {
            return array();
        }
        $count = (int) $count;
        if ($count <= 0) {
            return array();
        }

        $slice_id = md5(uniqid('files_source_sync', true));

        $this->exec("
            UPDATE `files_source_sync`
            SET `slice_id` = '{$slice_id}'
            WHERE source_id = {$source_id} AND slice_id IS NULL
            ORDER BY id
            LIMIT {$count}
        ");

        $items = $this->getItemsBySliceId($slice_id);

        if ($clean_right_away) {
            $this->deleteItemsBySliceId($slice_id);
        }

        return $items;
    }

    public function delete($source_id)
    {
        $this->exec("DELETE s, sp 
              FROM `files_source_sync` s
              LEFT JOIN `files_source_sync_params` sp ON s.id = sp.source_sync_id
              WHERE s.source_id IN (:0)
        ", array($source_id));

        $this->deleteByField('source_id', $source_id);
        $this->clearExpired();
    }

    public function markSlicedByField($field, $timeout = null)
    {
        $slice_id = md5(uniqid(str_replace('::', '_', __METHOD__), true));
        $timeout = (int) $timeout;
        if ($timeout <= 0) {
            $timeout = 60;
        }
        $field['slice_id'] = null;
        $where = $this->getWhereByField($field);
        $now = date('Y-m-d H:i:s');
        $slice_expired_datetime = date('Y-m-d H:i:s', time() + $timeout);
        $this->exec("
              UPDATE `{$this->table}` 
              SET slice_id = '{$slice_id}', slice_expired_datetime = '{$slice_expired_datetime}'
              WHERE {$where} AND (slice_expired_datetime < '{$now}' OR slice_expired_datetime IS NULL)
        ");
        return $slice_id;
    }

    /**
     * @param int|array[]int $source_id
     * @return bool|array[]bool
     */
    public function inSync($source_id)
    {
        $source_ids = filesApp::toIntArray($source_id);
        $map = array_fill_keys($source_ids, false);
        if ($source_ids) {
            foreach ($this->select('DISTINCT source_id')->where('source_id IN (:source_id)', array('source_id' => $source_ids))->fetchAll() as $row) {
                $map[$row['source_id']] = true;
            }
        }
        return is_array($source_id) ? $map : $map[(int) $source_id];
    }

    private function getAvailableTypes()
    {
        return $this->getConstantsByPrefix('TYPE_');
    }

    protected function clearExpired()
    {
        $datetime = date('Y-m-d H:i:s', strtotime('-24 hour'));
        $this->exec("DELETE s, sp 
              FROM `files_source_sync` s
              LEFT JOIN `files_source_sync_params` sp ON s.id = sp.source_sync_id
              WHERE s.datetime <= :0
        ", array($datetime));

        $sspm = new filesSourceSyncParamsModel();
        $sspm->clean();

        //$this->exec("DELETE FROM `files_source_sync` WHERE datetime < :0", array($datetime));
    }

    protected function getItemsBySliceId($slice_id)
    {
        $items = $this->getByField(array('slice_id' => $slice_id), 'id');
        $ids = array_keys($items);

        $ssp = new filesSourceSyncParamsModel();
        $all_plain_params = $ssp->getByField(array('source_sync_id' => $ids), true);
        foreach ($all_plain_params as $param_item) {
            $item_id = $param_item['source_sync_id'];
            if (!$this->fieldExists($param_item['name'])) {
                $items[$item_id] = ifset($items[$item_id], array());
                $items[$item_id][$param_item['name']] = $param_item['value'];
            }
        }

        return $items;
    }

    public function deleteItemsBySliceId($slice_id)
    {
        $sspm = new filesSourceSyncParamsModel();
        $sspm->clean();
        return $this->deleteByFieldWithRelations(array('slice_id' => $slice_id));
    }

    public function deleteByFieldWithRelations($field, $value = null)
    {
        $sspm = new filesSourceSyncParamsModel();
        $sspm->clean();
        if ($where = $this->getWhereByField($field, $value, 's')) {
            return $this->exec("DELETE s, sp 
                  FROM `files_source_sync` s
                  LEFT JOIN `files_source_sync_params` sp ON s.id = sp.source_sync_id
                  WHERE {$where}
            ");
        }
        return true;
    }

    protected function insertItems($items)
    {
        $uids = array_keys($items);
        $this->deleteByField(array('uid' => $uids));

        $all_params = array();
        foreach ($items as $uid => $item) {
            if (!empty($item['params'])) {
                foreach ($item['params'] as $key => $value) {
                    if ($value === null) {
                        unset($item['params'][$key]);
                    }
                }
                $all_params[$uid] = $item['params'];
                unset($items[$uid]['params']);
            }
        }

        $items = array_values($items);
        $this->multipleInsert($items);

        // insert params
        if ($all_params) {

            $uid_id_map = $this->select('id,uid')->where($this->getWhereByField(array(
                'uid' => $uids
            )))->fetchAll('uid', true);

            $plain_all_params = array();
            foreach ($all_params as $uid => $item_params) {
                $id = $uid_id_map[$uid];
                foreach ($item_params as $name => $value) {
                    $plain_all_params[] = array('source_sync_id' => $id, 'name' => $name, 'value' => $value);
                }
            }

            $ssp = new filesSourceSyncParamsModel();
            $ssp->multipleInsert($plain_all_params);
        }
    }
}

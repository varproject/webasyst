<?php

class filesSourceModel extends filesModel
{
    protected $table = 'files_source';

    public function delete($id)
    {
        $source = $this->getById($id);

        $fm = new filesFileModel();
        $parent = null;
        $folder_id = $source['folder_id'];
        if ($folder_id) {
            $folder = $fm->getItem($folder_id, false);
            $parent_id = $folder['parent_id'];
            if ($parent_id) {
                $parent = $fm->getItem($parent_id, false);
            }
        }

        $this->deleteById($id);

        $spm = new filesSourceParamsModel();
        $spm->deleteByField(array('source_id' => $id));

        $queue = new filesSourceSyncModel();
        $queue->delete($id);

        // delete all files related data by one query
        $aliases = array();
        $joins = array();
        $fm = new filesFileModel();
        $i = 0;
        foreach ($fm->getRelatedModels() as $m) {
            /**
             * @var filesFileRelatedInterface|filesModel $m
             */
            foreach ($m->getRelatedFields() as $key) {
                $t = $m->getTableName();
                $a = 't' . $i;
                $aliases[] = $a;
                $joins[] = "LEFT JOIN {$t} AS {$a} ON {$a}.{$key} = f.id";
                $i += 1;
            }
        }

        $aliases = join(',', $aliases);
        $joins = join(PHP_EOL, $joins);

        $sql = "DELETE f, {$aliases} 
                  FROM files_file f
                  {$joins}
                  WHERE f.source_id IN (i:source_id)";

        $fm->exec($sql, array('source_id' => array($id, -$id)));

        if ($parent) {
            $fm->updateById($parent['id'], array(
                'right_key' => $parent['left_key'] + 1
            ));
            $all_folder_ids = array_keys($fm->getAncestors($parent['id']));
            $all_folder_ids[] = $parent['id'];
            $fm->updateCount($all_folder_ids);
        }

        $sm = new filesStorageModel();
        $sm->deleteByField(array(
            'source_id' => $id
        ));
        $sm->updateCount($source['storage_id']);
    }

    /*
    public function deleteByField($field, $value = null)
    {
        if (is_array($field)) {
            $items = $this->getByField($field, $this->id);
            $ids = array_keys($items);
        } elseif ($field == $this->id) {
            $ids = $value;
        } else {
            $items = $this->getByField($field, $value, $this->id);
            $ids = array_keys($items);
        }
        $res = false;
        if ($ids) {
            $res = parent::deleteByField($this->id, $ids);
            if ($res) {
                $params_model = new filesSourceParamsModel();
                $params_model->deleteByField('source_id', $ids);
                //TODO delete files&folders at files_file table by source_id
                //XXX and repair left/right keys for them
            }
        }
        return $res;
    }*/

    /**
     * @param mixed [string] $data
     * @param string [string] $data [type]
     * @param string [string] $data [name]
     * @param int [string] $data [contact_id]
     * @param string [string] $data [create_datetime']
     * @param string [string] $data['params']
     * @param string [string] $data['hello']
     *
     * @return array
     */
    public function save($data)
    {
        if (!empty($data['id'])) {
            if (isset($data['contact_id'])) {
                unset($data['contact_id']);
            }
            if (isset($data['create_datetime'])) {
                unset($data['create_datetime']);
            }
            $this->updateById($data['id'], $data);
        } else {
            $data['create_datetime'] = date('Y-m-d H:i:s');
            $data['contact_id'] = wa()->getUser()->getId();
            $data['id'] = $this->insert($data);
        }

        if (isset($data['params'])) {
            $model = new filesSourceParamsModel();
            $model->add($data['id'], $data['params']);
        }
        return $data;
    }

    public function getAllSources()
    {
        if (filesRights::inst()->isAdmin()) {
            $sources = $this->getAll('id');
        } else {
            $sources = $this->select('*')
                ->where('contact_id = i:0', array($this->contact_id))
                ->fetchAll();
        }

        return $this->workupSources($sources);
    }

    public function countAllSources()
    {
        if (filesRights::inst()->isAdmin()) {
            return $this->countAll();
        } else {
            return $this->countByField('contact_id', $this->contact_id);
        }
    }

    private function workupSources($sources)
    {
        $types = array();
        foreach ($sources as $item) {
            $types[] = $item['type'];
        }
        $types = array_unique($types);

        $plugins = array();
        foreach ($types as $type) {
            $plugins[$type] = filesSourcePlugin::factory($type);
        }

        foreach ($sources as &$item) {
            $plugin = ifset($plugins[$item['type']]);
            $item['icon'] = (is_object($plugin) && $plugin instanceof filesSourcePlugin) ? $plugin->getIconUrl() : null;
            $source_object = filesSource::factory($item['id']);
            $item['has_valid_token'] = $source_object->hasValidToken();
        }
        unset($item);

        return $sources;
    }

    public function getOneSynchronized($timeout, $update_in_progress_datetime = true)
    {
        $timeout = (int) $timeout;
        $datetime = date('Y-m-d H:i:s', strtotime('-' . $timeout . ' second'));
        $max_long_pause = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $res = $this->query("
              SELECT fs.* FROM files_source fs
              JOIN files_source_params fspt ON fspt.source_id = fs.id AND fspt.name = 'token'
              LEFT JOIN files_source_params fsptinv ON fsptinv.source_id = fs.id AND fspt.name = 'token_invalid'
              LEFT JOIN files_source_params fspp ON fspp.source_id = fs.id AND fspp.name = 'sync_paused' 
              WHERE 
                (fs.storage_id IS NOT NULL OR fs.folder_id IS NOT NULL) AND 
                  (fs.in_progress_datetime < '{$datetime}' OR fs.in_progress_datetime IS NULL) AND
                  (fspt.value IS NOT NULL OR fspt.value != '') AND
                  fsptinv.value IS NULL AND (fspp.value IS NULL OR fspp.value < '{$max_long_pause}')
              ORDER BY fs.in_progress_datetime, fs.synchronize_datetime LIMIT 1
        ")->fetchAssoc();

        if ($res && $update_in_progress_datetime) {
            $in_progress_datetime = date('Y-m-d H:i:s');
            $this->updateById($res['id'], array(
                'in_progress_datetime' => $in_progress_datetime
            ));
            $res['in_progress_datetime'] = $in_progress_datetime;
        }
        return $res;
    }
}

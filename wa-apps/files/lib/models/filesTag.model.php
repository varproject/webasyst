<?php

class filesTagModel extends filesModel
{
    protected $table = 'files_tag';

    const CLOUD_MAX_SIZE = 120;
    const CLOUD_MIN_SIZE = 80;
    const CLOUD_MAX_OPACITY = 100;
    const CLOUD_MIN_OPACITY = 30;

    public function getByName($name, $return_id = false)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE name LIKE '".$this->escape($name, 'like')."'";
        $row = $this->query($sql)->fetch();
        return $return_id ? (isset($row['id']) ? $row['id'] : null) : $row;
    }

    public function getByTerm($term, $limit = 10)
    {
        $limit = (int) $limit;

        $user_tags = $this->getUserTags();
        if (!$user_tags) {
            return array();
        }
        $tag_ids_str = "'" . join("','", array_keys($user_tags)) . "'";

        $sql = "SELECT * FROM files_tag
            WHERE name LIKE '".$this->escape($term, 'like') . "%' AND id IN ({$tag_ids_str})
            LIMIT 0, {$limit}";

        $tags = $this->query($sql)->fetchAll('id');
        foreach ($tags as &$tag) {
            $tag['count'] = $user_tags[$tag['id']]['count'];
        }
        unset($tag);

        return $this->sortTags($tags);
    }

    private function sortTags($tags)
    {
        uasort($tags, function($a, $b) {
            if ($a['count'] == $b['count']) {
                return strcmp($a['name'], $b['name']);
            } else {
                return $a['count'] < $b['count'] ? 1 : -1;
            }
        });
        return $tags;
    }

    private function getCacheTags($cache_key)
    {
        $csm = new waContactSettingsModel();
        $tags_cache = $csm->getOne($this->contact_id, $this->app_id, 'tags/cache');
        $tags_cache = @json_decode($tags_cache, true);
        if (is_array($tags_cache) &&
                isset($tags_cache[$cache_key]) &&
                isset($tags_cache[$cache_key]['datetime']) &&
                isset($tags_cache[$cache_key]['data'])
        )
        {
            $cache_time = strtotime($tags_cache[$cache_key]['datetime']);
            $now_time = time();
            $five_minutes = 300;
            // is cache actual ?
            if ($cache_time + $five_minutes > $now_time) {
                return $tags_cache[$cache_key]['data'];
            }
        }
        return null;
    }

    private function setCacheTags($tags, $cache_key)
    {
        $cache = array(
            $cache_key => array(
                'datetime' => date('Y-m-d H:i:s'),
                'data' => $tags
            )
        );
        $csm = new waContactSettingsModel();
        $csm->set($this->contact_id, $this->app_id, 'tags/cache', json_encode($cache));
    }

    public function clearCacheTags()
    {
        $csm = new waContactSettingsModel();
        $csm->delete($this->contact_id, $this->app_id, 'tags/cache');
    }

    private function getUserTags()
    {
        $groups = filesRights::inst()->getGroupIds();
        sort($groups);
        $groups_str = "'" . join("','", $groups) . "'";

        $storage_model = new filesStorageModel();
        $storages = array_keys($storage_model->getAvailableStorages());
        sort($storages);
        $storages_str = "'" . join("','", $storages) . "'";

        $cache_key = md5($groups_str . $storages_str);

        $tags = $this->getCacheTags($cache_key);
        if ($tags !== null) {
            return $tags;
        }

        $where = array();
        $joins = array(
            "JOIN files_file_tags ft ON ft.tag_id = t.id",
            "JOIN files_file f ON f.id = ft.file_id"
        );

        if ($storages) {
            $joins[] = "LEFT JOIN files_file_rights fr ON fr.file_id = f.id AND fr.group_id IN({$groups_str})";
            $where[] = "(f.storage_id IN({$storages_str}) OR fr.group_id IS NOT NULL)";
        } else {
            $joins[] = "JOIN files_file_rights fr ON fr.file_id = f.id AND fr.group_id IN({$groups_str})";
        }

        $joins_str = join(' ', $joins);
        $where_str = $where ? "WHERE " . join(' AND ', $where) : '';

        $sql = "SELECT t.*, COUNT(*) AS count
            FROM files_tag t
            {$joins_str}
            {$where_str}
            GROUP BY t.id";

        $tags = $this->query($sql)->fetchAll('id');

        $this->setCacheTags($tags, $cache_key);

        return $tags;
    }

    public function getByFile($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $file_ids = filesApp::dropNotPositive($file_ids);
        if (!$file_ids) {
            return $file_ids;
        }
        $sql = "SELECT * FROM files_tag t
            JOIN files_file_tags ft ON t.id = ft.tag_id
            WHERE ft.file_id IN(i:id)";

        $tags = array_fill_keys($file_ids, array());
        foreach ($this->query($sql, array('id' => $file_ids)) as $item) {
            $tags[$item['file_id']][$item['id']] = array(
                'id' => $item['id'],
                'name' => $item['name']
            );
        }

        return is_numeric($file_id) ? $tags[$file_id] : $tags;
    }

    /**
     *
     * @param int $file_id
     * @param array $tags array of strings
     * @return bool
     */
    public function assign($file_id, $tags)
    {
        $file_model = new filesFileModel();
        $file = $file_model->getFile($file_id);
        if (!$file) {
            return false;
        }
        $tag_ids = $this->getIds($tags);

        $file_tags_model = new filesFileTagsModel();

        // simple kind of realization: delete all, than assign all
        $file_tags_model->deleteByFile($file_id);

        $data = array();
        foreach ($tag_ids as $tag_id) {
            $data[] = array(
                'file_id' => $file_id,
                'tag_id'   => $tag_id
            );
        }

        $file_tags_model->multipleInsert($data);
        $this->clearCacheTags();
        return true;
    }

    public function add($file_id, $tags)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $tag_ids = $this->getIds($tags);

        if (!$file_ids || !$file_ids) {
            return;
        }

        $sql = "INSERT IGNORE INTO `files_file_tags` (file_id, tag_id)";

        $values = array();
        foreach ($file_ids as $fid) {
            foreach ($tag_ids as $tid) {
                $values[] = "({$fid}, {$tid})";
            }
        }
        $values = join(',', $values);
        $sql .= " VALUES {$values}";
        $this->exec($sql);

    }

    public function getIds($tags)
    {
        $result = array();
        foreach ($tags as $t) {
            $t = trim($t);
            if ($id = $this->getByName($t, true)) {
                $result[] = $id;
            } else {
                $result[] = $this->insert(array('name' => $t));
            }
        }
        return $result;
    }

    public function getCloud($limit = 100)
    {
        $sql = "SELECT id, name, COUNT(*) as count
                FROM ".$this->table." t
                    JOIN files_file_tags tt ON t.id = tt.tag_id
                GROUP BY t.id
                ORDER BY count DESC";
        if ($limit) {
            $sql .= ' LIMIT '.(int)$limit;
        }
        $tags = $this->query($sql)->fetchAll();
        if (!empty($tags)) {
            $first = current($tags);
            $max_count = $min_count = $first['count'];
            foreach ($tags as $tag) {
                if ($tag['count'] > $max_count) {
                    $max_count = $tag['count'];
                }
                if ($tag['count'] < $min_count) {
                    $min_count = $tag['count'];
                }
            }
            $diff = $max_count - $min_count;
            $diff = $diff <= 0 ? 1 : $diff;
            $step_size = (self::CLOUD_MAX_SIZE - self::CLOUD_MIN_SIZE) / $diff;
            $step_opacity = (self::CLOUD_MAX_OPACITY - self::CLOUD_MIN_OPACITY) / $diff;
            foreach ($tags as &$tag) {
                $tag['size'] = ceil(self::CLOUD_MIN_SIZE + ($tag['count'] - $min_count) * $step_size);
                $tag['opacity'] = number_format((self::CLOUD_MIN_OPACITY + ($tag['count'] - $min_count) * $step_opacity) / 100, 2, '.', '');
            }
            unset($tag);
        }
        return $tags;
    }

    public function getPopularTags($limit = 10, $id_name = false)
    {
        $limit = (int) $limit;
        $user_tags = $this->getUserTags();
        if (!$user_tags) {
            return array();
        }
        $tag_ids_str = "'" . join("','", array_keys($user_tags)) . "'";
        $sql = "SELECT * FROM files_tag WHERE id IN ({$tag_ids_str})";
        $tags = $this->query($sql)->fetchAll('id');
        foreach ($tags as &$tag) {
            $tag['count'] = $user_tags[$tag['id']]['count'];
        }
        unset($tag);

        $tags = $this->sortTags($tags);

        // and now slice by limit
        $tags = array_slice($tags, 0, $limit, true);

        if ($id_name) {
            $res = array();
            foreach ($tags as $tag) {
                $res[$tag['id']] = $tag['name'];
            }
            return $res;
        }

        return $tags;
    }

    /*
    public function recount()
    {
        $select = "`{$this->table}` t
        LEFT JOIN (
            SELECT tag_id, count(tag_id) cnt FROM `files_file_tags` GROUP BY tag_id
        ) tt
        ON t.id = tt.tag_id";

        $sql = "SELECT t.id FROM {$select} WHERE tt.tag_id IS NULL";
        $ids = array_keys($this->query($sql)->fetchAll('id'));

        if ($ids) {
            // delete tags that has no assignments
            $sql = "DELETE FROM `{$this->table}` WHERE id IN (".implode(',', $ids).")";
            $this->exec($sql);
        }

        // update counters
        $sql = "UPDATE {$select} SET t.count = tt.cnt";
        $this->exec($sql);
    }*/

}
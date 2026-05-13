<?php

class filesFileRightsModel extends filesModel implements filesFileRelatedInterface
{
    protected $table = 'files_file_rights';

    public function set($file_id, $data)
    {
        $file_model = new filesFileModel();
        $file = $file_model->getById($file_id);
        if (!$file) {
            $this->clean($file_id);
            return false;
        }

        $data = $this->prepareBeforeAdd($file, $data);
        if (!$data) {
            return false;
        }

        $old_groups = $this->getGroups($file_id);

        $delete_groups = array();

        foreach ($data as $k => $item) {
            $group_right = ifset($old_groups[$item['group_id']]);
            if ($group_right && $group_right['level'] !== $item['level']) {
                $delete_groups[] = $item['group_id'];
            }
        }

        if ($delete_groups) {
            $this->deleteByField(array(
                'file_id' => $file_id,
                'group_id' => $delete_groups
            ));
        }

        if ($file['type'] === filesFileModel::TYPE_FILE) {
            $this->addFile($data);
        } else {
            $this->addFolder($file, $data);
        }

        $groups = filesApp::getFieldValues($data, 'group_id');
        filesRights::inst()->setLimitedAccess($groups);

        return true;
    }

    private function prepareBeforeAdd($file, $data)
    {
        $levels = filesApp::inst()->getConfig()->getRightConfig()->getRightLevels(true);
        foreach ($data as &$item) {
            $level = (int) ifset($item['level']);
            $available = in_array($level, $levels);
            if (!$available || $level === filesRightConfig::RIGHT_LEVEL_NONE) {
                continue;
            }
            $item['level'] = $level;
            $item['group_id'] = (int) $item['group_id'];
            $item['create_datetime'] = date('Y-m-d H:i:s');
            $item['root_file_id'] = $file['id'];
            $item['file_id'] = $file['id'];
            $item['creator_contact_id'] = $this->contact_id;
        }
        unset($item);

        return $data;
    }

    public function clean($file_id)
    {
        $this->deleteByField(array(
            'root_file_id' => $file_id
        ));
        $this->deleteByField(array(
            'file_id' => $file_id
        ));
    }

    private function addFile($data)
    {
        $insert = array();
        foreach ($data as $item) {
            $this->deleteByField(array(
                'file_id' => $item['file_id'],
                'group_id' => $item['group_id']
            ));
            $this->deleteByField(array(
                'root_file_id' => $item['file_id']
            ));
            $insert[] = $item;
        }

        $this->multipleInsert($insert);
        return true;
    }

    private function addFolder($folder, $data)
    {
        foreach ($data as $item) {
            $this->deleteByField(array(
                'file_id' => $item['file_id'],
                'group_id' => $item['group_id']
            ));
            $this->deleteByField(array(
                'root_file_id' => $item['file_id']
            ));
        }

        // insert for each group
        foreach ($data as $item) {

            // insert folder content tree
            $sql = "REPLACE INTO {$this->table} (file_id, group_id, level, root_file_id, create_datetime, creator_contact_id)
            SELECT
                DISTINCT f.id,
                '{$item['group_id']}',
                '{$item['level']}',
                '{$item['file_id']}',
                '{$item['create_datetime']}',
                '{$item['creator_contact_id']}'
            FROM files_file f
            JOIN files_file p ON p.id = f.parent_id
            WHERE f.storage_id = {$folder['storage_id']} AND
                (p.left_key BETWEEN {$folder['left_key']} AND {$folder['right_key']})";

            $this->exec($sql);

            // insert item itself
            $this->insert($item);
        }
    }

    public function update($file_id, $data)
    {
        $file_model = new filesFileModel();
        $file = $file_model->getById($file_id);

        if (!$file) {
            // just in case garbage cleaning
            $this->deleteByField(array(
                'root_file_id' => $file['id']
            ));
            $this->deleteByField(array(
                'file_id' => $file['id']
            ));
            return false;
        }

        foreach ($data as $info)
        {
            $info['group_id'] = (int) $info['group_id'];
            $info['level'] = (int) $info['level'];

            $row = $this->getByField(array(
                'file_id' => $file['id'],
                'group_id' => $info['group_id'],
                'root_file_id' => $file['id']
            ));

            if (!$row || $row['level'] == $info['level']) {
                continue;
            }

            if ($info['level'] == filesRightConfig::RIGHT_LEVEL_NONE) {
                $this->deleteByField(array(
                    'group_id' => $info['group_id'],
                    'root_file_id' => $file['id']
                ));
            } else {
                $this->updateByField(array(
                    'group_id' => $info['group_id'],
                    'root_file_id' => $file['id']
                ), array(
                    'level' => $info['level']
                ));
            }
        }

        return true;
    }

    public function delete($file_id)
    {
        $this->deleteByField(array(
            'root_file_id' => $file_id
        ));
    }

    public function getRights($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $rights = array_fill_keys($file_ids, array());
        foreach ($this->getByField(array('root_file_id' => $file_ids), true) as $item)
        {
            $rights[$item['root_file_id']] = ifset($rights[$item['root_file_id']], array());
            $rights[$item['root_file_id']][$item['file_id']] = ifset($rights[$item['root_file_id']][$item['file_id']], array());
            $rights[$item['root_file_id']][$item['file_id']][$item['group_id']] = $item;
        }
        return !is_array($file_id) ? $rights[(int) $file_id] : $rights;
    }

    /**
     * @param int|array[int] $file_id
     * @param bool $reset_cache
     * @return array
     */
    public function getGroups($file_id, $reset_cache = false)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $rights = array_fill_keys($file_ids, array());
        $where = $this->getWhereByField(array(
            'file_id' => $file_ids
        ));

        if ($reset_cache) {
            $this->clearQueryCache(__METHOD__);
        }

        $query = $this->cacheQuery("SELECT * FROM {$this->table} WHERE {$where}", __METHOD__);
        foreach ($query as $item)  {
            $rights[$item['file_id']][$item['group_id']] = $item;
        }
        return !is_array($file_id) ? $rights[(int) $file_id] : $rights;
    }

    /**
     * @param string $type user|group
     * @param int|array[int] $file_id
     * @param bool|true $format
     * @return array
     */
    private function getGroupsOfType($type, $file_id, $format = true)
    {
        $res_groups = array();
        foreach ($this->getGroups((array) $file_id) as $st_id => $groups) {
            if ($type === 'group') {
                $res_groups[$st_id] = $this->extractGroups($groups);
            } else if ($type === 'user') {
                $res_groups[$st_id] = $this->extractUsers($groups);
            } else {
                $res_groups[$st_id] = array();
            }
        }
        return !is_array($file_id) ? $res_groups[(int) $file_id] : $res_groups;
    }

    /**
     * Get just groups (not users)
     * @param int|array[int] $file_id
     * @param bool|true $format
     * @return array
     */
    public function getJustGroups($file_id, $format = true)
    {
        return $this->getGroupsOfType('group', $file_id, $format);
    }

    /**
     * Get just users (not groups)
     * @param int|array[int] $file_id
     * @param bool|true $format
     * @return array
     */
    public function getJustUsers($file_id, $format = true)
    {
        return $this->getGroupsOfType('user', $file_id, $format);
    }

    public function getMaxRightsToFile($file_id, $groups = null)
    {
        $file_ids = filesApp::toIntArray($file_id);

        $where = array('file_id' => $file_ids);
        if ($groups === null) {
            $groups = filesRights::inst()->getGroupIds();
        }
        $where['group_id'] = array_map('intval', (array) $groups);
        $where_str = $this->getWhereByField($where);

        $rights = array_fill_keys($file_ids, filesRightConfig::RIGHT_LEVEL_NONE);
        $sql = "SELECT MAX(level) AS level, file_id FROM `{$this->table}`
            WHERE {$where_str}
            GROUP BY file_id";

        foreach ($this->query($sql) as $right) {
            $rights[$right['file_id']] = $right['level'];
        }
        return !is_array($file_id) ? $rights[(int) $file_id] : $rights;
    }

    public function cloneRights($folder_src_id, $file_dst_id)
    {
        $file_dst_id = filesApp::toIntArray($file_dst_id);
        $file_dst_id = filesApp::dropNotPositive($file_dst_id);

        if (!$file_dst_id) {
            return false;
        }

        $folder_src_id = (int) $folder_src_id;
        if ($folder_src_id <= 0) {
            return false;
        }

        $sql = "REPLACE INTO files_file_rights
                SELECT f.id AS file_id, pr.group_id, pr.level, pr.root_file_id, s:datetime, i:contact_id
                FROM files_file_rights pr
                JOIN files_file f
                WHERE pr.file_id=i:src_id AND f.id IN (i:dst_id)";

        if (!$this->exec($sql, array(
            'datetime' => date('Y-m-d H:i:s'),
            'contact_id' => $this->contact_id,
            'src_id' => $folder_src_id,
            'dst_id' => $file_dst_id
        ))) {
            return false;
        }

        return true;

    }

    /**
     * Event method called when file(s) is deleting
     * @param array[int]|int $file_id
     */
    public function onDeleteFile($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $this->deleteByField(array(
            'file_id' => $file_ids
        ));
    }

    public function getRelatedFields()
    {
        return array('file_id');
    }

}

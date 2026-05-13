<?php

class filesSourceSyncDefaultDriver extends filesSourceSyncDriver
{
    public function process($params = array())
    {
        // when first call of process, params should not have folder tree
        // but another calls should for cash (see getFolderTree method)
        if (isset($params['folder_tree'])) {
            $this->options['folder_tree'] = $params['folder_tree'];
        }

        // get list and the handle each item of list
        $list = $this->queue->sliceFirst($this->source->getId(), $this->getChunkSize());

        $list_count = count($list);

        if ($list_count <= 0) {
            return array(
                'count' => $list_count
            );
        }

        $count = 0;

        $count += $this->processDelete($list);
        if ($count < $list_count) {
            $count += $this->processAdd($list);
        }

        $this->clearExpired();

        return array(
            'count' => $count,
            'folder_tree' => $this->getFolderTree()
        );
    }

    private function processDelete($list)
    {
        $source_id = $this->source->getId();

        $delete_files = array();
        foreach ($list as $item) {
            $type = $item['type'];
            if ($type !== filesSourceSyncModel::TYPE_DELETE) {
                continue;
            }
            $delete_files[] = array('source_path' => $item['source_path']);
        }

        $this->fm->syncDeleteFiles($delete_files, $source_id);

        return count($delete_files);
    }

    private function processAdd($list)
    {
        $source_id = $this->source->getId();
        $source_storage_id = $this->source->getStorageId();
        $source_folder_id = $this->source->getFolderId();

        // map for keep hierarchy of folders (map has folder name as a key)
        $folder_tree = $this->getFolderTree();

        // files to insert
        $files = array();

        $types_map = array(
            filesSourceSyncModel::TYPE_ADD_FILE => true,
            filesSourceSyncModel::TYPE_ADD_FOLDER => true
        );

        foreach ($list as $item) {

            if (!isset($types_map[$item['type']])) {
                continue;
            }

            $type = $item['type'];

            /**
             * Source path for building $folder_tree map, source path is unique path for current source
             * It will be saved as files_file.source_path field
             * In contrast, path - it's display path, this is just displayed names joined with '/'
             */
            $source_path = $item['path'];
            if (!empty($item['source_path'])) {
                $source_path = $item['source_path'];
            }
            $source_path = trim(trim($source_path), '/');
            $source_path_ar = explode('/', $source_path);

            $path = trim(trim($item['path']), '/');
            $path_ar = explode('/', $path);

            // drop file name tail, not need
            if ($type === 'file') {
                array_pop($source_path_ar);
            }

            // So main loop below it's about:
            // Walk through path and add folder if needed,
            // And build tree map for keeping track of hierarchy

            // current parent
            $parent_id = $source_folder_id;

            // current place in hierarchy
            $tree = &$folder_tree;

            // update folder hierarchy
            $source_path = '';

            $current_folder_node = null;

            foreach ($source_path_ar as $part_index => $source_path_part) {

                $source_path .= '/' . $source_path_part;

                // if already exists in hierarchy map (also in DB) - keep moving on path
                if (isset($tree[$source_path_part])) {
                    $current_folder_node = &$tree[$source_path_part];
                    $parent_id = $tree[$source_path_part]['id'];
                    $tree = &$tree[$source_path_part]['children'];
                    continue;
                }

                // no in hierarchy map, (also no in DB), so add folder...
                $folder_info = array(
                    'name' => !empty($path_ar[$part_index]) ? $path_ar[$part_index] : $source_path_part,
                    'parent_id' => $parent_id,
                    'storage_id' => $source_storage_id,
                    'source_id' => $source_id,
                    'source_path' => $source_path
                );
                if ($source_path === $item['source_path'] &&
                    $item['type'] === filesSourceSyncModel::TYPE_ADD_FOLDER) {
                    $folder_info['source_inner_id'] = $item['source_inner_id'];
                }

                $folder_id = $this->fm->syncAddFolder($folder_info, $source_id);

                // ...and correct hierarchy map
                if ($folder_id) {
                    $folder_info['id'] = $folder_id;
                    $folder_info['children'] = array();
                    $tree[$source_path_part] = $folder_info;
                    $current_folder_node = &$tree[$source_path_part];
                    $tree = &$tree[$source_path_part]['children'];
                    $parent_id = $folder_info['id'];
                }
            }

            if ($type === filesSourceSyncModel::TYPE_ADD_FOLDER && $current_folder_node) {
                $update = array();
                if (empty($current_folder_node['source_inner_id'])) {
                    $current_folder_node['source_inner_id'] = $item['source_inner_id'];
                    $update['source_inner_id'] = $current_folder_node['source_inner_id'];
                }
                if ($current_folder_node['name'] !== $item['name']) {
                    $current_folder_node['name'] = $item['name'];
                    $update['name'] = $item['name'];
                }
                if ($update) {
                    $this->fm->updateById($current_folder_node['id'], $update);
                }
            }
            unset($current_folder_node);

            if ($type === filesSourceSyncModel::TYPE_ADD_FILE) {
                $file_info = array(
                    'parent_id' => $parent_id,
                    'storage_id' => $source_storage_id,
                    'name' => $item['name'],
                    'size' => ifset($item['size'], 0),
                    'source_id' => $source_id,
                    'source_inner_id' => ifset($item['source_inner_id']),
                    'source_path' => ifset($item['source_path'], $source_path . '/' . $item['name'])
                );
                $files[] = $file_info;
            }
        }

        unset($tree);

        $list_count = count($list);

        if ($files) {
            $this->fm->syncAddFiles($files, $this->source->getId());
        }

        $this->options['folder_tree'] = $folder_tree;

        $this->source->setSynchronizeDatetime();

        $source_folder = $source_folder_id;
        if ($source_folder['hash'] && $list_count) {
            $this->fm->updateByField(array(
                'source_id' => $this->source->getId()
            ), array(
                'hash' => $source_folder['hash']
            ));
        }

        return $list_count;
    }

    /***
     * Folder tree - auxiliary data structure for tracking folders hierarchy in runtime
     *  and build correct folders relationship (parent-child) in db
     * @return mixed
     */
    private function getFolderTree()
    {
        if (!array_key_exists('folder_tree', $this->options)) {
            $folder_tree = array();
            $type_folder = filesFileModel::TYPE_FOLDER;
            $source_id = $this->source->getId();
            $folder_id = $this->source->getFolderId();
            $where = "WHERE `source_id` = {$source_id} AND `type` = '$type_folder'";
            if ($folder_id) {
                $where .= " AND id != {$folder_id}";
            }
            $folders = $this->fm->query("
              SELECT * FROM files_file 
              {$where}
              ORDER BY `source_path`
            ")->fetchAll();

            foreach ($folders as $folder) {
                $tree = &$folder_tree;
                $folder['children'] = array();
                $path_ar = explode('/', trim($folder['source_path'], '/'));
                $end_chunk = array_pop($path_ar);
                foreach ($path_ar as $path_part) {
                    if (!isset($tree[$path_part])) {
                        break;
                    }
                    $tree = &$tree[$path_part]['children'];
                }
                $tree[$end_chunk] = $folder;
                $tree = &$tree[$end_chunk]['children'];
            }
            unset($tree);
            $this->options['folder_tree'] = $folder_tree;
        }
        return $this->options['folder_tree'];
    }

    private function clearExpired()
    {
        $datetime = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $files = $this->fm->query("
            SELECT *  FROM `files_file` 
            WHERE source_id = :source_id 
              AND update_datetime < :datetime
        ", array(
            'source_id' => -$this->source->getId(),
            'datetime' => $datetime
        ))->fetchAll('id');
        $this->fm->delete($files);
    }

}

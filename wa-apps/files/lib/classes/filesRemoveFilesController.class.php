<?php

class filesRemoveFilesController extends filesController
{
    protected $start_time;
    protected $max_execution_time;

    public function execute()
    {
        $this->start_time = time();
        $this->max_execution_time = $this->getConfig()->getMaxExecutionTime();

        $this->executeRemove();
    }

    protected function executeRemove()
    {
        $file_ids = $this->getFileIds();
        $files = $this->getFileModel()->getById($file_ids);

        // separate to 2 groups: from app sources and from external source
        // treat them differently
        $res = $this->separate($files);
        $app_files = $res['app_files'];
        $external_files = $res['external_files'];

        $app_file_ids = $this->deleteAppFiles($app_files);
        $external_file_ids = $this->deleteExternalFiles($external_files);

        $file_ids = array_merge($app_file_ids, $external_file_ids);
        $this->logAction('delete', join(',', $file_ids));

        $this->assign(array(
            'success' => true,
            'files' => $files,
        ));
    }

    public function getFileIds()
    {
        // override it
        return array();
    }

    public function isPermanently()
    {
        // override it
        return false;
    }

    public function dropInSync($file_ids)
    {
        // override it
        return $file_ids;
    }

    protected function unmountInnerSources($files)
    {
        $sources_to_unmount = array();

        // find sources just parent folders
        $source_of_folders = $this->getFileModel()->getAllSourcesOfFolders($files);
        $source_of_folders = filesApp::toIntArray($source_of_folders);

        // find that sources that are roots, they need be unmount
        foreach ($source_of_folders as $folder_id => $source_of_folder) {
            $source = filesSource::factory($source_of_folder);
            if (!$source->isApp() && !$source->isNull() && $source->isMounted()) {
                $info = $source->getInfo();
                $source_folder_id = (int) ifset($info['folder_id']);
                if ($source_folder_id === (int) $folder_id) {
                    $sources_to_unmount[$source->getId()] = $source;
                }
            }
        }

        // find all inner sources, because they need be unmount too
        $sources_inside_folders = $this->getFileModel()->getAllSourcesInsideFolders($files);
        $sources_inside_folders = filesApp::toIntArray($sources_inside_folders);

        // ignore own sources
        foreach ($sources_inside_folders as $i => $source_id) {
            if (in_array($source_id, $source_of_folders) === false && !isset($sources_to_unmount[$source_id])) {
                $source = filesSource::factory($source_id);
                if (!$source->isApp() && !$source->isNull()) {
                    $sources_to_unmount[$source->getId()] = $source;
                }
            }

        }

        // unmount
        foreach ($sources_to_unmount as $source) {
            $source->delete();
        }
    }

    public function moveToTrash($move_to_trash_files)
    {
        $this->unmountInnerSources($move_to_trash_files);
        $this->getFileModel()->moveToTrash($move_to_trash_files);
        foreach ($move_to_trash_files as $id) {
            /**
             * Extend delete process
             * Make extra workup
             * @event move_to_trash
             */
            wa()->event('file_move_to_trash', $id);
        }
    }

    public function delete($delete_files, $options = array())
    {
        $this->getFileModel()->delete($delete_files, $options);
        foreach ($delete_files as $id) {
            /**
             * Extend delete process
             * Make extra workup
             * @event file_delete
             */
            wa()->event('file_delete', $id);
        }
    }

    protected function separate($files)
    {
        $app_files = array();
        $external_files = array();
        foreach ($files as $file) {
            if ($file['source_id'] > 0) {
                $external_files[$file['id']] = $file;
            } else {
                $app_files[$file['id']] = $file;
            }
        }
        return array('app_files' => $app_files, 'external_files' => $external_files);
    }

    protected function deleteAppFiles($files)
    {
        $file_ids = array_keys($files);
        if (!$file_ids) {
            return array();
        }
        if (!$this->isPermanently()) {
            $this->moveToTrash($file_ids);
        } else {
            $threshold = 250;
            $head = array_slice($file_ids, 0, $threshold);
            $tail = array_slice($file_ids, $threshold);
            $this->delete($head);
            if ($tail) {
                $this->delete($tail, array('is_async' => true));
            }
        }
        return $file_ids;
    }

    protected function deleteExternalFiles($files)
    {
        $file_ids = array_keys($files);
        if (!$file_ids) {
            return array();
        }
        $this->unmountInnerSources($file_ids);

        $elapsed_time = time() - $this->start_time;
        $rest_time = $this->max_execution_time - $elapsed_time;
        $max_time_per_one_file = 10;    // because of execution remote call
        $threshold = intval($rest_time / $max_time_per_one_file);

        $head = array_slice($file_ids, 0, $threshold);
        $tail = array_slice($file_ids, $threshold);

        $this->delete($head);

        if ($tail) {
            $this->delete($tail, array('is_async' => true));
        }

        return $file_ids;
    }
}

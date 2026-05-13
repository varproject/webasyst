<?php

class filesFolderDownloadController extends filesController
{
    public function execute()
    {
        $folder = $this->getFolder();
        $this->sendFolder($folder);
    }

    public function sendFolder($folder)
    {
        $fm = $this->getFileModel();
        $items = $fm->getChildrenItems($folder['id']);

        $temp_path = tempnam(wa()->getTempPath(), 'tmp_files_folder_' . $folder['id']);

        $archive = new ZipArchive();
        $archive->open($temp_path, ZipArchive::CREATE);

        $tree_paths = array(
            $folder['id'] => ''
        );

        $added_count = 0;

        foreach ($items as $item) {

            $parent_path = ifset($tree_paths[$item['parent_id']], '');

            if ($item['type'] === filesFileModel::TYPE_FOLDER) {
                $tree_paths[$item['id']] = trim($parent_path . '/' . $item['name'], '/');
                $archive->addEmptyDir($tree_paths[$item['id']]);
                continue;
            }

            if ($item['source_id'] == 0) {
                $filepath = $fm->getFilePath($item);
            } else {
                try {
                    $source = filesSource::factory($item['source_id'],
                        array(
                            'token_invalid_throw_exception' => true,
                            'in_pause_throw_exception' => true
                        )
                    );
                    $filepath = tempnam(wa()->getTempPath(), 'tmp_files_file_' . $item['id']);
                    $source->download($item, filesSource::DOWNLOAD_FILEPATH, array(
                        'filepath' => $filepath
                    ));
                } catch (Exception $e) {
                    // just ignore
                    continue;
                }
            }
            $archive->addFile($filepath, trim($parent_path . '/' . $item['name'], '/'));
            $added_count += 1;
        }

        $archive->close();

        if ($added_count <= 0) {
            die(_w('There are no files available to pack'));
        } else {
            waFiles::readFile($temp_path, $folder['name'].'.zip', false);
            unlink($temp_path);
        }

        exit;
    }

    public function getFolder()
    {
        $id = wa()->getRequest()->get('id', null, waRequest::TYPE_INT);
        $folder = $this->getFileModel()->getFolder($id);
        if (!$folder) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        if ($folder['in_copy_process']) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        if ($folder['count'] > $this->getConfig()->getMaxFilesDownloadInArchive()) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        $storage = $this->getStorageModel()->getStorage($folder['storage_id']);
        if (!$storage) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        return $folder;
    }
}

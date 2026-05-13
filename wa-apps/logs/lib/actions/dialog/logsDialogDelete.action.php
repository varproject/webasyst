<?php

class logsDialogDeleteAction extends waViewAction
{
    protected $directories = [];

    public function execute()
    {
        try {
            $this->executeHelper();
        } catch (Throwable $exception) {
            $this->view->assign('error', $exception->getMessage());
        }

        $this->setTemplate('DialogDelete.html');
    }

    protected function executeHelper()
    {
        if (!$this->getRights('delete_files')) {
            throw new Exception(_w('Insufficient access rights to delete log files or directories.'));
        }

        $path = waRequest::post('path', '');

        if (!strlen($path)) {
            throw new logsInvalidDataException();
        }

        if (!logsItemFile::check($path)) {
            throw new logsInvalidDataException();
        }

        $is_dir = is_dir(logsHelper::getFullPath($path));
        $dialog_title = $is_dir ? _w('Delete directory') : _w('Delete file');

        $paths = $this->getPathsWithDirectoryFiles([$path]);
        $items = $this->getFormattedItems($paths);

        $warnings = [];

        if ($is_dir) {
            $directory_contains_files = count($items) > 1;

            if ($directory_contains_files) {
                $this->addWarningDirectoryFiles($warnings);
                $this->addWarningDirectoryFilesPublished($warnings, $items);
                $this->addWarningDirectoryFilesTracked($warnings, $items);
            }
        } else {
            $this->addWarningFilePublished($warnings, reset($items));
            $this->addWarningFileTracked($warnings, reset($items));
        }

        $this->view->assign('title', $dialog_title);
        $this->view->assign('warnings', $warnings);
        $this->view->assign('items', $items);
        $this->view->assign('selected_paths', [$path]);
        $this->view->assign('paths', $paths);
        $this->view->assign('show_bulk_delete_promo', logsHelper::mustDisplayPremiumPromo('bulk-delete'));
    }

    protected function getPathsWithDirectoryFiles(array $paths): array
    {
        $files = array_reduce($paths, function ($result, $path) {
            array_push($result, $path);

            $full_path = logsHelper::getFullPath($path);

            if (is_dir($full_path)) {
                $this->directories[] = $path;

                $result = array_merge(
                    $result,
                    array_map(
                        function ($file_path) use ($path) {
                            return $path . '/' . $file_path;
                        },
                        logsHelper::listDir($full_path, true)
                    )
                );
            }

            return $result;
        }, []);

        sort($files);

        return $files;
    }

    protected function getFormattedItems(array $items): array
    {
        $formatted_items = array_map(function ($path) use ($items) {
            static $published;
            static $tracked;

            if (is_null($published)) {
                $published = (new logsPublishedModel())
                    ->select('path')
                    ->where('path IN (s:paths)', [
                        'paths' => $items,
                    ])
                    ->fetchAll(null, true) ?: [];
            }

            if (is_null($tracked)) {
                $tracked = (new logsTrackedModel())
                    ->select('DISTINCT path')
                    ->where('path IN (s:paths)', [
                        'paths' => $items,
                    ])
                    ->fetchAll(null, true) ?: [];
            }

            $item = [
                'path' => $path,
                'published' => in_array($path, $published),
                'tracked' => in_array($path, $tracked),
                'dir' => in_array($path, $this->directories),
            ];

            return $item;
        }, $items);

        return $formatted_items;
    }

    private function addWarningDirectoryFiles(array &$warnings): void
    {
        $warnings[] = _w('The files contained in this directory are listed here and will be deleted, too.');
    }

    private function addWarningDirectoryFilesPublished(&$warnings, $items): void
    {
        $published_files_selected = array_reduce($items, function ($result, $item) {
            return $result || !empty($item['published']);
        }, false);

        if ($published_files_selected) {
            $warnings[] = sprintf_wp(
                'Some of the files to be deleted have %s&nbsp;<em>published links</em> enabled; they will no longer work after the files are deleted.',
                '<i class="fas fa-globe"></i>'
            );
        }
    }

    private function addWarningDirectoryFilesTracked(array &$warnings, array $items): void
    {
        $published_files_selected = array_reduce($items, function ($result, $item) {
            return $result || !empty($item['tracked']);
        }, false);

        if ($published_files_selected) {
            $warnings[] = sprintf_wp(
                'Some of the files to be deleted are marked as %s&nbsp;<em>tracked</em>. Users will continue to receive notifications on such files’ updates until they disable the corresponding option once the files are created again.',
                '<i class="fas fa-flag"></i>'
            );
        }
    }

    private function addWarningFilePublished(array &$warnings, array $file): void
    {
        $is_published_file = !empty($file['published']);

        if ($is_published_file) {
            $warnings[] = sprintf_wp(
                'This file has a %s&nbsp;<em>published link</em> enabled. It will no longer work after the file is deleted.',
                '<i class="fas fa-globe"></i>'
            );
        }
    }

    private function addWarningFileTracked(array &$warnings, array $file): void
    {
        $is_tracked_file = !empty($file['tracked']);

        if ($is_tracked_file) {
            $warnings[] = sprintf_wp(
                'This file is marked as %s&nbsp;<em>tracked</em>. Users will continue to receive notifications on its updates until they disable the corresponding option once the file is created again.',
                '<i class="fas fa-flag"></i>'
            );
        }
    }
}

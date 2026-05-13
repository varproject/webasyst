<?php

class logsDialogDeleteBulkAction extends logsDialogDeleteAction
{
    protected function executeHelper()
    {
        try {
            if (!logsLicensing::check()->hasPremiumLicense()) {
                throw new Exception(_w('The Logs+ license is required for bulk deletion.'));
            }

            if (!$this->getRights('delete_files')) {
                throw new Exception(_w('Insufficient access rights to delete log files or directories.'));
            }

            $post_paths = waRequest::post('paths', [], waRequest::TYPE_ARRAY_TRIM);

            if (!$post_paths) {
                throw new Exception(_w('Nothing is selected.'));
            }

            $allowed_post_paths = logsHelper::getAllowedPaths($post_paths);

            if (!$allowed_post_paths) {
                throw new Exception(_w('No allowed files or directories are selected. Please reload this page and try again.'));
            }

            $paths = $this->getPathsWithDirectoryFiles($allowed_post_paths);
            $items = $this->getFormattedItems($paths);

            $warnings = [];
            $this->addWarningDirectories($warnings, $allowed_post_paths, $paths);
            $this->addWarningPublishedFiles($warnings, $items);
            $this->addWarningTrackedFiles($warnings, $items);

            $this->view->assign('title', _w('Delete selected'));
            $this->view->assign('warnings', $warnings);
            $this->view->assign('items', $items);
            $this->view->assign('selected_paths', $post_paths);
            $this->view->assign('paths', $paths);
        } catch (Throwable $exception) {
            $this->view->assign('error', $exception->getMessage());
        }
    }

    private function addWarningDirectories(&$warnings, $items, $files)
    {
        if (count($files) != count($items)) {
            $warnings[] = sprintf_wp(
                'Selected items include some %s&nbsp;<em>directories</em>; the files contained in them are listed here and will be deleted, too.',
                '<i class="fas fa-folder"></i>'
            );
        }
    }

    private function addWarningPublishedFiles(&$warnings, $items)
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

        return $warnings;
    }

    private function addWarningTrackedFiles(&$warnings, $items)
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

        return $warnings;
    }
}

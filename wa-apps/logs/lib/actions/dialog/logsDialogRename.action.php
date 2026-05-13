<?php

class logsDialogRenameAction extends waViewAction
{
    public function execute()
    {
        try {
            $path = waRequest::get('path', '');

            if (!$this->getUser()->getRights($this->getAppId(), 'rename')) {
                throw new Exception(_w('You have no access rights to rename files and directories.'));
            }

            $full_path = logsHelper::getFullPath($path);

            if (!logsItemFile::check($full_path)) {
                throw new logsInvalidDataException();
            }

            $is_dir = is_dir($full_path);
            $dialog_title = $is_dir ? _w('Rename directory') : _w('Rename file');
            $item = logsHelper::getPathParts($path) + [
                'edit_name' => basename($path),
            ];
            $warnings = [];

            if ($is_dir) {
                $dir_files = $this->getDirFiles($path);

                if ($dir_files) {
                    $this->addDirWarningFilesPublished($warnings, $dir_files);
                    $this->addDirWarningFilesTracked($warnings, $dir_files);
                }
            } else {
                $this->addFileWarningPublished($warnings, $path);
                $this->addFileWarningTracked($warnings, $path);
            }

            $item['warnings'] = $warnings;

            $this->view->assign('path', $path);
            $this->view->assign('item', $item);
            $this->view->assign('title', $dialog_title);
        } catch (Throwable $exception) {
            $this-> view->assign('error', $exception->getMessage());
        }
    }

    private function getDirFiles($dir_path)
    {
        $file_paths = logsHelper::listDir(logsHelper::getFullPath($dir_path), true);

        array_walk($file_paths, function(&$file_path) use ($dir_path) {
            $file_path = $dir_path . '/' . logsHelper::normalizePath($file_path);
        });

        return $file_paths;
    }

    private function addDirWarningFilesPublished(&$warnings, $files)
    {
        $published_files_count = (new logsPublishedModel())->countByField([
            'path' => $files,
        ]);

        if ($published_files_count) {
            $warnings[] = _w(
                '%u published file in this directory will no longer be available via a published link.',
                '%u published files in this directory will no longer be available via published links.',
                $published_files_count
            );
        }
    }

    private function addDirWarningFilesTracked(&$warnings, $files)
    {
        $tracked_files_entries = (new logsTrackedModel())
            ->select('path, contact_id')
            ->where('path IN (s:paths)', [
                'paths' => $files,
            ])
            ->fetchAll();


        if (!$tracked_files_entries) {
            return;
        }

        $tracked = array_reduce($tracked_files_entries, function($result, $entry) {
            $result['paths'] = array_merge(
                ifset($result['paths'], []),
                [$entry['path']]
            );

            $result['user_ids'] = array_merge(
                ifset($result['user_ids'], []),
                [$entry['contact_id']]
            );

            return $result;
        }, []);

        if (in_array($this->getUserId(), $tracked['user_ids'])) {
            if (count($tracked['user_ids']) > 1) {
                $warnings[] = _w(
                    'You and other users will no longer be notified on %u file’s updates in this directory.',
                    'You and other users will no longer be notified on %u files’ updates in this directory.',
                    count($tracked['paths'])
                );
            } else {
                $warnings[] = _w(
                    'You will no longer be notified on %u file’s updates in this directory.',
                    'You will no longer be notified on %u files’ updates in this directory.',
                    count($tracked['paths'])
                );
            }
        } else {
            $warnings[] = _w(
                'Other users will no longer be notified on %u file’s updates in this directory.',
                'Other users will no longer be notified on %u files’ updates in this directory.',
                count($tracked['paths'])
            );
        }
    }

    private function addFileWarningPublished(&$warnings, $path)
    {
        $is_published_file = (new logsPublishedModel())->countByField(array(
            'path' => $path,
        )) > 0;

        if ($is_published_file) {
            $warnings[] = sprintf_wp(
                'This file has a %s&nbsp;<em>published link</em> enabled. It will no longer work after the file is deleted.',
                '<i class="fas fa-globe"></i>'
            );
        }
    }

    private function addFileWarningTracked(&$warnings, $path)
    {
        $tracked_user_ids = (new logsTrackedModel())
            ->select('contact_id')
            ->where('path = ?', $path)
            ->fetchAll(null, true);

        if ($tracked_user_ids) {
            if (in_array($this->getUserId(), $tracked_user_ids)) {
                if (count($tracked_user_ids) > 1) {
                    $warnings[] = sprintf_wp('You and other users will be further notified on this file’s updates <em>by its old name</em> until the “%” option is disabled.', _w('Track changes'));
                } else {
                    $warnings[] = sprintf_wp('You will be further notified on this file’s updates <em>by its old name</em> until the “%” option is disabled.', _w('Track changes'));
                }
            } else {
                $warnings[] = sprintf_wp('Other users will be further notified on this file’s updates <em>by its old name</em> until the “%” option is disabled.', _w('Track changes'));
            }
        }
    }
}

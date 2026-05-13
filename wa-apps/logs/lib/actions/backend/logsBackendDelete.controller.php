<?php

class logsBackendDeleteController extends waJsonController
{
    public function execute()
    {
        try {
            if (!$this->getRights('delete_files')) {
                throw new Exception(_w('Insufficient access rights to delete log files or directories.'));
            }

            $paths = $this->getPostPaths('paths');

            if (!$paths) {
                throw new logsInvalidDataException();
            }

            $selected_paths = $this->getPostPaths('selected_paths');

            if (count($selected_paths) > 1 && !logsLicensing::check()->hasPremiumLicense()) {
                throw new Exception(_w('The Logs+ license is required for bulk deletion.'));
            }

            $paths = logsHelper::getAllowedPaths($paths);

            if (!$paths) {
                throw new logsInvalidDataException();
            }

            $items = $this->getFormattedItems($paths);
            $delete_result_paths = $this->delete($items);

            if ($delete_result_paths['yes']) {
                $files = array_filter($items, function ($item) {
                    return is_file($item['full_path']);
                });

                $deleted_file_paths = array_intersect(
                    $delete_result_paths['yes'],
                    array_column($files, 'path')
                );

                if ($deleted_file_paths) {
                    (new logsPublishedModel())->deleteByField([
                        'path' => $deleted_file_paths,
                    ]);

                    array_walk($deleted_file_paths, function ($path) {
                        $this->logAction('file_delete', $path);
                    });

                    $total_size = logsHelper::getTotalLogsSize();
                    $is_large = logsHelper::isLargeSize($total_size);

                    if (!$is_large) {
                        logsHelper::hideCountBadge();
                    }

                    $this->response['total_size'] = logsHelper::formatSize($total_size);
                    $this->response['total_size_class'] = 'total-size' . ($is_large ? ' total-size-large' : '');
                    $this->response['is_large'] = $is_large;
                }

                $this->response['deleted_paths'] = array_intersect(
                    $paths,
                    $delete_result_paths['yes']
                );
            }

            if ($delete_result_paths['no']) {
                $this->response['message'] = logsHelper::getIconHtml('fas fa-exclamation-triangle')
                    . ' ' . _w('Failed to delete the items marked below. Check server permissions. Or reload this page and try again.');
                $this->response['error_paths'] = $delete_result_paths['no'];
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    protected function getFormattedItems(array $paths): array
    {
        return array_map(function ($path) {
            $full_path = logsHelper::getFullPath($path);

            return [
                'path' => $path,
                'full_path' => $full_path,
                'dir' => is_dir($full_path),
            ];
        }, $paths);
    }

    protected function delete(array $items): array
    {
        return array_reduce($items, function ($result, $item) {
            try {
                if (file_exists($item['full_path'])) {
                    $deleted = waFiles::delete($item['full_path'], true);
                } else {
                    $deleted = true;
                }
            } catch (Throwable $throwable) {
                $deleted = false;
            }

            $key = $deleted ? 'yes' : 'no';
            $result[$key][] = $item['path'];

            return $result;
        }, [
            'yes' => [],
            'no' => [],
        ]);
    }

    private function getPostPaths($key): array
    {
        try {
            $paths = waUtils::jsonDecode(waRequest::post($key, '', waRequest::TYPE_STRING_TRIM));
        } catch (Throwable $throwable) {
        }

        return $paths ?? [];
    }
}

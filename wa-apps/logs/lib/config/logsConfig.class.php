<?php

class logsConfig extends waAppConfig
{
    public function explainLogs($logs)
    {
        foreach ($logs as $id => $log) {
            if (
                in_array($log['action'], ['file_delete', 'file_publish', 'file_unpublish'])
                && strlen(strval(ifset($log, 'params', '')))
            ) {
                $logs[$id]['params_html'] = 'wa-log/' . $log['params'];
            }
        }

        return $logs;
    }

    public function onCount()
    {
        $this->markUpdatedTrackedFiles();
        $updated_files_count = (new logsTrackedModel())->getUpdatedFilesCount();

        if ($updated_files_count) {
            return [
                'count' => $updated_files_count,
                'url' => logsHelper::getLogsBackendUrl() . '?action=files&mode=updatetime&from_count=1',
            ];
        }

        if ($this->notifyOnLargeLogs()) {
            return [
                'count' => _wd('logs', '1+ GB'),
                'url' => logsHelper::getLogsBackendUrl() . '?action=files&mode=size&from_count=1',
            ];
        }
    }

    public function markUpdatedTrackedFiles($user_ids = null)
    {
        if (is_array($user_ids) && !$user_ids) {
            return;
        }


        $user_ids = is_array($user_ids) ? $user_ids : (array) wa()->getUser()->getId();

        $tracked_model = new logsTrackedModel();

        $users_tracked_files = $tracked_model
            ->select('*')
            ->where('contact_id IN(i:user_ids)', [
                'user_ids' => $user_ids
            ])
            ->fetchAll('contact_id', 2);

        if (!$users_tracked_files) {
            return;
        }

        $insert_data = [];

        foreach ($users_tracked_files as $user_id => $user_tracked_files) {
            array_walk($user_tracked_files, function (&$file) use ($user_id) {
                $file['contact_id'] = $user_id;
            });

            $updated_files = $this->getUpdatedFilesData($user_tracked_files);
            $insert_data = array_merge($insert_data, $updated_files);
        }

        $tracked_model->multipleInsert($insert_data, array_keys(reset($insert_data)));
    }

    private function getUpdatedFilesData($files)
    {
        clearstatcache();

        array_walk($files, function(&$file) {
            $file_update_timestamp = (int) @filemtime(logsHelper::getFullPath($file['path']));
            $is_updated_file = $file_update_timestamp > strtotime($file['viewed_datetime']);
            $file['updated'] = (int) $is_updated_file;
        });

        return $files;
    }

    private function notifyOnLargeLogs()
    {
        $php_logging = new logsPhpLogging();

        $in_cloud = logsHelper::inCloud();
        $is_debug = waSystemConfig::isDebug();
        $php_logging_admin = $php_logging->adminConfigEnabled();
        $unlimited_logging_allowed = !$in_cloud || $php_logging_admin;

        $time_config_data = $php_logging->getConfigData(true);
        $php_logging_enabled = $php_logging->getSetting();
        $php_errors = $php_logging->getSetting('errors');

        //update PHP logging config on debug mode setting toggle
        if ($unlimited_logging_allowed && $php_logging_enabled && ($is_debug && $time_config_data || !$is_debug && !$time_config_data)) {
            $php_logging->setSetting(true, $php_errors);
        } elseif ($php_logging->isExpired()) {
            $php_logging->setSetting(false);
        }

        //notify user on large logs size
        $csm = new waContactSettingsModel();
        $large_logs_notify = $csm->getOne(wa()->getUser()->getId(), wa()->getApp(), 'large_logs_notify');
        $large_logs_notify = strlen(strval($large_logs_notify)) ? (bool) (int) $large_logs_notify : true;    //enabled by default

        if ($large_logs_notify) {
            $total_size = logsHelper::getTotalLogsSize();
            return logsHelper::isLargeSize($total_size);
        }

        return false;
    }
}

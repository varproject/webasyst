<?php

class filesSyncCli extends waCliController
{
    public function execute()
    {
        filesTasksPerformer::perform();

        $asm = new waAppSettingsModel();
        $asm->set('files', 'sync_cli_start', date('Y-m-d H:i:s'));
        /**
         * @event start_sync_tasks
         */
        wa('files')->event('start_sync_tasks');

        $max_execute_time = 60;
        if (ini_get('max_execution_time') > 0) {
            $max_execute_time = ini_get('max_execution_time');
        }

        $chunk_size = floor($max_execute_time / 0.3);

        for ($start = time(); time() - $start < $max_execute_time; /* here nothing */) {
            $res = filesSourceSyncController::runNextSyncTask(array(
                'chunk_size' => $chunk_size
            ));
            if ($res === null) {
                sleep(1);
            }
        }

        $asm->set('files', 'sync_cli_end', date('Y-m-d H:i:s'));
    }
}

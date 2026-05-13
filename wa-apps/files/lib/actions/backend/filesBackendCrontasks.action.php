<?php

class filesBackendCrontasksAction extends filesController
{
    public function execute()
    {
        $asm = new waAppSettingsModel();
        $this->assign(array(
            'copy_cli_start' => $asm->get('files', 'copy_cli_start'),
            'sync_cli_start' => $asm->get('files', 'sync_cli_start'),
            'copy_cron_command' => 'php '.wa()->getConfig()->getRootPath().'/cli.php files copy',
            'sync_cron_command' => 'php '.wa()->getConfig()->getRootPath().'/cli.php files sync',
        ));
    }
}
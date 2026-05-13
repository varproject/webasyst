<?php

class filesCopyCli extends waCliController
{
    public function execute()
    {
        $asm = new waAppSettingsModel();
        $asm->set('files', 'copy_cli_start', date('Y-m-d H:i:s'));

        filesTasksPerformer::perform();

        $config = $this->getConfig();
        filesCopytask::perform(
            5 * $config->getTasksPerRequest(),
            array(
                'max_execution_time' => $config->getMaxExecutionTime(600)
            )
        );

        $asm->set('files', 'copy_cli_end', date('Y-m-d H:i:s'));
    }

    /**
     * @return filesConfig
     */
    public function getConfig()
    {
        return wa('files')->getConfig();
    }
}

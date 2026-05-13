<?php

class logsRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('rename', _w('Renaming files and directories'), 'checkbox');
        $this->addItem('delete_files', _w('Deletion of files and directories'), 'checkbox');
        $this->addItem('publish_files', _w('Managing public links to files'), 'checkbox');
        if (function_exists('phpinfo')) {
            $this->addItem('view_phpinfo', _w('Viewing PHP configuration'), 'checkbox');
        }
        $this->addItem('publish_phpinfo', _w('Managing public link to PHP configuration viewing page'), 'checkbox');
        $this->addItem('change_settings', _w('Common settings management'), 'checkbox');
    }
}

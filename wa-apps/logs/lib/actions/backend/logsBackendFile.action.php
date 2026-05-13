<?php

class logsBackendFileAction extends logsBackendItemAction
{
    public function __construct()
    {
        $this->action = 'file';
        $this->id = 'path';
        parent::__construct();
    }

    public function execute()
    {
        parent::execute();
        $this->markTrackedFileAsNotUpdated();
        $this->getResponse()->setTitle($this->value);
    }

    private function markTrackedFileAsNotUpdated()
    {
        (new logsTrackedModel())->updateByField([
            'path' => $this->value,
            'contact_id' => $this->getUserId(),
        ], [
            'viewed_datetime' => date('Y-m-d H:i:s'),
            'updated' => 0,
        ]);

        logsHelper::updateUpdatedFilesBadgeValue();
    }

    protected function check()
    {
        return logsItemFile::check($this->value);
    }

    protected function getItem($params)
    {
        $item = new logsItemFile($this->value);
        return $item->get($params);
    }
}

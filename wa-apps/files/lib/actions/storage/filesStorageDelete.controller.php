<?php

class filesStorageDeleteController extends filesController
{
    public function execute()
    {
        $storage = $this->getFilesStorage();
        $this->delete($storage);
        $this->assign(array(
            'storage' => $storage
        ));
    }

    public function getFilesStorage() {
        $id = (int) $this->getRequest()->post('id');
        $storage = $this->getStorageModel()->getStorage($id);
        if (!$storage) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
        }
        $check_res = $this->checkStorageId($storage['id']);
        if ($check_res !== true) {
            filesApp::inst()->reportAboutError($check_res);
        }

        if (!filesRights::inst()->canDeleteStorage($storage)) {
            filesApp::inst()->reportAboutError($this->getAccessDeniedError());
        }

        return $storage;
    }

    public function delete($storage)
    {
        $source_id = (int) $storage['source_id'];

        // find all sources, because they need be unmount first
        $sources_inside = $this->getStorageModel()->getAllSourcesInsideStorage($storage['id']);

        // find own source_id, and drop it off. Use strict type
        $sources_inside = filesApp::toIntArray($sources_inside);
        $pos = array_search($source_id, $sources_inside, true);
        if ($pos !== false) {
            unset($sources_inside[$pos]);
        }

        // there are sources yet, so unmount them
        if ($sources_inside) {
            $sources = filesSource::factory($sources_inside);
            foreach ($sources as $source) {
                $source->delete();  // unmount
            }
        }

        $this->getStorageModel()->delete($storage['id']);
    }

}
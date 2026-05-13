<?php

class filesFolderFilesAction extends filesFilesAction
{
    protected $folder;

    public function filesExecute() {
        $folder = $this->getFolder();
        $storage = $this->getStorageModel()->getStorage($folder['storage_id']);
        $has_full_access_to_folder = filesRights::inst()->hasFullAccessToFile($folder['id']);
        $is_folder_shared = !empty($folder['hash']);
        if (!$is_folder_shared && $has_full_access_to_folder) {
            $share_rights = $this->getFileRightsModel()->getByField(array('file_id' => $folder['id']));
            $is_folder_shared = !empty($share_rights);
        }

        /**
         * Extend folder listing page
         * @event backend_folder
         *
         * @param array $params
         * @param array $params['folder']
         * @return array[string][string]string $return[%plugin_id%]['menu'] Top menu in folder listing
         */
        $params = array(
            'folder' => $folder
        );
        $backend_folder = wa()->event('backend_folder', $params);

        $source = filesSource::factory($folder['source_id']);
        if ($source->isOnDemand()) {
            $this->sync($source, $folder);
        }

        return array(
            'storage' => $storage,
            'folder' => $folder,
            'hash' => "folder/{$folder['id']}",
            'breadcrumbs' => $this->getFileModel()->getPathToFolder($folder['id']),
            'url' => $this->getUrl() . "&id={$folder['id']}",
            'can_rename' => $this->canRename($folder['id']),
            'can_delete' => $this->canDelete($folder['id']),
            'in_sync' => $this->getFileModel()->inSync($folder),
            'has_full_access_to_folder' => $has_full_access_to_folder,
            'is_folder_shared' => $is_folder_shared,
            'backend_folder' => $backend_folder
        );
    }

    public function getFolder() {
        if ($this->folder === null) {
            $id = (int)wa()->getRequest()->get('id', null, waRequest::TYPE_INT);
            $check_res = $this->checkFolderId($id);
            if ($check_res !== true) {
                filesApp::inst()->reportAboutError($check_res);
            }
            $fm = $this->getFileModel();
            $folder = $fm->getItem($id, false);
            $folder['frontend_url'] = $fm->getPrivateLink($folder, false);
            $folder['is_personal'] = filesRights::inst()->isFilesPersonal($folder);
            $this->folder = $folder;
        }
        return $this->folder;
    }

    private function canRename($folder_id)
    {
        $allowed = filesRights::inst()->dropUnallowedToMove(array($folder_id));
        return !!$allowed;
    }

    private function canDelete($folder_id)
    {
        $allowed = filesRights::inst()->dropUnallowedToMove(array($folder_id));
        return !!$allowed;
    }

    public function getSourceInfo()
    {
        $folder = $this->getFolder();
        $source = filesSource::factory($folder['source_id']);
        $icon = $source ? $source->getIconUrl() : '';
        if ($icon) {
            $icon = "<i class='icon16 plugins' style='background: url({$icon}) no-repeat; background-size: contain;'></i>";
        }
        return array(
            'id' => $folder['source_id'],
            'icon_html' => $icon,
            'access' => filesRights::inst()->hasAccessToSource($folder['source_id']),
            'name' => $source->getName(),
            'provider_name' => $source->getProviderName(),
            'has_valid_token' => $source->hasValidToken()
        );
    }

    public function isSourceRoot()
    {
        $folder = $this->getFolder();
        $source_id = abs((int) $folder['source_id']);
        $source = filesSource::factory($source_id);
        if ($source->isMounted()) {
            $info = $source->getInfo();
            $folder_id = (int) ifset($info['folder_id']);
            return $folder_id === (int) $folder['id'];
        }
        return false;
    }

    /**
     * @param filesSource $source
     * @param array $folder
     */
    protected function sync($source, $folder)
    {
        $source->syncData(array(
            'context' => array(
                'folder' => $folder
            )
        ));
        $source->pauseSync();
        $sync = new filesSourceSync($source);
        $sync->process();
        $source->unpauseSync();
    }
}

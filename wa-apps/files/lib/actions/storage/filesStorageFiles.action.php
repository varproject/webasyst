<?php

class filesStorageFilesAction extends filesFilesAction
{
    private $file_storage;
    private $storage_groups = array();

    public function filesExecute() {
        $storage = $this->getFilesStorage();
        $can_edit = filesRights::inst()->canEditStorage($storage);
        $can_delete = filesRights::inst()->canEditStorage($storage);

        /**
         * Extend storage listing page
         * @event backend_storage
         *
         * @param array $params
         * @param array $params['storage']
         * @return array[string][string]string $return[%plugin_id%]['menu'] Top menu in storage listing
         */
        $params = array(
            'storage' => $storage
        );
        $backend_storage = wa()->event('backend_storage', $params);

        $source = filesSource::factory($storage['source_id']);
        if ($source->isOnDemand()) {
            $this->sync($source, $storage);
        }

        return array(
            'storage' => $storage,
            'hash' => "storage/{$storage['id']}",
            'breadcrumbs' => $this->getBreadCrumbs($storage['id']),
            'url' => $this->getUrl() . "&id={$storage['id']}",
            'icons' => filesApp::inst()->getConfig()->getStorageIcons(),
            'levels' => filesApp::inst()->getRightConfig()->getRightLevels(),
            'all_groups' => filesRights::inst()->getAllGroups(),
            'admin_map' => array_fill_keys(array_keys(filesRights::inst()->getAllAdmins()), true),
            'users' => $this->getUsers($storage['id']),
            'groups' => $this->getGroups($storage['id']),
            'can_delete' => $can_delete,
            'can_edit' => $can_edit,
            'in_sync' => $this->getStorageModel()->inSync($storage),
            'backend_storage' => $backend_storage
        );
    }

    public function getFilesStorage() {
        if ($this->file_storage === null) {
            $id = (int)$this->getRequest()->get('id', null, waRequest::TYPE_INT);
            $storage = $this->getStorageModel()->getStorage($id);
            if (!$storage) {
                filesApp::inst()->reportAboutError($this->getPageNotFoundError());
            }
            $check_res = $this->checkStorageId($storage['id']);
            if ($check_res !== true) {
                filesApp::inst()->reportAboutError($check_res);
            }
            $persistent_id = $this->getStorageModel()->getPersistentStorageId();
            $storage['is_persistent'] = $persistent_id == $storage['id'];
            $storage['is_personal'] = filesRights::inst()->isStoragesPersonal($storage);
            $res = $this->getFileModel()->select('COUNT(*) children_count, MAX(update_datetime) max_children_update_datetime')
                ->where("storage_id = ? AND parent_id = 0", $storage['id'])->query()->fetchAssoc();
            $storage = array_merge($storage, $res);
            $this->file_storage = $storage;
        }
        return $this->file_storage;
    }

    public function getUsers($id)
    {
        $users = array();
        foreach ($this->getStorageGroups($id) as $gid => $level)  {
            if ($gid <= 0) {
                $uid = -$gid;
                $contact = new waContact($uid);
                if ($contact->exists()) {
                    $name = $contact->getName();
                    $photo_url_20 = $contact->getPhoto(20);
                } else {
                    $name = sprintf(_w("User %s"), $uid);
                    $photo_url_20 = waContact::getPhotoUrl(0, null, 20);
                }
                $users[$uid] = array(
                    'id' => $uid,
                    'name' => $name,
                    'photo_url_20' => $photo_url_20,
                    'level' => $level
                );
            }
        }
        return $users;
    }

    public function getGroups($id)
    {
        $groups = array();
        foreach ($this->getStorageGroups($id) as $gid => $level)  {
            if ($gid > 0) {
                $groups[$gid] = $level;
            }

        }
        return $groups;
    }

    private function getBreadCrumbs($storage_id)
    {
        $storage = $this->getStorageModel()->getStorage($storage_id);
        $breadcrumbs = array($storage);
        return $breadcrumbs;
    }

    private function getStorageGroups($id)
    {
        if (!isset($this->storage_groups[$id])) {
            $this->storage_groups[$id] = $this->getStorageModel()->getGroups($id);
        }
        return $this->storage_groups[$id];
    }

    public function getSourceInfo()
    {
        $storage = $this->getFilesStorage();
        $source = filesSource::factory($storage['source_id']);
        $icon = $source ? $source->getIconUrl() : '';
        if ($icon) {
            $icon = "<i class='icon16 plugins' style='background: url({$icon}) no-repeat; background-size: contain;'></i>";
        }
        return array(
            'id' => $storage['source_id'],
            'name' => $source->getName(),
            'provider_name' => $source->getProviderName(),
            'icon_html' => $icon,
            'access' => filesRights::inst()->hasAccessToSource($storage['source_id']),
            'has_valid_token' => $source->hasValidToken()
        );
    }

    public function isSourceRoot()
    {
        $storage = $this->getFilesStorage();
        $source_id = abs((int) $storage['source_id']);
        $source = filesSource::factory($source_id);
        if ($source->isMounted()) {
            $info = $source->getInfo();
            $storage_id = (int) ifset($info['storage_id']);
            return $storage_id === (int) $storage['id'] && empty($info['folder_id']);
        }
        return false;
    }

    /**
     * @param filesSource $source
     * @param array $storage
     */
    protected function sync($source, $storage)
    {
        $source->syncData(array(
            'context' => array(
                'storage' => $storage
            )
        ));
        $source->pauseSync();
        $sync = new filesSourceSync($source);
        $sync->process();
        $source->unpauseSync();
    }

}

<?php

class filesBrowserActions extends waViewActions
{
    private $level;
    private $browser_id;

    public function __construct(waSystem $system = null) {

        parent::__construct($system);

        // get level
        $this->level = (int) wa()->getRequest()->request('level');
        if (!$this->level) {
            $this->level = filesRightConfig::RIGHT_LEVEL_READ;
        }
        $all_levels = filesApp::inst()->getConfig()->getRightConfig()->getRightLevels();
        if (!isset($all_levels[$this->level])) {
            $this->level = filesRightConfig::RIGHT_LEVEL_READ;
        }

        // get browser_id
        $this->browser_id = wa()->getRequest()->get('browser_id');
        if (!$this->browser_id) {
            $this->browser_id = uniqid('f-browser-');
        }
    }

    public function defaultAction()
    {
        $sm = new filesStorageModel();
        $storages = $sm->getAvailableStorages($this->level);
        if (empty($storages)) {
            filesApp::inst()->reportAboutError(array(
                'code' => 403,
                'msg' => _w('No available storages')
            ));
        }

        // selected location
        $location = array(
            'storage' => array('id' => 0),
            'folders' => array()
        );
        $selected = array('type' => '', 'id' => 0);

        $storage_id = (int) wa()->getRequest()->request('storage_id');
        $folder_id = (int) wa()->getRequest()->request('folder_id');
        if ($this->level === filesRightConfig::RIGHT_LEVEL_FULL && ($folder_id > 0 || $storage_id > 0)) {
            if ($folder_id) {
                $fm = new filesFileModel();
                $path = $fm->getPathToFolder($folder_id, false);
                $selected = array('type' => 'folder', 'id' => $folder_id);
            } else {
                $path = array($sm->getStorage($storage_id));
                $selected = array('type' => 'storage', 'id' => $storage_id);
            }

            $location = array(
                'storage' => $path[0],
                'folders' => array_slice($path, 1)
            );

            foreach ($location['folders'] as &$folder) {
                $folder['is_leaf'] = $folder['right_key'] - $folder['left_key'] == 1;
            }
            unset($folder);

        }

        // set on is_personal flags
        $personal_map = filesRights::inst()->isStoragesPersonal($storages);
        $storages = filesApp::margeWithBoolMap($storages, $personal_map, 'is_personal');

        $this->assign(array(
            'storages' => $storages,
            'location' => $location,
            'selected' => $selected
        ));
    }

    public function storageAction()
    {
        // get storage and check rights
        $storage_id = (int) wa()->getRequest()->get('id');
        $sm = new filesStorageModel();
        $storage = $sm->getStorage($storage_id);
        if (!$storage) {
            filesApp::inst()->reportAboutError(array(
                'code' => 404,
                'msg' => _w('Storage not found')
            ));
        }
        if (!filesRights::inst()->checkAccessLevelOfStorage(
            $storage,
            $this->level
        ))
        {
            filesApp::inst()->reportAboutError(array(
                'code' => 403,
                'msg' => _w('Access denied to storage')
            ));
        }

        // get first-level folders in this storage
        $folders = $this->getFolders("storage/{$storage['id']}");

        $this->assign(array(
            'storage' => $storage,
            'folders' => $folders
        ));
    }

    public function folderAction()
    {
        // get folder in check rights
        $fm = new filesFileModel();
        $sm = new filesStorageModel();

        $folder_id = (int) wa()->getRequest()->get('id');
        $folder = $fm->getFolder($folder_id);
        if (!$folder) {
            filesApp::inst()->reportAboutError(_w('Folder not found'));
        }

        $storage = $sm->getStorage($folder['storage_id']);
        if (!$storage) {
            filesApp::inst()->reportAboutError(_w('Storage not found'));
        }

        $rights = filesRights::inst();

        $folder_allows = $rights->checkAccessLevelOfFile($folder, $this->level);
        $storage_allows = $rights->checkAccessLevelOfStorage($storage, $this->level);
        $access = $folder_allows || $storage_allows;

        if (!$access) {
            filesApp::inst()->reportAboutError(_w('Access denied to storage'));
        }

        // get child-folders of current folder
        $folders = $this->getFolders("folder/{$folder['id']}");
        $this->assign(array(
            'folder' => $folder,
            'folders' => $folders,
        ));
    }

    public function assign($assign = array())
    {
        $assign['browser_id'] = $this->browser_id;
        $assign['level'] = $this->level;
        $this->view->assign($assign);
    }

    public function getFolders($hash)
    {
        $col = new filesCollection($hash, array(
            'filter' => array(
                'type' => filesFileModel::TYPE_FOLDER
            )
        ));
        $total_count = $col->count();
        $folders = $col->getItems('*', 0, $total_count);
        if (!$folders) {
            return array();
        }

        // set on is_personal flags
        $personal_map = filesRights::inst()->isFilesPersonal($folders);
        $folders = filesApp::margeWithBoolMap($folders, $personal_map, 'is_personal');

        return $folders;
    }

}
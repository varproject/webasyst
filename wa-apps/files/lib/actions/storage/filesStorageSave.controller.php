<?php

class filesStorageSaveController extends filesController
{
    private $storage_id;

    public function execute()
    {
        if (!filesRights::inst()->canCreateStorage()) {
            $this->reportAboutError($this->getAccessDeniedError());
        }

        $data = $this->getData();
        $errors = $this->validate($data);
        if (!empty($errors)) {
            $this->errors = $errors;
            return;
        }
        $this->response['storage'] = $this->save($data);
    }

    public function save($data)
    {
        $id = $this->getStorageId();
        $m = $this->getStorageModel();
        if (!$id) {
            $id = $m->add($data);
            if (!$id) {
                return false;
            }
            $this->logAction('storage_create', $id);
        } else {
            $m->update($id, $data);
        }

        if ($this->whenSaveAccess()) {
            $storage = $m->getStorage($id, true);
            if ($storage['access_type'] === filesStorageModel::ACCESS_TYPE_LIMITED) {
                $m->setGroups($id, $data['groups']);
            } else {
                $m->delGroups($id);
            }
        }

        return $m->getById($id);
    }

    public function getStorageId()
    {
        if ($this->storage_id === null) {
            $id = (int)wa()->getRequest()->post('id');
            if ($id > 0 && !filesRights::inst()->hasFullAccessToStorage($id)) {
                $this->reportAboutError($this->getAccessDeniedError());
            }
            $this->storage_id = $id;
        }
        return $this->storage_id;
    }

    public function validate(&$data)
    {
        $errors = array();

        if (!$this->whenSaveInfo()) {
            return $errors;
        }

        if (strlen($data['name']) <= 0) {
            $errors[] = array(
                'name' => 'name',
                'msg' => _w('This field is required')
            );
        }

        $val = $data['name'];
        $banned_symbols = $this->getFileModel()->getBannedSymbols();
        if (preg_match($banned_symbols, $val)) {
            $errors[] = array(
                'name' => 'name',
                'msg' => _w('There are a forbidden symbols')
            );
        }

        if ($errors) {
            return $errors;
        }

        if (!$this->checkUnique($data['name'], $this->getStorageId())) {
            $errors[] = array(
                'name' => 'name',
                'msg' => _w('Not unique')
            );
        }

        return $errors;
    }

    public function getData()
    {
        $data = array();
        if ($this->whenSaveInfo()) {
            $data['name'] = $this->getName();
            $data['icon'] = $this->getIcon();
        }
        if ($this->whenSaveAccess()) {
            $data['access_type'] = $this->getAccessType();

            $levels = filesApp::inst()->getRightConfig()->getRightLevels(true);

            $groups = array();
            foreach ((array) wa()->getRequest()->post('level') as $group_id => $level) {
                $group_id = (int) $group_id;
                $level = (int) $level;
                $available = in_array($level, $levels);
                if (!$available || $level === filesRightConfig::RIGHT_LEVEL_NONE) {
                    continue;
                }
                $groups[$group_id] = $level;
            }
            $data['groups'] = $groups;

            // For limited user and limited access make full access level to this storage
            if (filesRights::inst()->hasLimitedAccess() && $data['access_type'] === filesStorageModel::ACCESS_TYPE_LIMITED) {
                $data['groups'][-$this->contact_id] = filesRightConfig::RIGHT_LEVEL_FULL;
            }

        }

        return $data;
    }

    public function checkUnique($name, $storage_id = null)
    {
        $m = $this->getStorageModel();
        return $m->isNameUnique($name, null, (array) $storage_id);
    }

    public function getName()
    {
        return $this->getRequest()->post('name', '', waRequest::TYPE_STRING_TRIM);
    }

    public function getIcon()
    {
        return $this->getRequest()->post('icon', null, waRequest::TYPE_STRING_TRIM);
    }

    public function getAccessType()
    {
        $access_type = $this->getRequest()->post('access_type', '', waRequest::TYPE_STRING_TRIM);
        if (!in_array($access_type, $this->getStorageModel()->getAllAccessTypes())) {
            $access_type = filesStorageModel::ACCESS_TYPE_LIMITED;
        }
        return $access_type;
    }

    public function getSaveType()
    {
        return $this->getRequest()->request('save_type') === 'access' ? 'access' : 'info';
    }

    public function whenSaveAccess()
    {
        return $this->getSaveType() === 'access' || !$this->getStorageId();
    }

    public function whenSaveInfo()
    {
        return $this->getSaveType() === 'info' || !$this->getStorageId();
    }
}

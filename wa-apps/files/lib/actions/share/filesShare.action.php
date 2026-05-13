<?php

class filesShareAction extends filesController
{
    public function execute()
    {
        $file = $this->getFile();

        $this->assign(array(
            'file' => $file,
            'levels' => $this->getLevels($file),
            'groups' => $this->getGroups($file),
            'users' => $this->getUsers($file),
            'setted_groups' => $this->getSettedGroups($file),
            'site_url' => $this->getConfig()->getRootUrl(true) . $this->getConfig()->getBackendUrl() . '/site/'
        ));
    }

    public function getSettedGroups($file)
    {
        $access_type = $file['storage']['access_type'];
        if ($access_type === filesStorageModel::ACCESS_TYPE_EVERYONE) {
            return array();
        }

        $all_groups = array();
        $all_groups += $file['groups'];
        $all_groups += filesApp::negativeKeys($file['users']);

        if ($access_type === filesStorageModel::ACCESS_TYPE_LIMITED) {
            $all_groups += $file['storage']['groups'];
            $all_groups += filesApp::negativeKeys($file['storage']['users']);
            $all_groups += filesRights::inst()->getAllAdmins();
        }

        if (isset($all_groups[-$this->contact_id])) {
            unset($all_groups[-$this->contact_id]);
        }

        return filesApp::getFieldValues($all_groups, 'name');
    }

    public function getLevels($file)
    {
        $levels = filesApp::inst()->getConfig()->getRightConfig()->getRightLevels();
        unset($levels[filesRightConfig::RIGHT_LEVEL_NONE]);
        if ($file['type'] === filesFileModel::TYPE_FILE) {
            unset($levels[filesRightConfig::RIGHT_LEVEL_ADD_FILES]);
        }
        return $levels;
    }

    public function getGroups($file)
    {
        $storage = $file['storage'];
        $access_type = $storage['access_type'];

        // nothing to do, because for everyone storages there isn't group settings
        if ($access_type === filesStorageModel::ACCESS_TYPE_EVERYONE) {
            return array();
        }

        // admin groups needed only when workuping limited storages
        $admin_groups = array();
        if ($access_type === filesStorageModel::ACCESS_TYPE_LIMITED) {
            $admin_groups = filesRights::inst()->getAllAdminGroups();
        }

        // default template stub for group info
        $tmpl_info = array(
            'checked' => false,
            'disabled' => false,
            'min_level' => filesRightConfig::RIGHT_LEVEL_NONE
        );

        // collect all groups info here
        $all_groups = array();

        // group workup
        foreach (filesRights::inst()->getAllGroups() as $group_id => $name) {

            // init group info for current group
            $info = array_merge($tmpl_info,
                array(
                    'id' => $group_id,
                    'name' => $name,
                    'level' => filesRightConfig::RIGHT_LEVEL_ADD_FILES
                ));

            // if current group set for file
            if (isset($file['groups'][$group_id])) {
                $info = array_merge($info, array(
                    'checked' => true,
                    'level' => $file['groups'][$group_id]['level']
                ));
            }

            // also take into account storage

            if ($access_type === filesStorageModel::ACCESS_TYPE_LIMITED) {

                if (isset($storage['groups'][$group_id])) {
                    $info = array_merge($info, array(
                        'checked' => true,
                        'disabled' => true,
                        'min_level' => $storage['groups'][$group_id]['level']
                    ));
                }

                // if limited storage look test if this group is admin group
                if (isset($admin_groups[$group_id])) {
                    $info = array_merge($info, array(
                        'checked' => true,
                        'disabled' => true,
                        'min_level' => filesRightConfig::RIGHT_LEVEL_FULL
                    ));
                }
            }

            $all_groups[$group_id] = $info;
        }

        return $all_groups;
    }

    public function getUsers($file)
    {
        $storage = $file['storage'];
        $access_type = $storage['access_type'];

        $users = array();
        foreach ($file['users'] as $user_id => $user) {
            $users[$user_id] = array_merge($user, array(
                'delete' => true,
                'min_level' => filesRightConfig::RIGHT_LEVEL_NONE
            ));
        }

        // adding users from storage, but with special flags
        if ($access_type === filesStorageModel::ACCESS_TYPE_LIMITED) {
            foreach ($storage['users'] as $user_id => $user) {
                $user['min_level'] = $user['level'];
                if (isset($users[$user_id])) {
                    $user['level'] = $users[$user_id]['level'];
                }
                $users[$user_id] = array_merge($user, array(
                    'delete' => false,
                ));
            }
        }

        return $users;

    }

    public function getFile()
    {
        $file = $this->getFileForShareModule(false);
        if (isset($file['users'][$this->contact_id])) {
            unset($file['users'][$this->contact_id]);
        }
        return $file;
    }
}
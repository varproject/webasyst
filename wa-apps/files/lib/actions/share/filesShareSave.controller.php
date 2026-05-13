<?php

class filesShareSaveController extends filesController
{

    public function execute()
    {
        $file = $this->getFileForShareModule();

        $data = $this->getShareData($file);
        if ($data) {
            $this->getFileRightsModel()->set($file['id'], $data);
        } else {
            $this->getFileRightsModel()->clean($file['id']);
        }

        $this->sendMessage($file, $data);

        $file = $this->getFileModel()->getItem($file['id'], false, true);
        $file['is_personal'] = filesRights::inst()->isFilesPersonal($file);

        // response
        $this->assign(array(
            'file' => $file
        ));
    }

    public function getShareData($file)
    {
        $storage = $file['storage'];
        $access_type = $storage['access_type'];

        $data = array();
        foreach ((array) wa()->getRequest()->post('level') as $group_id => $level) {
            $group_id = (int) $group_id;
            $level = (int) $level;

            // for personal and limit storages, ignore if level less then level of storage
            if ($access_type === filesStorageModel::ACCESS_TYPE_PERSONAL || $access_type === filesStorageModel::ACCESS_TYPE_LIMITED)
            {
                if (isset($storage['groups'][$group_id]) && $level <= $storage['groups'][$group_id]['level']) {
                    continue;
                }
                if (isset($storage['users'][-$group_id]) && $level <= $storage['users'][-$group_id]['level']) {
                    continue;
                }
            }

            $data[$group_id] = array(
                'group_id' => $group_id,
                'level' => $level
            );
        }

        return array_values($data);
    }

    protected function sendMessage($file, $share_data)
    {
        $contact_ids = $group_ids = array();
        if (is_array($share_data)) {
            foreach ($share_data as $d) {
                if ($d['group_id'] > 0) {
                    $group_ids[$d['group_id']] = $d['level'];
                } else {
                    $contact_ids[$d['group_id'] * -1] = $d['level'];
                }
            }
        }
        if ($group_ids) {
            $ugm = new waUserGroupsModel();
            $res = $ugm->getByField('group_id', array_keys($group_ids), true);
            foreach ($res as $r) {
                $contact_ids[$r['contact_id']] = max($group_ids[$r['group_id']], ifset($contact_ids[$r['contact_id']]));
            }
        }
        if (!$contact_ids) {
            return false;
        }
        unset($contact_ids[$this->contact_id]);

        $to = array();
        foreach ($contact_ids as $id => $l) {
            try {
                $c = new waContact($id);
                $name = $c->getName();
                $email = $c->get('email', 'default');
                if ($email) {
                    $to[$id] = array($email, $name, $c->getLocale());
                }
            } catch (waException $e) {
                // No such contact exists. Ignore.
            }
        }
        $message_model = $this->getMessageQueueModel();

        $original_locale = $this->getUser()->getLocale();
        foreach ($to as $id => $contact) {

            if ($original_locale != $contact[2]) {
                wa()->setLocale($contact[2]);
            }
            $all_levels = filesApp::inst()->getConfig()->getRightConfig()->getRightLevels();
            if (isset($all_levels[$contact_ids[$id]])) {
                $level_name = $all_levels[$contact_ids[$id]];
            } else {
                if ($contact_ids[$id] > 3) {
                    $level_name = $all_levels[255];
                } else {
                    $level_name = $all_levels[0];
                }
            }
            $vars = array(
                '{USER_NAME}' => htmlspecialchars($this->getUser()->getName()),
                '{RECIPIENT_NAME}' => htmlspecialchars($contact[1]),
                '{FILE_NAME}' => htmlspecialchars($file['name']),
                '{ACCESS_LEVEL}' => $level_name,
                '{FILE_URL}' => wa()->getRootUrl(true) . '' . wa()->getConfig()->getBackendUrl()
                . '/' . $this->getApp()
                . '/#/' . $file['type'] . '/' . $file['id'],
                '{ACCOUNT_NAME}' => wa()->accountName(),
                '{LOCALE}' => $contact[2]
            );
            $message_model->pushTemplate(array($contact[0] => $contact[1]), 'share', $vars);
        }
        wa()->setLocale($original_locale);

        return true;
    }

}
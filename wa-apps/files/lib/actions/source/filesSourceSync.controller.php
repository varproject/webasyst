<?php

class filesSourceSyncController extends filesController
{
    public function execute()
    {
        $this->getStorage()->close();

        $res = self::runNextSyncTask(array(
            'folder_id' => $this->getRequest()->post('folder_id'),
            'storage_id' => $this->getRequest()->post('storage_id')
        ));
        if ($res !== null && $res['result']['count'] > 0) {
            $this->assign(array(
                'source_id' => $res['source']->getId()
            ));
        }
    }

    public static function runNextSyncTask($options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }

        $fm = new filesFileModel();
        $stm = new filesStorageModel();
        $sm = new filesSourceModel();

        $timeout = filesApp::inst()->getConfig()->getSyncTimeout();

        $source_info = $sm->getOneSynchronized($timeout);
        if (empty($source_info)) {
            return null;
        }

        $id = $source_info['id'];
        $source = filesSource::factory($id);
        if (empty($source)) {
            return null;
        }

        $context = array();
        $apply_context = false;
        $context['folder_id'] = ifset($options['folder_id']);
        $context['storage_id'] = ifset($options['storage_id']);
        unset($options['folder_id'], $options['storage_id']);
        if ($context['folder_id'] > 0 || $context['storage_id'] > 0) {
            if ($context['folder_id'] > 0) {
                $folder = $fm->getById($context['folder_id']);
                if ($folder && $folder['source_id'] == $source->getId()) {
                    $apply_context = true;
                    $context = array(
                        'folder' => $folder
                    );
                }
            } else if ($context['storage_id'] > 0) {
                $storage = $stm->getById($context['storage_id']);
                if ($storage && $storage['source_id'] == $source->getId()) {
                    $apply_context = true;
                    $context = array(
                        'storage' => $storage
                    );
                }
            }
        }
        
        $sync = new filesSourceSync($source, $options);
        if ($apply_context) {
            $data = $source->syncData(array(
                'context' => $context
            ));
        } else {
            $data = $source->syncData();
        }

        $list = ifset($data['list'], array());
        if (!empty($list)) {
            $sync->append($list);
        }

        $res = array('count' => 0);
        $is_done = ifset($data['info']['done'], false);
        if ($is_done) {
            $source->pauseSync();
            $res = $sync->process();
            $source->unpauseSync();
        }

        return array(
            'source' => $source,
            'result' => $res
        );
    }
}

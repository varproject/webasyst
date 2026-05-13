<?php

class filesBackendChangesController extends filesController
{
    public function execute()
    {
        $this->getStorage()->close();
        $hash = $this->getHash();

        if (!$this->allowedExecute($hash)) {
            return;
        }

        $info = array();
        if ($this->getHashType($hash) === 'file') {
            $info = $this->getFileInfo($this->getHashId($hash));
        } else {
            try {
                $collection = new filesCollection($hash);
                $info = $collection->getPageInfoAggregates();
            } catch (waException $e) {
            }
        }

        $this->assign(array(
            'hash' => $hash,
            'info' => $info
        ));
    }

    public function getFileInfo($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }
        $info = null;
        $fm = $this->getFileModel();
        $info = $fm->select('update_datetime, count, size')->where('id = ?', $id)->fetchAssoc();
        return $info;
    }

    public function getHash()
    {
        return trim((string) $this->getRequest()->request('hash'), '/');
    }

    public function getHashType($hash)
    {
        $hash_ar = explode('/', $hash, 2);
        return $hash_ar[0];
    }

    public function getHashId($hash)
    {
        $hash_ar = explode('/', $hash, 2);
        return (int) ifset($hash_ar[1]);
    }

    public function allowedExecute($hash)
    {
        $hash_type = $this->getHashType($hash);
        foreach ($this->getConfig()->getHashesForChangeListener() as $hash) {
            if ($hash_type === $hash) {
                return true;
            }
        }
        return false;
    }
}


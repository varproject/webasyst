<?php

class filesSearchFilesAction extends filesFilesAction
{
    public function filesExecute()
    {
        $hash = $this->getHash();
        $query = $this->getQuery($hash);

        $res = array(
            'hash' => "search/{$hash}",
            'url' => $this->getUrl() . "&hash={$hash}",
            'query' => $query
        );

        if ($query) {
            $res['title'] = $query;
        }

        return $res;
    }

    public function getQuery($hash)
    {
        $hash = urldecode($hash);
        $hash_ar = filesCollection::parseSearchHash($hash);
        if (isset($hash_ar['name'])) {
            return $hash_ar['name']['val'];
        } else {
            return '';
        }
    }

    public function getHash()
    {
        return wa()->getRequest()->get('hash', '', waRequest::TYPE_STRING_TRIM);
    }

}

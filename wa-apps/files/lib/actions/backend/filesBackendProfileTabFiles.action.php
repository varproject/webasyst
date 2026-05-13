<?php

class filesBackendProfileTabFilesAction extends filesFilesAction
{
    public function filesExecute()
    {
        $contact_id = (int) wa()->getRequest()->get('id');

        $res = array(
            'app_url' => wa()->getAppUrl($this->app_id)
        );
        if ($contact_id > 0) {
            $res['collection_options'] = array(
                'filter' => array(
                    'contact_id' => $contact_id
                )
            );
        }
        return $res;
    }

    public function getLimit()
    {
        return 500;
    }

    public function getOffset()
    {
        return 0;
    }
}
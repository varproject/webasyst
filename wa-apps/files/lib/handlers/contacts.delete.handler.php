<?php

class filesContactsDeleteHandler extends waEventHandler
{
    /**
     * @param int[] $params Deleted contact_id
     * @see waEventHandler::execute()
     * @return void
     */
    public function execute(&$params)
    {
        $contact_ids = filesApp::toIntArray($params);

        // delete personal storages of that contacts with files (file_rights deleting inside)
        $sm = new filesStorageModel();
        $sm->deletePersonal($contact_ids);

        // delete favorites
        $favm = new filesFavoriteModel();
        $favm->deleteByField(array(
            'contact_id' => $contact_ids
        ));

        // delete personal filter of that contacts
        $fltm = new filesFilterModel();
        $fltm->deletePersonal($contact_ids);

        // delete rights records by creator ...
        $frm = new filesFileRightsModel();
        $frm->deleteByField(array(
            'creator_contact_id' => $contact_ids
        ));

        // ..and groups 
        $group_ids = filesApp::negativeValues($contact_ids);
        $frm->deleteByField(array(
            'group_id' => $group_ids
        ));

        // update to zero
        $models = array(
            new filesFileModel(),
            new filesStorageModel(),
            new filesFilterModel(),
            new filesSourceModel(),
            new filesFileCommentsModel()
        );
        foreach ($models as $m) {
            /**
             * @var filesModel $m
             */
            $m->updateByField(array(
                'contact_id' => $contact_ids
            ), array(
                'contact_id' => 0
            ));
        }

    }
}
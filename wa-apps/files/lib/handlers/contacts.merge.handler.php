<?php

/**
 * Update all tables when several contacts are merged into one.
 */
class filesContactsMergeHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $master_id = (int) $params['id'];
        $merge_ids = filesApp::toIntArray($params['contacts']);

        if (!$merge_ids) {
            return null;
        }

        $m = new waModel();

        //
        // All the simple cases: update contact_id in tables
        //
        foreach(array(
            array('files_file', 'contact_id'),
            array('files_file_comments', 'contact_id'),
            array('files_filter', 'contact_id'),
            array('files_storage', 'contact_id'),
            array('files_file_rights', 'creator_contact_id'),
            array('files_source', 'contact_id'), // also see below
        ) as $pair)
        {
            list($table, $field) = $pair;
            $sql = "UPDATE $table SET $field = :master WHERE $field in (:ids)";
            $m->exec($sql, array('master' => $master_id, 'ids' => $merge_ids));
        }


        // just simple deleting :)

        // todo: more complex and better way
        $frm = new filesFileRightsModel();
        $frm->deleteByField(array(
            'group_id' => filesApp::negativeValues($merge_ids)
        ));

        // todo: more complex and better way
        $favm = new filesFavoriteModel();
        $favm->deleteByField(array(
            'contact_id' => $merge_ids
        ));

        return null;
    }
}


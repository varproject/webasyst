<?php

class filesSourceSyncParamsModel extends filesModel
{
    protected $table = 'files_source_sync_params';


    public function clean()
    {
        $sql = "DELETE sp 
                  FROM `files_source_sync_params` sp
                  LEFT JOIN `files_source_sync` s ON sp.source_sync_id = s.id
                  WHERE s.id IS NULL";
        $this->exec($sql);
    }

}
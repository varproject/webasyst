<?php

class filesContactsLinksHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $result = array();
        $contacts = $params;
        if (is_array($contacts)) {
            $cs = array();
            foreach ($contacts as $v) {
                if ( ( $v = (int)$v)) {
                    $cs[] = $v;
                }
            }
            $contacts = $cs;
        } else {
            if ( ( $contacts = (int)$contacts)) {
                $contacts = array($contacts);
            }
        }

        if (!$contacts) {
            return null;
        }

        $m = new waModel();

        waLocale::loadByDomain('files');

        foreach(array(
            array('files_file', 'contact_id', 'Files'),
            array('files_storage', 'contact_id', 'Storages'),
            array('files_source', 'contact_id', 'Sources'),
        ) as $data) {
            list($table, $field, $role) = $data;
            $role = _wd('files', $role);
            $sql = "SELECT $field AS id, count(*) AS n
                    FROM $table
                    WHERE $field IN (".implode(',', $contacts).")
                    GROUP BY $field";
            foreach ($m->query($sql) as $row) {
                $result[$row['id']][] = array(
                    'role' => $role,
                    'links_number' => $row['n'],
                );
            }
        }
        return $result ? $result : null;
    }
}


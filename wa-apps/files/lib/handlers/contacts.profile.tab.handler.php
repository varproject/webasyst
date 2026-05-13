<?php

/**
 * Tabs with additional info in contact profile page.
 */
class filesContactsProfileTabHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $contact_id = (is_array($params) ? ifset($params, 'id', 0) : $params);
        $counter_inside = is_array($params) ? ifset($params, 'counter_inside', true) : waRequest::param('profile_tab_counter_inside', true);
        $total_count = $this->getCount($contact_id);
        if ($total_count <= 0) {
            return null;
        }

        return array(
            'html' => '',
            'url' => wa()->getAppUrl('files').'?module=backend&action=profileTabFiles&id='.$contact_id,
            'count' => (int) ($counter_inside ? 0 : $total_count),
            'title' => _wd('files', 'Files').($counter_inside && $total_count ? ' ('.$total_count.')' : ''),
        );
    }

    protected function getCount($contact_id)
    {
        // Calculate files count here rather than via collection
        // because collection tends to make a single heavy query for .5+ sec,
        // slowing down user profiles. So we optimize it by splitting into two.

        $result = 0;
        $model = new filesStorageModel();
        $storages = array_keys($model->getAvailableStorages());
        $storages = array_filter($storages, wa_lambda('$a', 'return $a > 0;'));

        // Count files in visible storages
        if ($storages) {
            $sql = "SELECT COUNT(*)
                        FROM files_file f
                    WHERE f.contact_id = ?
                        AND f.type = 'file'
                        AND f.source_id >= 0
                        AND f.storage_id IN (?)";
            $result += $model->query($sql, array(
                $contact_id, $storages,
            ))->fetchField();
        }

        // Count visible files in hidden storages
        $groups = filesRights::inst()->getGroupIds();
        if ($groups) {
            $storage_sql = '';
            if ($storages) {
                $storage_sql = "AND f.storage_id NOT IN (?)";
            }
            $sql = "SELECT COUNT(DISTINCT f.id)
                    FROM files_file f
                        JOIN files_file_rights ffr1
                            ON ffr1.file_id = f.id
                                AND ffr1.group_id IN(?)
                    WHERE f.contact_id = ?
                        AND f.type = 'file'
                        AND f.source_id >= 0
                        AND f.storage_id > 0
                        ".$storage_sql;
            $result += $model->query($sql, array(
                $groups, $contact_id, $storages,
            ))->fetchField();
        }

        return $result;
    }
}

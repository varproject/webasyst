<?php

class cabinetStatusModel extends waModel
{
    use CabinetModelPrefixFieldsTrait;

    protected $table = 'cabinet_status';

    public function getListWithGroup(array $filters = []): array
    {
        $csgm = new cabinetStatusGroupModel();
        $csgm_table = $csgm->getTableName();

        $sql = "
            SELECT
                s.*,
                " . $this->prefixFields('g', 'group', $csgm) . "
            FROM {$this->getTableName()} AS s
            JOIN {$csgm_table} AS g
                ON g.id = s.group_id
            WHERE 1
        ";

        $params = [];

        $sql .= " ORDER BY s.create_datetime DESC";

        return $this->query($sql, $params)->fetchAll();
    }


    public function getTree(): array
    {
        $rows = $this->getListWithGroup();

        $tree = [];

        foreach ($rows as $row) {

            $gid = $row['group_id'];

            // создаём группу, если нет
            if (!isset($tree[$gid])) {
                $tree[$gid] = [
                    'id'       => $gid,
                    'name'     => $row['group_name'],
                    'sort'     => $row['group_sort'],
                    'statuses' => []
                ];
            }

            // добавляем статус
            $tree[$gid]['statuses'][] = [
                'id'         => $row['id'],
                'name'       => $row['name'],
                'color'      => $row['color'],
                'sort'       => $row['sort'],
                'is_system'  => $row['is_system'],
                'created'    => $row['create_datetime']
            ];
        }

        // сортируем группы
        usort($tree, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });

        // сортируем статусы внутри групп
        foreach ($tree as &$g) {
            usort($g['statuses'], function ($a, $b) {
                return $a['sort'] <=> $b['sort'];
            });
        }

        return $tree;
    }
}

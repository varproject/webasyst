<?php

class filesNestedSetModel extends waNestedSetModel
{
    /**
     * @var waUser
     */
    protected $user;

    /**
     * @var int
     */
    protected $contact_id;

    /**
     * @var array
     */
    protected $banned_symbols = array('/', '\\', ':', '?', '*', '"', /*"'",*/ '|');

    /**
     * @var string
     */
    protected $app_id = 'files';

    public function __construct($type = null, $writable = false) {
        parent::__construct($type, $writable);
        $this->user = wa()->getUser();
        $this->contact_id = $this->user->getId();
    }

    public function setContactId($contact_id)
    {
        $this->contact_id = $contact_id;
    }

    public function getFirst($field = null)
    {
        if (!$field) {
            $q = $this->select('*')->limit(1);
        } else {
            $where = $this->getWhereByField($field);
            $q = $this->select('*')->where($where)->limit(1);
        }
        $res = $q->fetchAll();
        return reset($res);
    }

    protected function getParentChain($id, $conds = null, $with_item = false) {
        $item = is_array($id) ? $id : $this->getById($id);
        if (!$item) {
            return array();
        }

        $root = $item[$this->root];
        $where = "{$this->root} = {$root}";
        if ($conds !== null) {
            $where .= " AND " . $this->getWhereByField($conds);
        }

        $op = array('<', '>');
        if ($with_item) {
            $op = array('<=', '>=');
        }

        $sql = "SELECT * FROM {$this->table}
            WHERE {$where} AND
                ({$this->left} {$op[0]} {$item[$this->left]} AND
                    {$this->right} {$op[1]} {$item[$this->right]})
            ORDER BY {$this->left}";

        return $this->query($sql)->fetchAll($this->id);
    }

    protected function updateParentChain($id, $update_map, $conds = null, $with_item = false)
    {
        if (!is_array($id)) {
            $item = $this->getById((int) $id);
        } else {
            $item = $id;
        }

        if (!$item) {
            return false;
        }

        $root = $item[$this->root];
        $where = "{$this->root} = {$root}";
        if ($conds !== null) {
            $where .= " AND " . $this->getWhereByField($conds);
        }

        $set_sql = array();
        foreach ($update_map as $field => $val) {
            if ($this->fieldExists($field)) {
                $val = $this->escape($val);
                $set_sql[] = "`{$field}` = '{$val}'";
            }
        }
        if (!$set_sql) {
            return false;
        }
        $set_sql = join(',', $set_sql);

        $op = array('<', '>');
        if ($with_item) {
            $op = array('<=', '>=');
        }

        $sql = "UPDATE {$this->table}
            SET {$set_sql}
            WHERE {$where} AND
                (`{$this->left}` {$op[0]} '{$item[$this->left]}' AND
                    `{$this->right}` {$op[1]} '{$item[$this->right]}')";

        if (!$this->exec($sql)) {
            return false;
        }
        return true;
    }

    public function getEmptyRow()
    {
        $result = array();
        foreach ($this->getMetadata() as $fld_id => $fld) {
            if (isset($fld['default'])) {
                $result[$fld_id] = $fld['default'];
            } else {
                if (!isset($fld['null']) || $fld['null']) {
                    $result[$fld_id] = null;
                } else {
                    $result[$fld_id] = '';
                }
            }
        }
        return $result;
    }

    public function getBannedSymbols($regexp = true)
    {
        if (!$regexp) {
            return $this->banned_symbols;
        } else {
            $str = array();
            foreach ($this->banned_symbols as $symbol) {
                $str[] = preg_quote($symbol, '/');
            }
            return "/(" . join('|', $str) . ")/";
        }
    }

    protected function moveInOneTree($id, $parent_id = null, $conds = null)
    {
        $element = $this->getById($id);
        $parent = $this->getById($parent_id);
        $left = $element[$this->left];
        $right = $element[$this->right];

        $root_where = " AND ".$this->root." = ".(int)$element[$this->root];
        $where = '';
        if ($conds !== null) {
            $where = " AND " . $this->getWhereByField($conds);
        }

        if ($parent && $parent[$this->left] > $left && $parent[$this->right] < $right) {
            return false;
        }

        $this->updateById($id, array(
            $this->parent  => $parent ? $parent[$this->id] : 0
        ));

        $this->exec("
        UPDATE `{$this->table}`
        SET `{$this->depth}` = `{$this->depth}` + i:parent_depth - i:depth + 1
        WHERE
        `{$this->left}` BETWEEN i:left AND i:right".$root_where.$where,
                array(
                    'left' => $left,
                    'right' => $right,
                    'parent_depth' => $parent ? $parent[$this->depth] : -1,
                    'depth' => $element[$this->depth]
                )
        );

        $params = array(
            'left'  => $left,
            'right' => $right,
            'width' => $right - $left + 1
        );

        if (!$parent) { // move element to root level
            $sql = "SELECT MAX($this->right) max FROM {$this->table}";
            if ($this->root) {
                $sql .= " WHERE ".$this->root." = ".(int)$element[$this->root];
            }
            $params['step'] = $this->query($sql)->fetchField('max') - $right;

            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, -i:width)
                WHERE `{$this->left}` >= i:left".$root_where.$where, $params);
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, -i:width)
                WHERE `{$this->right}` >= i:left".$root_where.$where, $params);

            return true;
        }

        $parent_left = $parent[$this->left];
        $parent_right = $parent[$this->right];
        $params['parent_left'] = $parent_left;
        $params['parent_right'] = $parent_right;

        // right
        if ($right > $parent_right) {
            $params['step'] = $parent_right - $left;
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, i:width)
                WHERE
                `{$this->left}` >= i:parent_right AND `{$this->left}` <= i:right
            ".$root_where.$where, $params);
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, i:width)
                WHERE
                `{$this->right}` >= i:parent_right AND `{$this->right}` <= i:right
            ".$root_where.$where, $params);
        } // left
        else {
            $params['step'] = $parent_right - $right - 1;
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->left}` = `{$this->left}` + IF(`{$this->left}` BETWEEN i:left AND i:right, i:step, -i:width)
                WHERE
                `{$this->left}` >= i:left AND `{$this->left}` < i:parent_right
            ".$root_where.$where, $params);
            $this->exec("
                UPDATE `{$this->table}`
                SET `{$this->right}` = `{$this->right}` + IF(`{$this->right}` BETWEEN i:left AND i:right, i:step, -i:width)
                WHERE
                `{$this->right}` >= i:left AND `{$this->right}` < i:parent_right
            ".$root_where.$where, $params);
        }

        return true;
    }

    protected function moveToDifferentTree($id, $parent_id, $root_id, $conds = null)
    {
        $element = $this->getById($id);
        $parent = $this->getById($parent_id);
        $left = $element[$this->left];
        $right = $element[$this->right];
        $depth = $element[$this->depth];

        $outplace_root_where = " AND ".$this->root . " = " . $element[$this->root];
        $inplace_root_where = " AND ".$this->root . " = " . $root_id;
        $where = '';
        if ($conds !== null) {
            $where = " AND " . $this->getWhereByField($conds);
        }

        if ($parent && $parent[$this->left] > $left && $parent[$this->right] < $right) {
            return false;
        }

        if ($parent) {

            // move next elements' right to make room
            $this->exec("
            UPDATE `{$this->table}`
            SET `{$this->right}` = `{$this->right}` + i:shift
            WHERE `{$this->right}` >= i:right".$inplace_root_where.$where,
                array(
                    'right' => $parent[$this->right],
                    'shift' => $right - $left + 1
                ));

            // move next elements' left to make room
            $this->exec("
            UPDATE `{$this->table}`
            SET `{$this->left}` = `{$this->left}` + i:shift
            WHERE `{$this->left}` > i:left".$inplace_root_where.$where,
                array(
                    'left' => $parent[$this->right],
                    'shift' => $right - $left + 1
                ));

            $new_left = $parent[$this->right];
            $new_depth = $parent[$this->depth] + 1;

        } else {
            // calculate new left key in new tree
            $sql = "SELECT MAX($this->right) max FROM {$this->table}
                WHERE {$this->root} = i:root_id" . $where;
            $new_left = $this->query($sql, array('root_id' => $root_id))->fetchField('max') + 1;
            $new_depth = 0;
        }

        $this->updateById($element['id'], array(
            'parent_id' => $parent ? $parent['id'] : 0
        ));

        // move subtree to new tree
        $this->exec("
        UPDATE {$this->table}
        SET {$this->depth} = {$this->depth} - i:depth_shift,
            {$this->left} = {$this->left} - i:shift,
            {$this->right} = {$this->right} - i:shift,
            {$this->root} = i:root_id
        WHERE {$this->left} >= i:left AND {$this->right} <= i:right".$outplace_root_where.$where,
                array(
                    'left' => $left,
                    'right' => $right,
                    'shift' => $left - $new_left,
                    'depth_shift' => $depth - $new_depth,
                    'root_id' => $root_id,
                ));

        // update left branch in old tree (all keys - shift)
        $this->exec("
        UPDATE `{$this->table}`
        SET `{$this->right}` = `{$this->right}` - i:shift,
            `{$this->left}`  = `{$this->left}`  - i:shift
        WHERE `{$this->left}` > i:left AND `{$this->right}` > i:right".$outplace_root_where.$where,
                array(
                    'left' => $left,
                    'right' => $right,
                    'shift' => $right - $left + 1
                ));

        // update parent branch in old tree (right keys - shift)
        $this->exec("
        UPDATE `{$this->table}`
        SET `{$this->right}` = `{$this->right}` - i:shift
        WHERE `{$this->right}` > i:right AND `{$this->left}` < i:left".$outplace_root_where.$where,
                array(
                    'left' => $left,
                    'right' => $right,
                    'shift' => $right - $left + 1
                ));

        return true;
    }

    public function move($id, $parent_id = null, $root_id = null, $conds = null) {
        $element = $this->getById($id);
        $root_id = (int) $root_id;
        if (!$root_id) {
            $root_id = $element[$this->root];
        }
        if ($root_id == $element[$this->root]) {
            return $this->moveInOneTree($id, $parent_id, $conds);
        } else {
            return $this->moveToDifferentTree($id, (int) $parent_id, $root_id, $conds);
        }
    }

    public function getAffectedRows()
    {
        return $this->adapter->affected_rows();
    }

}
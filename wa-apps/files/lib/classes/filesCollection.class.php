<?php

class filesCollection
{
    protected $hash;
    protected $where = array();
    protected $joins;
    protected $prepared = false;
    protected $title = '';
    protected $options = array();
    protected $check_rights = true;
    protected $check_active_time = true;
    protected $count;
    protected $order_by = 'f.type DESC,f.name,f.id';
    protected $required_fields = array('f.id', 'f.name', 'f.type');
    protected $join_index = array();
    protected $info;

    /**
     * @var filesFileModel
     */
    protected $model;

    /**
     * @var waUser
     */
    protected $user;
    protected $contact_id;

    /**
     * Constructor for collections of photos
     *
     * @param string|array $hash
     * @param array $options
     */
    public function __construct($hash = '', $options = array())
    {
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
        $this->setHash($hash);

        if (!isset($options['contact_id'])) {
            $this->user = wa()->getUser();
            $this->contact_id = $this->user->getId();
        } else {
            $this->contact_id = (int) $options['contact_id'];
            $this->user = new waContact($this->contact_id);
        }

        $this->model = new filesFileModel();

        if (!empty($options['filter'])) {
            foreach ((array)$options['filter'] as $field => $val) {
                if ($this->model->fieldExists($field)) {
                    $this->addWhere("f.{$field}" . $this->getExpression('=', $val));
                }
            }
        }

        if (!empty($options['check_hash'])) {
            $this->addWhere("f.hash IS NOT NULL AND f.hash != ''");
        }

        $this->where['f.source_id >= 0'] = 'f.source_id >= 0';

        $this->check_rights = ifset($options['check_rights'], true);
        $this->check_active_time = ifset($options['check_active_time'], false);
    }

    protected function setHash($hash)
    {
        if (is_array($hash)) {
            $hash = '/id/' . implode(',', $hash);
        }
        if (substr($hash, 0, 1) == '#') {
            $hash = substr($hash, 1);
        }
        $this->hash = trim($hash, '/');
        if ($this->hash == 'all') {
            $this->hash = '';
        }
        $this->hash = explode('/', $this->hash, 2);
    }

    protected function prepare($add = false, $auto_title = true)
    {
        if (!$this->prepared || $add) {
            $type = $this->hash[0];

            if ($type) {
                $method = strtolower($type) . 'Prepare';
                if (method_exists($this, $method)) {
                    $this->$method(isset($this->hash[1]) ? $this->hash[1] : '', $auto_title);
                } else {
                    $params = array(
                        'collection' => $this,
                        'auto_title' => $auto_title,
                        'add' => $add,
                        'hash' => $this->hash,
                        'options' => $this->options
                    );
                    /**
                     * @event collection
                     * @param array[string]mixed $params
                     * @param array[string]filesCollection $params['collection']
                     * @param array[string]boolean $params['auto_title']
                     * @param array[string]boolean $params['add']
                     * @param array[string]array $params['options']
                     * @return bool null if ignored, true when something changed in the collection
                     */
                    $processed = wa()->event('collection', $params);
                    if (!$processed) {
                        throw new waException('Unknown collection hash type: ' . htmlspecialchars($type));
                    }
                }
            } elseif ($auto_title) {
                $this->allPrepare();
            }

            $params = array(
                'collection' => $this,
                'auto_title' => $auto_title,
                'add' => $add,
                'hash' => $this->hash,
                'options' => $this->options,
            );
            /**
             * @event extra_prepare_collection
             * @param array[string]mixed $params
             * @param array[string]filesCollection $params['collection']
             * @param array[string]boolean $params['auto_title']
             * @param array[string]boolean $params['add']
             * @param array[string]array $params['options']
             */
            wa('files')->event('extra_prepare_collection', $params);

            if ($this->prepared) {
                return;
            }
            $this->prepared = true;
        }
    }

    private function checkRights($negative_storage_ids = false)
    {
        $groups = filesRights::inst()->getGroupIds();
        $groups_str = "'" . join("','", $groups) . "'";

        $model = new filesStorageModel();

        $storages = array_keys($model->getAvailableStorages());
        if ($negative_storage_ids) {
            $storages = filesApp::negativeValues($storages);
        }
        $storages_str = "'" . join("','", $storages) . "'";

        $join = array(
            'type' => 'LEFT',
            'table' => 'files_file_rights',
            'on' => ":table.file_id = f.id AND :table.group_id IN({$groups_str})"
        );
        if ($storages) {
            $join['where'] = "f.storage_id IN({$storages_str}) OR :table.group_id IS NOT NULL";
        } else {
            $join['where'] = ":table.group_id IS NOT NULL";
        }
        $this->addJoin($join);
        if ($negative_storage_ids) {
            $this->addWhere("f.storage_id < 0");
        } else {
            $this->addWhere("f.storage_id > 0");
        }
    }

    public function allPrepare()
    {
        if ($this->check_rights) {
            $this->checkRights();
        }
        $type = filesFileModel::TYPE_FILE;
        $this->addWhere("f.type = '{$type}'");
        $this->addTitle(_w('All files'));
    }

    public function favoritePrepare()
    {
        if ($this->check_rights) {
            $this->checkRights();
        }
        $this->addJoin('files_favorite', ':table.file_id = f.id', ':table.contact_id = ' . $this->contact_id);
        $this->setTitle(_w('Favorites'));
    }

    public function trashPrepare($ids, $auto_title = true)
    {
        $this->addWhere('f.parent_id = 0');

        $type = $this->model->getTrashType();
        if ($type === filesFileModel::TRASH_TYPE_PERSONAL) {
            $sm = new filesStorageModel();
            $storages = $sm->getOwnStorages();
            $storage_ids = array_keys($storages);
            $storage_ids = filesApp::negativeValues($storage_ids);
            if (!$storage_ids) {
                $this->addWhere(0);
            } else {
                $this->addWhere("f.storage_id IN (" . join(',', $storage_ids) . ")");
            }
        } else {
            if ($this->check_rights) {
                $this->checkRights(true);
            }
            $this->addWhere('f.storage_id < 0');
        }

        $title = _w('Trash');

        $ids = trim($ids);
        if (strlen($ids) > 0) {
            $file_ids = filesApp::toIntArray(array_map('trim', explode(',', $ids)));
            $this->addWhere('f.id ' . $this->getExpression('@=', join(',', $file_ids)));
            $title .= ' : ' . join(',', $file_ids);
        }

        if ($auto_title) {
            $this->addTitle($title);
        }
    }

    public function storagePrepare($id, $auto_title = true)
    {
        $model = new filesStorageModel();
        $storage = $model->getStorage($id);
        if (!$storage) {
            $this->where[] = 0;
            return;
        }
        if ($this->check_rights) {
            if (!filesRights::inst()->canReadFilesInStorage($storage['id'])) {
                $this->where[] = 0;
                return;
            }
        }
        if ($auto_title) {
            $this->addTitle($storage['name']);
        }
        $this->addWhere("f.storage_id = '{$storage['id']}' AND f.parent_id = 0");
    }

    public function folderPrepare($id, $auto_title = true)
    {
        $folder = $this->model->getFolder($id);
        if (!$folder) {
            $this->where[] = 0;
            return;
        }
        if ($this->check_rights) {
            if (!filesRights::inst()->canReadFile($folder['id'])) {
                $this->where[] = 0;
                return;
            }
        }
        if ($folder['source_id'] < 0) {
            if (isset($this->where['f.source_id >= 0'])) {
                unset($this->where['f.source_id >= 0']);
            }
            $this->where['f.source_id < 0'] = 'f.source_id < 0';
        }

        if ($auto_title) {
            $this->addTitle($folder['name']);
        }
        $this->addWhere("f.parent_id = '{$folder['id']}'");

        $info = $this->getInfo();
        $info['folder'] = $folder;
        $this->setInfo($info);
    }

    public static function parseSearchHash($query)
    {
        $i = $offset = 0;
        $query_parts = array();
        while (($j = strpos($query, '&', $offset)) !== false) {
            // escaped &
            if ($query[$j - 1] != '\\') {
                $query_parts[] = str_replace('\&', '&', substr($query, $i, $j - $i));
                $i = $j + 1;
            }
            $offset = $j + 1;
        }
        $query_parts[] = str_replace('\&', '&', substr($query, $i));

        $hash = array();
        foreach ($query_parts as $query_part) {
            if (!($query_part = trim($query_part))) {
                continue;
            }
            $parts = preg_split("/(\\\$=|\^=|\*=|==|!=|>=|<=|=|>|<|@=)/uis", $query_part, 2, PREG_SPLIT_DELIM_CAPTURE);
            if (count($parts) < 3) {
                continue;
            }
            $hash[$parts[0]] = array(
                'op' => $parts[1],
                'val' => $parts[2]
            );
        }

        return $hash;
    }

    public function idPrepare($ids, $auto_title = true)
    {
        $this->listPrepare($ids, $auto_title);
    }

    public function listPrepare($ids, $auto_title = true)
    {
        if ($this->check_rights) {
            $this->checkRights();
        }
        $file_ids = filesApp::toIntArray(array_map('trim', explode(',', $ids)));
        if (!$file_ids) {
            $this->where[] = 0;
        } else {
            $this->addWhere('f.id ' . $this->getExpression('@=', join(',', $file_ids)));
        }
        if ($auto_title) {
            $this->addTitle(_w('Files IDs: ') . join(',', $file_ids));
        }
    }

    public function tagPrepare($tags, $auto_title = true)
    {
        if ($this->check_rights) {
            $this->checkRights();
        }
        $model = new filesTagModel();
        $tags = explode(',', $tags);
        $tag_ids = $model->getIds($tags);
        if (!$tag_ids) {
            $this->where[] = 0;
        } else {
            $this->addJoin(
                'files_file_tags', null, ":table.tag_id" . $this->getExpression('@=', join(',', $tag_ids))
            );
        }
        if ($auto_title) {
            $this->addTitle(count($tags) <= 1 ? $tags[0] : join(',', $tags));
        }
    }

    public function searchPrepare($query, $auto_title = true)
    {
        $query = urldecode($query);
        $hash = self::parseSearchHash($query);

        if ($this->check_rights) {
            $this->checkRights();
        }

        $title = array();
        foreach ($hash as $field => $val) {
            if ($this->model->fieldExists($field)) {
                if ($field === 'name' && strpos($val['val'], '*') !== false) {
                    $this->addWhere("f." . $field . " LIKE '" .
                        $this->model->escape(str_replace('*', '%', $val['val'])) .
                        "'");
                } else {
                    $this->addWhere("f." . $field . $this->getExpression($val['op'], $val['val']));
                }

                $field_str = $field;
                if ($field === 'name') {
                    $field_str = _w('Name');
                }

                $title[] = $field_str . $val['op'] . $val['val'];
            } else if ($field === 'file_type') {

                $file_types = filesApp::inst()->getConfig()->getFileTypes();
                $vals = explode(',', $val['val']);
                $exts = array();
                $subtitle = array(
                    'file_type' => array(
                        'name' => _w('File type'),
                        'vals' => array()
                    ),
                    'ext' => array(
                        'name' => _w('Extension'),
                        'vals' => array()
                    )
                );

                foreach ($vals as $ft) {
                    if (isset($file_types[$ft])) {
                        $exts = array_merge($exts, ifset($file_types[$ft]['ext'], array()));
                        $subtitle['file_type']['vals'][] = $file_types[$ft]['name'];
                    } else if (substr($ft, 0, 1) === '.') {
                        $exts[] = substr($ft, 1);
                        $subtitle['ext']['vals'][] = substr($ft, 1);
                    }
                }

                $this->addWhere("f.ext" . $this->getExpression("@=", join(',', array_unique($exts))));

                $subtitle_str = array();
                foreach ($subtitle as $key => $sbt_opts) {
                    if ($sbt_opts['vals']) {
                        $sbt_opts['name'] .= '=' .
                            (count($sbt_opts['vals']) <= 1 ? $sbt_opts['vals'][0] : '(' . join(',', $sbt_opts['vals']) . ')'
                            );
                    }
                }
                if ($subtitle_str) {
                    $title[] = join(' ' . _w('or') . ' ', $subtitle_str);
                }
            } else if ($field === 'tag') {
                $check_rights = $this->check_rights;
                $this->check_rights = false;
                $this->tagPrepare($val['val'], false);
                $this->check_rights = $check_rights;
            }
        }

        $this->addWhere("f.storage_id > 0");

        if ($auto_title) {
            $this->addTitle(_w('Search results of') . ' ' . implode(', ', $title));
        }
    }

    public function filterPrepare($filter_id, $auto_title = true)
    {
        $filter_model = new filesFilterModel();
        $filter = $filter_model->getById($filter_id);
        if (!$filter) {
            $this->where[] = 0;
        }
        $hash = $filter['conditions'];
        $this->setHash($hash ? $hash : '');
        $this->prepare(true, true);
        if ($auto_title) {
            $this->setTitle($filter['name']);
        }
    }

    /**
     * Returns expression for SQL
     *
     * @param string $op - operand ==, >=, etc
     * @param string $value - value
     * @return string
     */
    protected function getExpression($op, $value)
    {
        $model = $this->model;
        switch ($op) {
            case '>':
            case '>=':
            case '<':
            case '<=':
            case '!=':
                return " " . $op . " '" . $model->escape($value) . "'";
            case "^=":
                return " LIKE '" . $model->escape($value, 'like') . "%'";
            case "$=":
                return " LIKE '%" . $model->escape($value, 'like') . "'";
            case "*=":
                return " LIKE '%" . $model->escape($value, 'like') . "%'";
            case '@=':
                $values = array();
                foreach (explode(',', $value) as $v) {
                    $values[] = "'" . $model->escape($v) . "'";
                }
                return ' IN (' . implode(',', $values) . ')';
            case "==":
            case "=";
            default:
                return " = '" . $model->escape($value) . "'";
        }
    }

    public function getSQL()
    {
        $this->prepare();
        $sql = "FROM files_file f";

        if ($this->joins) {
            foreach ($this->joins as $join) {
                $alias = isset($join['alias']) ? $join['alias'] : '';
                if (isset($join['on'])) {
                    $on = $join['on'];
                } else {
                    $on = "f.id = " . ($alias ? $alias : $join['table']) . ".file_id";
                }
                $sql .= (isset($join['type']) ? " " . $join['type'] : '') . " JOIN " . $join['table'] . " " . $alias . " ON " . $on;
            }
        }

        $where = $this->where;
        if (empty($where)) {
            $where[] = 'f.parent_id = 0';
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        return $sql;
    }

    protected function getFields($fields)
    {
        $file_model = $this->model;
        if ($fields == '*') {
            return 'f.*' . ($this->required_fields ? ("," . implode(",", $this->required_fields)) : '');
        }

        $allowed_fields = array();
        foreach (array_map('trim', explode(",", $fields)) as $i => $f) {
            if ($f == '*') {
                $fields[$i] = 'f.*';
                continue;
            }
            if ($file_model->fieldExists($f)) {
                $allowed_fields[] = "f.{$f}";
                continue;
            }
        }

        $allowed_fields += $this->required_fields;
        $allowed_fields = array_unique($allowed_fields);

        return implode(",", $allowed_fields);
    }

    public function getItems($fields = "*", $offset = 0, $limit = 50)
    {
        $sql = $this->getSQL();
        $sql = "SELECT " . ($this->joins ? 'DISTINCT ' : '') . $this->getFields($fields) . " " . $sql;
        //$sql .= $this->getGroupBy();
        $sql .= $this->getOrderBy();
        $sql .= " LIMIT " . ($offset ? $offset . ',' : '') . (int) $limit;
        //header("X-FILES-COLLECTION-ITEMS-" . rand() . ": " . str_replace(PHP_EOL, " ", $sql));
        return $this->workupItems($this->model->query($sql)->fetchAll('id'));
    }

    public function addWhere($condition, $or = false)
    {
        $p = strpos(strtolower($condition), ' or ');
        if ($or) {
            if (!isset($this->where['_or'])) {
                $this->where['_or'] = array();
            }
            $where = &$this->where['_or'];
        } else {
            $where = &$this->where;
        }
        if ($p !== false) {
            $where[] = "({$condition})";
        } else {
            $where[] = $condition;
        }
        return $this;
    }

    public function getTitle()
    {
        if ($this->title === null) {
            $this->prepare();
        }
        return $this->title;
    }

    private function workupItems($items)
    {
        if (!isset($this->options['workup'])) {
            $items = $this->model->workupItems($items);

            $check_active_time = $this->check_active_time;
            $expire_time = filesApp::inst()->getConfig()->getNewnessExpireTime();
            foreach ($items as &$item) {
                $item['is_new'] = $check_active_time
                    && (time() - strtotime($item['create_datetime']) < $expire_time);
            }
            unset($item);

            return $items;
        } else if ($this->options['workup'] === true) {
            return $this->model->workupItems($items);
        } else if (is_callable($this->options['workup'])) {
            return call_user_func($this->options['workup'], $items);
        } else {
            return $items;
        }
    }

    /**
     * Returns ORDER BY clause
     * @return string
     */
    protected function getOrderBy()
    {
        if ($this->order_by) {
            return " ORDER BY " . $this->order_by;
        } else {
            return "";
        }
    }

    public function orderBy($order)
    {
        $order = (array) $order;
        $order_str = array();
        foreach ($order as $order_part) {
            $order_part = array_map('trim', explode(' ', $order_part));
            $field = $order_part[0];
            if (!$this->model->fieldExists($field)) {
                continue;
            }
            $direction = trim(strtoupper(ifset($order_part[1], 'asc')));
            if ($direction != 'ASC' && $direction != 'DESC') {
                continue;
            }
            $order_str[] = "f.{$field} {$direction}";
        }
        $this->order_by = join(',', $order_str);
    }

    public function addTitle($title, $delim = ', ')
    {
        $title = (string) $title;
        if (strlen($title) <= 0) {
            return;
        }
        if ($this->title) {
            $this->title .= $delim;
        }
        $this->title .= $title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function addJoin($table, $on = null, $where = null, $options = array())
    {
        $type = '';
        if (is_array($table)) {
            if (isset($table['on'])) {
                $on = $table['on'];
            }
            if (isset($table['where'])) {
                $where = $table['where'];
            }
            if (isset($table['type'])) {
                $type = $table['type'];
            }
            $table = $table['table'];
        }

        $alias = $this->getTableAlias($table);

        if (!isset($this->join_index[$alias])) {
            $this->join_index[$alias] = 1;
        } else {
            $this->join_index[$alias] ++;
        }
        $alias .= $this->join_index[$alias];

        $join = array(
            'table' => $table,
            'alias' => $alias,
            'type' => $type
        );
        if (!empty($options['force_index'])) {
            $join['force_index'] = $options['force_index'];
        }
        if ($on) {
            $join['on'] = str_replace(':table', $alias, $on);
        }
        $this->joins[] = $join;
        if ($where) {
            $this->addWhere(str_replace(':table', $alias, $where));
        }
        return $alias;
    }

    protected function getTableAlias($table)
    {
        $t = explode('_', $table);
        $alias = '';
        foreach ($t as $tp) {
            if ($tp == 'hub') {
                continue;
            }
            $alias .= substr($tp, 0, 1);
        }
        if (!$alias) {
            $alias = $table;
        }
        return $alias;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function setInfo($info)
    {
        $this->info = $info;
    }

    public function count()
    {
        if ($this->count === null) {
            $sql = $this->getSQL();
            $sql = "SELECT COUNT(" . ($this->joins ? 'DISTINCT ' : '') . "f.id) " . $sql;
            $this->count = (int) $this->model->query($sql)->fetchField();
        }
        return $this->count;
    }

    public function getAggregates($fields)
    {
        $sql = $this->getSQL();
        $sql = "SELECT {$fields} {$sql}";
        return $this->model->query($sql)->fetchAssoc();
    }

    public function getPageInfoAggregates()
    {
        return $this->getAggregates('COUNT(*) count, MAX(update_datetime) update_datetime, SUM(size) size');
    }


}

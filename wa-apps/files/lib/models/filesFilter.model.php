<?php

class filesFilterModel extends filesModel
{
    protected $table = 'files_filter';

    const ACCESS_TYPE_PERSONAL = 'personal';
    const ACCESS_TYPE_SHARED = 'shared';

    const ICON_DOCUMENTS = 'notebook fas fa-file-alt';
    const ICON_IMAGES = 'pictures fas fa-images';
    const ICON_FILE = 'lightning';
    const ICON_FOLDER = 'folder';

    const ACTING_TYPE_SEARCH = 'search';
    const ACTING_TYPE_LIST = 'list';
    const ACTING_TYPE_UNKNOWN = '';

    public function add($data)
    {
        $data = $this->prepareBeforeSave($data);
        return $this->insert($data);
    }

    public function update($id, $data)
    {
        $data = $this->prepareBeforeSave($data);
        // don't change icon, if it empty
        if (array_key_exists('icon', $data) && empty($data['icon'])) {
            unset($data['icon']);
        }
        return $this->updateById($id, $data);
    }

    private function prepareBeforeSave($data)
    {
        if (empty($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        if (empty($data['contact_id'])) {
            $data['contact_id'] = $this->contact_id;
        }
        $type = ifset($data['access_type'], '');
        if (!filesRights::inst()->hasFullAccess() || !in_array($type, array(self::ACCESS_TYPE_PERSONAL, self::ACCESS_TYPE_SHARED))) {
            $type = self::ACCESS_TYPE_PERSONAL;
        }
        $data['access_type'] = $type;

        if (isset($data['conditions']) && !$data['conditions']) {
            $data['conditions'] = null;
        }

        return $data;
    }

    public function getOwnFilters()
    {
        return $this->workupFilters(
            $this->getByField(array(
                'contact_id' => $this->contact_id,
                'access_type' => self::ACCESS_TYPE_PERSONAL
            ), 'id')
        );
    }

    public function getSharedFilters()
    {
        return $this->workupFilters(
            $this->getByField(array(
                'access_type' => self::ACCESS_TYPE_SHARED
            ), 'id')
        );
    }

    public function getAvailableFilters()
    {
        $personal = self::ACCESS_TYPE_PERSONAL;
        $shared = self::ACCESS_TYPE_SHARED;
        $contact_id = $this->contact_id;
        $filters = $this->query("SELECT * FROM `{$this->table}`
                      WHERE (access_type = '{$personal}' AND contact_id = {$contact_id}) OR
                        access_type = '{$shared}'")->fetchAll('id');
        return $this->workupFilters($filters);
    }

    private function workupFilters($filters)
    {
        foreach ($filters as &$filter) {
            $filter = $this->workupFilter($filter);
        }
        unset($filter);
        return $filters;
    }

    private function workupFilter($filter)
    {
        $prefix = 'url=';
        $len = strlen($prefix);
        $filter['icon_url'] = null;
        if (substr($filter['icon'], 0, $len) === $prefix) {
            $filter['icon_url'] = substr($filter['icon'], $len);
        }
        return $filter;
    }

    public function getFilter($id)
    {
        $filter = $this->getById($id);
        if (!$filter) {
            return false;
        }

        $filter['conditions_parsed'] = array();
        $filter['acting_type'] = $this->getActingType($filter);
        if ($filter['acting_type'] === self::ACTING_TYPE_SEARCH) {
            $query = substr($filter['conditions'], strlen(self::ACTING_TYPE_SEARCH) + 1);
            $filter['conditions_parsed'] = (array) filesCollection::parseSearchHash($query);
            if (!empty($filter['conditions_parsed']['file_type']['val'])) {

                $raw_val = $filter['conditions_parsed']['file_type']['val'];

                $ext_val = array();
                $file_type_val = array();

                foreach(explode(',', $raw_val) as $val) {
                    $val = trim($val);
                    if ($val && $val[0] === '.') {
                        $ext_val[] = substr($val, 1);
                    } else {
                        $file_type_val[] = $val;
                    }
                }

                if (!$file_type_val) {
                    unset($filter['conditions_parsed']['file_type']);
                } else {
                    $filter['conditions_parsed']['file_type']['val'] = join(',', $file_type_val);
                }

                if ($ext_val) {
                    $filter['conditions_parsed']['ext'] = array(
                        'op' => '=',
                        'val' => join(',', $ext_val)
                    );
                }


            }

            foreach ($filter['conditions_parsed'] as $field_id => &$info) {
                $val_ar = array_map('trim', explode(',', $info['val']));
                $info['val_map'] = array_fill_keys($val_ar, true);
            }
            unset($info);

        }
        return $this->workupFilter($filter);
    }

    private function getActingType($filter)
    {
        $type = self::ACTING_TYPE_UNKNOWN;
        $hash = (string) $filter['conditions'];
        if ($hash) {
            $pos = strpos($hash, '/');
            if ($pos > 0) {
                $type = substr($hash, 0, $pos);
            }
        }
        if (!in_array($type, $this->getAllActingTypes())) {
            $type = self::ACTING_TYPE_UNKNOWN;
        }
        return $type;
    }

    public function getAllActingTypes()
    {
        $prefix = "ACTING_TYPE_";
        $len = strlen($prefix);
        $refl = new ReflectionClass($this);
        $types = array();
        foreach ($refl->getConstants() as $name => $val) {
            if (substr($name, 0, $len) === $prefix) {
                $types[] = $val;
            }
        }
        return $types;
    }

    public function getEmptyRow() {
        $row = parent::getEmptyRow();
        $row['conditions_parsed'] = array();
        $row['access_type'] = self::ACCESS_TYPE_PERSONAL;
        $row['acting_type'] = $this->getActingType($row);
        return $this->workupFilter($row);
    }

    /**
     * @param array[int]|int|null $contact_id
     */
    public function deletePersonal($contact_id = null)
    {
        if ($contact_id === null) {
            $contact_id = $this->contact_id;
        }
        $contact_ids = filesApp::toIntArray($contact_id);
        $this->deleteByField(array(
            'access_type' => self::ACCESS_TYPE_PERSONAL,
            'contact_id' => $contact_ids
        ));
    }

}

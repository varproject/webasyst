<?php

class filesSourceParamsModel extends waModel
{
    protected $table = 'files_source_params';

    /**
     * @param int $source_id
     * @param string [string] $params
     */
    public function set($source_id, $params)
    {
        $delete = array();
        foreach ($params as $name => $value) {
            if ($value === null) {
                $delete[] = $name;
            } else {
                $this->insert(
                    array(
                        'source_id' => $source_id,
                        'name' => $name,
                        'value' => $value
                    ),
                1);
            }
        }
        if ($delete) {
            $this->deleteByField(array(
                'source_id' => $source_id,
                'name' => $delete
            ));
        }
    }

    public function add($source_id, $params)
    {
        $delete = array();
        foreach ($params as $name => $value) {
            if ($value === null) {
                $delete[] = $name;
            } else {
                $this->insert(
                    array(
                        'source_id' => $source_id,
                        'name' => $name,
                        'value' => $value
                    ),
                1);
            }
        }
        if ($delete) {
            $this->deleteByField(array('source_id' => $source_id, 'name' => $delete));
        }
    }

    public function addOne($source_id, $key, $value)
    {
        return $this->add($source_id, array($key => $value));
    }

    public function delete($source_id, $key)
    {
        $this->deleteByField(array('source_id' => $source_id, 'name' => (array) $key));
    }

    /**
     * @param int|array $source_id
     * @return array key-value in case of source_id is int,
     *  otherwise indexed by source array of array of key-value
     */
    public function get($source_id)
    {
        $source_ids = filesApp::toIntArray($source_id);
        $params = array_fill_keys($source_ids, array());
        foreach ($this->getByField(array('source_id' => $source_ids), true) as $item) {
            $params[$item['source_id']][$item['name']] = $item['value'];
        }
        return !is_array($source_id) ? $params[(int) $source_id] : $params;
    }

}

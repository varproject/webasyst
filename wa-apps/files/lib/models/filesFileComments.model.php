<?php

class filesFileCommentsModel extends filesModel implements filesFileRelatedInterface
{
    protected $table = 'files_file_comments';

    public function add($file_id, $data)
    {
        $file_model = new filesFileModel();
        $file = $file_model->getFile($file_id);
        if (!$file) {
            return false;
        }
        $data['content'] = ifset($data['content'], '');
        if (strlen($data['content']) <= 0) {
            return false;
        }
        if (empty($data['contact_id'])) {
            $data['contact_id'] = $this->contact_id;
        }
        if (empty($data['datetime'])) {
            $data['datetime'] = date('Y-m-d H:i:s');
        }
        $data['file_id'] = $file_id;
        return $this->insert($data);
    }

    public function getByFile($file_id)
    {
        $file_id = (int) $file_id;
        return $this->getComments($file_id);
    }

    public function getCounters($file_id)
    {
        $file_ids = array_map('intval', (array) $file_id);
        $where = $this->getWhereByField(array(
            'file_id' => $file_id
        ));
        $res = array_fill_keys($file_ids, 0);
        $sql = "SELECT file_id, COUNT(*) AS count FROM `{$this->table}`
            WHERE {$where}
            GROUP BY file_id";
        foreach ($this->query($sql) as $item) {
            $res[$item['file_id']] = $item['count'];
        }
        return $res;
    }

    public function getComments($file_id)
    {
        $file_ids = array_map('intval', (array) $file_id);
        $where = $this->getWhereByField(array(
            'file_id' => $file_id
        ));
        $res = array_fill_keys($file_ids, array());
        foreach ($this->query("SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY datetime")->fetchAll('id') as $comment) {
            $res[$comment['file_id']][$comment['id']] = $comment;
        }
        return !is_array($file_id) ? $res[(int) $file_id] : $res;
    }

    /**
     * Event method called when file(s) is deleting
     * @param array[int]|int $file_id
     */
    public function onDeleteFile($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $this->deleteByField(array(
            'file_id' => $file_ids
        ));
    }

    public function getRelatedFields()
    {
        return array('file_id');
    }

}
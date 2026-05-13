<?php

class filesFileTagsModel extends filesModel implements filesFileRelatedInterface
{
    protected $table = 'files_file_tags';
    protected $id = array('file_id', 'tag_id');

    public function getByFile($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        $file_ids = filesApp::dropNotPositive($file_ids);
        $tags = array_fill_keys($file_ids, array());
        foreach ($this->getByField(array('file_id' => $file_ids), 'file_id') as $fid => $file) {
            $tags[$fid] = $file;
        }
        return is_numeric($file_id) ? $tags[$file_id] : $tags;
    }

    public function deleteByFile($file_id)
    {
        $this->deleteByField('file_id', $file_id);
    }

    /**
     * Event method called when file(s) is deleting
     * @param array[int]|int $file_id
     */
    public function onDeleteFile($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        if ($this->countByField(array('file_id' => $file_ids))) {
            $this->deleteByField(array(
                'file_id' => $file_ids
            ));
            $tag_model = new filesTagModel();
            $tag_model->clearCacheTags();
        }
    }

    public function getRelatedFields()
    {
        return array('file_id');
    }

}

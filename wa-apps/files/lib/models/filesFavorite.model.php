<?php

class filesFavoriteModel extends filesModel implements filesFileRelatedInterface
{
    protected $table = 'files_favorite';

    public function getFavorites($file_id = null)
    {
        $where = array('contact_id' => $this->contact_id);
        if ($file_id !== null) {
            $file_ids = array_map('intval', (array) $file_id);
            $where['file_id'] = $file_ids;
        }
        return $this->getByField($where, 'file_id');
    }

    public function isFavorite($file_id)
    {
        $file_id = (int) $file_id;
        $favorites = $this->getFavorites($file_id);
        return isset($favorites[$file_id]);
    }

    public function setFavorite($file_id)
    {
        $file_ids = filesApp::toIntArray($file_id);
        foreach ($file_ids as $file_id) {
            $this->insert(array(
                'contact_id' => $this->contact_id,
                'file_id' => $file_id
            ), 1);
        }
    }

    public function unsetFavorite($file_id)
    {
        $this->deleteByField(array(
            'contact_id' => $this->contact_id,
            'file_id' => $file_id
        ));
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
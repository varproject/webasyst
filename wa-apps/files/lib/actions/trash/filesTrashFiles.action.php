<?php

class filesTrashFilesAction extends filesFilesAction
{
    public function filesExecute() {
        return array(
            'hash' => "trash",
            'collection_options' => array(
                'check_active_time' => false
            ),
            'url' => $this->getUrl(),
            'type' => $this->getFileModel()->getTrashType()
        );
    }

    public function workupItems($items) {
        $items = parent::workupItems($items);
        foreach ($items as &$item) {
            $item['backend_url'] = null;
            $item['download_url'] = null;
            $item['comment_count'] = 0;
            $item['tags'] = array();

        }
        unset($item);
        return $items;
    }

}
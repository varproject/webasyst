<?php

class filesFrontendFilesAction extends filesController
{
    /**
     * @var filesCollection
     */
    protected $collection;

    public function execute()
    {
        $res = $this->filesExecute();

        $hash = ifset($res['hash'], '');
        $collection = $this->getCollection($hash, array(
            'check_hash' => true,
            'check_rights' => false
        ));
        $this->collection = $collection;
        $offset = $this->getOffset();
        $limit = $this->getLimit();
        $items = $this->workupItems($collection->getItems('*', $offset, $limit));

        $total_count = $this->getTotalCount($collection);
        $pages_count = ceil((float) $total_count / $limit);

        $theme = 'folder.html';
        if (isset($res['theme'])) {
            $theme = $res['theme'];
        }

        $this->assign(array_merge(array(
            'files' => $items,
            'count' => count($items),
            'title' => $collection->getTitle(),
            'offset' => $offset,
            'limit' => $limit,
            'total_count' => $total_count,
            'pages_count' => $pages_count
        ), $res));

        $this->setThemeTemplate($theme);
    }

    public function workupItems($items)
    {
        return $items;
    }

    private function getOffset()
    {
        $page = (int) wa()->getRequest()->get('page');
        if ($page < 1) {
            $page = 1;
        }
        $limit = $this->getLimit();
        return ($page - 1) * $limit;
    }

    private function getLimit()
    {
        $limit = (int) wa()->getRequest()->get('limit');
        if (!$limit) {
            $limit = (int)waRequest::cookie('files_per_page');
        }
        if ($limit <= 0 || $limit > 100) {
            $limit = filesApp::inst()->getConfig()->getFilesPerPage();
        }
        return $limit;
    }


    private function getTotalCount(filesCollection $collection)
    {
        $total_count = wa()->getRequest()->request('total_count');
        if (is_numeric($total_count)) {
            return $total_count;
        } else {
            return $collection->count();
        }
    }

    public function filesExecute()
    {
        return array(
            'hash' => '',
            'url' => $this->getUrl()
        );
    }
}
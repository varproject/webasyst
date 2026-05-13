<?php

class filesUploadedWidget extends waWidget
{

    public function defaultAction()
    {
        $result = array(
            'title' => $this->getSettings('title'),
        );
        $limit = intval($this->getSettings('limit'));
        $hash = $this->getSettings('filter');

        $fm = new filesFilterModel();
        $all_filters = array(
            filesFilterModel::ACCESS_TYPE_PERSONAL => $fm->getOwnFilters(),
            filesFilterModel::ACCESS_TYPE_SHARED => $fm->getSharedFilters()
        );
        $filters = array(
            'all' => /* _w */('All files'),
            'favorite' => /* _w */('Favorites')
        );
        foreach ($all_filters as $f1) {
            foreach ($f1 as $f2) {
                $filters['filter/' . $f2['id']] = filesApp::truncate($f2['name']);
            }
        }
        $result['filter_name'] = ifempty($filters[$hash], $result['title']);

        $collection = new filesCollection($hash);
        $collection->orderBy(array('create_datetime DESC'));
        $collection->addWhere("type = 'file'");

        $result['files'] = $collection->getItems('*', 0, $limit);

        $result['app_url'] = wa()->getConfig()->getBackendUrl(true) . $this->getApp() . '/';

        $this->display($result);
    }

}
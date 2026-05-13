<?php

class filesFilterSaveController extends filesController
{
    public function execute()
    {
        $filter_id = $this->getFilterId();
        if (!$filter_id) {
            $filter_id = $this->getFilterModel()->add($this->getData());
        } else {
            $this->getFilterModel()->update($filter_id, $this->getData(false));
        }
        if ($filter_id) {
            $filter = $this->getFilterModel()->getById($filter_id);
            $this->assign(array(
                'filter' => $filter
            ));
        }
    }

    public function getFilterId()
    {
        return (int) wa()->getRequest()->post('id');
    }

    public function getActingType()
    {
        return (string) wa()->getRequest()->post('acting_type');
    }

    private function getDataForListActingType($add = true)
    {
        if ($add) {
            $file_ids = $this->getFileIds();
            if (!$file_ids) {
                $this->reportAboutError(_w("Incorrect data for saving"));
            } else if (count($file_ids) == 1) {

                $file_id = $file_ids[0];
                $files = $this->getFileModel()->getById($file_ids);
                if (!$files) {
                    $this->reportAboutError($this->getPageNotFoundError());
                }
                $files = $this->getFileModel()->workupItems($files);
                $file_item = $files[$file_id];
                $icon = ($file_item['type'] == filesFileModel::TYPE_FOLDER) ? 'folder' : 'url=' . $file_item['photo_url']['sidebar'];
                
                return array(
                    'name' => $file_item['name'],
                    'access_type' => filesFilterModel::ACCESS_TYPE_PERSONAL,
                    'icon' => $icon,
                    'conditions' => 'list/' . $file_item['id']
                );
            } else {
                return array(
                    'name' => $this->getName(),
                    'access_type' => $this->getType(),
                    'icon' => $this->getIcon(),
                    'conditions' => 'list/' . join(',', $file_ids)
                );
            }
        }

        return array(
            'name' => $this->getName(),
            'access_type' => $this->getType(),
            'icon' => $this->getIcon()
        );

    }

    private function getDataForSearchActingType()
    {
        return array(
            'name' => $this->getName(),
            'access_type' => $this->getType(),
            'icon' => $this->getIcon(),
            'conditions' => $this->getConditions()
        );
    }

    public function getData($add = true)
    {
        if ($this->getActingType() === filesFilterModel::ACTING_TYPE_LIST) {
            return $this->getDataForListActingType($add);
        } else {
            return $this->getDataForSearchActingType();
        }
    }

    public function getFileIds()
    {
        return filesApp::toIntArray(wa()->getRequest()->post('file_id'));
    }

    public function getIcon()
    {
        return wa()->getRequest()->post('icon', '', waRequest::TYPE_STRING_TRIM);
    }

    public function getName()
    {
        return wa()->getRequest()->post('name', '', waRequest::TYPE_STRING_TRIM);
    }

    public function getType()
    {
        $type = filesFilterModel::ACCESS_TYPE_PERSONAL;
        if (filesRights::inst()->hasFullAccess() && wa()->getRequest()->post('access_type')) {
            $type = filesFilterModel::ACCESS_TYPE_SHARED;
        }
        return $type;
    }

    public function getConditions()
    {
        $conds = (array) wa()->getRequest()->post('conditions');

        if (isset($conds['file_type'])) {
            $key = array_search(':custom', $conds['file_type']);
            if ($key !== false) {
                unset($conds['file_type'][$key]);
            }
            if (isset($conds['file_type']['ext'])) {

                $ext_val = trim(ifempty($conds['file_type']['ext'], ''));
                if ($ext_val) {
                    $exts = array();
                    foreach (explode(',', $ext_val) as $ext) {
                        $ext = trim($ext);
                        if ($ext) {
                            $exts[] = ".{$ext}";
                        }
                    }
                    $exts = array_unique($exts);

                    if ($exts) {
                        $conds['file_type'] = array_merge($conds['file_type'], $exts);
                    }
                }

                unset($conds['file_type']['ext']);
            }
        }

        if (isset($conds['tag'])) {
            $tags = array();
            foreach (explode(',', $conds['tag']) as $tag)  {
                $tag = trim($tag);
                if ($tag) {
                    $tags[] = $tag;
                }
            }
            $conds['tag'] = $tags;
        }

        if (isset($conds['storage_id'])) {
            $conds['storage_id'] = filesApp::dropNotPositive(filesApp::toIntArray($conds['storage_id']));
            if ($conds['storage_id'] <= 0) {
                unset($conds['storage_id']);
            }
        }

        $conds_str = array();
        foreach ($conds as $field => $val)
        {
            if (!empty($val)) {

                $op = '=';
                if (is_array($val)) {
                    $op = '@=';
                    $val = join(',', $val);
                } else if ($field === 'name') {
                    $op = '*=';
                    if (strpos($val, '*') !== false) {
                        $val = preg_replace('/\*+/', '*', $val);
                        $op = '=';
                    }
                }

                $val = trim($val);
                $conds_str[] = "{$field}{$op}{$val}";
            }
        }

        $conds_str = trim(join('&', $conds_str));
        if (!$conds_str) {
            return null;
        }

        return 'search/' . $conds_str;
    }

}
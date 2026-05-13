<?php

/**
 * Class filesViewHelper
 * @method folderHtml(string $hash, array $options = array())
 */
class filesViewHelper
{
    public function __call($name, $arguments)
    {
        $proxy_name = ucfirst($name);
        $proxy_name = "call{$proxy_name}";
        if (!method_exists($this, $proxy_name)) {
            throw new waException("Call to undefined method filesViewHelper::{$name}");
        }
        $old_app = wa()->getApp();
        wa('files', true);
        $is_from_template = waConfig::get('is_template');
        waConfig::set('is_template', null);
        $result = call_user_func_array(array($this, $proxy_name), $arguments);
        waConfig::set('is_template', $is_from_template);
        wa($old_app, true);
        return $result;
    }

    protected function callFolderHtml($hash, $options = array())
    {
        if (!$hash) {
            return '';
        }
        $folder_hash = $hash;
        $folder_id = substr($hash, 16, -16);
        $hash = substr($hash, 0, 16) . substr($hash, -16);

        $file_model = new filesFileModel();
        $folder = $file_model->getFolder($folder_id);
        if (!$folder || !$folder['hash'] || $folder['hash'] !== $hash || $folder['storage_id'] <= 0) {
            return '';
        }

        $collection = new filesCollection("folder/{$folder['id']}", array(
            'check_hash' => true,
            'check_rights' => false
        ));

//        $limit = (int) ifset($options['limit'], 0);
//        if (!$limit) {
//            $limit = (int) waRequest::cookie('files_per_page');
//        }
//        if ($limit <= 0 || $limit > 100) {
//            $limit = filesApp::inst()->getConfig()->getFilesPerPage();
//        }
//        $page = (int) ifset($options['page'], 0);
//        if ($page < 1) {
//            $page = 1;
//        }
//        $offset = ($page - 1) * $limit;

        $items = $collection->getItems('*', 0, 500);
        foreach ($items as &$item) {
            if ($item['type'] === filesFileModel::TYPE_FOLDER) {
                $item['frontend_url'] .= '?helper=1';
            }
        }
        unset($item);

        $total_count = $collection->count();
        //$pages_count = ceil((float) $total_count / $limit);

        $view = wa()->getView();
        $old_vars = $view->getVars();
        $view->clearAllAssign();

        $file_model = new filesFileModel();
        $parent = $file_model->getFolder($folder['parent_id']);
        if ($parent) {
            $parent['frontend_url'] .= '?helper=1';
        }

        $view->assign(array(
            'folder' => $folder,
            'options' => $options,
            'folder_hash' => $folder_hash,
            'parent' => $parent,
            'files' => $items,
            'count' => count($items),
            'title' => $collection->getTitle(),
            'total_count' => $total_count,

//            'offset' => $offset,
//            'limit' => $limit,
//            'pages_count' => $pages_count

        ));

        $path = wa()->getAppPath('templates/view.helper.folder.html', 'files');
        $html = $view->fetch($path);
        $view->clearAllAssign();
        $view->assign($old_vars);

        return $html;
    }



}

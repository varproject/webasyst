<?php

class filesFilesAction extends filesListController
{
    /**
     * @see filesListController
     * @var array
     */
    protected $orders = array('name', 'update_datetime', 'size');

    /**
     * @var filesCollection
     */
    protected $collection;

    public function execute()
    {
        $res = $this->filesExecute();
        $hash = ifset($res['hash'], '');

        $collection_options = ifset($res['collection_options'], array('check_active_time' => true));
        $collection = $this->getCollection($hash, $collection_options);
        $this->collection = $collection;
        $offset = $this->getOffset();
        $limit = $this->getLimit();
        $this->collection->orderBy(array(
            'type DESC', $this->getOrder(true), 'id'
        ));

        $items = $this->workupItems($collection->getItems('*', $offset, $limit));
        $page_info = $this->collection->getPageInfoAggregates();

        if (empty($res['hash_url'])) {
            $res['hash_url'] = $res['hash'];
        }

        /**
         * Extend file list page
         *
         * @event backend_file_list
         * @param array $params
         * @param string $params['hash']
         * @return array[string][string]string $return[%plugin_id%]['operations_menu']
         */
        $params = array(
            'hash' => $res['hash']
        );
        $this->view->assign('backend_file_list', wa()->event('backend_file_list', $params));

        $this->assign(array_merge(array(
            'order_info' => $this->getOrderInfo(),
            'files' => $items,
            'count' => count($items),
            'title' => $collection->getTitle(),
            'offset' => $offset,
            'limit' => $limit,
            'total_count' => $this->getTotalCount(),
            'in_lazy_process' => $this->inLazyProcess(),
            'contact_id' => $this->contact_id,
            'source' => $this->getSourceInfo(),
            'is_source_root' => $this->isSourceRoot(),
            'page_info' => $page_info
        ), $res));
    }

    public function getSourceInfo()
    {
        return array(
            'id' => '',
            'icon_html' => '',
            'access' => false,
            'has_valid_token' => true,
        );
    }

    public function isSourceRoot()
    {
        return false;
    }

    public function workupItems($items)
    {
        $max_files_download_in_archive = $this->getConfig()->getMaxFilesDownloadInArchive();
        $is_ZipArchive_installed = $this->getConfig()->isZipArchiveInstalled();

        $is_debug_mode = waSystemConfig::isDebug();
        $is_admin = wa()->getUser()->isAdmin('webasyst');

        $ctm = new filesCopytaskModel();
        $copytasks = array();
        if ($is_debug_mode && $is_admin) {
            $copytasks = $ctm->getFileTasks(array_keys($items));
        }

        $source_ids = array();
        foreach ($items as $item) {
            $source_ids[] = $item['source_id'];
        }
        $sources = filesSource::factory($source_ids);

        foreach ($items as &$item) {
            $icon_url = '';
            $source_is_on_demand = false;
            if (isset($sources[$item['source_id']])) {
                /**
                 * @var filesSource $source
                 */
                $source = $sources[$item['source_id']];
                $icon_url = $source->getIconUrl();
                $source_is_on_demand = $source->isOnDemand();
            }
            $item['source_icon_url'] = $icon_url;
            $item['source_is_on_demand'] = $source_is_on_demand;
            if ($item['type'] === filesFileModel::TYPE_FILE) {
                $item['can_download'] = !$item['in_copy_process'];
            } else {
                $item['can_download'] = !$item['source_is_on_demand'] &&
                        $is_ZipArchive_installed &&
                        !$item['in_copy_process'] &&
                        $item['count'] > 0 &&
                        $item['count'] <= $max_files_download_in_archive;
            }

            if ($is_debug_mode && $is_admin && !empty($copytasks[$item['id']])) {
                $item['copy_process_info'] = $copytasks[$item['id']];
            } else {
                $item['copy_process_info'] = array();
            }
        }
        unset($item);

        return $items;
    }

    public function getTotalCount()
    {
        $total_count = parent::getTotalCount();
        if (is_numeric($total_count)) {
            return $total_count;
        } else {
            return $this->collection->count();
        }
    }

    public function filesExecute()
    {
        return array(
            'hash' => 'all',
            'url' => $this->getUrl()
        );
    }

}

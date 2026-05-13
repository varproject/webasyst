<?php

class filesBackendSidebarAction extends filesController
{

    public function execute()
    {
        /**
         * Extend sidebar
         * Add extra item to sidebar
         * @event backend_sidebar
         * @return array[string][string]string $return[%plugin_id%]['menu'] Extra item for menu in sidebar
         * @return array[string][string]string $return[%plugin_id%]['section'] Extra section in sidebar
         */
        $this->view->assign('backend_sidebar', wa()->event('backend_sidebar'));

        $asm = new waAppSettingsModel();

        $this->assign(array(
            'all_storages' => $this->getAllStorages(),
            'is_admin' => filesRights::inst()->isAdmin(),
            'shared' => $this->getSharedItems(),
            'counters' => $this->getCounters(),
            'all_filters' => $this->getFilters(),
            'can_create_storage' => filesRights::inst()->canCreateStorage(),
            'sources' => $this->getSources(),
            'is_any_source_plugin_installed' => $this->isAnySourcePluginInstalled(),
            'has_access_to_source_module' => filesRights::inst()->hasAccessToSourceModule(),
            'copy_cli_start' => $asm->get('files', 'copy_cli_start'),
            'sync_cli_start' => $asm->get('files', 'sync_cli_start'),
        ));
    }

    public function getSources()
    {
        if (!filesRights::inst()->hasAccessToSourceModule()) {
            return array();
        }
        $sources = $this->getSourceModel()->getAllSources();

        return $sources;
    }

    public function getCounters()
    {
        return array(
            'all' => $this->getCollection()->count(),
            'favorite' => $this->getCollection('favorite')->count(),
            'trash' => $this->getCollection('trash')->count()
        );
    }

    public function getFilters()
    {
        $all_filters = array(
            filesFilterModel::ACCESS_TYPE_PERSONAL => array(),
            filesFilterModel::ACCESS_TYPE_SHARED => array()
        );
        foreach ($this->getFilterModel()->getAvailableFilters() as $filter) {
            $all_filters[$filter['access_type']][$filter['id']] = $filter;
        }
        $user = $this->getUser();
        foreach ($all_filters as &$filters) {
            foreach ($filters as &$filter) {
                $count = $user->getSettings($this->app_id, 'filters/' . $filter['id']);
                if (is_numeric($count)) {
                    $filter['count'] = $count;
                } else {
                    $col = $this->getCollection("filter/{$filter['id']}");
                    $filter['count'] = $col->count();
                    $user->setSettings($this->app_id, 'filters/' . $filter['id'], $filter['count']);
                }
            }
            unset($filter);
        }
        unset($filters);

        return $all_filters;
    }

    public function getSharedItems()
    {
        $items = array(
            filesFileModel::TYPE_FOLDER => array(),
            filesFileModel::TYPE_FILE => array()
        );
        foreach ($this->getFileModel()->getSharedItems() as $item) {
            $items[$item['type']][$item['id']] = $item;
        }
        return $items;
    }

    protected function getAllStorages()
    {
        $storages = $this->getStorageModel()->getAvailableStorages();
        foreach ($storages as &$storage) {
            $storage['source_has_valid_token'] = true;
            if ($storage['source_id'] > 0) {
                $storage['source_has_valid_token'] = filesSource::factory($storage['source_id']);
            }
        }
        unset($storage);
        return $storages;
    }
}

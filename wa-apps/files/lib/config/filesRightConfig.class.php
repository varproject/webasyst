<?php

class filesRightConfig extends waRightConfig
{
    const RIGHT_CREATE_STORAGE = 'create_storage';
    const RIGHT_STORAGE = 'storage';

    const RIGHT_LEVEL_NONE = 0;
    const RIGHT_LEVEL_READ = 1;
    const RIGHT_LEVEL_READ_COMMENT = 2;
    const RIGHT_LEVEL_ADD_FILES = 3;
    const RIGHT_LEVEL_FULL = 255;

    /**
     * @var waContactRightsModel
     */
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new waContactRightsModel();
    }

    public function init()
    {
        $this->addItem(self::RIGHT_CREATE_STORAGE, _w('Can create storage'), 'checkbox');
        $storages = $this->getStorages();
        if ($storages) {
            $this->addItem(self::RIGHT_STORAGE, _w('Storage'), 'selectlist', array(
                'items' => $storages,
                'position' => 'right',
                'options' => $this->getRightLevels()
            ));
        }
        /**
         * @event rights.config
         * @param waRightConfig $this Rights setup object
         * @return void
         */
        wa()->event('rights.config', $this);
    }

    public function getStorages()
    {
        $model = new filesStorageModel();
        $storages = array();
        foreach ($model->getByType(filesStorageModel::ACCESS_TYPE_LIMITED) as $storage) {
            $storages[$storage['id']] = $storage['name'];
        }
        return $storages;
    }

    public function getRightLevels($only_ids = false)
    {
        $levels = array(
            self::RIGHT_LEVEL_NONE => _w('No access'),
            self::RIGHT_LEVEL_READ => _w('View only'),
            self::RIGHT_LEVEL_READ_COMMENT => _w('View and comment'),
            self::RIGHT_LEVEL_ADD_FILES => _w('Add new files'),
            self::RIGHT_LEVEL_FULL => _w('Full access')
        );
        if ($only_ids) {
            $levels = array_keys($levels);
        }
        return $levels;
    }

}

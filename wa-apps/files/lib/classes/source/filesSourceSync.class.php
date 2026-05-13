<?php

/**
 * Class filesSourceSync
 *
 * All that methods is proxy-methods to filesSourceSyncDriver class
 *
 * @method void append(array $items = array())
 * @method int getChunkSize()
 * @method int getTotalCount()
 * @method array process(array $params = array())
 *
 */
final class filesSourceSync
{
    /**
     * @var filesSourceSyncDriver
     */
    protected $driver;

    /**
     * @var int number of items to process
     */
    protected $chunk_size = 100;

    public function __construct(filesSource $source, $options = array())
    {
        if (empty($options['chunk_size'])) {
            $options['chunk_size'] = $this->chunk_size;
        }
        $options['chunk_size'] = filesApp::toIntegerNumber($options['chunk_size']);

        $driver = $source->getSyncDriver($options);
        if (!($driver instanceof filesSourceSyncDriver)) {
            $driver = new filesSourceSyncDefaultDriver($source, $options);
        }
        $this->driver = $driver;
    }

    /**
     * @param int|array[]int $source_id
     * @return bool|array[]bool
     */
    public static function inSync($source_id)
    {
        $sm = new filesSourceSyncModel();
        return $sm->inSync($source_id);
    }

    public function __call($name, $arguments)
    {
        if (is_callable(array($this->driver, $name))) {
            return call_user_func_array(array($this->driver, $name), $arguments);
        }
        return null;
    }
}

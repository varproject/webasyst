<?php

abstract class filesSourceSyncDriver
{
    /**
     * @var int number of items to process
     */
    protected $chunk_size = 100;

    /**
     * @var filesSourceSyncModel
     */
    protected $queue;

    /**
     * @var filesFileModel
     */
    protected $fm;

    /**
     * @var filesSource
     */
    protected $source;

    /**
     * @var $options
     */
    protected $options;

    public function __construct(filesSource $source, $options = array())
    {
        $this->source = $source;
        $this->options = $options;
        $chunk_size = filesApp::toIntegerNumber(ifset($this->options['chunk_size'], $this->chunk_size));
        if ($chunk_size > 0) {
            $this->chunk_size = $chunk_size;
        }
        $this->queue = new filesSourceSyncModel();
        $this->fm = new filesFileModel();
        $this->fm->setSourceIgnoring($this->source->getId());
        $info = $source->getInfo();
        $this->fm->setContactId($info['contact_id']);
    }

    public function append($items = array())
    {
        $this->queue->append($this->source->getId(), $items);
    }

    public function getChunkSize()
    {
        return $this->chunk_size;
    }

    public function getTotalCount()
    {
        return $this->queue->count($this->source->getId());
    }

    abstract function process($params = array());
}

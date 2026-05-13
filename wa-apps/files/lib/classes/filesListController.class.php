<?php

class filesListController extends filesController
{
    /**
     * For cache
     * @var string
     */
    private $order;

    /**
     * If it's needed to save order into (and get in default case from) contact settings table, set this field
     * @var string|null
     */
    private $order_setting_key;

    /**
     * Available order values, for example array('name', 'update_datetime', ...)
     * @var array
     */
    protected $orders = array();

    public function __construct($params = null)
    {
        parent::__construct($params);
        $class = get_class($this);

        // slice 'files' prefix
        $class = substr($class, 5);

        // slice 'Action' suffix
        if (substr($class, -6) === 'Action') {
            $class = substr($class, 0, -6);
        }

        // slice 'Controller' suffix
        if (substr($class, -10) === 'Controller') {
            $class = substr($class, 0, -10);
        }

        $this->order_setting_key = filesApp::lcfirst($class);
    }

    public function getOrder($str = false)
    {
        if ($this->order === null) {
            $order = (array)wa()->getRequest()->get('order');

            if (!$order) {
                if ($this->order_setting_key) {
                    $csm = new waContactSettingsModel();
                    $order = $csm->getOne($this->contact_id, $this->getAppId(), $this->order_setting_key);
                    if ($order) {
                        $order = explode(' ', $order);
                    }
                }
            }
            if (empty($order[0]) && !empty($this->orders[0])) {
                $order[0] = $this->orders[0];
            }
            if (empty($order[1]) && !empty($this->orders[0])) {
                $order[1] = 'asc';
            }

            if ($this->order_setting_key) {
                $csm = new waContactSettingsModel();
                $csm->set($this->contact_id, $this->getAppId(), $this->order_setting_key, "{$order[0]} {$order[1]}");
            }

            $this->order = $order;
        }

        if ($str) {
            return "{$this->order[0]} {$this->order[1]}";
        } else {
            return $this->order;
        }
    }

    public function getOrderInfo()
    {
        $order = $this->getOrder();

        $info = array();
        foreach ($this->orders as $field) {
            $info[$field] = array(
                'order' => '',
                'link' => "order[0]={$field}"
            );
            if ($order[0] === $field) {
                $info[$field]['order'] = strtolower($order[1]) === 'asc' ? 'desc' : 'asc';
                $info[$field]['link'] .= "&order[1]={$info[$field]['order']}";
            }
        }

        return $info;
    }

    public function inLazyProcess()
    {
        return waRequest::get('lazy', false);     // is now lazy loading?
    }

    public function getOffset()
    {
        return wa()->getRequest()->request('offset', 0, waRequest::TYPE_INT);
    }

    public function getLimit()
    {
        return filesApp::inst()->getConfig()->getFilesPerPage();
    }

    public function getTotalCount()
    {
        return wa()->getRequest()->request('total_count');
    }


    public function getUrl()
    {
        $url = parent::getUrl();
        $order = $this->getOrder();
        return $url . "&order[0]={$order[0]}&order[1]={$order[1]}";
    }

}

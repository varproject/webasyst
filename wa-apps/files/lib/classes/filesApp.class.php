<?php

class filesApp
{
    private static $instance;
    private static $app_id = 'files';
    private $exts;

    const TYPE_LOG_ERROR = 'error';
    const TYPE_LOG_DEBUG = 'debug';

    /**
     * @var waContact
     */
    private $user;

    /**
     * @var int
     */
    private $contact_id;

    private function __construct() {
        $this->user = wa($this->getAppId())->getUser();
        $this->contact_id = $this->user->getId();
    }

    private function __clone() {
        ;
    }

    /**
     * @return filesApp
     */
    public static function inst()
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAppId()
    {
        return self::$app_id;
    }

    /**
     * Get info by file extension
     * @param string $ext
     * @return array
     */
    public function getExtInfo($ext)
    {
        if ($this->exts === null) {
            $this->exts = $this->getConfig()->getExts();
        }
        $ext = strtolower($ext);
        $info = ifset($this->exts[$ext], $this->exts['__file__']);
        $ext_img_url = $this->getConfig()->getExtImgUrl();
        if ($info['img'][0] !== '/') {
            $info['img'] = $ext_img_url . $info['img'];
        }
        $info['id'] = $ext;
        return $info;
    }

    /**
     * Get folder icon img
     * @return string
     */
    public function getFolderImg()
    {
        if ($this->exts === null) {
            $this->exts = $this->getConfig()->getExts();
        }
        $info = $this->exts['__folder__'];
        $ext_img_url = $this->getConfig()->getExtImgUrl();
        if (strpos($info['img'], '/') === false) {
            $info['img'] = $ext_img_url . $info['img'];
        }
        return $info['img'];
    }

    /**
     * Get icon img by file extension
     * @param $ext
     * @return mixed
     */
    public function getExtImg($ext)
    {
        $info = $this->getExtInfo($ext);
        return $info['img'];
    }

    /**
     * Get name by file extension
     * @param $ext
     * @return mixed
     */
    public function getExtName($ext)
    {
        $info = $this->getExtInfo($ext);
        return $info['name'];
    }


    /**
     * @param array $file
     * @param array $options
     * @param boolean $check_supported
     * @return filesThumbnail | null
     */
    public function getThumbnail($file, $options = array(), $check_supported = true)
    {
        return filesThumbnail::factory($file, $options, $check_supported);
    }

    /**
     * @return filesCopyCli
     */
    public function getCopyAction()
    {
        return new filesCopyCli();
    }

    /**
     * Write message into error log
     * @param mixed $message
     */
    public function logError($message)
    {
        $this->log($message, self::TYPE_LOG_ERROR);
    }

    /**
     * Write message int debug log
     * @param mixed $message
    public function logDebug($message)
    {
        // test for debug level of wa
        $this->log($message, self::TYPE_LOG_DEBUG);
    }
     */

    /**
     * Write message into log
     * @param mixed $message
     * @param string $type filesApp::TYPE_LOG_ERROR|filesApp::TYPE_LOG_DEBUG
     */
    private function log($message, $type = self::TYPE_LOG_ERROR)
    {
        $app_id = self::$app_id;
        $file = "{$app_id}/{$type}.log";
        if (!is_string($message)) {
            $message = var_export($message, true);
        }
        waLog::log($message, $file);
    }

    /**
     * Prepare log string from exception
     * @param Exception $exception
     * @param mixed $extra
     * @return string
     */
    private function getLogString(Exception $exception, $extra = null)
    {
        return $exception->getMessage() . ". Code: " .
                $exception->getCode() . PHP_EOL .
                $exception->getTraceAsString() . PHP_EOL .
                ". Extra = " . var_export($extra, true);
    }


    /**
     * Throw exception-error + write to log
     * @param array|string $error
     * @throws waException
     * @throws waRightsException
     */
    public function reportAboutError($error)
    {
        if (is_string($error)) {
            $error = array(
                'code' => 404,
                'msg' => $error
            );
        }
        $app_id = self::$app_id;
        $log_file = $app_id . '/errors.log';
        if ($error['code'] === 403) {
            $exception = new waRightsException($error['msg'], $error['code']);
        } else {
            $exception = new filesException($error['msg'], $error['code']);
        }
        waLog::log($this->getLogString($exception, ifset($error['extra'], '<NONE>')), $log_file);
        throw $exception;
    }

    /**
     * @return filesConfig
     */
    public function getConfig()
    {
        return wa($this->getAppId())->getConfig();
    }

    /**
     * @return filesRightConfig
     */
    public function getRightConfig()
    {
        return $this->getConfig()->getRightConfig();
    }

    /**
     * Get last active time in app
     * @return string
     */
    public function getLastActiveTime()
    {
        $csm = new waContactSettingsModel();
        $last_active_time = $csm->getOne(
            $this->contact_id,
            $this->getAppId(),
            'last_active_time'
        );
        if (!$last_active_time) {
            $this->setLastActiveTime();
            $last_active_time = $csm->getOne(
                $this->contact_id,
                $this->getAppId(),
                'last_active_time'
            );
        }
        return $last_active_time;
    }

    /**
     * Set last active time in app
     */
    public function setLastActiveTime()
    {
        $csm = new waContactSettingsModel();
        $csm->set($this->contact_id, $this->getAppId(), 'last_active_time', date('Y-m-d H:i:s'));
    }

    public function renderViewAction($action)
    {
        if (is_string($action) && class_exists($action)) {
            $action = new $action();
        }
        if (!($action instanceof waViewAction)) {
            return null;
        }
        $view = wa()->getView();
        $vars = $view->getVars();
        $html = $action->display();
        $view->clearAllAssign();
        $view->assign($vars);
        return $html;
    }

    //================ APP SPECIFIC HELPERS =============

    public static function getFileFolder($file_id)
    {
        $str = str_pad($file_id, 4, '0', STR_PAD_LEFT);
        return substr($str, -2).'/'.substr($str, -4, 2);
    }

    public static function getFilePath($file_id, $sid, $ext)
    {
        $file_name = self::getFileFolder($file_id).'/'.$file_id. '.' . $sid . '.' . $ext;
        return wa()->getDataPath($file_name, false, self::$app_id);
    }


    public static function getContacts($ids = array(), $fields = array())
    {
        if (!$ids) {
            return array();
        }
        $ids = array_unique($ids);
        $fields = array_merge((array) $fields, array('id', 'name', 'firstname', 'middlename', 'lastname', 'photo', 'photo_url_20'));
        $default = array_fill_keys($fields, '');
        $default['exists'] = false;
        $contacts = array_fill_keys($ids, $default);
        $col = new waContactsCollection('id/' . join(',', $ids));
        foreach ($col->getContacts(join(',', $fields), 0, count($ids)) as $contact) {
            $contact['name'] = waContactNameField::formatName($contact);
            $contact['exists'] = true;
            $contacts[$contact['id']] = $contact;
        }
        return $contacts;
    }

    /**
     * Test if array is plain (just simple array started by 0 and ended by length-1)
     *
     * @param array $array
     * @return boolean
     */
    public static function isPlainArray($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $len = count($array);
        for ($i = 0; $i < $len; $i += 1) {
            if (!isset($array[$i])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Cast to array of integers
     * @param mixed $val
     * @return array[int]
     */
    public static function toIntArray($val)
    {
        return array_map('intval', (array) $val);
    }

    /**
     * For every value in array make subtract it from 0
     * @param array[int] $int_array array of numeric values
     * @return array[int]
     */
    public static function negativeValues($int_array)
    {
        foreach ($int_array as &$int) {
            $int = -$int;
        }
        unset($int);
        return $int_array;
    }

    public static function negativeKeys($array_with_int_keys)
    {
        $res = array();
        foreach ($array_with_int_keys as $k => $v) {
            $res[-$k] = $v;
        }
        return $res;
    }

    public static function absValues($int_array) {
        return array_map('abs', $int_array);
    }

    /**
     * Drop all not positive values from input array
     * @param array[int] $int_array
     * @return array[int]
     */
    public static function dropNotPositive($int_array)
    {
        foreach ($int_array as $index => $int) {
            if ($int <= 0) {
                unset($int_array[$index]);
            }
        }
        return $int_array;
    }

    /**
     * Calculate diff of assoc arrays (not cast to string every element)
     * @param array $minuend
     * @param array $subtrahend
     * @return array
     */
    public static function assocDiff($minuend, $subtrahend)
    {
        $result = array();
        foreach ($minuend as $key => $value) {
            if (!isset($subtrahend[$key])) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Extract values of specific field(key) from array of array
     * @param array $array array of associative arrays
     * @param string $field
     * @return array[string] array of unique values
     */
    public static function getFieldValues($array, $field)
    {
        $values = array();
        foreach ($array as $elem) {
            if (array_key_exists($field, $elem)) {
                $values[] = $elem[$field];
            }
        }
        return array_unique($values);
    }

    public static function margeWithBoolMap($array, $bool_map, $field, $default = false)
    {
        foreach ($array as $key => &$item) {
            $item[$field] = array_key_exists($key, $bool_map) ? $bool_map[$key] : $default;
        }
        unset($item);
        return $array;
    }

    /**
     * Return info about path to file. A little bit more convenient for app purpose than standard pathinfo
     * @param string $filename
     * @param null|int $ext_max_len
     * @return array associative info array.
     *  Always has keys: dirname, basename, filename, ext
     */
    public static function pathInfo($filename, $ext_max_len = null)
    {
        $original_filename = $filename;
        if ($ext_max_len === null) {
            $ext_max_len = PHP_INT_MAX;
        }

        $ext = '';
        $point_pos = strrpos($filename, '.');
        if ($point_pos !== false) {
            $ext = substr($filename, $point_pos + 1);
            if (strlen($ext) <= $ext_max_len) {
                $filename = substr($filename, 0, $point_pos);
            } else {
                $ext = '';
            }
        }

        $info = pathinfo($original_filename);
        $info = array_merge($info, array(
            'original' => $original_filename,
            'ext' => strtolower($ext),
            'ext_original' => $ext
        ));

        return $info;
    }

    /**
     * Convert file size to formatted string
     * @param int $file_size
     * @return string
     */
    public static function formatFileSize($file_size)
    {
        $_kb = 1024;
        $_mb = 1024 * $_kb;
        if ($file_size <= $_kb) {
            $file_size = $file_size . ' ' . _w('B');
        } else if ($file_size > $_kb && $file_size < $_mb) {
            $file_size = round($file_size/$_kb) . ' ' . _w('KB');
        } else {
            $file_size = round($file_size/$_mb, 1) . ' ' . _w('MB');
        }
        return $file_size;
    }

    /**
     * Convert first symbol to upper case. Exists for symmetry with lcfirst
     * @see filesApp::lcfirst
     * @param string $str
     * @return string
     */
    public static function ucfirst($str)
    {
        if (strlen($str) <= 0) {
            return $str;
        }
        return strtoupper(substr($str, 0, 1)) . substr($str, 1);
    }

    /**
     * Convert first symbol to lower case. Standard lcfirst isn't supported php below 5.3 version
     * @param string $str
     * @return string
     */
    public static function lcfirst($str)
    {
        if (strlen($str) <= 0) {
            return $str;
        }
        return strtolower(substr($str, 0, 1)) . substr($str, 1);
    }

    /**
     * Truncate long strings.
     * @param string $str
     * @param int $len
     * @return string
     */
    public static function truncate($str, $len = 20)
    {
        if (mb_strlen($str) > $len) {
            return mb_substr($str, 0, $len) . '...';
        }
        return $str;
    }

    /**
     * Check is file is stream
     * @param $file
     * @return bool
     */
    public static function isStream($file)
    {
        return is_resource($file) && get_resource_type($file) === 'stream';
    }

    public static function isRequestFile($file)
    {
        return $file instanceof waRequestFile;
    }

    public static function getAbsoluteUrl()
    {
        $config = wa()->getConfig();
        $url = $config->getRootUrl(true) . $config->getBackendUrl() . '/files/';
        return $url;
    }

    private static function typecaseItems($data, $items_type, $options = array())
    {
        $input_type = '';
        $item_ids = array();

        $items = $data;

        if (wa_is_int($items)) {
            $input_type = 'id';
            $item_ids = array($data);
        }

        $items = (array) $items;

        // if it single record ?
        if (isset($items['id'])) {
            $input_type = 'record';
            $items = array(
                $items['id'] => $items
            );
            $item_ids = array_keys($items);
        }

        $file = reset($items);

        if (is_array($file) && isset($file['id']) && !$input_type) {
            $input_type = 'records';
            $item_ids = filesApp::getFieldValues($items, 'id');
        }

        if (wa_is_int($file)) {
            $input_type = $input_type ? $input_type : 'ids';
        }

        $fields = explode(',', ifset($options['fields'], 'type,items,ids'));
        $fields_map = array_fill_keys($fields, true);

        if ($input_type === 'id' || $input_type === 'ids') {
            $item_ids = filesApp::toIntArray($items);
            if (ifset($fields_map['items'])) {

                if ($items_type === 'storages') {

                    $model = new filesStorageModel();

                    // a little bit optimization, get cached data
                    if (count($item_ids) <= 1) {
                        $storage = $model->getStorage($item_ids[0]);
                        if ($storage) {
                            $items = array($storage['id'] => $storage);
                        } else {
                            $items = array();
                        }
                    } else {
                        $items = $model->getById($item_ids);
                    }

                    $item_ids = array_keys($items);

                } else {

                    $model = new filesFileModel();

                    // a little bit optimization, get cached data
                    if (count($item_ids) <= 1) {
                        $file = $model->getItem($item_ids[0], false);
                        if ($file) {
                            $items = array($file['id'] => $file);
                        } else {
                            $items = array();
                        }
                    } else {
                        $items = $model->getById($item_ids);
                    }

                    $item_ids = array_keys($items);
                }
            }
        }

        $res = array();
        if (ifset($fields_map['type'])) {
            $res['type'] = $input_type;
        }
        if (ifset($fields_map['items'])) {
            $res['items'] = $items;
        }
        if (ifset($fields_map['ids'])) {
            $res['ids'] = $item_ids;
        }
        return $res;
    }

    /**
     * Helper method for typecast input parameter files.
     *
     * @param array|int $data
     * It can be
     *     numeric ID,
     *     array of IDs,
     *     associative array-record from DB
     *     array of associative array-records from DB
     * @param $options
     *      'fields' => string, example: 'type,files,ids'|'type'|'files', etc.
     *
     * @return array with keys:
     *          'type' (id|ids|record|records),
     *          'files' - files from DB
     */
    public static function typecastFiles($data, $options = array())
    {
        if (isset($options['fields'])) {
            $options['fields'] = str_replace('files', 'items', $options['fields']);
        }
        $res = self::typecaseItems($data, 'files', $options);
        if (isset($res['items'])) {
            $res['files'] = $res['items'];
            unset($res['items']);
        }
        return $res;
    }

    /**
     * Helper method for typecast input parameter sources.
     *
     * @param array|int $data
     * It can be
     *     numeric ID,
     *     array of IDs,
     *     associative array-record from DB
     *     array of associative array-records from DB
     * @param $options
     *      'fields' => string, example: 'type,storages,ids'|'type'|'storages', etc.
     *
     * @return array with keys: 'type' (id|ids|record|records) and 'storages': storages from DB
     */
    public static function typecastStorages($data, $options = array())
    {
        if (isset($options['fields'])) {
            $options['fields'] = str_replace('storages', 'items', $options['fields']);
        }
        $res = self::typecaseItems($data, 'storages', $options);
        if (isset($res['items'])) {
            $res['storages'] = $res['items'];
        }
        return $res;
    }

    /**
     * @param $val
     * @return int|float
     *
     * NOTICE: Use float to prevent overflow int capacity
     * 1G = 2e30 bytes < 2e31 - 1 (PHP_INT_MAX)
     * 2G = 2e32 bytes > 2e31 - 1 (PHP_INT_MAX)
     */
    public static function convertToBytes($val)
    {
        if (empty($val)) {
            return 0;
        }

        $val = trim($val);

        preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);

        $last = '';
        if(isset($matches[2])){
            $last = $matches[2];
        }

        if(isset($matches[1])){
            $val = (int) $matches[1];
        }

        switch (strtolower($last))
        {
            case 'g':
            case 'gb':
                $val *= 1024 * 1024 * 1024;
                break;
            case 'm':
            case 'mb':
                $val *= 1024 * 1024;
                break;
            case 'k':
            case 'kb':
                $val *= 1024;
                break;
        }

        return $val;
    }

    /**
     * Convert to integer number that will be represented by int or float depended on possible overflowing PHP_INT_MAX
     * @param mixed $val
     * @param bool $as_str - IF need to send somewhere by net (or to print in template) convert to string
     * @return float|int
     */
    public static function toIntegerNumber($val, $as_str = false)
    {
        if (is_scalar($val)) {
            $val = ceil((float)$val);
            if ($val < PHP_INT_MAX) {
                $val = (int)$val;
                if ($as_str) {
                    return strval($val);
                } else {
                    return $val;
                }
            } else {
                if ($as_str) {
                    $str_val = strval($val);
                    $p = strpos($str_val, '.');
                    if ($p !== false) {
                        return substr($str_val, 0, $p);
                    }
                    $p = strpos($str_val, ','); // if strval return localize string (with ',')
                    if ($p !== false) {
                        return substr($str_val, 0, $p);
                    }
                    return $str_val;
                } else {
                    return $val;
                }
            }
        }
        return 0;
    }

    public static function getAccessDeniedError($msg = null, $extra = null)
    {
        return array(
            'msg' => $msg ? $msg : _w('Access denied'),
            'code' => 403,
            'extra' => $extra
        );
    }

}

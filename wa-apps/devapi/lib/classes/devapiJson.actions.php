<?php
class devapiJsonActions extends waJsonActions
{
    /**
     * @param array $data
     * @param array $fields
     */
    public function checkRequiredFields($data, $fields)
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        foreach ($fields as $f) {
            if (!isset($data[$f]) || !$data[$f]) {
                $this->setError(sprintf(_w('Отсутствует обязательный параметр %s'), $f));
            }
        }
    }

    /**
     * @param string $message
     * @param array $data
     */
    public function setError($message, $data = array())
    {
        if (wa()->getEnv() == 'cli') {
            throw new waException($message);
        }
        if ($data) {
            $this->errors[] = array($message, $data);
        } else {
            $this->errors[] = $message;
        }
    }

    public function execute($action)
    {
        ini_set('display_errors', 'off');
        try {
            parent::execute($action);
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
        }
    }
}
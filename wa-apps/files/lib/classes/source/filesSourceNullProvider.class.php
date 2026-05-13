<?php

/**
 * Class filesSourceDiscardProvider
 * Null-provider, discard all calls, do nothing, just for not using check for null everywhere
 *
 * @example
 * $source = filesSource::factory(...)  // is filesSourceDiscardProvider, not false, or null, or etc, not if required
 * $data = array(...);  // some data
 * $success = $source->add($data);  // without check for null
 */
final class filesSourceNullProvider extends filesSourceProvider
{
    public function getId()
    {
        return null;
    }

    public function beforeAdd($params)
    {
        return $params;
    }

    public function afterAdd($params)
    {
        return $params;
    }

    public function beforeReplace($params)
    {
        return $params;
    }

    public function afterReplace($params)
    {
        return $params;
    }

    public function beforeCopy($params)
    {
        return $params;
    }

    public function afterCopy($params)
    {
        return $params;
    }

    public function beforeRename($params)
    {
        return $params;
    }

    public function afterRename($params)
    {
        return $params;
    }


    public function beforeDelete($params)
    {
        return $params;
    }

    public function afterDelete($params)
    {
        return $params;
    }

    public function beforeMove($params)
    {
        return $params;
    }

    public function afterMove($params)
    {
        return $params;
    }

    public function beforeMoveToTrash($params)
    {
        return $params;
    }

    public function afterMoveToTrash($params)
    {
        return $params;
    }

    public function getFilePath($file)
    {
        return '';
    }

    public function getFilePhotoUrls($file)
    {
        return array();
    }

    public function download($file, $type = filesSource::DOWNLOAD_STDOUT, $options = array())
    {
        return;
    }

    public function pullChunk($progress_info = array())
    {
        return array(
            'progress' => 100,
            'done' => 100
        );
    }

    public function syncData($options = array())
    {
        return array();
    }
}

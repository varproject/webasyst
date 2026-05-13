<?php

class filesLogExplainer
{
    protected static $static_cache = array();

    protected $log;
    protected $file_ids;
    protected $files;
    protected $storage_ids;
    protected $storages;
    protected $comment_ids;
    protected $comments;
    protected $available_storages;
    protected $app_url;

    public function __construct($log, $options = array())
    {
        $this->log = $log;

        $options = is_array($options) ? $options : array();
        $this->app_url = $options['app_url'];
    }

    protected function initExplainStates()
    {
        $this->file_ids = array();
        $this->storage_ids = array();
        $this->comment_ids = array();
        $this->files = null;
        $this->storages = null;
        $this->comments = null;
        $this->available_storages = null;
    }

    protected function collectIds()
    {
        foreach ($this->log as $log_item) {
            $ids = $this->toIntIds($log_item['params']);
            if ($this->isStorageAction($log_item['action'])) {
                $this->storage_ids = array_merge($this->storage_ids, $ids);
            } elseif ($this->isCommentAction($log_item['action'])) {
                $this->comment_ids = array_merge($this->comment_ids, $ids);
            } else {
                $this->file_ids = array_merge($this->file_ids, $ids);
            }
        }
        $this->storage_ids = array_unique($this->storage_ids);
        $this->comment_ids = array_unique($this->comment_ids);
        $this->file_ids = array_unique($this->file_ids);
    }

    protected function getAvailableStorages()
    {
        if ($this->available_storages === null) {
            $this->available_storages = $this->getStorageModel()->getAvailableStorages();
        }
        return $this->available_storages;
    }

    protected function loadFiles()
    {
        if (!$this->file_ids || $this->files !== null) {
            return;
        }

        $available_storages = $this->getAvailableStorages();

        $files = $this->getFileModel()->getById($this->file_ids);

        // hide unavailable files
        foreach ($files as &$f) {
            if (empty($available_storages[$f['storage_id']])) {
                $f['name'] = null;
            }
        }
        unset($f);

        $this->files = $files;
    }

    protected function loadStorages()
    {
        if (!$this->storage_ids && $this->storages !== null) {
            return;
        }

        $available_storages = $this->getAvailableStorages();

        $storages = $this->getStorageModel()->getById($this->storage_ids);

        // hide unavailable storages
        foreach ($storages as &$s) {
            if (empty($available_storages[$s['id']])) {
                $s['name'] = null;
            }
        }
        unset($s);

        $this->storages = $storages;
    }

    /**
     * IMPORTANT: First must be loaded comments
     * Cause of files. Loading comments add more file IDS to $this->file_ids array
     */
    protected function loadComments()
    {
        if (!$this->comment_ids && $this->comments !== null) {
            return;
        }
        $comments = $this->getCommentsModel()->getById($this->comment_ids);
        foreach ($comments as $comment) {
            $this->file_ids[] = $comment['file_id'];
        }
        $this->file_ids = array_unique($this->file_ids);
        $this->comments = $comments;
    }

    protected function loadResources()
    {
        // Order matter, don't change it! Must be first loadComments call
        $this->loadComments();
        // must be called after loadComments
        $this->loadFiles();
        //
        $this->loadStorages();
    }

    public function explain()
    {
        $this->initExplainStates();
        $this->collectIds();
        $this->loadResources();
        return $this->explainLog();
    }

    protected function explainLog()
    {
        $log = $this->log;

        foreach ($log as &$l) {
            if ($this->isStorageAction($l['action'])) {
                $this->explainStorageLogItem($l);
            } elseif ($this->isCommentAction($l['action'])) {
                $this->explainCommentLogItem($l);
            } else {
                $this->explainFileLogItem($l);
            }
            if (empty($l['params_html'])) {
                $l = null;
            }
        }
        unset($l);

        return $log;
    }

    protected function explainCommentLogItem(&$l)
    {
        $ids = $this->toIntIds($l['params']);
        foreach ($ids as $id) {
            if (empty($this->comments[$id])) {
                continue;
            }
            $comment = $this->comments[$id];
            $file_id = $comment['file_id'];
            if ($file_id > 0 && !empty($this->files[$file_id]['name'])) {
                $file = $this->files[$file_id];
                $url = $this->app_url . '#/' . $file['type'] . '/' . $file_id . '/';
                $file_name = $this->formFileName($file_id);

                $html = array();

                $comment_content = is_scalar($comment['content']) ? (string)$comment['content'] : '';
                if (strlen($comment_content) > 0) {
                    $comment_content = filesApp::truncate(htmlspecialchars(strip_tags($comment_content)));
                    $html[] = $comment_content;
                }

                $html[] = '<a href="' . $url . '">'
                    . $file_name
                    . '</a>';

                $l['params_html'] = join('&nbsp;-&nbsp;', $html);
            }
        }
    }

    protected function explainStorageLogItem(&$l)
    {
        $ids = $this->toIntIds($l['params']);
        foreach ($ids as $id) {
            if (!empty($this->storages[$id]['name'])) {
                $url = $this->app_url . '#/storage/' . $l['params'] . '/';
                $l['params_html'] = '<a href="' . $url . '">'
                    . filesApp::truncate(htmlspecialchars(strip_tags($this->storages[$id]['name'])))
                    . '</a>';
            }
        }
    }

    protected function formFileName($id)
    {
        return isset($this->files[$id]['name']) ? filesApp::truncate(htmlspecialchars(strip_tags($this->files[$id]['name']))) : '';
    }

    protected function explainFileLogItem(&$l)
    {
        $file_names = array();
        $ids = $this->toIntIds($l['params']);
        foreach ($ids as $id) {
            if (!empty($this->files[$id]['name'])) {
                $url = $this->app_url . '#/' . $this->files[$id]['type'] . '/' . $id . '/';
                $file_names[] = '<a href="' . $url . '">'
                    . $this->formFileName($id)
                    . '</a>';
            }
        }
        if (count($file_names) > 3) {
            $file_names = array_slice($file_names, 0, 3);
            $file_names[] = '...';
        }
        $l['params_html'] = join(', ', $file_names);
    }

    protected function isStorageAction($action)
    {
        return substr($action, 0, 8) === 'storage_';
    }

    protected function isCommentAction($action)
    {
        return substr($action, 0, 8) === 'comment_';
    }


    protected function toIntIds($scalar)
    {
        if (!is_scalar($scalar)) {
            return array();
        }
        $scalar = trim((string)$scalar);
        if (strlen($scalar) <= 0) {
            return array();
        }

        if (wa_is_int($scalar) && $scalar > 0) {
            $ids = array($scalar);
        } else {
            $ids = preg_split('/\s*,\s*/', $scalar);
        }
        $ids = filesApp::toIntArray($ids);
        $ids = filesApp::dropNotPositive($ids);
        return $ids;
    }

    /**
     * @return filesStorageModel
     */
    protected function getStorageModel()
    {
        if (!isset(self::$static_cache['models']['storage'])) {
            self::$static_cache['models']['storage'] = new filesStorageModel();
        }
        return self::$static_cache['models']['storage'];
    }

    /**
     * @return filesFileCommentsModel
     */
    protected function getCommentsModel()
    {
        if (!isset(self::$static_cache['models']['comments'])) {
            self::$static_cache['models']['comments'] = new filesFileCommentsModel();
        }
        return self::$static_cache['models']['comments'];
    }

    /**
     * @return filesFileModel
     */
    protected function getFileModel()
    {
        if (!isset(self::$static_cache['models']['file'])) {
            self::$static_cache['models']['file'] = new filesFileModel();
        }
        return self::$static_cache['models']['file'];
    }

}

<?php

class filesFileAction extends filesController
{

    public function execute()
    {
        $file = $this->getFile();
        $storage = $this->getStorageModel()->getStorage($file['storage_id']);
        $has_full_access_to_file = filesRights::inst()->hasFullAccessToFile($file['id']);
        $is_file_shared = !empty($file['hash']);
        if (!$is_file_shared && $has_full_access_to_file) {
            $share_rights = $this->getFileRightsModel()->getByField(array('file_id' => $file['id']));
            $is_file_shared = !empty($share_rights);
        }

        /**
         * Plugin hook for extend file page
         * @event backend_file
         *
         * @params array $params
         * @params array $params['file']
         * @return array[string]string $return[%plugin_id%]['top']
         * @return array[string]string $return[%plugin_id%]['bottom']
         * @return array[string]string $return[%plugin_id%]['preview']
         * @return array[string]string $return[%plugin_id%]['menu']
         */
        $event_params = array(
            'file' => $file
        );
        $backend_file = wa()->event('backend_file', $event_params);

        $this->assign(array(
            'file' => $file,
            'in_sync' => $this->getFileModel()->inSync($file),
            'storage' => $storage,
            'breadcrumbs' => $this->getBreadcrumbs($file),
            'contacts_url' => wa()->getAppUrl('contacts'),
            'text_file_show_max_size' => $this->getTextFileShowMaxSize(),
            'text_file_show_max_size_str' => $this->getTextFileShowMaxSize(true),
            'can_rename' => $this->canRename($file['id']),
            'popular_tags' => $this->getTagModel()->getPopularTags(10, true),
            'can_comment' => $this->canComment($file['id']),
            'can_edit_tags' => $this->canEditTags($file['id']),
            'attach_max_size' => wa()->getConfig()->getOption('messages_attach_max_size'),
            'has_full_access_to_file' => $has_full_access_to_file,
            'is_file_shared' => $is_file_shared,
            'marks' => $this->getMarks(),
            'backend_file' => $backend_file
        ));
    }

    public function getFile()
    {
        $id = wa()->getRequest()->get('id', null, waRequest::TYPE_INT);
        $file = $this->getFileModel()->getFile($id);
        if (!$file) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        $storage = $this->getStorageModel()->getStorage($file['storage_id']);
        if (!$storage) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        if ($file['in_copy_process']) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        if (!filesRights::inst()->canReadFile($file['id'])) {
            $this->reportAboutError($this->getAccessDeniedError());
            return false;
        }
        $file['author'] = $this->getAuthor($file['contact_id']);
        $file['comments'] = $this->getComments($file['id']);
        if ($file['file_type'] === filesConfig::FILE_TYPE_TEXT) {
            $file['content'] = $this->getFileContent($file['path'], 0, $this->getTextFileShowMaxSize());
            $file['content_size'] = @filesize($file['path']);
            $file['content_size'] = $file['content_size'] !== false ? $file['content_size'] : 0;
            $file['content_size_str'] = filesApp::formatFileSize($file['content_size']);
        }

        $file['source'] = array(
            'id' => $file['source_id'],
            'icon_html' => '',
            'access' => false
        );
        $source = filesSource::factory($file['source_id']);
        $icon = $source ? $source->getIconUrl() : '';
        if ($icon) {
            $file['source']['icon_html'] = "<i class='icon16 plugins' style='background: url({$icon}) no-repeat; background-size: contain;'></i>";
            $file['source']['access'] = filesRights::inst()->hasAccessToSource($file['source_id']);
            $file['source']['name'] = $source->getName();
            $file['source']['provider_name'] = $source->getProviderName();
        }

        $file['is_personal'] = filesRights::inst()->isFilesPersonal($file);

        return $file;
    }

    private function getFileContent($filepath, $offset = 0, $maxlen = null)
    {
        if ($maxlen !== null) {
            $contents = @file_get_contents($filepath, false, null, $offset, $maxlen);
        } else {
            $contents = @file_get_contents($filepath, false, null, $offset);
        }
        return $contents !== false ? $contents : '';
    }

    public function getTextFileShowMaxSize($str = false)
    {
        if ($str) {
            return filesApp::formatFileSize($this->getConfig()->getTextFileShowMaxSize());
        } else {
            return $this->getConfig()->getTextFileShowMaxSize();
        }
    }

    public function getComments($file_id)
    {
        $comments = $this->getFileCommentsModel()->getByFile($file_id);

        // Get info about authors of comments
        $contact_ids = filesApp::getFieldValues($comments, 'contact_id');
        $contacts = array();
        foreach ($contact_ids as $contact_id) {
            $contact = new waContact($contact_id);
            $name = $contact->getName();
            $photo_url = waContact::getPhotoUrl($contact->getId(), $contact['photo'], '20');
            $contacts[$contact_id] = array(
                'id' => $contact_id,
                'name' => $name,
                'photo_url' => $photo_url
            );
        }

        // extend comments by authors info
        $dummy = array(
            'id' => '',
            'name' => 'Unknown',
            'photo_url' => wa()->getRootUrl() . 'wa-content/img/userpic20.jpg'
        );
        foreach ($comments as &$comment) {
            $comment['author'] = ifset($contacts[$comment['contact_id']], $dummy);

            $comment['has_rights'] = $comment['contact_id'] == $this->contact_id
                || filesRights::inst()->hasFullAccess();
        }
        unset($comment);

        return $comments;
    }

    public function getAuthor($contact_id)
    {
        $contact = new waContact($contact_id);
        $author = array(
            'id' => $contact_id,
            'exists' => $contact->exists()
        );
        if ($contact->exists()) {
            $author['name'] = $contact->getName();
            $author['photo_url'] = waContact::getPhotoUrl($contact->getId(), $contact['photo'], '96');
            $author['photo_url_20'] = waContact::getPhotoUrl($contact->getId(), $contact['photo'], '20');
        }
        return $author;
    }

    public function getBreadcrumbs($file)
    {
        if ($file['parent_id']) {
            $path = $this->getFileModel()->getPathToFolder($file['parent_id']);
        } else {
            $storage = $this->getStorageModel()->getStorage($file['storage_id']);
            $path = array($storage);
        }
        $path[] = $file;
        return $path;
    }

    private function canRename($file_id)
    {
        $allowed = filesRights::inst()->dropUnallowedToMove(array($file_id));
        return !!$allowed;
    }

    private function canComment($file_id)
    {
        return filesRights::inst()->canCommentFile($file_id);
    }

    private function canEditTags($file_id)
    {
        return filesRights::inst()->hasFullAccessToFile($file_id);
    }

    public function getMarks()
    {
        $marks = $this->getFileModel()->getAvailableMarks();
        array_unshift($marks, '');
        return $marks;
    }

}
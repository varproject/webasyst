<?php

class filesFrontendFolderAction extends filesFrontendFilesAction
{
    public function filesExecute()
    {
        if (wa()->getRequest()->get('helper')) {
            $this->checkCORS();
            $vh = new filesViewHelper();
            echo $vh->folderHtml($this->getHash());
            exit;
        }

        $folder = $this->getFolder();
        $parent = $this->getFileModel()->getFolder($folder['parent_id']);

        wa()->getResponse()->setTitle($folder['name']);

        return array(
            'parent' => $parent,
            'folder' => $folder,
            'hash' => "folder/{$folder['id']}"
        );
    }

    public function getHash()
    {
        return wa()->getRequest()->param('hash', '', waRequest::TYPE_STRING_TRIM);
    }

    public function getFolder()
    {
        $hash = $this->getHash();
        if (!$hash) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
        }

        $folder_id = substr($hash, 16, -16);
        $hash = substr($hash, 0, 16) . substr($hash, -16);
        $folder = $this->getFileModel()->getFolder($folder_id);
        if (!$folder) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
        }

        if (!$folder['hash'] || $folder['hash'] !== $hash || $folder['storage_id'] <= 0) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
        }

        return $folder;
    }

    /**
     * Check Cross-origin resource sharing (CORS)
     * If we send ajax from site page (using view-helper) from one domain to another
     * we must set up special headers
     */
    public function checkCORS()
    {
        $all_domains = wa()->getRouting()->getDomains();

        $referer = wa()->getRequest()->server('HTTP_REFERER');
        $is_https = substr($referer, 0, 5) === 'https';

        $permitted_domain = '';
        foreach ($all_domains as $domain) {
            $pattern = "!^(http|https):\/\/" . preg_quote($domain) . "!";
            if (preg_match($pattern, $referer)) {
                $permitted_domain = $domain;
                break;
            }
        }

        if ($permitted_domain) {
            header("Access-Control-Allow-Origin: " . ($is_https ? 'https://' : 'http://') . $permitted_domain);
        }
    }

}
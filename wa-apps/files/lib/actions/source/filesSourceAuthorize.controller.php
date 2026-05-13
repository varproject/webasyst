<?php

class filesSourceAuthorizeController extends filesController
{
    public function execute()
    {
        if ($this->isBegin()) {
            try {
                $this->authorizeBegin();
            } catch (filesSourceAuthorizeFailedException $e) {
                $this->errors[] = array('title' => _w('Authorize failed'), 'msg' => $e->getMessage());
                return;
            }
        } else {
            try {
                $this->authorizeEnd();
            } catch (filesSourceAuthorizeFailedException $e) {
                $params = $e->getParams();
                if (!empty($params['source_id'])) {
                    $this->getStorage()->set('source/' . $params['source_id'] . '/auth_failed', $e->getMessage());
                    $this->redirectToEdit($params['source_id']);
                } else {
                    die($e->getMessage());
                }
            }
        }
    }

    public function isBegin()
    {
        return !wa()->getRequest()->get('authorize_end');
    }

    public function authorizeBegin()
    {
        $source_id = wa()->getRequest()->get('id');
        $is_renew = wa()->getRequest()->get('is_renew', 0);
        
        $source = filesSource::factory($source_id);
        if ($source->isNull()) {
            $this->reportAboutError(_w('Source not found'));
        }
        if ($source->getContactId() != $this->contact_id) {
            $this->reportAboutError($this->getAccessDeniedError());
        }
        $plugin = filesSourcePlugin::factory($source->getType());
        if (!$plugin) {
            $this->reportAboutError(_w('Source plugin not found'));
        }
        die($plugin->authorizeBegin($source_id, array('is_renew' => $is_renew)));
    }

    public function authorizeEnd()
    {
        $plugin_id = wa()->getRequest()->get('id');
        $plugin = filesSourcePlugin::factory($plugin_id);
        if (!$plugin) {
            $this->reportAboutError(_w('Source plugin not found'));
        }
        $res = $plugin->authorizeEnd();
        $res = is_array($res) ? $res : array();
        $source_id = (int)ifset($res['source_id']);
        $token = (string)ifset($res['token']);
        $source = filesSource::factory($source_id);
        if (!$source) {
            $this->reportAboutError(_w("Source not found"));
        }
        if ($source->getContactId() != $this->contact_id) {
            $this->reportAboutError($this->getAccessDeniedError());
        }
        $params = $res;
        unset($params['source_id'], $params['token']);

        $source->setToken($token);
        $source->addParams($params);
        $source->save();
        $this->redirectToEdit($source_id);
    }

    public function redirectToEdit($id)
    {
        $url = wa('files')->getUrl(true) . '#/source/' . $id . '/';
        die("<script>window.location.href = '{$url}';</script>");
    }

}

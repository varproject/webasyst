<?php

class shopB2bPluginFrontendBlogPostAction extends shopB2bPluginFrontendBaseAction
{
    public function execute()
    {
        $this->prepareB2b('blog');
        if (empty($this->access['allowed'])) {
            return;
        }
        $this->setTemplate(wa()->getAppPath('plugins/b2b/templates/actions/frontend/FrontendBlogPost.html', 'shop'));
    }
}

<?php

class filesTagFilesAction extends filesFilesAction
{
    public function filesExecute()
    {
        $tags = $this->getTags();
        return array(
            'hash' => 'tag/' . join(',', $tags),
            'hash_url' => 'tag/' . urlencode(join(',', $tags)),
            'url' => $this->getUrl() . "&tags=" . urlencode(join(',', $tags))
        );
    }

    public function getTags()
    {
        $tags_str = wa()->getRequest()->get('tags', '', waRequest::TYPE_STRING_TRIM);
        $tags = array();
        foreach (explode(',', $tags_str) as $tag) {
            $tag = trim($tag);
            if ($tag) {
                $tags[] = $tag;
            }
        }
        return $tags;
    }

}

<?php

class apanelCategoryTreeController extends waViewController
{
    public function execute()
    {
        $toggle_id = waRequest::get('toggle_id');

        

        $state = new apanelStateModel();
        $state->setState('toggle_id', [$toggle_id]);
    }
}

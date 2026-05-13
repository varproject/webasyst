<?php

class filesBackendLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();

        // Application locale strings
        foreach(array(
            "Couldn't save filter",         //  _w("Couldn't save filter")
            "Cancel",                       // _w("Cancel")
            "Couldn't create storage",      // _w("Couldn't create storage")
            "Server error",                 // _w("Server error")
            "Files from the same place",    // _w("Files from the same place")
            "Plugins",                      // _w("Plugins")
            "Close",                        // _w("Close")
            "Stop upload",                  // _w("Stop upload"),
            "Delete",                       // _w("Delete"),
            "Yes",                          // _w("Yes"),
            "cancel",                       // _w("cancel"),
            "Sync is in process. No changes available until synchronization finish.",    // _w("Sync is in process. No changes available until synchronization finish.")
            "Too many requests to external source service. Please try again in a few minutes.",     // _w("Too many requests to external source service. Please try again in a few minutes.")
            "MB",  // _w("MB")
            "KB",  // _w("KB")
            "B",    // _w("B")
            "GB"    // _w("GB")
        ) as $s) {
            $strings[$s] = _w($s);
        }

        // multiple forms
        foreach(array(
            array('Uploaded %d files', 'Uploaded %d file'),
            array('%d comments', '%d comment')
        ) as $s) {
            $forms = array(
                str_replace('1 ', '%d ', _w($s[1],$s[0],1)),
                str_replace('2 ', '%d ', _w($s[1],$s[0],2)),
                str_replace('5 ', '%d ', _w($s[1],$s[0],5))
            );
            $strings[$s[0]] = $forms;
        }

        $this->view->assign('strings', $strings ? $strings : new stdClass()); // stdClass is used to show {} instead of [] when there's no strings
        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}

<?php

waFiles::delete($this->getAppPath('lib/actions/storage/filesStorageMove.controller.php'));

$m = new waModel();
try {
    $m->query("SELECT sort FROM files_storage WHERE 0");
    $m->exec("ALTER TABLE files_storage DROP COLUMN sort");
} catch (waDbException $e) {

}
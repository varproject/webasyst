<?php

$m = new filesFileModel();

try {
    $m->query("SELECT count FROM files_file WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE files_file ADD COLUMN count INT(11) NOT NULL DEFAULT '0' AFTER size");
}

$m->updateCount();
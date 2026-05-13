<?php

$m = new waModel();
$m->exec("DELETE FROM files_file_rights WHERE group_id = 0");
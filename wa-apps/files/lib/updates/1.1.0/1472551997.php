<?php

$m = new waModel();

// add lock field
try {
    $m->query('SELECT `lock` FROM `files_copytask` WHERE 0');
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `files_copytask` ADD COLUMN `lock` VARCHAR (32) NULL DEFAULT NULL");
    $m->exec("CREATE INDEX `lock` ON `files_copytask` (`lock`)");
}

// add lock expired field
try {
    $m->query("SELECT `lock_expired_datetime` FROM `files_copytask` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `files_copytask` ADD COLUMN `lock_expired_datetime` DATETIME NULL DEFAULT NULL");
}

// add process id field, for copy in dialog and for move (explore code)
try {
    $m->query("SELECT `process_id` FROM `files_copytask` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `files_copytask` ADD COLUMN `process_id` BIGINT(20) NOT NULL DEFAULT '0'");
    $m->exec("CREATE INDEX `process_id` ON `files_copytask` (`process_id`)");
    $m->exec("CREATE INDEX `in_copy_process` ON `files_file` (`in_copy_process`)");
}

// is_move flag
try {
    $m->query("SELECT `is_move` FROM `files_copytask` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `files_copytask` ADD COLUMN `is_move` TINYINT(1) NOT NULL DEFAULT '0'");
}

// create index for source id for more fast search by this field
try {
    $m->exec("CREATE INDEX `source_id` ON `files_copytask` (`source_id`)");
} catch (waDbException $e) {
    // already created, ignore
}
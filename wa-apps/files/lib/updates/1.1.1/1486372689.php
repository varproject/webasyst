<?php



try {
    $_file_path = wa()->getAppPath('lib/classes/filesFileStream.class.php', 'files');
    if (file_exists($_file_path)) {
        waFiles::delete($_file_path);
    }
} catch (Exception $e) {

}


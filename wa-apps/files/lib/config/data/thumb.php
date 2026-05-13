<?php
/**
 * @todo check allowed sizes
 * @todo use resize options (quiality and filters)
 * @todo use error handlers to display error while resize
 */


$path = realpath(dirname(__FILE__)."/../../../../../");
$config_path =$path."/wa-config/SystemConfig.class.php";
if (!file_exists($config_path)) {
    header("Location: ../../../../../../wa-apps/photos/img/image-not-found.png");
    exit;
}

require_once($config_path);
$config = new SystemConfig();
waSystem::getInstance(null, $config);

$request_file = wa()->getConfig()->getRequestUrl(true, true);

if (preg_match("@^thumb.php/(.+)@", $request_file, $matches)) {
    $request_file = $matches[1];
}

$public_path = $path.'/wa-data/public/files/';
$protected_path = $path.'/wa-data/protected/files/';

$file = false;
$size = false;

/**
 * @var \filesConfig $app_config
 */
$app_config = wa('files')->getConfig();

$enable_2x = false;

if (preg_match('#^((?:\d{2}/){2}([0-9]+)(?:\.[0-9a-f]+)?)/\\2\.([a-h0-9]{32}).(\d+(?:x\d+)?)(@2x)?\.([a-z]{3,4})$#i', $request_file, $matches)) {
    $sid = $matches[3];
    $file = $matches[1] . '.' . $sid . '.' . $matches[6];
    $size = $matches[4];

    if ($file) {
        $thumbnail_sizes = $app_config->getPhotoSizes();
        if (in_array($size, $thumbnail_sizes) === false) {
            $file = false;
        }
    }

    if ($file && $matches[5] && $app_config->getPhotoEnable2x()) {
        $enable_2x = true;
        $size = explode('x', $size);
        foreach ($size as &$s) {
            $s *= 2;
        }
        unset($s);
        $size = implode('x', $size);
    }
}
wa()->getStorage()->close();

if ($file && file_exists($protected_path.$file) && !file_exists($public_path.$request_file)) {
    $pathinfo = filesApp::pathInfo($file);
    $file_id = str_replace(".{$sid}", '', $pathinfo['filename']);
    $thumnail = filesApp::inst()->getThumbnail(array(
        'id' => $file_id,
        'ext' => $pathinfo['ext'],
        'sid' => $sid
    ), array(
        'enable_2x' => $enable_2x
    ));

    if (!$thumnail) {
        $file = false;
    } else {
        $thumnail->generateOne($size, $public_path.$request_file);
        clearstatcache();
    }
}

if($file && file_exists($public_path.$request_file)){
    waFiles::readFile($public_path.$request_file);
} else{
    header("HTTP/1.0 404 Not Found");
    exit;
}

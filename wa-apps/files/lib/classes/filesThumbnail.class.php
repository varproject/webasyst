<?php

class filesThumbnail
{
    const SHARP_AMOUNT = 6;

    private $file;
    private $options;
    private $supported;


    public static function factory($file, $options, $check_supported = true)
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        // there checking obligatory keys
        foreach (array('id', 'ext', 'sid') as $key) {
            if (!array_key_exists($key, $file)) {
                throw new waException("No such key {$key}");
            }
        }

        if ($check_supported) {
            if (self::isExtSupported($file['ext'])) {
                return new filesThumbnail($file, $options);
            } else {
                return null;
            }
        }
        return new filesThumbnail($file, $options);
    }

    private function __construct($file, $options = array()) {
        $this->file = $file;

        if ($this->isSupported()) {

            if (!isset($this->file['path'])) {
                $this->file['path'] = filesApp::getFilePath($this->file['id'], $this->file['sid'], $this->file['ext']);
            }

            $config = filesApp::inst()->getConfig();

            // default options
            $this->options = array(
                'sharpen' => $config->getPhotoSharpen(),
                'max_size' => $config->getPhotoMaxSize(),
                'enable_2x' => $config->getPhotoEnable2x(),
                'quality' => $config->getPhotoSaveQuality($config->getPhotoEnable2x()),
                'main_size' => $config->getPhotoMainSize()
            );

            // redefine options
            foreach (array_keys($this->options) as $key) {
                if (!empty($options[$key])) {
                    $this->options[$key] = $options[$key];
                }
            }

            $this->options['main_thumb_info'] = array(
                'size' => $this->options['main_size'],
                'path' => $this->getPath($this->file, $this->options['main_size'])
            );
        }

    }

    public function generateAll($sizes)
    {
        foreach ($sizes as $size) {
            $this->generateOne($size);
        }
    }

    public function isSupported()
    {
        if ($this->supported === null) {
            $this->supported = self::isExtSupported($this->file['ext']);
        }
        return $this->supported;
    }

    public static function isExtSupported($ext)
    {
        return in_array($ext, self::getSupportedExts());
    }

    public static function getSupportedExts()
    {
        return array('png', 'jpg', 'jpeg', 'gif');
    }

    public function generateOne($size, $path_to_save = null)
    {
        $max_size = $this->options['max_size'];
        $enable_2x = $this->options['enable_2x'];
        $image = $this->generate(
            $this->options['main_thumb_info'],
            $this->file['path'],
            $size,
            $this->options['sharpen'],
            $max_size ? ($enable_2x ? 2 * $max_size : $max_size) : false
        );
        if ($image) {
            $path_to_save = $path_to_save ? $path_to_save : $this->getPath($this->file, $size);
            $image->save($path_to_save, $this->options['quality']);
        }
    }

    public function getThumbPath($size)
    {

    }

    private function generate($main_thumbnail_info, $original_path, $size, $sharpen = false, $max_size = false)
    {
        if (!file_exists($original_path)) {
            return null;
        }
        $main_thumbnail_path = $main_thumbnail_info['path'];
        $main_thumbnail_size = $main_thumbnail_info['size'];
        if (!file_exists($main_thumbnail_path)) {
            $size_info = self::parseSize($main_thumbnail_size);
            $type = $size_info['type'];
            $width = $size_info['width'];
            $height = $size_info['height'];
            if ($image = self::generateThumb($original_path, $type, $width, $height)) {
                $image->save($main_thumbnail_path);
            }
            $main_thumbnail_width = $image->width;
            $main_thumbnail_height = $image->height;
        } else {
            $image = waImage::factory($main_thumbnail_path);
            $main_thumbnail_width = $image->width;
            $main_thumbnail_height = $image->height;
        }

        $width = null;
        $height = null;
        $size_info = self::parseSize($size);
        $type = $size_info['type'];
        $width = $size_info['width'];
        $height = $size_info['height'];

        if (!$width && !$height) {
            return null;
        }
        switch($type) {
            case 'max':
                if (is_numeric($max_size) && $width > $max_size) {
                    return null;
                }
                if ($width > max($main_thumbnail_width, $main_thumbnail_height)) {
                    $image = waImage::factory($original_path);  // make thumb from original photo
                }
                break;
            case 'crop':
                if (is_numeric($max_size) && $width > $max_size) {
                    return null;
                }
                /* HERE ISN'T BREAK, BECAUSE REST PART OF THAT CASE IS SIMILAR WITH 'RECTANGLE' CASE */
            case 'rectangle':
                if (is_numeric($max_size) && ($width > $max_size || $height > $max_size)) {
                    return null;
                }
                if ($width > $main_thumbnail_width || $height > $main_thumbnail_height) {
                    $image = waImage::factory($original_path);  // make thumb from original photo
                }
                break;
            case 'width':
                $w = !is_null($width) ? $width : $height;
                $original_image = waImage::factory($original_path);
                $h = $original_image->height * ($w/$original_image->width);
                $w = min(round($w), $original_image->width);
                $h = min(round($h), $original_image->height);
                if ($w == $main_thumbnail_width && $h == $main_thumbnail_height) {
                    return $image;
                }
                if (is_numeric($max_size) && ($w > $max_size || $h > $max_size)) {
                    return null;
                }
                if ($w > $main_thumbnail_width || $h > $main_thumbnail_height) {
                    $image = $original_image;  // make thumb from original photo
                }
                break;
            case 'height':
                $h = !is_null($width) ? $width : $height;
                $original_image = waImage::factory($original_path);
                $w = $original_image->width * ($h/$original_image->height);
                $w = min(round($w), $original_image->width);
                $h = min(round($h), $original_image->height);
                if ($w == $main_thumbnail_width && $h == $main_thumbnail_height) {
                    return $image;
                }
                if (is_numeric($max_size) && ($w > $max_size || $h > $max_size)) {
                    return null;
                }
                if ($w > $main_thumbnail_width || $h > $main_thumbnail_height) {
                    $image = $original_image;  // make thumb from original photo
                }
                break;
            default:
                $type = 'unknown';
                break;
        }
        return self::generateThumb($image, $type, $width, $height, $sharpen);
    }

    public function getUrl($size = null, $absolute = false)
    {
        if (!$size) {
            $size = filesApp::inst()->getConfig()->getPhotoDefaultSize();
        }
        $path = $this->getFolder($this->file['id']).'/'.$this->file['id'];
        $path .= '/'.$this->file['id']. '.' . $this->file['sid'] . '.'.($size ?  $size.'.' : '').$this->file['ext'];


        if (waSystemConfig::systemOption('mod_rewrite')) {
            return wa()->getDataUrl($path, true, 'files', $absolute);
        } else {
            if (file_exists(wa()->getDataPath($path, true, 'files'))) {
                return wa()->getDataUrl($path, true, 'files', $absolute);
            } else {
                return wa()->getDataUrl('thumb.php/'.$path, true, 'files', $absolute);
            }
        }
    }

    public function getDir()
    {
        $path = $this->getFolder($this->file['id']).'/'.$this->file['id'];
        return wa()->getDataPath($path, true, 'files');
    }

    public function getPathsForDeleting()
    {
        return array($this->getDir());
    }

    public function getPath($file, $size)
    {
        $thumb_path = $this->getDir();
        return $thumb_path.'/'.$file['id']. '.' . $file['sid'] . '.' . $size . '.' . $file['ext'];
    }

    private function getFolder($file_id)
    {
        $str = str_pad($file_id, 4, '0', STR_PAD_LEFT);
        return substr($str, -2).'/'.substr($str, -4, 2);
    }

    protected static function generateThumb($path, $type, $width, $height, $sharpen = false)
    {
        $image = null;
        switch($type) {
            case 'crop':
                if ($path instanceof waImage) {
                    $image = $path;
                } else {
                    $image = waImage::factory($path);
                }
                $image->resize($width, $height, waImage::INVERSE)->crop($width, $height);
                break;
            case 'rectangle':
                if ($path instanceof waImage) {
                    $image = $path;
                } else {
                    $image = waImage::factory($path);
                }
                if ($width > $height) {
                    $w = $image->width;
                    $h = $image->width*$height/$width;
                    if ($h > $image->height) {
                        $h = $image->height;
                        $w = $image->height*$width/$height;
                    }
                } else {
                    $h = $image->height;
                    $w = $image->height*$width/$height;
                    if ($w > $image->width) {
                        $w = $image->width;
                        $h = $image->width*$height/$width;
                    }
                }
                $image->crop($w, $h)->resize($width, $height, waImage::INVERSE);
                break;
            case 'max':
            case 'width':
            case 'height':
                if ($path instanceof waImage) {
                    $image = $path;
                } else {
                    $image = waImage::factory($path);
                }
                $image->resize($width, $height);
                break;
            default:
                break;
        }
        if ($sharpen) {
            $image->sharpen(self::SHARP_AMOUNT);
        }
        return $image;
    }

    /**
     * Parsing size-code (e.g. 500x400, 500, 96x96, 200x0) into key-value array with info about this size
     *
     * @see Client-side has the same function with the same realization
     * @param string $size
     * @returns array
     */
    private static function parseSize($size)
    {
        $type = 'unknown';
        $ar_size = explode('x', $size);
        $width = !empty($ar_size[0]) ? $ar_size[0] : null;
        $height = !empty($ar_size[1]) ? $ar_size[1] : null;

        if (count($ar_size) == 1) {
            $type = 'max';
            $height = $width;
        } else {
            if ($width == $height) {  // crop
                $type = 'crop';
            } else {
                if ($width && $height) { // rectange
                    $type = 'rectangle';
                } else if (is_null($width)) {
                    $type = 'height';
                } else if (is_null($height)) {
                    $type = 'width';
                }
            }
        }
        return array(
            'type' => $type,
            'width' => $width,
            'height' => $height
        );
    }
}

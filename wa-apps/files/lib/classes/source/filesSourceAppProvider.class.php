<?php

/**
 * Class filesSourceAppProvider
 * @see filesSourceProvider for documentation
 */
class filesSourceAppProvider extends filesSourceProvider
{
    public function getTypeName()
    {
        return _w('Application');
    }

    public function getName()
    {
        return _w('Application');
    }

    /**
     * @param array $params
     * @return int|float
     */
    public function getUploadChunkSize($params = array())
    {
        return filesApp::toIntegerNumber(filesApp::inst()->getConfig()->getMemoryLimit() / 2);
    }

    public function beforeAdd($params)
    {
        return $params;
    }

    public function afterAdd($params)
    {
        return $this->afterAddOrReplace($params);
    }

    public function getRenameAllowance($params = null)
    {
        return true;
    }

    public function beforeReplace($data)
    {
        return $data;
    }

    public function afterReplace($params)
    {
        return $this->afterAddOrReplace($params);
    }

    public function beforeCopy($params)
    {
        if (!empty($params['call_type']) && $params['call_type'] === 'individual' && !empty($params['file'])) {
            $params['file']['source_id'] = 0;
            $params['file']['source_inner_id'] = null;
            $params['file']['source_path'] = null;
        }
        return $params;
    }

    public function afterCopy($params)
    {
        return $params;
    }


    public function getReplaceAllowance($params = null)
    {
        return true;
    }

    public function beforeRename($params)
    {
        return $params;
    }

    public function afterRename($params)
    {
        $files = $this->getFilesFromParams($params);
        if (!$files) {
            return $params;
        }
        $file = reset($files);

        if ($file['type'] !== filesFileModel::TYPE_FILE || $file['prev_ext'] === $file['ext']) {
            // ignore these cases
            return $params;
        }

        $cur_file = $file;
        $prev_file = $file;
        $prev_file['name'] = $file['prev_name'];
        $prev_file['ext'] = $file['prev_ext'];
        $prev_file['sid'] = $file['prev_sid'];


        $prev_path = $this->getFilePath($prev_file);
        $cur_path = $this->getFilePath($cur_file);
        if (file_exists($prev_path) && $prev_path !== $cur_path) {
            waFiles::move($prev_path, $cur_path);
            foreach ($this->getPathsForDeleting($prev_file) as $path) {
                waFiles::delete($path);
            }
            $this->generateAllThumbs($cur_file);
        }

        return $params;
    }

    public function beforeDelete($params)
    {
        return $params;
    }

    public function afterDelete($params)
    {
        $items = $this->getFilesFromParams($params);
        if (!$items) {
            return $params;
        }

        foreach ($items as $item) {
            if ($item['type'] === filesFileModel::TYPE_FILE) {
                $rm_paths = $this->getPathsForDeleting($item);
                $sleep = 1;
                for ($try = 1; $try < 3; $try += 1) {
                    foreach ($rm_paths as $index => $path) {
                        try {
                            waFiles::delete($path);
                            unset($rm_paths[$index]);
                        } catch (waException $e) {
                        }
                    }
                    if (empty($rm_paths)) {
                        break;
                    }
                    sleep($sleep);
                    $sleep *= 2;
                }
            }
        }



        return $params;
    }

    public function getMoveAllowance($params = null)
    {
        return true;
    }

    public function beforeMove($params)
    {
        return $params;
    }

    public function afterMove($params)
    {
        return $params;
    }

    public function beforeMoveToTrash($params)
    {
        return $params;
    }

    public function afterMoveToTrash($params)
    {
        return $params;
    }

    public function afterPerformCopytask($params)
    {
        if ($params['copytask_file_role'] !== 'target') {
            return $params;
        }
        try {
            $thumbnail = filesApp::inst()->getThumbnail($params['task']['target_file']);
            if ($thumbnail) {
                $sizes = filesApp::inst()->getConfig()->getPhotoSizes();
                $thumbnail->generateAll($sizes);
            }
        } catch (Exception $e) {
        }

        return $params;
    }

    public function getAttachmentInfo($params)
    {
        $file = $params['file'];
        return array('path' => $this->getFilePath($file), 'name' => $file['name']);
    }

    private function afterAddOrReplace($params)
    {
        $files = $this->getFilesFromParams($params);
        if (!$files) {
            return $params;
        }
        $key = key($files);
        $data = $files[$key];

        if ($data['type'] === filesFileModel::TYPE_FILE) {
            $res = $this->uploadFile($data);
            $data = array_merge($data, $res);
        }

        $files[$key] = $data;
        $params = $this->setFilesToParams($files, $params);
        return $params;
    }

    private function uploadFile($data)
    {
        $file_path = $this->getFilePath($data);
        if ((file_exists($file_path) && !is_writable($file_path)) ||
            (!file_exists($file_path) && !waFiles::create($file_path))
        ) {
            throw new waException(sprintf(
                    _w("Insufficient file write permissions for folder %s."),
                    substr($file_path, strlen(filesApp::inst()->getConfig()->getRootPath())))
            );
        }

        $file = $data['upload_file'];

        // move file
        if (filesApp::isRequestFile($file)) {
            if (!$file->uploaded()) {
                throw new waException("File is not uploaded");
            }
            $file->moveTo($file_path);
        } else if (filesApp::isStream($file)) {
            $dst_fh = @fopen($file_path, 'wb');
            if (!$dst_fh) {
                sleep(1);
                $dst_fh = @fopen($file_path, 'wb');
                if (!$dst_fh) {
                    throw new waException("Can't move file to {$file_path}");
                }
            }
            stream_copy_to_stream($file, $dst_fh);
        }

        $original_file_path = $file_path;

        if ($data['file_type'] === filesConfig::FILE_TYPE_IMAGE) {
            $exif_data = filesPhotoExif::getInfo($file_path);
            if (!empty($exif_data['Orientation'])) {
                $image = waImage::factory($file_path);
                if ($image) {
                    $image_changed = $this->correctOrientation($exif_data['Orientation'], $image);
                    if ($image_changed) {
                        $original_file_path = $file_path . '.original';
                        waFiles::move($file_path, $original_file_path);
                        $config = filesApp::inst()->getConfig();
                        $quality = $config->getPhotoSaveQuality($config->getPhotoEnable2x());
                        $image->save($file_path, $quality);
                    }
                }
            }
        }

        $this->generateAllThumbs($data);

        return array(
            'size' => filesize($original_file_path),
            'md5_checksum' => md5_file($original_file_path)
        );
    }

    private function correctOrientation($orientation, waImage $image)
    {
        $angles = array(
            3 => '180', 4 => '180',
            5 => '90',  6 => '90',
            7 => '-90', 8 => '-90'
        );
        if (isset($angles[$orientation])) {
            $image->rotate($angles[$orientation]);
            return true;
        }
        return false;
    }

    private function generateAllThumbs($file)
    {
        $thumbnail = filesApp::inst()->getThumbnail($file);
        if ($thumbnail) {
            $photo_sizes = filesApp::inst()->getConfig()->getPhotoSizes();
            $thumbnail->generateAll($photo_sizes);
        }
    }


    public function getFilePath($file)
    {
        return filesApp::getFilePath($file['id'], $file['sid'], $file['ext']);
    }

    public function getFilePhotoUrls($file)
    {
        $photo_url = array();
        if (!empty($file['in_copy_process'])) {
            return array();
        }
        $thumbnail = filesApp::inst()->getThumbnail($file);
        if ($thumbnail) {
            foreach (filesApp::inst()->getConfig()->getPhotoSizes() as $size_name => $size) {
                $photo_url[$size_name] = $thumbnail->getUrl($size);
            }
        }
        return $photo_url;
    }

    private function getPathsForDeleting($file)
    {
        $thumbnail = filesApp::inst()->getThumbnail($file);
        $rm_paths = array();
        if ($thumbnail) {
            $rm_paths = $thumbnail->getPathsForDeleting();
        }
        $rm_paths[] = isset($file['path']) ? $file['path'] : $this->getFilePath($file);
        return $rm_paths;
    }

    public function download($file, $type = filesSource::DOWNLOAD_STDOUT, $options = array())
    {
        if (!isset($file['path'])) {
            $file['path'] = $this->getFilePath($file);
        }
        $path = $file['path'];
        if (file_exists($path.'.original')) {
            $path .= '.original';
        }
        if ($type === filesSource::DOWNLOAD_STDOUT) {
            waFiles::readFile($path, $file['name'], true);
            return true;
        } else if ($type === filesSource::DOWNLOAD_FILEPATH || $type === filesSource::DOWNLOAD_STREAM) {

            $stream = @fopen($path, 'rb');
            if (!$stream) {
                sleep(1);
                $stream = @fopen($path, 'rb');
            }

            if ($type === filesSource::DOWNLOAD_STREAM) {
                return $stream;
            }

            if ($type === filesSource::DOWNLOAD_FILEPATH) {

                if (!isset($options['filepath'])) {
                    throw new filesSourceUnknownTypeException();
                }

                $output_stream = @fopen((string)$options['filepath'], 'wb');
                if (!$output_stream) {
                    sleep(1);
                    $output_stream = @fopen((string)$options['filepath'], 'wb');
                    if (!$output_stream) {
                        return false;
                    }
                }
                stream_copy_to_stream($stream, $output_stream);
                return true;
            }

        } else {
            throw new filesSourceUnknownTypeException();
        }

        return false;
    }

    public function downloadChunk($file, $offset, $chunk_size)
    {
        $stream = $this->download($file, filesSource::DOWNLOAD_STREAM);
        if (!$stream) {
            throw new filesSourceException("File doesn't exist");
        }
        @fseek($stream, $offset);
        return stream_get_contents($stream, $chunk_size, $offset);
    }

    public function upload($file, $data)
    {
        if (!isset($file['path'])) {
            $file['path'] = $this->getFilePath($file);
        }
        $path = $file['path'];
        if (file_exists($path.'.original')) {
            $path .= '.original';
        }

        $done = false;

        /**
         * @var waRequestFile $data
         */
        if (filesApp::isRequestFile($data)) {
            $data->moveTo($path);
            $done = true;
        }

        /**
         * @var resource $data stream
         */
        if (!$done && filesApp::isStream($data)) {
            $stream = @fopen($path, 'wb');
            if (!$stream) {
                sleep(1);
                $stream = @fopen($path, 'wb');
            }
            if (!$stream) {
                return false;
            }
            stream_copy_to_stream($data, $stream);
            $done = true;
        }

        if (!$done) {
            $data = (string)$data;
            file_put_contents($path, $data);
            $done = true;
        }

        return $done ? array('size' => filesize($path), 'md5_checksum' => md5_file($path)) : false;
    }

    public function uploadChunk($file, $offset, $chunk)
    {
        if (!isset($file['path'])) {
            $file['path'] = $this->getFilePath($file);
        }
        $path = $file['path'];
        if (file_exists($path.'.original')) {
            $path .= '.original';
        }
        $stream = @fopen($path, 'cb');
        if (!$stream) {
            sleep(1);
            $stream = @fopen($path, 'crb');
        }
        if (!$stream) {
            return false;
        }
        @fseek($stream, $offset);
        $len = fwrite($stream, $chunk);
        $offset += (int) $len;
        return $offset;
    }

    public function pullChunk($progress_info = array())
    {
        return null;
    }

    private function getFilesFromParams($params)
    {
        $files = ifset($params['files'], array());
        reset($files);
        return $files;
    }

    private function setFilesToParams($files, $params)
    {
        $params['files'] = $files;
        reset($params['files']);
        return $params;
    }

}

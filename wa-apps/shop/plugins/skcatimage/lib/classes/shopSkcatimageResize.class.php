<?php

class shopSkcatimageResize{

    public static function generateThumb($path, $width, $height){
        $image = null;

        if($path instanceof waImage){
            $image = $path;
        }else{
            $image = waImage::factory($path);
        }

        if($width && $height && $image->width <= $width && $image->height <= $height){
            return $image;
        }elseif($width && $height && ($width == $height || $image->width < $width || $image->height < $height)){
            $image->resize($width, $height, waImage::INVERSE)->crop($width, $height);
            return $image;
        }elseif($width && !$height && $image->width <= $width){
            return $image;
        }elseif(!$width && $height && $image->height <= $height){
            return $image;
        }elseif(!$width || !$height){
            $image->resize($width, $height, waImage::INVERSE);
            return $image;
        }

        if($width > $height){
            $w = $image->width;
            $h = $image->width * $height / $width;
            if($h > $image->height){
                $h = $image->height;
                $w = $image->height * $width / $height;
            }
        }else{
            $h = $image->height;
            $w = $image->height * $width / $height;
            if($w > $image->width){
                $w = $image->width;
                $h = $image->width * $height / $width;
            }
        }

        $image->crop($w, $h)->resize($width, $height, waImage::INVERSE);

        return $image;
    }

    public static function getName2X($filename){
        $filename_2x = explode(".", $filename);
        if(count($filename_2x) > 1){
            $filename_2x[(count($filename_2x) - 2)] .= "@2x";
        }else{
            $filename_2x[0] .= "@2x";
        }

        $filename_2x = implode(".", $filename_2x);

        return $filename_2x;
    }

}
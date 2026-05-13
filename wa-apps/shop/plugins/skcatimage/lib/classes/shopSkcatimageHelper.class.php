<?php

/**
 * Класс Хелпер для запросов изображений категории
 */
class shopSkcatimageHelper{

    static public $all = null;

    public static function getImages($category_id = null, $group_name = null){

        $plugin_id = "skcatimage";

        $settings = wa("shop")->getPlugin($plugin_id)->getSettings();

        if(!$settings["status"]){
            return array();
        }

        $data = self::$all;
        $cache = null;

        if(empty($data)){

            if($settings["is_cache"]){
                $cache = new waSerializeCache('shopSkcatimage', 3600, 'shop/skcatimage');
                if ($cache && $cache->isCached()) {
                    $data = $cache->get();
                }
            }

            if(empty($data)){

                $dataModel = new shopSkcatimageDataModel();
                $data = $dataModel->getAllImages();
                if($settings["is_cache"] && ifset($cache)){
                    $cache->set($data);
                }

            }

            self::$all = $data;

        }

        if(!empty($data) && $category_id){
            if(isset($data[$category_id])){
                $data = $data[$category_id];
                if($group_name){
                    if(isset($data[$group_name])){
                        $data = $data[$group_name];
                    }else{
                        $data = "";
                    }
                }
            }else{
                $data = array();
            }
        }

        return $data;

    }

    public static function translitFile($filename){

        $converter = array(
            'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
            'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
            'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
            'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
            'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
            'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
            'э' => 'e',    'ю' => 'yu',   'я' => 'ya',

            'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
            'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
            'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
            'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
            'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
            'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
            'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya',
        );

        if (!empty($filename)) {
            $filename = str_replace(array(' ', ','), '_', $filename);
            $filename = strtr($filename, $converter);
            $filename = mb_ereg_replace('[-]+', '-', $filename);
            $filename = trim($filename, '-');
        }

        return $filename;
    }

}
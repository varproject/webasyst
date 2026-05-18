<?php

if (!function_exists('dd')) {
    function dd($arr = [])
    {
        if (empty($arr)) {
            return [];
        }

        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }
}

if (!function_exists('ddd')) {
    function ddd($arr = [])
    {
        if (empty($arr)) {
            return [];
        }

        echo '<pre>';
        print_r($arr);
        echo '</pre>';

        die();
    }
}
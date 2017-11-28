<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('get_language_name')) {

    function get_language_name($code = '')
    {
        $ci =& get_instance();
        $codes = $ci->config->item('available_languages');

        return $codes[$code]['name'];
    }
}

if (!function_exists('get_language_native')) {
    function get_language_native($code = '')
    {
        $ci =& get_instance();
        $codes = $ci->config->item('available_languages');

        return $codes[$code]['native'];


    }
}

if (!function_exists('get_code')) {
    function get_code($lang = '')
    {
        $ci =& get_instance();
        $codes = $ci->config->item('available_languages');
        $index = array_search($lang, array_column($codes, 'name'));

        return array_keys($codes)[$index];
    }
}

if (!function_exists('get_languages')) {
    function get_languages()
    {
        $ci =& get_instance();
        $codes = $ci->config->item('available_languages');

        return $codes;
    }
}

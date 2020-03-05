<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('set_null')) {

    function set_null($val)
    {
        if ($val == '') {
            return null;
        } else {
            return $val;
        }
    }
}

if (!function_exists('set_bool')) {

    function set_bool($val)
    {
        if ($val == '' || $val == null) {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('set_datestr')) {

    function set_datestr($val)
    {
        if (empty($val)) {
            return '';
        }

        return date_format(date_create($val),'Y-m-d H:i:s');
    }
}

if (!function_exists('set_check')) {

    function set_check_icon($true)
    {
        if ($true) {
            return "<span class='glyphicon glyphicon-ok' aria-hidden='true'></span>";
        } else {
            return "";  //"<span class='glyphicon glyphicon-minus' aria-hidden='true'></span>";
        }
    }
}

if (!function_exists('set_arr')) {

    function set_arr($val)
    {
        if ($val != null){
            $blids = implode($val,',');
            if ($blids != ''){
                return '{' . $blids . '}';
            }
        }

        return $val;
    }
}

if (!function_exists('get_first')) {

    function get_first($val)
    {
        $space = strpos($val,' ');
        if($space === FALSE) {
            return $val;
        } else {
            return explode(' ',$val)[0];
        }
    }
}



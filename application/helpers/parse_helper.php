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
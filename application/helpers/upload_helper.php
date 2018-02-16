<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('check_main_upload_dir')) {

    function check_main_upload_dir()
    {
        $ci =& get_instance();
        $dir = set_realpath($ci->config->item('main_upload_dir'), false);

        $exist = file_exists($dir);

        if ($exist) {
            return '';
        } else {
            return '<div class="alert alert-danger">' . $ci->lang->line('gp_upload_no_folder') . '<b>'.$dir . '</b></div>';
        }
    }
}
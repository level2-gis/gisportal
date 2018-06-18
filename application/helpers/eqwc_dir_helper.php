<?php
require_once(__DIR__.'/../../../gisapp/admin/settings.php');

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
            return $ci->lang->line('gp_upload_no_folder') . '<b>'.$dir . '</b>';
        }
    }
}

if (!function_exists('check_qgis_project')) {

    function check_qgis_project($project, $file, $client)
    {
        $valid = false;
        $name = "";

       //first check if project has full path stored in db and if that exist and is readable
        if (!empty($file)) {
            if (is_readable($file)) {
                $valid = true;
            }
            return ["valid" => $valid, "name" => $file];
        }

        //check if the project can be found in main project folder
        $fn = $project.'.qgs';
        if (is_readable(PROJECT_PATH . $fn)) {
            return ["valid" => true, "name" => PROJECT_PATH . $fn];
        }

        //check if project is in client subfolder
        if (is_readable(PROJECT_PATH . $client . DIRECTORY_SEPARATOR . $fn)) {
            return ["valid" => true, "name" => PROJECT_PATH . $client . DIRECTORY_SEPARATOR . $fn];
        }

        //default return
        return ["valid" => $valid, "name" => "Project not found:</br>".PROJECT_PATH . $fn.'<br>'.PROJECT_PATH . $client . DIRECTORY_SEPARATOR . $fn];
    }
}

if (!function_exists('get_qgis_project_path')) {

    function get_qgis_project_path()
    {
        return set_realpath(PROJECT_PATH);
    }
}

if (!function_exists('get_ogr')) {

    function get_ogr()
    {
        return str_replace('ogr2ogr','',OGR2OGR);
    }
}
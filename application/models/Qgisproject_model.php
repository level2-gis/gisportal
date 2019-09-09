<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../../../gisapp/admin/settings.php');

class Qgisproject_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('path'));
        $this->main_path = set_realpath(PROJECT_PATH);
    }

    public $qgs_file    = null;
    public $qgs_xml     = null;
    public $error       = null;
    public $main_path   = null;

    /**
     * Default location of QGIS project file when uploading or using template
     * Depends on settings
     */
    public function get_default_qgs_project_path($first,$second = NULL) {
        $ci =& get_instance();
        $config = $ci->config->item('qgis_project_default_location');

        $dir = '';

        switch($config) {
            case QGS_MAIN :
                $dir = set_realpath(PROJECT_PATH);
                break;
            case QGS_CLIENT :
                $dir = set_realpath(PROJECT_PATH . $first);
                break;
            case QGS_GROUP :
                $dir = set_realpath(PROJECT_PATH . $first . DIRECTORY_SEPARATOR . $second);
        }

        if(!file_exists($dir) && !empty($dir)) {
            mkdir($dir,0777, true);
        }
        return $dir;
    }

    public function check_qgs_file($project_id) {

        $message = '';
        $valid = FALSE;
        $config = $this->config->item('qgis_project_default_location');

        try {
            //$this->load->project_model();

            //get other project attributes
            $project = $this->project_model->get_project($project_id, TRUE);
            if(empty($project)) {
                throw new Exception('Project ID unknown: '.$project_id);
            }

            //TODO what about different case and qgz extension!?!
            $project_file = $project->name . '.qgs';

            //first check if project has full path stored in db and if that exist and is readable
            if (!empty($project->project_path)) {
                if (is_readable($project->project_path)) {
                    $valid = true;
                    $message = $project->project_path;
                } else {
                    $message = "Project: " . $project_file . ' not found in:</br>' . $project->project_path;
                }
                return ["valid" => $valid, "name" => $message];
            }

            if($config===QGS_MAIN || $config === QGS_CLIENT) {

                //check if the project can be found in main project folder
                if (is_readable($this->main_path . $project_file)) {
                    $valid = true;
                    $message = $this->main_path . $project_file;
                    return ["valid" => $valid, "name" => $message];
                }

                //check if project is in client subfolder
                if (is_readable($this->main_path . $project->client_name . DIRECTORY_SEPARATOR . $project_file)) {
                    $valid = true;
                    $message = $this->main_path . $project->client_name . DIRECTORY_SEPARATOR . $project_file;
                    return ["valid" => $valid, "name" => $message];
                }


            } elseif ($config === QGS_GROUP) {
                $project->project_path = $this->main_path . $project->client_name . DIRECTORY_SEPARATOR . $project->group_name . DIRECTORY_SEPARATOR . $project_file;

                //check if project is in client/group subfolder
                if (is_readable($project->project_path)) {
                    $valid = true;
                    $message = $project->project_path;
                    //store found project_path to database for gisapp
                    $this->project_model->upsert_project(array("id" => $project->id, "project_path" => $project->project_path));
                } else {
                    //get group
                    $group = $this->project_group_model->get_project_group($project->group_id, TRUE);
                    if(!empty($group->parent_id)) {
                        //look if exists in menu group
                        $project->project_path = $this->main_path . $project->client_name . DIRECTORY_SEPARATOR . $group->parent . DIRECTORY_SEPARATOR . $project->group_name . DIRECTORY_SEPARATOR . $project_file;
                        if (is_readable($project->project_path)) {
                            $valid = true;
                            $message = $project->project_path;
                            //store found project_path to database for gisapp
                            $this->project_model->upsert_project(array("id" => $project->id, "project_path" => $project->project_path));
                        } else {
                            $message = "Project: " . $project_file . ' not found in:</br>' . $project->project_path;
                        }
                    } else {
                        $message = "Project: " . $project_file . ' not found in:</br>' . $project->project_path;
                    }
                }

                return ["valid" => $valid, "name" => $message];
            }

            //project not found, report directory only regarding setting
            $message = "Project: ". $project_file . ' not found in:</br>'. $this->get_default_qgs_project_path($project->client_name,$project->group_name);
            return ["valid" => $valid, "name" => $message];

        } catch (Exception $e) {
            $message = $e->getMessage();
            return ["valid" => $valid, "name" => $message];
        }
    }

    public function read_qgs_file() {
        $xml = null;
        try {
            libxml_clear_errors();
            libxml_use_internal_errors(true);
            if (file_exists($this->qgs_file) && is_readable($this->qgs_file)) {
                $xml = simplexml_load_file($this->qgs_file);
                if (!$xml) {
                    throw new Exception('Project not valid XML: ' . $this->qgs_file);
                }
            } else {
                throw new Exception('Project not found or no permission: ' . $this->qgs_file);
            }
            $this->qgs_xml = $xml;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

    public function write_qgs_file($file) {
        if(empty($this->qgs_xml)) {
            return false;
        }
        if(!empty($this->error)) {
            return false;
        }
        return $this->qgs_xml->asXml($file);
    }

    public function get_layer_by_id($id) {
        try {
            $xpath = '//maplayer/id[.="' . $id . '"]/parent::*';
            if (!$layer = $this->qgs_xml->xpath($xpath)) {
                throw new Exception("layerid not found: ".$id);
            }
            return $layer[0];
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function get_layer_info(SimpleXMLElement $layer) {

        // Datasource
        $datasource = (string)$layer->datasource;

        // Parse datasource
        $ds_parms = array(
            'provider' => (string)$layer->provider,
            'type' => '',
            'geom_column' => '',
            'crs' => (string)$layer->srs->spatialrefsys->authid,
            'sql' => '',
            'key' => ''
        );

        //only for postgres and spatialite layers
        if ((string)$layer->provider == 'postgres' or (string)$layer->provider == 'spatialite') {

            // First extract sql=
            if (preg_match('/sql=(.*)/', $datasource, $matches)) {
                $datasource = str_replace($matches[0], '', $datasource);
                $ds_parms['sql'] = $matches[1];
            }
            //extract table name same way
            if (preg_match('/table=(.*)/', $datasource, $matches)) {
                $datasource = str_replace($matches[0], '', $datasource);

                // parse (geom)
                if (preg_match('/\(([^\)]+)\)/', $matches[0], $match)) {
                    $ds_parms['geom_column'] = $match[1];
                }
                $ds_parms['table'] = trim($matches[1]);
                if (array_key_exists(0,$match)) {
                    $ds_parms['table'] = trim(str_replace($match[0], '', $ds_parms['table']));
                }
            }

            foreach (explode(' ', $datasource) as $token) {
                $kvn = explode('=', $token);
                if (count($kvn) == 2) {
                    $ds_parms[$kvn[0]] = trim($kvn[1],"'");
                } else { // Parse (geom)
                    if (preg_match('/\(([^\)]+)\)/', $kvn[0], $matches)) {
                        $ds_parms['geom_column'] = $matches[1];
                    }
                    // ... maybe other parms ...
                }
            }

            //read user and pass for authcfg from settings
            if(isset($ds_parms['authcfg']) && defined('AUTHCFG')) {
                $auth = AUTHCFG;
                $cfg = $auth[$ds_parms['authcfg']];
                if(!empty($cfg)) {
                    $ds_parms['user'] = $cfg['user'];
                    $ds_parms['password'] = $cfg['password'];
                }
            }
        }
        return $ds_parms;
    }

    public function get_layer_feature_count($connection_string, $table) {
        try {
            $mycmd = $this->get_ogr() .'ogrinfo -so "' . $connection_string . '" ' . $table;
            $output = shell_exec($mycmd);

            $matches = array();
            preg_match('/Feature Count:(.*)/', $output, $matches);

            if(is_numeric($matches[1])) {
                return intval($matches[1]);
            } else {
                throw new Exception('Cant get feature count with ogrinfo');
            }


        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return -1;
        }
    }

    /**
     * This is here because of gisapp setting, later you can use this to work with ogr:
     * https://github.com/geo6/php-gdal-wrapper
     */
    public function get_ogr()
    {
        return str_replace('ogr2ogr','',OGR2OGR);
    }
}
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Qgisproject_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public $qgs_file    = null;
    public $qgs_xml     = null;
    public $error       = null;

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
        }
        return $ds_parms;
    }

    public function get_layer_feature_count($connection_string, $table) {
        try {
            $mycmd = get_ogr() .'ogrinfo -so "' . $connection_string . '" ' . $table;
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
}
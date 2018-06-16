<?php
class Plugin_model extends CI_Model
{

    public function __construct()
    {
        $this->load->database();
    }

    public function get_plugins_with_project_flag($ids) {

        $sql = "SELECT id, name, ";
        if (empty($ids)) {
            $sql.="0 AS idx ";
        } else {
            $sql.= "idx('".$ids."',id) ";
        }
        $sql.= "FROM plugins ORDER by name;";
        $query = $this->db->query($sql);

        return $query->result_array();
    }
}
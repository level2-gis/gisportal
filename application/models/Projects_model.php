<?php
class Projects_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    public function get_projects($client_id = FALSE, $user_projects = FALSE)
    {
        if($user_projects === NULL) {
            return null;
        }

        $this->db->order_by('client', 'ASC');
        $this->db->order_by('display_name', 'ASC');
        if ($client_id === FALSE)
        {
            $this->db->where("id = ANY('".$user_projects."')");
            $query = $this->db->get('projects_view');
            return $query->result_array();
        }

        //$user_projects
        $this->db->where("client_id = ".$client_id." AND id = ANY('".$user_projects."')");
        $query = $this->db->get('projects_view');
        return $query->result_array();
    }

    public function get_public_projects()
    {
        $this->db->order_by('client', 'ASC');
        $this->db->order_by('display_name', 'ASC');
        $this->db->where("public = TRUE");
        $query = $this->db->get('projects_view');

        return $query->result_array();
    }
}
<?php
class Projects_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    public function get_projects($client_id = FALSE, $user_projects = FALSE)
    {
        $this->db->order_by('display_name', 'ASC');
        if ($client_id === FALSE)
        {
            $query = $this->db->get('projects_view');
            return $query->result_array();
        }

        //$user_projects
        $this->db->where("client_id = ".$client_id." AND id = ANY('".$user_projects."')");
        $query = $this->db->get('projects_view');
        return $query->result_array();
    }
}
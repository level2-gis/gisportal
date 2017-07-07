<?php
class Projects_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    public function get_projects($client_id = FALSE)
    {
        if ($client_id === FALSE)
        {
            $query = $this->db->get('projects');
            return $query->result_array();
        }

        $query = $this->db->get_where('projects', array('client_id' => $client_id));
        return $query->result_array();
    }
}
<?php
class Clients_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    public function get_clients($user_projects = FALSE)
    {
        $this->db->order_by('display_name', 'ASC');

        //if ($user_projects === FALSE) {
        //    $query = $this->db->get('clients_view');
        //    return $query->result_array();
        //}

        if($user_projects === NULL) {
            return null;
        }

        //$user_projects
        $this->db->where("project_ids && '".$user_projects."'");
        $query = $this->db->get('clients_view');
        return $query->result_array();
    }

    public function get_client_by_id($id)
    {
        $this->db->where('id', $id);
        $query = $this->db->get('clients');
        return $query->result();
    }
}
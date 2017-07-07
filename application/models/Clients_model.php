<?php
class Clients_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    public function get_clients()
    {
        $query = $this->db->get('clients');
        return $query->result_array();
    }
}
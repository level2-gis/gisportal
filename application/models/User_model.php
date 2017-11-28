<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model
{
	function __construct()
    {
        parent::__construct();
    }

    function get_user($key, $pwd)
    {
        $this->db->where('user_email', $key);
        $this->db->or_where('user_name', $key);
        $user = $this->db->get('users')->result();

        $pass = password_verify($pwd, $user[0]->user_password_hash);

        if ($pass) {
            return $user;
        } else {
            return null;
        }
    }
	function get_user_by_name($key) {
		$this->db->where('user_name', $key);
		$query = $this->db->get('users');

		return $query->result();	
	}

	// get user
	function get_user_by_id($id)
	{
		$this->db->where('user_id', $id);
        $query = $this->db->get('users');
		return $query->result();
	}
	
	// insert
	function insert_user($data)
    {
		return $this->db->insert('users', $data);
	}

    function update_user($id, $sql)
    {
        //$this->db->where('user_id', $id);
        //$this->db->update('users', $data);

        $sql.= " WHERE user_id = ".$id;

        //returns bool
        $result = $this->db->query('UPDATE users SET '.$sql);
    }
}

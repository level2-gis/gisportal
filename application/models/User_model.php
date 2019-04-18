<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model
{
	function __construct()
    {
        parent::__construct();
    }


    function get_users()
    {
        $this->db->order_by('user_name', 'ASC');
        $query = $this->db->get('users_view');
        return $query->result_array();
    }

    /**
     * This method by default does not return administrators
     *
     * @param $text
     * @return mixed
     */
    function search($text) {

        //$this->db->like('first_name', $text);
        //$this->db->or_like('last_name', $text);
        //$this->db->or_like('user_email', $text);

        //for ilike search we have to use direct sql
        $where = "(first_name ILIKE '%".$text."%' ESCAPE '!' OR ";
        $where.= "last_name ILIKE '%".$text."%' ESCAPE '!' OR ";
        $where.= "user_email ILIKE '%".$text."%' ESCAPE '!')";

        $this->db->select("user_id AS id, trim(coalesce(last_name,'') || ' ' || coalesce(first_name,'')) || ' (' || user_email || ')' AS name", FALSE);
        $this->db->where($where);
        $this->db->where('admin', FALSE);
        $this->db->order_by('name', 'DESC');

        $query = $this->db->get('users_view');
        return $query->result_array();
    }

    /*
     * Return users with selected field (true/false) meaning permission to use given project id
     */
//    public function get_users_with_project_flag($project_id) {
//
//        $sql = "select user_id, user_name, user_email, display_name, ";
//        if ($project_id != null){
//            $sql = $sql . $project_id . " = ANY(project_ids) selected";
//        } else{
//            $sql = $sql . "false selected";
//        }
//        $sql = $sql . " FROM users WHERE admin = false order by user_email";
//        $query = $this->db->query($sql);
//
//        return $query->result_array();
//    }

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

//	function get_user_by_name($key) {
//		$this->db->where('user_name', $key);
//		$query = $this->db->get('users');
//
//		return $query->result();
//	}

    /**
     * Single project group role insert
     * @param $data
     * @return mixed
     *
     */
    function insert_project_group_role($data) {

        return $this->db->insert('users_roles', $data);
    }

    function insert_project_group_roles($data) {

        $return = $this->db->insert_batch('users_roles',$data);
    }

    function update_project_group_role($group_id, $user_id, $role_id) {

        $this->db->set('role_id', $role_id);
        $this->db->where('user_id', $user_id);
        $this->db->where('project_group_id', $group_id);
        $this->db->update('users_roles');

        if ($this->db->affected_rows() === 1)
        {
            return TRUE;
        }
        return FALSE;
    }

    function delete_project_group_role($group_id, $user_id) {

        if(!empty($user_id)) {
            $this->db->where('user_id', $user_id);
        }
        if(!empty($group_id)) {
            $this->db->where('project_group_id', $group_id);
        }

        $this->db->delete('users_roles');

        if ($this->db->affected_rows() >= 1)
        {
            return TRUE;
        }
        return FALSE;
    }

    function has_project_group_role($user_id, $project_group_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('project_group_id', $project_group_id);
        $query = $this->db->get('users_roles');
        $row = $query->row();

        return isset($row);
    }

    function copy_project_group_roles($source, $destination) {
        $this->db->select('user_id, role_id,'.$destination.' AS project_group_id');
        $this->db->where('project_group_id ='.$source.' AND idx((select array_agg(user_id) from users_roles where project_group_id = '.$destination.'), user_id) = 0');
        $query = $this->db->get('users_roles');
        $insert = $query->result_array();
        if ($insert) {
            $res = $this->db->insert_batch('users_roles',$insert);
        }
    }

    function get_project_group_ids($user_id, $group = FALSE) {
        if($group) {
            $this->db->select('array_agg(project_group_id) AS project_group_ids');
        } else {
            $this->db->select('project_group_id');
        }
        $this->db->where('user_id', $user_id);
        $this->db->where('project_group_id !=', null);
        $query = $this->db->get('users_roles');
        if ($query->result()) {
            if($group) {
                return $query->result()[0]->project_group_ids;
            } else {
                return $query->result_array();
            }
        }
        return null;
    }

    /**
     * This method does not return administrators
     *
     * @param $group_id
     * @return mixed
     */
    function get_project_group_users($group_id) {
        $this->db->select('ur.id, ur.user_id, role_id, project_group_id, user_name, user_email, r.display_name as role, last_login, count_login, registered, organization, first_name, last_name, phone, name as role_name');
        $this->db->from('users_roles ur');
        $this->db->join('users_view', 'users_view.user_id = ur.user_id');
        $this->db->join('roles r', 'r.id = ur.role_id');
        $this->db->where('project_group_id',$group_id);
        $this->db->where('users_view.admin',FALSE);
        $this->db->order_by('users_view.last_name','ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    function get_project_groups_for_user($user_id) {
        $this->db->select('ur.id, ur.user_id, role_id, ur.project_group_id, CASE WHEN p.display_name IS NULL THEN p.name ELSE p.display_name || \' (\' || p.name || \')\' END AS name, p.client, p.client_name, r.display_name as role');
        $this->db->from('users_roles ur');
        $this->db->join('project_groups_view p', 'p.id = ur.project_group_id');
        $this->db->join('roles r', 'r.id = ur.role_id');
        $this->db->where('user_id',$user_id);
        $this->db->order_by('p.name','ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    function get_roles() {

        $this->db->order_by('id', 'ASC');
        $this->db->select("id, display_name AS name", FALSE);
        $this->db->where('id >=', 20);

        $query = $this->db->get('roles');
        return $query->result_array();
    }

    function get_role($name) {

        $this->db->select("id, display_name AS name", FALSE);
        $this->db->where('name', $name);

        $query = $this->db->get('roles');
        return $query->result()[0];
    }

	// get user
	function get_user_by_id($id)
	{
		$this->db->where('user_id', $id);
        $query = $this->db->get('users');
        if ($query->result()) {
            return $query->result()[0];
        }
        return null;
	}
	
	// insert
	function insert_user($data)
    {
        $manual_activation = $this->config->item('manual_activation', 'ion_auth');

        $data['active'] = $manual_activation === FALSE ? 1 : 0;

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

	public function save_user($data)
    {
		$id = $data['user_id'];

		//if ($id != null){
			$this->db->where('user_id',$id);
			$this->db->update('users',$data);
			return $id;
		//}

		//unset($data['id']);
		//$this->db->insert('clients', $data);

		//return $this->db->insert_id();
    }

//	function get_projectusers($userid)
//	{
//        $sql = "select p.id, p.name, p.display_name, c.display_name client_name, case when  u.user_id is null then false else true end selected
//		from projects p left join clients c on c.id = p.client_id left join users u on p.id = ANY(project_ids) and u.user_id = " . $userid . " order by p.display_name";
//
//		$query = $this->db->query($sql);
//
//        return $query->result_array();
//
//	}

	public function delete_user($id)
    {
        $this->db->where('user_id', $id);
        $this->db->delete('users');
    }

    public function clear_print($name)
    {
        $this->db->where('user_name', $name);
        $this->db->delete('users_print');
    }

    //todo return array client_id, client display_name
    public function get_admin_scope($id) {
        return 'xxx '.$id;
    }
}
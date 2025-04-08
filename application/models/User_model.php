<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model
{
	function __construct()
    {
        parent::__construct();
    }


    function get_users($client_filter = NULL, $remove_links = FALSE)
    {
        $this->db->order_by('user_name', 'ASC');
        if(empty($client_filter)) {
            $query = $this->db->get('users_view');
        } else {
            $this->db->select('user_id,first_name,last_name,display_name,user_name,user_email,organization,registered,count_login,last_login,lang,active,phone,bool_or(admin) as admin,filter,scope,min(role_id) as role_id, min(role_name) as role_name,max(role_display_name) as role_display_name,sum(groups) as groups');
            $this->db->where('filter',$client_filter);
            if($remove_links) {
				//we remove users that have only link to the client without any group access
            	$this->db->where('NOT (role_id = 9 AND groups = 0)');
			}
            $this->db->group_by('user_id,first_name,last_name,display_name,user_name,user_email,organization,registered,count_login,last_login,lang,active,phone,filter,scope');
            $query = $this->db->get('users_view_for_clients');
        }

        return $query->result_array();
    }

    //TODO this is duplicate, can be solved using function above and array_column to get only emails when needed
    function get_portal_users($role, $filter = NULL, $email_only = FALSE)
    {
        if($email_only) {
			$this->db->select('user_email');
			$this->db->where('receive_system_emails', true);
		}

        if(!empty($role)) {
            $this->db->where('role_name',$role);
		}

		//if(!empty($filter)) {
		$this->db->where('filter', $filter);
		//}

		$query = $this->db->get('users_view_for_clients');
		return $query->result_array();
	}

	function get_client_admins($client_id)
	{

		$admins = array_column($this->get_portal_users('admin', null, true), 'user_email');
		//add system admin from config
		array_push($admins, $this->config->item('admin_email'));
		$admins2 = [];
		$admins3 = [];

		if (!empty($client_id)) {
			$admins2 = array_column($this->get_portal_users('admin', $client_id, true), 'user_email');
			$admins3 = array_column($this->get_portal_users('power', $client_id, true), 'user_email');
		}

		//remove duplicates
		return array_unique(array_merge($admins, $admins2, $admins3));
	}

	/**
	 * @param $text
	 * @param $filter
	 * @return mixed
	 */
	function search($text, $filter)
	{
		$like_text = '%' . $this->db->escape_like_str($text) . '%';

		//$this->db->like('first_name', $text);
		//$this->db->or_like('last_name', $text);
        //$this->db->or_like('user_email', $text);

        //for ilike search we have to use direct sql
		$where = "(first_name ILIKE " . $this->db->escape($like_text) . " ESCAPE '!' OR "
			. "last_name ILIKE " . $this->db->escape($like_text) . " ESCAPE '!' OR "
			. "user_email ILIKE " . $this->db->escape($like_text) . " ESCAPE '!')";

        $this->db->select("user_id AS id, trim(coalesce(last_name,'') || ' ' || coalesce(first_name,'')) || ' (' || user_email || ')' AS name", FALSE);
		$this->db->where($where, null, false);

        if(!empty($filter)) {
            $this->db->where('filter', $filter);
        }

        $this->db->where('admin', FALSE);
        //$this->db->or_where('admin = TRUE AND filter IS NOT NULL');
        $this->db->order_by('name', 'DESC');

        if(!empty($filter)) {
            $query = $this->db->get('users_view_for_clients');
        } else {
            $query = $this->db->get('users_view');
        }
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
        $user = $this->db->get('users_view')->result();

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

        $this->db->insert('users_roles', $data);
    }

    function insert_project_group_roles($data) {

        $this->db->insert_batch('users_roles', $data);
    }

    function update_project_group_role($group_id, $user_id, $role_id) {

        $this->db->set('role_id', $role_id);
        $this->db->where('user_id', $user_id);
		$this->db->where('project_group_id', $group_id);
		$this->db->update('users_roles');

		if ($this->db->affected_rows() === 1) {
			return TRUE;
		}
		return FALSE;
	}

	function update_project_group_mask($group_id, $user_id, $mask_id)
	{

		if ($mask_id == -1) {
			$mask_id = NULL;
		}

		$this->db->set('mask_id', $mask_id);
		$this->db->where('user_id', $user_id);
		$this->db->where('project_group_id', $group_id);
		$this->db->update('users_roles');

		if ($this->db->affected_rows() === 1) {
			return TRUE;
		}
		return FALSE;
	}


	function delete_project_group_role($group_id, $user_id)
	{

		//we delete only project roles, role_id over 10

		if (!empty($user_id)) {
			$this->db->where('user_id', $user_id);
		}
		if (!empty($group_id)) {
			$this->db->where('project_group_id', $group_id);
        }

        $this->db->where('role_id >', 10);
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
        if(!empty($project_group_id)) {
            $this->db->where('project_group_id', $project_group_id);
        }
        $query = $this->db->get('users_roles');
        $row = $query->row();

        return isset($row);
    }

    function copy_project_group_roles($source, $destination) {
        $this->db->select('user_id, role_id,'.$destination.' AS project_group_id');
        $this->db->where('project_group_id ='.$source.' AND idx((SELECT CASE WHEN array_agg(user_id) IS NULL THEN \'{-1}\'::integer[] ELSE array_agg(user_id) END from users_roles where project_group_id = '.$destination.'), user_id) = 0');
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
     * @param $group_id
     * @return mixed
     */
    function get_project_group_users($group_id) {
		$this->db->select('ur.id, ur.user_id, ur.role_id, project_group_id, user_name, user_email, r.display_name as role, last_login, count_login, registered, organization, first_name, last_name, phone, name as role_name, users_view.active, mask_id');
		$this->db->from('users_roles ur');
        $this->db->join('users_view', 'users_view.user_id = ur.user_id');
        $this->db->join('roles r', 'r.id = ur.role_id');
        $this->db->where('project_group_id',$group_id);
        $this->db->where('(users_view.admin = FALSE OR users_view.admin = TRUE AND filter is not null)');
        $this->db->order_by('users_view.last_name','ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    function get_project_groups_for_user($user_id, $filter = NULL) {
		$this->db->select('ur.id, ur.user_id, role_id, ur.project_group_id, CASE WHEN p.display_name IS NULL THEN p.name ELSE p.display_name || \' (\' || p.name || \')\' END AS name, p.client_id, p.client, p.client_name, p.projects, r.display_name as role, mask_id');
		$this->db->from('users_roles ur');
        $this->db->join('project_groups_view p', 'p.id = ur.project_group_id');
        $this->db->join('roles r', 'r.id = ur.role_id');
        $this->db->where('user_id',$user_id);
        if(!empty($filter)) {
            $this->db->where('p.client_id', $filter);
        }
        $this->db->order_by('p.name','ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
     * Web Client project roles
     */
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
	function get_user_by_id($id, $filter = NULL)
	{
		$this->db->where('user_id', $id);

		if(empty($filter)) {
            $query = $this->db->get('users_view');
        } else {
            $this->db->where('filter',$filter);
		    $query = $this->db->get('users_view_for_clients');
        }


        if ($query->result()) {
            return $query->result()[0];
        }
        return null;
	}
	
	// insert
//	function insert_user($data)
//    {
//        $manual_activation = $this->config->item('manual_activation', 'ion_auth');
//
//        $data['active'] = $manual_activation === FALSE ? 1 : 0;
//
//        return $this->db->insert('users', $data);
//	}

//    function update_user($id, $sql)
//    {
//        //$this->db->where('user_id', $id);
//        //$this->db->update('users', $data);
//
//        $sql.= " WHERE user_id = ".$id;
//
//        //returns bool
//        $result = $this->db->query('UPDATE users SET '.$sql);
//    }

    //used for links, because user can have more links, so we need client parameter to check
    function has_portal_role($role, $user_id, $client_id) {

        $this->db->where('user_id',$user_id);
        $this->db->where('role_id',$role);
        $this->db->where('client_id',$client_id);

        $query = $this->db->get('users_roles');
        $row = $query->row();

        return isset($row);
    }

    function set_link($user_id, $client_id) {

        $link_group = 9;

        //first check if user has link to client
        $exist = $this->has_portal_role($link_group,$user_id, $client_id);

        //else, add to group
        if(!$exist) {
            $this->ion_auth->add_to_group($link_group, $user_id, $client_id);
        }
    }

    function remove_link($user_id, $client_id = NULL) {

		$link_group = 9;

		$this->db->where('user_id', $user_id);
		$this->db->where('role_id', $link_group);
		if (!empty($client_id)) {
			$this->db->where('client_id', $client_id);
		}
		$this->db->delete('users_roles');
	}

	function get_contact_group_ids($user_id)
	{

		$this->db->select('id');
		$this->db->where('contact_id', $user_id);
		$query = $this->db->get('project_groups');

		return array_column($query->result_array(), 'id');
	}

	public function save_user($data)
	{
		$id = $data['user_id'];
		$uname = $data['user_name'];

		//remove user_email and user_name from updating
		unset($data['user_email']);
		unset($data['user_name']);

		//to update we need user_id and user_name
		$this->db->where('user_id', $id);
		$this->db->where('user_name', $uname);
		$this->db->update('users', $data);
		return $id;
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

    public function clear_gisapp_session()
    {
        $sess_items = array(
			'client_path',
			'project',
			'project_path',
			'data',
			'settings',
			'description',
			'gis_projects',
			'qgs',
			'map',
			'mask_layer',
			'mask_filter',
			'mask_wkt'
		);

        $this->session->unset_userdata($sess_items);
    }
}

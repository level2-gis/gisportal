<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('project_model');
        $this->load->model('user_model');
        $this->load->model('client_model');
        $this->load->helper(array('url', 'html', 'eqwc_parse'));
    }

    public function index()
    {
        $task = 'users_table_view';

        if (!$this->ion_auth->can_execute_task($task)){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('auth//login');
        }

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope();
        $filter = $user_role->filter;

		$data['title'] = $this->lang->line('gp_users_title');
        $data['users'] = $this->user_model->get_users($filter);
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;

        $this->load->view('templates/header', $data);
        $this->load->view('users_admin', $data);
        $this->load->view('templates/footer', $data);
    }

    public function edit($user_id = false)
    {
        $task = 'users_edit';

        if (!$this->ion_auth->logged_in()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        if (!$this->ion_auth->can_execute_task($task)){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/users');
        }

		$this->load->helper('form');
		$this->load->library('form_validation');
		
		$this->form_validation->set_rules('user_name', 'Username', 'trim|required');
		$this->form_validation->set_rules('user_email', 'Email', 'trim|required');
		//$this->form_validation->set_rules('display_name', 'Name', 'trim|required');
		
		if ($this->form_validation->run() === FALSE)
	    {

			$em = array ( 'user_id' => '', 'user_name' => '', 'user_email' => '', 'display_name' => '');

			$data['title'] = 'Create New User';
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
			$data['creating'] = true;

            //filter for client administrator
			$user_role = $this->ion_auth->admin_scope();
            $filter = $this->ion_auth->admin_scope()->filter;

			if(sizeof($_POST) > 0){
				$em = $this->extractUserData();
				$data['title'] = 'Edit User ' . $em['first_name'] . ' ' .  $em['last_name'];
				$data['creating'] = false;
			} else {
				if ($user_id !== false){
                    try {
                        $dq = $this->user_model->get_user_by_id($user_id, $filter);
                        if(empty($dq)) {
                            throw new Exception('User does not exist!');
                        }
                    }
                    catch (Exception $e){
                        $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
                        redirect('/users');
                    }

				    //$dq->display_name = $dq->first_name . ' ' . $dq->last_name;

					if ($dq->user_id != null){
						$em = (array)$dq;
						$data['title'] = 'Edit User ' . $em['first_name'] . ' ' .  $em['last_name'];
						$data['creating'] = false;
					}
				}
			}
			
			$data['user'] = $em;

            if(empty($filter)) {
                $data['clients'] = $this->client_model->get_clients();
            } else {
                $data['clients'] = [(array)$this->client_model->get_client($filter)];
            }

            $edit_user_role = $this->ion_auth->admin_scope($user_id);

            $data['groups'] = $this->user_model->get_project_groups_for_user($user_id, $filter);
            $data['roles'] = $this->user_model->get_roles();
            $data['role_admin'] = $this->user_model->get_role('admin')->name; //get role name from database
            $data['role_power'] = $this->user_model->get_role('power')->name; //get role name from database

            $data['user_role'] = $edit_user_role;
            $data['user']['admin'] = $edit_user_role ? $edit_user_role->admin : null;
            $data['role_filter'] = $edit_user_role ? $edit_user_role->filter : null;
			$data['logged_in'] = true;
            $data['can_edit_access'] = $this->ion_auth->can_execute_task('project_groups_edit_access');

            $data['is_admin'] = $user_role->admin;
            $data['role'] = $user_role->role_name;

            $data['role_scope'] = empty($edit_user_role->scope) ? $this->lang->line('gp_admin_full_name') : $edit_user_role->scope;
            if($edit_user_role) {
                $data['user_admin_msg'] = str_replace('{name}', $data['role_scope'] . ' ' . $edit_user_role->role_display_name, $this->lang->line('gp_user_is_admin'));
            }

            $data['current_role_filter'] = $filter;  //filter for current logged in user
            $data['logged_id'] =  $this->session->userdata('user_id');    //current user id

			$this->load->view('templates/header', $data);
			$this->load->view('user_edit', $data);
			$this->load->view('templates/footer', $data);
		} else {

			$user = $this->extractUserData();

			$user_id = $this->user_model->save_user($user);
            $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">User <strong>' . $user['display_name'] . '</strong> has been saved</div>');

			if($this->input->post('return') == null){
				redirect('/users/edit/' . $user_id);
			} else {
				redirect('/users');
			}
		}

    }

    public function remove($id)
    {
        $task = 'users_delete';

        if (!$this->ion_auth->can_execute_task($task)){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/users');
        }

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        $user = (array)$this->user_model->get_user_by_id($id, $filter);

        // check if the user exists before trying to delete it
        if(isset($user['user_id']))
        {
            try {

                //remove link
                $this->user_model->remove_link($id, $filter);

                $test = $this->user_model->has_project_group_role($id,null);
                if($test) {
                    throw new Exception('Cannot delete, user has roles!');
                }

                $this->user_model->clear_print($user['user_name']);
                $this->user_model->delete_user($id);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_user').' <strong>' . $this->input->post('name') . '</strong>'.$this->lang->line('gp_deleted').'</div>');
                redirect('/users');
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
                redirect('/users/edit/'.$id);
            }
        }
        else {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">The user you are trying to delete does not exist.</div>');
            redirect('/users');
        }
    }

    public function add_role_multi($groups, $user_id, $role_id, $client_id) {

        $task = 'project_groups_edit_access';

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope($user_id);
        $filter = $user_role->filter;

        function map_existing($item) {
            return $item['project_group_id'];
        }

        try {
            if (!$this->ion_auth->can_execute_task($task)){
                throw new Exception('No permission!');
            }

            if($user_role->admin && !empty($filter) && $filter === (integer)$client_id) {
                throw new Exception('User is Client Administrator and has already access to all groups for client!');
            }

            $groups = urldecode($groups);
            $groups_array = array_map("intval", explode(',', $groups));

            //get existing groups for user
            $existing_groups = $this->user_model->get_project_group_ids($user_id, FALSE);
            if(!empty($existing_groups)) {
                $existing_groups_array = array_map("map_existing", $existing_groups);
                $diff = array_diff($groups_array,$existing_groups_array);
            } else {
                $diff = $groups_array;
            }

            $data = [];

            foreach ($diff as $group) {
                array_push($data, [
                        "user_id" => $user_id,
                        "role_id" => $role_id,
                        "project_group_id" => $group

                    ]
                );
            }

            if(empty($data)) {
                throw new Exception('Nothing added, user has already access!');
            }

            $this->user_model->insert_project_group_roles($data);

            //set link
            $this->user_model->set_link($user_id,$client_id);

            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }

            $client = $this->client_model->get_client($client_id);
            $user = $this->user_model->get_user_by_id($user_id);
            $message = $this->load->view($this->config->item('email_templates', 'ion_auth') . 'new_access.tpl.php', $client, TRUE);
            $this->ion_auth->send_email(lang('gp_new_access'),$message,$user->user_email);
        }
        catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            redirect('users/edit/' . $user_id . '#edit-access');
        }
    }

    public function add_role($group_id, $user_id, $role_id, $back, $client_id) {

        $task = 'project_groups_edit_access';

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope($user_id);
        $filter = $user_role->filter;

        try {
            if (!$this->ion_auth->can_execute_task($task)){
                throw new Exception('No permission!');
            }

            if($user_role->admin && !empty($filter) && $filter === (integer)$client_id) {
                throw new Exception('User is Client Administrator and has already access to all groups for client!');
            }

            $data = [
                "user_id"           => $user_id,
                "role_id"           => $role_id,
                "project_group_id"  => $group_id
            ];

            //check first if user already has role for that group
            $check = $this->user_model->has_project_group_role($user_id,$group_id);
            if($check) {
                throw new Exception('Cannot add new role: User already has access!');
            }

            $this->user_model->insert_project_group_role($data);

            //set link
            $this->user_model->set_link($user_id,$client_id);

            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }

            $client = $this->client_model->get_client($client_id);
            $user = $this->user_model->get_user_by_id($user_id);
            $message = $this->load->view($this->config->item('email_templates', 'ion_auth') . 'new_access.tpl.php', $client, TRUE);
            $this->ion_auth->send_email(lang('gp_new_access'),$message,$user->user_email);
        }
        catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            $id = 0;
            if($back == 'users') {
                $id = $user_id;
            } else if ($back == 'project_groups') {
                $id = $group_id;
            }
            if($id>0) {
                redirect($back . '/edit/' . $id . '#edit-access');
            }
        }
    }

    public function set_admin($user_id, $has, $role, $client_id = NULL)
    {
        //fixed ids for admin and power roles!!
        $admin_group = $role === 'admin' ? 1 : 2;
        $to_remove = (boolean)$has;
        $client_id = (empty($client_id)) ? null : (integer)$client_id;

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        try {
            if (!$this->ion_auth->is_admin()) {
                throw new Exception('User not Admin!');
            }

            if(!empty($filter) && $role==='admin') {
                throw new Exception('No permission!');
            }

            if($to_remove) {
                $this->ion_auth->remove_from_group($admin_group, $user_id);
            } else {
                if($this->ion_auth->add_to_group($admin_group, $user_id, $client_id) > 0) {
                    $user_data = $this->ion_auth->admin_scope($user_id);
                    if($user_data->admin) {
                        $user_data->scope = empty($user_data->scope) ? $this->lang->line('gp_admin_full_name') : $user_data->scope;
                    }
                    $message = $this->load->view($this->config->item('email_templates', 'ion_auth') . 'new_role.tpl.php', $user_data, TRUE);
                    $this->ion_auth->send_email(lang('gp_new_role'),$message,$user_data->email);
                }
            }

        } catch (Exception $e) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
        } finally {
            $back = 'users';
            redirect($back . '/edit/' . $user_id);
        }
    }

    public function set_role($group_id, $user_id, $role_id, $back) {

        $task = 'project_groups_edit_access';

        try {
            if (!$this->ion_auth->can_execute_task($task)){
                throw new Exception('No permission!');
            }

            $res = $this->user_model->update_project_group_role($group_id,$user_id,$role_id);
            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }
        }
        catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            $id = 0;
            if($back == 'users') {
                $id = $user_id;
            } else if ($back == 'project_groups') {
                $id = $group_id;
            }
            if($id>0) {
                redirect($back . '/edit/' . $id . '#edit-access');
            }
        }
    }

    public function remove_role($group_id, $user_id, $back) {

        $task = 'project_groups_edit_access';

        try {
            if (!$this->ion_auth->can_execute_task($task)){
                throw new Exception('No permission!');
            }

            if($user_id === 'null') {
                $user_id = null;
            }

            if($group_id === 'null') {
                $group_id = null;
            }

            $res = $this->user_model->delete_project_group_role($group_id,$user_id);
            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }
        }
        catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            $id = 0;
            if($back == 'users') {
                $id = $user_id;
            } else if ($back == 'project_groups') {
                $id = $group_id;
            }
            if($id>0) {
                redirect($back . '/edit/' . $id . '#edit-access');
            }
        }
    }

    public function copy_roles($source, $destination) {

        $task = 'project_groups_edit_access';

        try {
            if (!$this->ion_auth->can_execute_task($task)){
                throw new Exception('No permission!');
            }

            if(empty($source) || empty($destination)) {
                throw new Exception('Missing data!');
            }

            $res = $this->user_model->copy_project_group_roles($source,$destination);
            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }
        }
        catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            redirect('project_groups/edit/' . $destination . '#edit-access');
        }
    }

    public function search() {

        $query = $this->input->get('query');

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        if(empty($query)) {
            return;
        }

        $result = $this->user_model->search(urldecode($query),$filter);

        $this->output
            ->set_content_type('text/html')
            ->set_status_header(200)
            ->set_output(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

	private function extractUserData(){

        $id = $this->input->post('user_id');

        $data = array(
			'user_id' => $id,
			'user_name' => $this->input->post('user_name'),
			'user_email' => $this->input->post('user_email'),
			'first_name' => $this->input->post('first_name'),
			'last_name' => $this->input->post('last_name'),
			'admin' => $this->ion_auth->is_admin($id),
            'organization' => $this->input->post('organization'),
            'phone' => $this->input->post('phone')
		);

//		$data['admin'] = $data['admin'] != '' ? $data['admin'] : 'false';
//		$data['project_ids'] = null;
//		if ($this->input->post('project_ids') != null){
//			$blids = implode($this->input->post('project_ids'),',');
//			if ($blids != ''){
//				$data['project_ids'] = '{' . $blids . '}';
//			}
//		}
		
		return $data;
	}
}
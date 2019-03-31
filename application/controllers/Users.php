<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('project_model');
        $this->load->model('user_model');
        $this->load->helper(array('url', 'html', 'eqwc_parse'));
    }

    public function index()
    {
        if (!$this->ion_auth->is_admin()){
            redirect('auth//login?ru=/' . uri_string());
        }

		$data['title'] = $this->lang->line('gp_users_title');
        $data['users'] = $this->user_model->get_users();
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['logged_in'] = true;
        $data['is_admin'] = true;

        $this->load->view('templates/header', $data);
        $this->load->view('users_admin', $data);
        $this->load->view('templates/footer', $data);
    }

    public function edit($user_id = false)
    {
        if (!$this->ion_auth->is_admin()){
            redirect('/auth/login?ru=/' . uri_string());
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

			if(sizeof($_POST) > 0){
				$em = $this->extractUserData();
				$data['title'] = 'Edit User ' . $em['first_name'] . ' ' .  $em['last_name'];
				$data['creating'] = false;
			} else {
				if ($user_id !== false){
					$dq = $this->user_model->get_user_by_id($user_id);

					if ($dq->user_id != null){
						$em = (array)$dq;
						$data['title'] = 'Edit User ' . $em['first_name'] . ' ' .  $em['last_name'];
						$data['creating'] = false;
					}
				}
			}
			
			$data['user'] = $em;
            $data['logged_in'] = true;
            $data['is_admin'] = true;

			$this->loadmeta($data);

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

    function remove($id)
    {
        if (!$this->ion_auth->is_admin()){
            redirect('/');
        }

        $user = (array)$this->user_model->get_user_by_id($id);

        // check if the user exists before trying to delete it
        if(isset($user['user_id']))
        {
            try {
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
        else
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">The users you are trying to delete does not exist.</div>');
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
            'organization' => $this->input->post('organization')
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

	private function loadmeta(&$data){
		//$data['projects'] = $this->user_model->get_projectusers($data['user']['user_id']);
        $data['groups'] = [];
	}



}
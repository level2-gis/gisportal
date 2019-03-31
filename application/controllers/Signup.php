<?php
class Signup extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form','url','date'));
		$this->load->library(array('form_validation'));
		$this->load->database();
		$this->load->model('user_model');
	}
	
	function index()
	{
		// set form validation rules
		$this->form_validation->set_rules('fname', $this->lang->line('gp_first_name'), 'trim|required|max_length[30]');
		$this->form_validation->set_rules('lname', $this->lang->line('gp_last_name'), 'trim|required|max_length[30]');
		$this->form_validation->set_rules('email', $this->lang->line('gp_email'), 'trim|required|valid_email|is_unique[users.user_email]');
        $this->form_validation->set_rules('username', $this->lang->line('gp_username'), 'trim|alpha_numeric|required|min_length[3]|max_length[30]|is_unique[users.user_name]');
        $this->form_validation->set_rules('password', $this->lang->line('gp_password'), 'trim|required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']');
        $this->form_validation->set_rules('cpassword', $this->lang->line('gp_confirm') . ' ' . $this->lang->line('gp_password'), 'trim|required|matches[password]');

        // submit
		if ($this->form_validation->run() == FALSE)
        {
			// fails
            $data['title'] = $this->lang->line('gp_register');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            //we allow registration also if user is logged in
            $data['logged_in'] = $this->ion_auth->logged_in();
            $data['is_admin'] = $data['logged_in'] ? $this->ion_auth->is_admin() : false;

            $this->load->view('templates/header', $data);
            $this->load->view('signup_view');
            $this->load->view('templates/footer', $data);
        }
		else
		{
			//insert user details into db
			$data = array(
                'first_name' => $this->input->post('fname'),
                'last_name' => $this->input->post('lname'),
                'user_name' => $this->input->post('username'),
				'user_email' => $this->input->post('email'),
				'user_password_hash' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
                'registered' => unix_to_human(now()),
                'organization' => $this->input->post('organization'),
			);
			
			if ($this->user_model->insert_user($data))
			{
                $msg = $this->lang->line('gp_register_success');
                $this->session->set_flashdata('message','<div class="alert alert-success text-center">' . $msg . '</div>');
                redirect('auth/login/');
			}
			else
			{
				// error
                $msg = $this->lang->line('gp_register_error');
				$this->session->set_flashdata('message','<div class="alert alert-danger text-center">' . $msg . '</div>');
				redirect('signup/');
			}
		}
	}
}
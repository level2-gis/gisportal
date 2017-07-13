<?php
class Signup extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form','url'));
		$this->load->library(array('session', 'form_validation'));
		$this->load->database();
		$this->load->model('user_model');
	}
	
	function index()
	{
		// set form validation rules
		$this->form_validation->set_rules('fname', 'First Name', 'trim|required|min_length[3]|max_length[30]');
		$this->form_validation->set_rules('lname', 'Last Name', 'trim|required|min_length[3]|max_length[30]');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|is_unique[users.user_email]');
        $this->form_validation->set_rules('username', 'Username', 'trim|alpha_numeric|required|min_length[3]|max_length[30]|is_unique[users.user_name]');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[3]');
        $this->form_validation->set_rules('cpassword', 'Confirm Password', 'trim|required|min_length[3]|matches[password]');
		
		// submit
		if ($this->form_validation->run() == FALSE)
        {
			// fails
            $data['title'] = 'Signup';

            $this->load->view('templates/header', $data);
            $this->load->view('signup_view');
            $this->load->view('templates/footer', $data);
        }
		else
		{
			//insert user details into db
			$data = array(
                'display_name' => $this->input->post('fname') . ' ' . $this->input->post('lname'),
                'user_name' => $this->input->post('username'),
				'user_email' => $this->input->post('email'),
				'user_password_hash' => password_hash($this->input->post('password'), PASSWORD_DEFAULT)
			);
			
			if ($this->user_model->insert_user($data))
			{
				$this->session->set_flashdata('msg','<div class="alert alert-success text-center">You are Successfully Registered! Please login to access your Profile!</div>');
				redirect('login/index');
			}
			else
			{
				// error
				$this->session->set_flashdata('msg','<div class="alert alert-danger text-center">Oops! Error.  Please try again later!!!</div>');
				redirect('signup/index');
			}
		}
	}
}
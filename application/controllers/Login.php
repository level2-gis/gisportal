<?php

class Login extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form','url','html'));
		$this->load->library(array('session', 'form_validation'));
		$this->load->database();
		$this->load->model('user_model');
	}
    public function index()
    {
		// get form input
		$email = $this->input->post("email");
        $password = $this->input->post("password");

		// form validation
		$this->form_validation->set_rules("email", "Email", "trim|required");
		$this->form_validation->set_rules("password", "Password", "trim|required");
		
		if ($this->form_validation->run() == FALSE)
        {
			// validation fail
            $data['title'] = 'Login';

            $this->load->view('templates/header', $data);
            $this->load->view('login_view');
            $this->load->view('templates/footer', $data);
		}
		else
		{
			// check for user credentials
			$uresult = $this->user_model->get_user($email, $password);
			if (count($uresult) > 0)
			{
				// set session
				$sess_data = array(
                    'user_name' => $uresult[0]->user_name,
                    'uid' => $uresult[0]->user_id,
                    'user_email' => $uresult[0]->user_email,
                    'user_is_logged_in' => true
                    );
				$this->session->set_userdata($sess_data);
				redirect("home/index");
			}
			else
			{
				$this->session->set_flashdata('msg', '<div class="alert alert-danger text-center">Wrong Email or Password!</div>');
				redirect('login/index');
			}
		}
    }
}
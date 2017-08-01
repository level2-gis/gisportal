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
        $this->lang->load('gisportal_lang');
	}
    public function index()
    {
		// get form input
		$key = $this->input->post("user");
        $password = $this->input->post("password");

		// form validation
		$this->form_validation->set_rules("user", "User", "trim|required");
		$this->form_validation->set_rules("password", "Password", "trim|required");
		
		if ($this->form_validation->run() == FALSE)
        {
			// validation fail
            $data['title'] = $this->lang->line('gp_login');

            $this->load->view('templates/header', $data);
            $this->load->view('login_view');
            $this->load->view('templates/footer', $data);
		}
		else
		{
			// check for user credentials
			$uresult = $this->user_model->get_user($key, $password);
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
                $this->user_model->update_user($uresult[0]->user_id);
				redirect("home/index");
			}
			else
			{
				$msg = $this->lang->line('gp_login_wrong');
                $this->session->set_flashdata('msg', '<div class="alert alert-danger text-center">' . $msg . '</div>');
				redirect('login/index');
			}
		}
    }
}
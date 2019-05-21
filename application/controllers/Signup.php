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
		//TODO read connect parameter from get, check for client name, based on setting allow "public" registration without connection
        //connection must be added to database with set_link

	    // set form validation rules
		$this->form_validation->set_rules('fname', $this->lang->line('gp_first_name'), 'trim|required|max_length[30]');
		$this->form_validation->set_rules('lname', $this->lang->line('gp_last_name'), 'trim|max_length[30]');
		$this->form_validation->set_rules('email', $this->lang->line('gp_email'), 'trim|required|valid_email|is_unique[users.user_email]');
        $this->form_validation->set_rules('username', $this->lang->line('gp_username'), 'trim|alpha_numeric|required|min_length[3]|max_length[30]|is_unique[users.user_name]');
        $this->form_validation->set_rules('password', $this->lang->line('gp_password'), 'trim|required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']');
        $this->form_validation->set_rules('cpassword', $this->lang->line('gp_confirm') . ' ' . $this->lang->line('gp_password'), 'trim|required|matches[password]');
        $this->form_validation->set_rules('organization', $this->lang->line('gp_organization'), 'trim');

        // submit
		if ($this->form_validation->run() == FALSE)
        {
			// fails
            $data['title'] = $this->lang->line('gp_register');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            //we allow registration also if user is logged in
            $data['logged_in'] = $this->ion_auth->logged_in();
            $data['is_admin'] = $data['logged_in'] ? $data['logged_in'] : false;

            $this->load->view('templates/header', $data);
            $this->load->view('signup_view');
            $this->load->view('templates/footer', $data);
        }
		else
		{
			//signup ok, insert data to db
            $email = strtolower($this->input->post('email'));
            $password = $this->input->post('password');
            $username = $this->input->post('username');

		    //insert user details into db
			$additional_data = array(
                'first_name' => $this->input->post('fname'),
                'last_name' => $this->input->post('lname'),
                'organization' => $this->input->post('organization')
			);
			
			$new_id = $this->ion_auth->register($username, $password, $email, $additional_data);

			if ($new_id)
			{
                $msg = $this->ion_auth->messages();     //old $this->lang->line('gp_register_success');
                $this->session->set_flashdata('message','<div class="alert alert-warning text-center">' . $msg . '</div>');

                //notify gisportal system admin
                $additional_data["id"] = $new_id;
                $additional_data["email"] = $email;
                $message = $this->load->view($this->config->item('email_templates', 'ion_auth') . 'new_user.tpl.php', $additional_data, TRUE);
                $this->ion_auth->send_email($this->lang->line('gp_new_user'),$message);

                redirect('auth/login', 'refresh');
			}
			else
			{
				// error
                $msg = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message'))); //old $this->lang->line('gp_register_error');
				$this->session->set_flashdata('message','<div class="alert alert-danger text-center">' . $msg . '</div>');
				redirect('signup/');
			}
		}
	}
}
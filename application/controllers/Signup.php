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
		$this->load->model('client_model');
	}
	
	function index()
	{
		try {
			$cl_query = $this->input->get('code', TRUE);

			$client = NULL;
			if (!empty($cl_query)) {
				$cl_decode = base64_decode($cl_query);
				//here we match codeigniter alpha_dash check
				if (preg_match('/^[a-z0-9_-]+$/i', $cl_decode)) {
					$client = $this->client_model->get_client_by_name($cl_decode);
				}
			} else {
				if ($this->config->item('public_registration') === FALSE) {
					throw new Exception('Client required!');
				}
			}

			if (empty($client) && ($this->config->item('public_registration') === FALSE)) {
				throw new Exception('Client not correct!');
			}

		} catch (Exception $e) {
			$data['logged_in'] = FALSE;
			$data['message'] = $e->getMessage();
			$data['type'] = 'danger';
			$this->load->view('templates/header', $data);
			$this->load->view('templates/header_navigation', $data);
			$this->load->view('message_view', $data);
			return;
		}

		// set form validation rules
		$this->form_validation->set_rules('fname', $this->lang->line('gp_first_name'), 'trim|required|max_length[30]');
		$this->form_validation->set_rules('lname', $this->lang->line('gp_last_name'), 'trim|max_length[30]');
		$this->form_validation->set_rules('email', $this->lang->line('gp_email'), 'trim|required|valid_email|is_unique[users.user_email]');
		$this->form_validation->set_rules('username', $this->lang->line('gp_username'), 'trim|alpha_numeric|required|min_length[3]|max_length[30]|is_unique[users.user_name]');
		$this->form_validation->set_rules('password', $this->lang->line('gp_password'), 'trim|required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']');
		$this->form_validation->set_rules('cpassword', $this->lang->line('gp_confirm') . ' ' . $this->lang->line('gp_password'), 'trim|required|matches[password]');
		$this->form_validation->set_rules('organization', $this->lang->line('gp_organization'), 'trim');
		if (!empty($this->config->item('terms_use'))) {
			$this->form_validation->set_rules('terms', '', 'required', array('required' => $this->lang->line('gp_terms_required')));
		}

		// submit
		if ($this->form_validation->run() == FALSE) {
			// fails
			$data['client'] = empty($client) ? null : (array)$client;
			$data['code'] = $cl_query;
			$data['title'] = $this->lang->line('gp_register');
			$data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
			//we allow registration also if user is logged in
			$data['logged_in'] = $this->ion_auth->logged_in();
			$data['is_admin'] = $data['logged_in'] ? $data['logged_in'] : false;
			$data['terms'] = empty($this->config->item('terms_use')) ? null : lang('gp_terms_read') . anchor($this->config->item('terms_use'), lang('gp_terms_name'), 'target="_blank"');

			$this->load->view('templates/header', $data);
			$this->load->view('templates/header_navigation', $data);
			$this->load->view('signup_view');
			$this->load->view('templates/footer');
		}
		else
		{
			//signup ok, insert data to db
            $email = strtolower($this->input->post('email'));
            $password = $this->input->post('password');
            $username = $this->input->post('username');

            $client_id = empty($client) ? null : $client->id;

		    //insert user details into db
			$additional_data = array(
                'first_name' => $this->input->post('fname'),
                'last_name' => $this->input->post('lname'),
                'company' => $this->input->post('organization'),
                'phone' => $this->input->post('phone')
			);
			
			$new_id = $this->ion_auth->register($username, $password, $email, $additional_data);

			if ($new_id)
			{
				$msg = $this->ion_auth->messages();     //old $this->lang->line('gp_register_success');
				$this->session->set_flashdata('message', '<div class="alert alert-info text-center">' . $msg . '</div>');

				//prepare message for administrators
				$additional_data["id"] = $new_id;
				$additional_data["email"] = $email;
				$additional_data["client"] = empty($client) ? null : $client->display_name;
				$message = $this->load->view($this->config->item('email_templates', 'ion_auth') . 'new_user.tpl.php', $additional_data, TRUE);

				//set link in case client exists
				if (!empty($client_id)) {
					$this->user_model->set_link($new_id, $client_id);
				}

				//notify administrators
				$admin_emails = $this->user_model->get_client_admins($client_id);
				$this->ion_auth->send_email($this->lang->line('gp_new_user'), $message, $admin_emails);

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

<?php

class Mail extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('email');
	}

	function index()
	{
		// list available commands
	}

	function test()
	{
		// using temporary data for testing
		$data = new stdClass();
		$data->mailto = $this->config->item('admin_email');
		$data->subject = 'Test mail';
		$data->body = 'This is just test mail to verify mail configuration is correct.';

		$this->sendMailWithResponse($data);
	}

	function send()
	{
		if (!$this->ion_auth->logged_in()) {
			$this->output
				->set_content_type('application/json')
				->set_status_header(403)
				->set_output(json_encode(array(
					'success' => 'false',
					'message' => 'No permissions for sending emails!'
				)));
		} else {
			$data = new stdClass();
			// Use CodeIgniter's XSS filtering by passing TRUE as second argument.
			$mailto = $this->input->post("mailto", TRUE);
			$data->mailto = empty($mailto) ? $this->config->item('admin_email') : $mailto;
			$data->subject = $this->input->post("subject", TRUE);

			// Check for template; sanitize input in both cases.
			if (empty($this->input->post("template", TRUE))) {
				$data->body = $this->input->post("body", TRUE);
			} else {
				// Clean the JSON string and then decode it.
				$clean_body = $this->security->xss_clean($this->input->post("body", TRUE));
				$body_data = json_decode($clean_body);
				$template = $this->input->post("template", TRUE);
				$body = $this->load->view($this->config->item('email_templates', 'ion_auth') . $template, $body_data, TRUE);
				$data->body = $body;
			}

			$this->sendMailWithResponse($data);
		}
	}

	private function sendMailWithResponse($data)
	{
		$this->email->to($data->mailto);
		$this->email->from($this->config->item('admin_email'), $this->config->item('site_title') . " " . $_SERVER['HTTP_HOST']);
		$this->email->subject($data->subject);
		$this->email->message($data->body);

		if (!$this->email->send()) {
			echo "Error sending email";
			show_error($this->email->print_debugger());
		} else {
			$this->output
				->set_content_type('application/json')
				->set_status_header(200)
				->set_output(json_encode(array(
					'success' => 'true',
					'message' => 'Email sent to ' . $data->mailto
				)));
		}
	}
}

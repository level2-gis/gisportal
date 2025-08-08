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
		
		if (!$this->ion_auth->logged_in()) {
			$this->output
				->set_content_type('application/json')
				->set_status_header(403)
				->set_output(json_encode(array(
					'success' => 'false',
					'message' => 'No permissions for sending emails!'
				)));
			return;
		}
				
		// using temporary data for testing
		$data = new stdClass();
		$data->mailto = $this->config->item('admin_email');
		$data->subject = 'Test mail';
		$data->body = 'This is just test mail to verify mail configuration is correct.';

		$this->sendMailWithResponse($data);
	}

	function send()
	{
		// Only allow POST requests for sending emails
		if ($this->input->method() !== 'post') {
			$this->output
				->set_content_type('application/json')
				->set_status_header(405)
				->set_output(json_encode(array(
					'success' => 'false',
					'message' => 'Method not allowed. Only POST requests are accepted.'
				)));
			return;
		}

		if (!$this->ion_auth->logged_in()) {
			$this->output
				->set_content_type('application/json')
				->set_status_header(403)
				->set_output(json_encode(array(
					'success' => 'false',
					'message' => 'No permissions for sending emails!'
				)));
			return;
		}

		$data = new stdClass();

		$mailto = $this->input->post("mailto");
		$data->mailto = empty($mailto) ? $this->config->item('admin_email') : $mailto;
		$data->subject = $this->input->post("subject");

		// Check for template
		if (empty($this->input->post("template"))) {
			$data->body = $this->input->post("body");
		} else {
			$body_data = json_decode($this->input->post("body"));
			$template = $this->input->post("template");
			$body = $this->load->view($this->config->item('email_templates', 'ion_auth') . $template, $body_data, TRUE);
			$data->body = $body;
		}

		$this->sendMailWithResponse($data);
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

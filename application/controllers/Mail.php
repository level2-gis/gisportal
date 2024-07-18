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
        //list available commands
    }


    function test()
    {
        // get mail data from post
        //$data = $this->input->post("data");

        //temp
        $data = new  stdClass();
        $data->mailto = $this->config->item('admin_email');
        $data->subject = 'Test mail';
        $data->body = 'This is just test mail to verify mail configuration is correct.';

        $this->sendMailWithResponse($data);


    }

    function send()
    {
		if (!$this->ion_auth->logged_in()) {
			echo "No permissions for sending email!";
		} else {

			$data = new  stdClass();
			$data->mailto = $this->input->post("mailto") == null ? $this->config->item('admin_email') : $this->input->post("mailto");
			$data->subject = $this->input->post("subject");

			//check template
			if (empty($this->input->post("template"))) {
				$data->body = $this->input->post("body");
			} else {
				$body_data = json_decode($this->input->post("body"));
				$body = $this->load->view($this->config->item('email_templates', 'ion_auth') . $this->input->post("template"), $body_data, TRUE);
				$data->body = $body;
			}

			$this->sendMailWithResponse($data);
		}
    }

    private function sendMailWithResponse($data)
    {
        $this->email->to($data->mailto);
        $this->email->from($this->config->item('admin_email'), $this->config->item('site_title') . " ". $_SERVER['HTTP_HOST']);
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
                    'message' => 'Email sent to ' . $data->mailto)
                ));
        }
    }
}

<?php


class Mail extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        //Load email library
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
        $data->mailto = $this->config->item('admin_email', 'ion_auth');
        $data->subject = 'Test mail';
        $data->body = 'This is just test mail to verify mail configuration is correct.';

        $this->sendMailWithResponse($data);


    }

    function send()
    {
        $data = new  stdClass();
        $data->mailto = $this->input->post("mailto") == null ? $this->config->item('admin_email', 'ion_auth') : $this->input->post("mailto");
        $data->subject = $this->input->post("subject");
        $data->body = $this->input->post("body");

        $this->sendMailWithResponse($data);
    }

    private function sendMailWithResponse($data)
    {
        $this->email->to($data->mailto);
        $this->email->from($this->config->item('admin_email', 'ion_auth'), $this->lang->line('gp_portal_title') . " ". $_SERVER['HTTP_HOST']);
        $this->email->subject($data->subject);
        $this->email->message($data->body);

        //Send email
        if (!$this->email->send()) {
            //i don't get this to write to error log so commenting out
            //log_message('error', 'GISPORTAL Mail sending error: '. $this->email->print_debugger(array('headers')););

            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(array(
                    'success' => 'false',
                    'message' => 'Error sending email.'
                )));
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
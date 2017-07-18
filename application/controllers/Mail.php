<?php


class Mail extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        //Load email library
        $this->load->library('email');

        $config = array(
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_port' => 465,
            'smtp_user' => $this->config->item('gmail_account'),
            'smtp_pass' => $this->config->item('gmail_password'),
            'charset' => 'utf-8'
        );

        $this->email->initialize($config);
        $this->email->set_mailtype("text");
        $this->email->set_newline("\r\n");

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
        $data->mailto = $this->config->item('company_email');
        $data->subject = 'Test mail';
        $data->body = 'This is just test mail to verify mail configuration is correct.';

        $this->sendMailWithResponse($data);


    }

    function send()
    {
        $data = new  stdClass();
        $data->mailto = $this->input->post("mailto") == null ? $this->config->item('company_email') : $this->input->post("mailto");
        $data->subject = $this->input->post("subject");
        $data->body = $this->input->post("body");

        $this->sendMailWithResponse($data);
    }

    private function sendMailWithResponse($data)
    {
        $this->email->to($data->mailto);
        $this->email->from($this->config->item('gmail_account'), $this->config->item('portal_title'));
        $this->email->subject($data->subject);
        $this->email->message($data->body);

        //Send email
        if (!$this->email->send()) {
            //log_message('error', 'Mail sending error: '. implode(',', $config));

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
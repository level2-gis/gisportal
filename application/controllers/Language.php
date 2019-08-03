<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Language extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url'));
        $this->load->database();
        $this->load->model('user_model');
    }

    function switchLang($code = '')
    {
        $this->session->set_userdata('lang', $code);

        $id = $this->session->userdata('user_id');

        $data = array(
            'user_id' => $id,
            'lang' => $code
        );

        $this->user_model->save_user($data);

        redirect($_SERVER['HTTP_REFERER']);
    }

}
<?php

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('portal_model');
    }

    /**
     * This controller is only for backwards compatibility to redirect old /login request to root where it will be handled
     */
    function index()
    {
        $ru = $this->input->get('ru');

        $this->session->set_flashdata('message','<div class="alert alert-info text-center">' . $this->portal_model->get_login_msg() . '</div>');

        if (empty($ru)) {
            redirect("/auth/login");
        } else {
            redirect("/auth/login?ru=".$ru);
        }
    }
}



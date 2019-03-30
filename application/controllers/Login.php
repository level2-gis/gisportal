<?php

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * This controller is only for backwards compatibility to redirect old /login request to root where it will be handled
     */
    function index()
    {
        $ru = $this->input->get('ru');

        if (empty($ru)) {
            redirect("/auth/login");
        } else {
            redirect("/auth/login?ru=".$ru);
        }
    }
}



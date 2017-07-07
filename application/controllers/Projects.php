<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller {


    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('clients_model');
        $this->load->helper(array('url', 'html'));
        $this->load->library('session');
    }

    public function index()
    {
        $data['title'] = 'Projects';
        $data['projects'] = $this->projects_model->get_projects();

        $this->load->view('templates/header', $data);
        $this->load->view('projects', $data);
        $this->load->view('templates/footer', $data);
    }

    public function view($client_id = false)
    {
        $data['title'] = 'Projects';
        $data['projects'] = $this->projects_model->get_projects($client_id);
        $data['client']= $this->clients_model->get_client_by_id($client_id);

        $this->load->view('templates/header', $data);
        $this->load->view('projects', $data);
        $this->load->view('templates/footer', $data);
    }

}
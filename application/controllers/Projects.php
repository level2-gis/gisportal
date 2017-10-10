<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller {


    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('user_model');
        $this->load->helper(array('url', 'html'));
        $this->load->library('session');
        $this->lang->load('gisportal_lang');
    }

    //TODO what to do with default view, list all projects or redirect
    public function index()
    {
//        $data['title'] = 'Projects';
//        $data['projects'] = $this->projects_model->get_projects();
//
//        $this->load->view('templates/header', $data);
//        $this->load->view('projects', $data);
//        $this->load->view('templates/footer', $data);
        redirect("home/index");
    }

    public function view($client_id = false)
    {
        if ($client_id === FALSE) {
            redirect("home/index");
        }

        if ($this->session->userdata('user_is_logged_in')){
            $data['title'] = $this->lang->line('gp_projects_title');

            $user = $this->user_model->get_user_by_id($this->session->userdata('uid'));

            $data['projects'] = $this->projects_model->get_projects($client_id, $user[0]->project_ids, $user[0]->admin);

            $this->load->view('templates/header', $data);
            $this->load->view('projects', $data);
            $this->load->view('templates/footer', $data);
        } else {

            $data['title'] = $this->lang->line('gp_home');

            $this->load->view('templates/header', $data);
            $this->load->view('home_view');
            $this->load->view('templates/footer', $data);
        }
    }

}
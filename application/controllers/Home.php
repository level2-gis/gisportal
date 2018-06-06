<?php

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Client_model');
        $this->load->model('User_model');
        $this->load->model('Project_model');
        $this->load->helper(array('url', 'html'));
    }

    function index()
    {
        if (!$this->session->userdata('user_is_logged_in')) {
            $this->session->set_flashdata('msg','<div class="alert alert-info text-center">' . $this->lang->line('gp_welcome_message') . '</div>');
            redirect('login/');
        }

        $data['title'] = $this->lang->line('gp_clients_title');
        $data['scheme'] = $_SERVER["REQUEST_SCHEME"];
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

        if ($this->session->userdata('uid') !== null) {
            $user = $this->User_model->get_user_by_id($this->session->userdata('uid'));
            $data['clients'] = $this->Client_model->get_clients($user->project_ids, $user->admin, false);
        } else {
            $data['clients'] = null;
        }

        $this->load->view('templates/header', $data);

        if (($data['clients'] === null) or (empty($data['clients']))) {
            $data['projects_public'] = $this->Project_model->get_public_projects();
            if ($this->session->userdata('user_name') !== 'guest') {
                $this->load->view('message_view', array('message' => $this->lang->line('gp_no_projects'), 'type' => 'warning'));
            }
            if (($data['projects_public'] === null) or (empty($data['projects_public']))) {
                $this->load->view('message_view', array('message' => $this->lang->line('gp_no_public_projects'), 'type' => 'info'));
            } else {
                $this->load->view('public_projects_view', $data);
            }
        } else if (count($data['clients']) === 1) {
            redirect('projects/view/' . $data['clients'][0][id]);
        } else {
            $this->load->view('clients', $data);
        }
        $this->load->view('templates/footer', $data);
    }

    function logout()
    {
        // destroy session
        $data = array('login' => '', 'uname' => '', 'uid' => '');
        $this->session->unset_userdata($data);
        $this->session->sess_destroy();
        redirect('login/');
    }
}



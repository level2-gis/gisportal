<?php

class Profile extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url', 'html'));
        $this->load->database();
        $this->load->model('user_model');
        $this->load->model('project_model');

    }

    function index()
    {
        if (!$this->session->userdata('user_is_logged_in')) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $details = $this->user_model->get_user_by_id($this->session->userdata('user_id'));

        $data['title'] = $this->lang->line('gp_profile_title');
        $data['projects_public'] = $this->project_model->get_public_projects();
        $data['available_languages'] = get_languages();
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

        $this->load->view('templates/header', $data);

        if ($this->session->userdata('user_id') !== null) {
            $data['user'] = $details;
            if ($details->admin) {
                $data['user']->display_name .= ' (Administrator)';
            }
            //$data['projects'] = $this->project_model->get_projects(false, $details->project_ids, $details->admin);

            $this->load->view('profile_view', $data);
        } else {
            $data['user'] = null;
            $data['projects'] = null;
        }

//        if (($data['projects'] === null) or (empty($data['projects']))) {
//            if ($this->session->userdata('user_name') !== 'guest') {
//                $this->load->view('message_view', array('message' => $this->lang->line('gp_no_projects'), 'type' => 'warning'));
//            }
//        } else {
//            $this->load->view('user_projects_view', $data);
//        }

        if (($data['projects_public'] === null) or (empty($data['projects_public']))) {
            $this->load->view('message_view', array('message' => $this->lang->line('gp_no_public_projects'), 'type' => 'info'));
        } else {
            $this->load->view('public_projects_view', $data);
        }

        $this->load->view('templates/footer', $data);

    }
}
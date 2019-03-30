<?php

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('client_model');
        $this->load->model('user_model');
        $this->load->model('project_model');
        $this->load->helper(array('url', 'html'));
    }

    function index()
    {
        if (!$this->ion_auth->logged_in())
        {
            $this->session->set_flashdata('msg','<div class="alert alert-info text-center">' . $this->lang->line('gp_welcome_message') . '</div>');
            redirect('auth/login', 'refresh');
        }

        $data['title'] = $this->lang->line('gp_clients_title');
        $data['scheme'] = $_SERVER["REQUEST_SCHEME"];
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

        $admin = $this->ion_auth->is_admin();

        if ($this->session->userdata('user_id') !== null) {
            $user = $this->user_model->get_user_by_id($this->session->userdata('user_id'));
            $groups = $this->user_model->get_project_group_ids($user->user_id);
            $data['clients'] = $this->client_model->get_clients($groups, $admin, false);
        } else {
            $data['clients'] = null;
        }

        $this->load->view('templates/header', $data);

        if (($data['clients'] === null) or (empty($data['clients']))) {
            $data['projects_public'] = $this->project_model->get_public_projects();
            if ($this->session->userdata('user_name') !== 'guest') {
                $this->load->view('message_view', array('message' => $this->lang->line('gp_no_projects'), 'type' => 'warning'));
            }
            if (($data['projects_public'] === null) or (empty($data['projects_public']))) {
                $this->load->view('message_view', array('message' => $this->lang->line('gp_no_public_projects'), 'type' => 'info'));
            } else {
                $this->load->view('public_projects_view', $data);
            }
        } else if (count($data['clients']) === 1) {
            redirect('projects/view/' . $data['clients'][0]['id']);
        } else {
            $this->load->view('clients', $data);
        }
        $this->load->view('templates/footer', $data);
    }
}



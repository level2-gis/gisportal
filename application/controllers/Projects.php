<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('user_model');
        $this->load->helper(array('url', 'html', 'path'));
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
        redirect("home/");
    }

    public function view($client_id = false)
    {
        if ($client_id === FALSE) {
            redirect("/");
        }

        if (!$this->session->userdata('user_is_logged_in')) {
            redirect('login/');
        }

        $data['title'] = $this->lang->line('gp_projects_title');

        $user = $this->user_model->get_user_by_id($this->session->userdata('uid'));

        $data['projects'] = $this->projects_model->get_projects($client_id, $user[0]->project_ids, $user[0]->admin);

        $this->load->view('templates/header', $data);
        $this->load->view('projects', $data);
        $this->load->view('templates/footer', $data);

    }

    public function upload($project_id = false)
    {
        if ($project_id === FALSE) {
            redirect("/");
        }

        try {
            $project = $this->projects_model->get_project_by_id($project_id);
            if ($project == null) {
                throw new Exception('Project not found!');
            }
            $client_name = $project[0]->client_name;
            $project_name = $project[0]->name;

            $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'), false) . $client_name, false);
            $dir .= DIRECTORY_SEPARATOR . $project_name;

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $config['upload_path']          = $dir;
            $config['allowed_types']        = 'gif|jpg|png|pdf';
            //$config['max_size']             = 100;
            //$config['max_width']            = 1024;
            //$config['max_height']           = 768;

            $this->load->library('upload', $config);

            if ( ! $this->upload->do_upload('userfile'))
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(500)
                    ->set_output(json_encode(array(
                        'success' => 'false',
                        'message' => $this->upload->display_errors()
                    )));

            }
            else
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode(array(
                        'success' => 'true',
                        'message' => $this->lang->line('gp_upload_success') . ': '.$this->upload->file_name
                    )));

            }

        } catch (Exception $e) {

            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(array(
                    'success' => 'false',
                    'message' => $e->getMessage()
                )));
        }


    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients extends CI_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('clients_model');
        $this->load->helper(array('form', 'url', 'html', 'path', 'upload', 'file', 'date', 'number'));
    }

    public function index()
    {
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

        $client = $this->clients_model->get_client_by_id($client_id)[0];
        $client_name = $client->name;
        $upload_dir = set_realpath($this->config->item('main_upload_dir'), false);

        $data['client'] = $client;
        $data['title'] = $client->display_name;

        $this->load->view('templates/header', $data);

        //client info
        $this->load->view('client_view', $data);

        //folder uploads
        $upload['main'] = $this->lang->line('gp_uploaded_files');
        $upload['test'] = check_main_upload_dir();

        $upload['files'] = get_dir_file_info($upload_dir.$client_name);
        $upload['dir'] = $this->config->item('main_upload_dir') . $client_name . DIRECTORY_SEPARATOR;

        if($upload['files']) {
            $this->load->view('client_files', $upload);
        }

        //upload form if main folder exists
        if ($upload['test'] === '') {
            $this->load->view('client_upload_form', array('client_id' => $client_id));
        }
        $this->load->view('templates/footer', $data);

    }

    public function upload($client_id = false)
    {
        if ($client_id === FALSE) {
            redirect("/");
        }

        $client = $this->clients_model->get_client_by_id($client_id)[0];
        $client_name = $client->name;

        $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'),false) . $client_name, false);

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
            $this->session->set_flashdata('upload_msg', '<div class="alert alert-danger">' . $this->upload->display_errors() . ' ('.$this->upload->file_name.')</div>');
            redirect('clients/view/'.$client_id);
        }
        else
        {
            //$data = array('upload_data' => $this->upload->data());

            $this->session->set_flashdata('upload_msg', '<div class="alert alert-success">' . $this->lang->line('gp_upload_success') . ' ('.$this->upload->file_name.')</div>');
            redirect('clients/view/'.$client_id);
        }
    }
}
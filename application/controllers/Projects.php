<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('client_model');
        $this->load->model('project_model');
        $this->load->model('user_model');
        $this->load->model('layer_model');
        $this->load->helper(array('url', 'html', 'path', 'eqwc_parse', 'eqwc_dir', 'file'));
    }

    public function index()
    {
        if (!$this->session->userdata('admin')){
            redirect('/');
        }

		$data['title'] = $this->lang->line('gp_projects_title');
        $data['projects'] = $this->project_model->get_projects(false, false, true);
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

        $this->load->view('templates/header', $data);
        $this->load->view('projects_admin', $data);
        $this->load->view('templates/footer', $data);
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
        $data['scheme'] = $_SERVER["REQUEST_SCHEME"];
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

        $user = $this->user_model->get_user_by_id($this->session->userdata('uid'));

        $data['projects'] = $this->project_model->get_projects($client_id, $user->project_ids, $user->admin);

        $this->load->view('templates/header', $data);
        $this->load->view('projects', $data);
        $this->load->view('templates/footer', $data);

    }

    public function upload_admin($client_id = false) {

        if (!$this->session->userdata('admin')){
            redirect('/');
        }

        try {
            if ($client_id === FALSE) {
                throw new Exception('Client not found!');
            }

            $project_id = $this->input->post('project_id');
            if ($project_id) {
                //editing existing project
                $project = $this->project_model->get_project($project_id);
            }

            //TODO pri obstoječih moraš nastaviti project_id in shraniti v project_path če obstaja

            $client = $this->client_model->get_client($client_id);
            if ($client == null) {
                throw new Exception('Client not found!');
            }
            $client_name = $client->name;
            //$project_name = $project->name;

            //put project to client subfolder, by default
            $dir = set_realpath(get_qgis_project_path() . $client_name);

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'qgs';
            $config['overwrite'] = true;
            $config['file_ext_tolower'] = true;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('userfile')) {
                $this->session->set_flashdata('upload_msg', '<div class="alert alert-danger">' . $this->upload->display_errors() . ' ('.$this->upload->file_name.')</div>');
                redirect('projects/edit/'.$project_id);
            } else {
                //$this->session->set_flashdata('upload_msg', '<div class="alert alert-success">' . $this->lang->line('gp_upload_success') . ' ('.$this->upload->file_name.')</div>');
                //pass qgis project name and client_id
                $file_name = $this->upload->file_name;
                $ext = $this->upload->file_ext;
                $project_name = str_replace($ext,'',$file_name);
                $this->session->set_flashdata('project_name',$project_name);
                $this->session->set_flashdata('client_id',$client_id);
                redirect('projects/edit/'.$project_id);
            }

        } catch (Exception $e) {

            $this->session->set_flashdata('upload_msg', '<div class="alert alert-danger">' . $e->getMessage() . '</div>');
            redirect('projects/edit/'.$project_id);
        }
    }



    public function upload($project_id = false)
    {
        if ($project_id === FALSE) {
            redirect("/");
        }

        try {
            $project = $this->project_model->get_project($project_id);
            if ($project == null) {
                throw new Exception('Project not found!');
            }
            $client = $this->client_model->get_client($project->client_id);
            if ($client == null) {
                throw new Exception('Client not found!');
            }
            $client_name = $client->name;
            $project_name = $project->name;

            $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'), false) . $client_name, false);
            $dir .= DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'gif|jpg|png|pdf';
            $config['overwrite'] = true;
            $config['file_ext_tolower'] = true;
            //$config['max_size']             = 100;
            //$config['max_width']            = 1024;
            //$config['max_height']           = 768;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('userfile')) {
                throw new Exception ($this->upload->display_errors('', ''));
            }

            //create thumb if upload is image
            if (strpos($this->upload->file_type, 'image/') > -1) {
                $res = self::imageResize($dir, $this->upload->file_name);
                if (!$res) {
                    throw new Exception ($dir.$this->upload->file_name.' '.$this->image_lib->display_errors('', ''));
                }
            }

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(200)
                ->set_output(json_encode(array(
                    'success' => true,
                    'message' => $this->lang->line('gp_upload_success'),
                    'file' => $this->upload->file_name
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));


        } catch (Exception $e) {

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(500)
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    public function files($project_id = false)
    {
        $scheme = $_SERVER["REQUEST_SCHEME"];

        if ($project_id === FALSE) {
            redirect("/");
        }

        try {
            $project = $this->project_model->get_project($project_id);
            if ($project == null) {
                throw new Exception('Project not found!');
            }
            $client = $this->client_model->get_client($project->client_id);
            if ($client == null) {
                throw new Exception('Client not found!');
            }
            $client_name = $client->name;
            $project_name = $project->name;

            $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'), false) . $client_name, false);
            $dir .= DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $files = get_dir_file_info($dir);

            $webdir = $this->config->item('main_upload_web') . $client_name . DIRECTORY_SEPARATOR. $project_name . DIRECTORY_SEPARATOR;

            $report = new stdClass();
            $report->files = [];
            foreach ($files as $key => $value)
            {
                if (is_dir($value["server_path"])) {
                    continue;
                }

                $extension = strtoupper(substr(strrchr($value["name"], '.'), 1));

                $thumb='';
                $url = base_url($webdir.$value['name']);
                if(file_exists($dir.'thumb'.DIRECTORY_SEPARATOR . $value["name"])) {
                    $thumb = base_url($webdir.'thumb'.DIRECTORY_SEPARATOR .$value['name']);
                } else {
                    $thumb = $scheme . "://dummyimage.com/225x150/e0e0e0/706e70?text=".$extension;
                }

                $newVal = array(
                    "name" => $value["name"],
                    "size" => $value["size"],
                    "lastmod" => $value["date"],
                    "thumb" => $thumb
                );

                array_push($report->files, $newVal);
            }

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(200)
                ->set_output(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));

        } catch (Exception $e) {

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(500)
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    public function edit($project_id = false)
    {
//        if (!$this->session->userdata('user_is_logged_in') || !$this->session->userdata('admin')){
//            redirect('/login?ru=/' . uri_string());
//        }

        if (!$this->session->userdata('admin')){
            redirect('/');
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('name', 'lang:gp_name', 'trim|required|alpha_dash|callback__unique_name');
        //$this->form_validation->set_rules('display_name', 'lang:gp_display_name', 'trim|required');
        $this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
        $this->form_validation->set_rules('ordr','lang:gp_order','integer');

        if ($this->form_validation->run() === FALSE)
        {
            $data['title'] = $this->lang->line('gp_create').' '.$this->lang->line('gp_new').' '.$this->lang->line('gp_project');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['creating'] = true;

            $em = $this->project_model->new_project();
            //pass data from uploaded project
            $em["name"] = $this->session->flashdata('project_name') ? $this->session->flashdata('project_name') : '';
            $em["client_id"] = $this->session->flashdata('client_id') ? $this->session->flashdata('client_id') : null;
            if(sizeof($_POST) > 0){
                $em = $this->extractProjectData();
                $data['title'] = $this->lang->line('gp_edit').' '.$this->lang->line('gp_project') .' '. $em['display_name'];
                $data['creating'] = false;
            } else {
                if ($project_id !== false){
                    $dq = $this->project_model->get_project($project_id);
                    if ($dq->id != null){
                        $em = (array)$dq;
                        $data['title'] = $this->lang->line('gp_edit').' '.$this->lang->line('gp_project') .' '. $em['display_name'];
                        $data['creating'] = false;
                    }
                }
            }


            $data['project'] = $em;
            $data['image'] = $this->getImage($em['name']);

            $this->loadmeta($data);
            $this->qgisinfo($data);

            $this->load->view('templates/header', $data);
            $this->load->view('project_upload_form', $data);
            $this->load->view('project_edit', $data);
            $this->load->view('project_edit_layers_func');
            //$this->load->view('templates/footer', $data);
        } else {

            $project = $this->extractProjectData();
            $users = $this->extractUserProjectData();
            try {
                $project_id = $this->project_model->upsert_project($project, $users);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_project').' <strong>' . $project['name'] . '</strong>'.$this->lang->line('gp_saved').'</div>');
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
            }
            if($this->input->post('return') == null){
                redirect('/projects/edit/' . $project_id);
            } else {
                redirect('/projects');
            }
        }
    }

    public function remove($id)
    {
        if (!$this->session->userdata('admin')){
            redirect('/');
        }

        $project = (array)$this->project_model->get_project($id);

        // check if the project exists before trying to delete it
        if(isset($project['id']))
        {
            try {
                $this->project_model->delete_project($id);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_project').' <strong>' . $this->input->post('name') . '</strong>'.$this->lang->line('gp_deleted').'</div>');
                redirect('/projects');
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
                redirect('/projects/edit/'.$id);
            }
        }
        else
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">The project you are trying to delete does not exist.</div>');
    }

    public function _unique_name($name) {

        //test if we already have name in database
        $exist = $this->project_model->project_exists($name);
        $id = $this->input->post('id');

        if ($exist && empty($id)) {
            $this->form_validation->set_message('_unique_name', $this->lang->line('gp_project').' '.$name.$this->lang->line('gp_exists').'!');
            return false;
        }

        return true;
    }

    private function imageResize($dir, $fn) {

        try {
            if (!file_exists($dir . 'thumb')) {
                mkdir($dir . 'thumb', 0777, true);
            }

            $config['image_library'] = 'gd2';
            $config['source_image'] = $dir . $fn;
            $config['new_image'] = $dir . 'thumb' . DIRECTORY_SEPARATOR;    //only have to specify new folder
            $config['maintain_ratio'] = TRUE;
            $config['width'] = 225;
            $config['height'] = 150;

            $this->load->library('image_lib', $config);

            return $this->image_lib->resize();
        }
        catch (Exception $e){
            return false;
        }
    }

    private function extractProjectData(){
        $data = array(
            'id'                        => $this->input->post('id'),
            'name'                      => $this->input->post('name'),
            'overview_layer_id'         => set_null($this->input->post('overview_layer_id')),
            'base_layers_ids'           => $this->input->post('base_layers_ids'),
            'extra_layers_ids'          => $this->input->post('extra_layers_ids'),
            'client_id'                 => set_null($this->input->post('client_id')),
            'public'                    => set_bool($this->input->post('public')),
            'display_name'              => $this->input->post('display_name'),
            'crs'                       => $this->input->post('crs'),
            'description'               => $this->input->post('description'),
            'contact'                   => $this->input->post('contact'),
            'restrict_to_start_extent'  => set_bool($this->input->post('restrict_to_start_extent')),
            'geolocation'               => set_bool($this->input->post('geolocation')),
            'feedback'                  => set_bool($this->input->post('feedback')),
            'measurements'              => set_bool($this->input->post('measurements')),
            'feedback_email'            => $this->input->post('feedback_email'),
            'print'                     => set_bool($this->input->post('print')),
            'zoom_back_forward'         => set_bool($this->input->post('zoom_back_forward')),
            'identify_mode'             => set_bool($this->input->post('identify_mode')),
            'permalink'                 => set_bool($this->input->post('permalink')),
            'ordr'                      => ($this->input->post('ordr'))
            //'project_path'              => $this->input->post('project_path')
        );

//        //TODO move to helper foo
//        if ($this->input->post('base_layers_ids') != null){
//            $blids = implode($this->input->post('base_layers_ids'),',');
//            if ($blids != ''){
//                $data['base_layers_ids'] = '{' . $blids . '}';
//            }
//        }

        return $data;
    }


    private function getImage($name) {
        $path = 'assets/img/projects/'.$name.'.png';
        $fn = set_realpath(FCPATH.$path, false);

        if (is_file($fn)) {
            return "<img class='img-responsive' src='" . base_url($path) . "'>";
        }
        else {
            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-alert' aria-hidden='true'></span> Image missing (250x177px)</br>".$fn."</div>";
        }
    }




    private function extractUserProjectData(){

        if ($this->input->post('user_projects_ids') != null){
            return implode($this->input->post('user_projects_ids'),',');
        }

        return null;
    }

    private function loadmeta(&$data){
        $data['clients'] = $this->client_model->get_clients();
        $data['user_projects'] = $this->user_model->get_users_with_project_flag($data['project']['id']);
        $data['base_layers'] = $this->layer_model->get_layers_with_project_flag($data['project']['base_layers_ids']);
        $data['extra_layers'] = $this->layer_model->get_layers_with_project_flag($data['project']['extra_layers_ids']);

//        $directory = new RecursiveDirectoryIterator(PROJECT_PATH);
//        $iterator  = new RecursiveIteratorIterator($directory);
//
//        foreach(new RegexIterator($iterator, '/^.+\.qgs$/i', RecursiveRegexIterator::GET_MATCH) as $file) {
//            $f = str_replace('\\', '/', $file[0]);
//            array_push($data['project_paths'], str_replace(PROJECT_PATH, '', $f));
//        }
    }

    private function qgisinfo(&$data){
        if ($data['project']['name'] == '') {
            $data['qgis_check'] =  ["valid" => false, "name" => ""];
            return;
        }

        $project_name = $data['project']['name'];
        $project_path = $data['project']['project_path'];
        $client_key = array_search($data['project']['client_id'], array_column($data['clients'], 'id'));
        $client_name = $data['clients'][$client_key]['name'];

        $data['qgis_check'] = check_qgis_project($project_name, $project_path, $client_name);
    }


}
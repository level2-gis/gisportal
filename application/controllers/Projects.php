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
        $this->load->helper(array('url', 'html', 'path', 'eqwc_parse', 'eqwc_dir'));
    }

    public function index()
    {
        if (!$this->session->userdata('admin')){
            redirect('/');
        }

		$data['title'] = $this->lang->line('gp_projects_title');
        $data['projects'] = $this->project_model->get_projects(false, false, true);

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

        $user = $this->user_model->get_user_by_id($this->session->userdata('uid'));

        $data['projects'] = $this->project_model->get_projects($client_id, $user->project_ids, $user->admin);

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
            $project = $this->project_model->get_project($project_id);
            if ($project == null) {
                throw new Exception('Project not found!');
            }
            $client_name = $project->client_name;
            $project_name = $project->name;

            $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'), false) . $client_name, false);
            $dir .= DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $config['upload_path']          = $dir;
            $config['allowed_types']        = 'gif|jpg|png|pdf';
            $config['overwrite']            = true;
            $config['file_ext_tolower']     = true;            
            //$config['max_size']             = 100;
            //$config['max_width']            = 1024;
            //$config['max_height']           = 768;

            $this->load->library('upload', $config);

            if ( ! $this->upload->do_upload('userfile'))
            {
                $this->output
                    ->set_content_type('text/html')
                    ->set_status_header(500)
                    ->set_output(json_encode(array(
                        'success' => false,
                        'message' => $this->upload->display_errors('','')
                    )));

            }
            else
            {
                //create thumb if upload is image
                if(strpos($this->upload->file_type,'image/')>-1) {
                    self::imageResize($dir, $this->upload->file_name);
                }

                $this->output
                    ->set_content_type('text/html')
                    ->set_status_header(200)
                    ->set_output(json_encode(array(
                        'success' => true,
                        'message' => $this->lang->line('gp_upload_success'),
                        'file'    => $this->upload->file_name
                    )));

            }

        } catch (Exception $e) {

            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $e->getMessage()
                )));
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
            $data['creating'] = true;

            $em = $this->project_model->new_project();
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
            $this->loadmeta($data);
            $this->qgisinfo($data);

            $this->load->view('templates/header', $data);
            $this->load->view('project_edit', $data);
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

        if (!file_exists($dir . 'thumb')) {
            mkdir($dir . 'thumb', 0777, true);
        }

        $config['image_library']    = 'gd2';
        $config['source_image']     = $dir . $fn;
        $config['new_image']        = $dir . 'thumb' . DIRECTORY_SEPARATOR;    //only have to specify new folder
        $config['maintain_ratio']   = TRUE;
        $config['width']            = 225;
        $config['height']           = 150;

        $this->load->library('image_lib', $config);

        if ( ! $this->image_lib->resize())
        {
            //log error
            log_message('error', $this->image_lib->display_errors());
        }
    }

    private function extractProjectData(){
        $data = array(
            'id'                        => $this->input->post('id'),
            'name'                      => $this->input->post('name'),
            'overview_layer_id'         => set_null($this->input->post('overview_layer_id')),
            'base_layers_ids'           => set_arr($this->input->post('base_layers_ids')),
            'extra_layers_ids'          => set_arr($this->input->post('extra_layers_ids')),
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
        if ($data['project']['id'] == null) {
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
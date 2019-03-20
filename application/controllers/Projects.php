<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('client_model');
        $this->load->model('project_model');
        $this->load->model('project_group_model');
        $this->load->model('user_model');
        $this->load->model('layer_model');
        $this->load->model('plugin_model');
        $this->load->helper(array('url', 'html', 'path', 'eqwc_parse', 'eqwc_dir', 'file', 'download'));
    }

    public function index()
    {
        //allow viewing of projects to every logged in user, not only admin (user sees only projects with permission)
        if (!$this->ion_auth->logged_in()){
            redirect('/auth/login?ru=/' . uri_string());
        }

		$data['title'] = $this->lang->line('gp_projects_title');
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

        $user = $this->user_model->get_user_by_id($this->session->userdata('user_id'));
        $groups = $this->user_model->get_project_group_ids($user->user_id);
        $admin = $this->ion_auth->is_admin();

        $data['projects'] = $this->project_model->get_projects(false, $groups, $admin);

        $this->load->view('templates/header', $data);
        $this->load->view('projects_admin', $data);
        $this->load->view('templates/footer', $data);
    }

    /*
     * Portal view
     */
    public function view($client_id = false)
    {
        if ($client_id === FALSE) {
            redirect("/");
        }

        if (!$this->ion_auth->logged_in()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $data['title'] = $this->lang->line('gp_projects_title');
        $data['scheme'] = $_SERVER["REQUEST_SCHEME"];
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

        $user = $this->user_model->get_user_by_id($this->session->userdata('user_id'));
        $groups = $this->user_model->get_project_group_ids($user->user_id);
        $admin = $this->ion_auth->is_admin();

        $data['projects'] = $this->project_model->get_projects($client_id, $groups, $admin);

        $this->load->view('templates/header', $data);
        $this->load->view('projects', $data);
        $this->load->view('templates/footer', $data);

    }

    /**
     * Upload QGIS project file
     */
    public function upload_admin($client_id = false) {

        if (!$this->ion_auth->is_admin()){
            redirect('/');
        }

        try {
            if ($client_id === FALSE) {
                throw new Exception('Client not found!');
            }

            $client = $this->client_model->get_client($client_id);
            if ($client == null) {
                throw new Exception('Client not found!');
            }
            $client_name = $client->name;

            //put project to which subfolder, from config
            $dir = set_realpath(get_qgis_project_path($client_name));

            $project_id = $this->input->post('project_id');
            if ($project_id) {
                //editing existing project, get project directory
                $project = $this->project_model->get_project($project_id);
                $qgis = check_qgis_project($project->name, $project->project_path, $client_name);
                if ($qgis["valid"]) {
                    $dir = set_realpath(dirname($qgis["name"]));
                }
            }

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'qgs';
            $config['overwrite'] = true;
            $config['file_ext_tolower'] = true;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('userfile')) {
                $this->session->set_flashdata('upload_msg', '<div class="alert alert-danger">' . $dir . $this->upload->display_errors() . ' ('.$this->upload->file_name.')</div>');
                if(!empty($project_id)) {
                    redirect('projects/edit/'.$project_id);
                } else {
                    redirect('projects/create/'.NEW_UPLOAD);
                }
            } else {
                $this->session->set_flashdata('upload_msg', '<div class="alert alert-success">' . $this->lang->line('gp_upload_success') . ' ('.$this->upload->file_name.')</div>');
                //pass qgis project name and client_id
                $file_name = $this->upload->file_name;
                $ext = $this->upload->file_ext;
                $project_name = str_replace($ext,'',$file_name);

                $this->session->set_flashdata('project_name',$project_name);
                $this->session->set_flashdata('client_id',$client_id);

                //set permission to 777
                if(is_file($dir . $file_name))
                {
                    chmod($dir . $file_name, 0777);
                }

                $this->clearCurrentUserProjectSession();
                if(!empty($project_id)) {
                    redirect('projects/edit/'.$project_id);
                } else {
                    redirect('projects/create/'.NEW_UPLOAD);
                }
            }

        } catch (Exception $e) {

            $this->session->set_flashdata('upload_msg', '<div class="alert alert-danger">' . $e->getMessage() . '</div>');
            if(!empty($project_id)) {
                redirect('projects/edit/'.$project_id);
            } else {
                redirect('projects/create/'.NEW_UPLOAD);
            }
        }
    }


    /**
     * Public upload files
     * Used in Editor plugin
     */
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
            //TODO client_id on project!
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
            //TODO client_id on project!
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

    /**
     * Method to download QGIS project file for administrators
     */
    public function download($project_id = false)
    {
        if (!$this->ion_auth->is_admin()) {
            redirect('/');
        }

        if ($project_id === FALSE) {
            redirect("/");
        }

        try {
            $project = $this->project_model->get_project($project_id);
            //TODO client_id on project!
            $client = $this->client_model->get_client($project->client_id);

            if ($client == null) {
                throw new Exception('Client not found!');
            }

            $client_name = $client->name;
            $qgs_file = '';
            $check = check_qgis_project($project->name, $project->project_path, $client_name);

            if($check['valid']) {
                $qgs_file = $check["name"];
            } else {
                throw new Exception($check['name']);
            }

            force_download($qgs_file, NULL);

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
        if (!$this->ion_auth->is_admin()){
            redirect('/auth/login?ru=/' . uri_string());
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        //$this->form_validation->set_rules('name', 'lang:gp_name', 'trim|required|alpha_dash|callback__unique_name');
        //$this->form_validation->set_rules('display_name', 'lang:gp_display_name', 'trim|required');
        //$this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
        $this->form_validation->set_rules('project_group_id', 'lang:gp_group', 'required');
        $this->form_validation->set_rules('ordr','lang:gp_order','integer');
        $this->form_validation->set_rules('feedback_email', 'lang:gp_feedback_email', 'valid_email');

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
            $data['clients'] = $this->client_model->get_clients();
            $data['groups'] = $this->project_group_model->get_project_groups($em["client_id"], true);

            $this->loadmeta($data);
            $this->qgisinfo($data);

            $this->load->view('templates/header', $data);
            $this->load->view('project_title', $data);
            $this->load->view('project_check', $data);
            $this->load->view('project_upload_form', $data);
            $this->load->view('project_edit', $data);
            //$this->load->view('templates/footer', $data);
        } else {

            $project = $this->extractProjectData();
            //$users = $this->extractUserProjectData();
            try {
                $project_id = $this->project_model->upsert_project($project);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_project').' <strong>' . $project['name'] . '</strong>'.$this->lang->line('gp_saved').'</div>');
                $this->clearCurrentUserProjectSession();
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

    public function create($action = FALSE) {

        if (!$this->ion_auth->is_admin()){
            redirect('/');
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('name', 'lang:gp_name', 'trim|required|alpha_dash|callback__unique_name');
        $this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
        $this->form_validation->set_rules('project_group_id', 'lang:gp_group', 'required');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = $this->lang->line('gp_create') . ' ' . $this->lang->line('gp_new') . ' ' . $this->lang->line('gp_project');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['creating'] = true;

            $em = $this->project_model->new_project();

            //pass data from uploaded project or session
            $em["name"] = $this->session->flashdata('project_name') ? $this->session->flashdata('project_name') : $this->input->post('name');
            $em["client_id"] = $this->session->flashdata('client_id') ? $this->session->flashdata('client_id') : $this->input->post('client_id');
            $em["display_name"] = $this->input->post('display_name');

            $data['project'] = $em;
            $data['templates'] = $this->project_model->get_templates();
            $data['action'] = $action;
            $data['clients'] = $this->client_model->get_clients();
            if($em["client_id"]) {
                $data['groups'] = $this->project_group_model->get_project_groups($em["client_id"], true);
            } else {
                $data['groups'] = [];
            }

            $this->load->view('templates/header', $data);
            $this->load->view('project_title', $data);

            if($action==NEW_UPLOAD) {
                $this->load->view('project_upload_form', $data);
            }

            $this->load->view('project_create', $data);

        } else {

            $project = $this->project_model->new_project();

            //pass data from uploaded project or session
            $project["name"] = $this->session->flashdata('project_name') ? $this->session->flashdata('project_name') : $this->input->post('name');
            $project["client_id"] = $this->session->flashdata('client_id') ? $this->session->flashdata('client_id') : $this->input->post('client_id');
            $project["display_name"] = $this->input->post('display_name');
            $project["project_group_id"] = $this->input->post('project_group_id');
            $project["template"] = $this->input->post('template');

            //TODO client_id on project!
            $client = $this->client_model->get_client($project["client_id"]);

            //$users = $this->extractUserProjectData();
            $project_id = null;
            try {
                //copy template if selected
                if(!empty($project["template"])) {
                    $this->project_model->copy_template($project["template"],$project["name"],$client->name);
                }

                $project_id = $this->project_model->upsert_project($project);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_project').' <strong>' . $project['name'] . '</strong>'.$this->lang->line('gp_saved').'</div>');
                $this->clearCurrentUserProjectSession();
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
        if (!$this->ion_auth->is_admin()){
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

    /**
     * Method to list available services for project
     */
    public function services($project_id = false)
    {
        if (!$this->ion_auth->is_admin()){
            redirect('/');
        }

        $project = $this->project_model->get_project($project_id);
        if(!$project) {
            redirect('/');
        }

        $data['title'] = $this->lang->line('gp_publish').' '. strtolower($this->lang->line('gp_project')) .' '.$project->display_name;
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['project'] = (array)$project;
        $data['clients'] = $this->client_model->get_clients();
        $data['services'] = [];

        $this->qgisinfo($data);
        if($data['qgis_check']['valid']) {
            $data['services'] = self::get_project_services($data['qgis_check']['name']);
        } else {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger"><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> '.$data['qgis_check']['name'].'</div>');
        }

        $this->load->view('templates/header', $data);
        $this->load->view('project_services', $data);
        $this->load->view('templates/footer', $data);

    }

    public function publish_service($project_id = FALSE, $name = null, $type = null) {
        if(!$project_id || !$name || !$type) {
            redirect('/projects');
        }

        $project = $this->project_model->get_project($project_id);
        if(!$project) {
            redirect('/');
        }

        $this->load->model('qgisproject_model');

        $data['project'] = (array)$project;
        $data['clients'] = $this->client_model->get_clients();

        $this->qgisinfo($data);

        $project_full_path = $data['qgis_check']['name'];

        $service = self::check_service($name, $type, basename($project_full_path));
        $service_path = set_realpath($service['path']);
        $project_new_name = $service['file_name'];

        $keep_wfst = $this->config->item('keep_wfs-t_from_qgs');

        try {
            $qgs = $this->qgisproject_model;
            $qgs->qgs_file = $project_full_path;
            if(!$qgs->read_qgs_file()) {
                throw new Exception($qgs->error);
            }

            //setting advertised service url in the project qgs
            if($name == 'wms') {
                $qgs->qgs_xml->properties->WMSUrl = base_url($service['url']);
            } else if ($name == 'wfs') {
                $qgs->qgs_xml->properties->WFSUrl = base_url($service['url']);
                //remove transactions in case of setting
                if($keep_wfst === FALSE) {
                    $qgs->qgs_xml->properties->WFSTLayers->Insert = null;
                    $qgs->qgs_xml->properties->WFSTLayers->Update = null;
                    $qgs->qgs_xml->properties->WFSTLayers->Delete = null;
                }
            }

            //write qgs to new location
            if(!$qgs->write_qgs_file($service_path . $project_new_name)) {
                throw new Exception($qgs->error);
            }

            //set permission to 777
            if(is_file($service_path . $project_new_name))
            {
                chmod($service_path . $project_new_name, 0777);
            }

            //if(!copy($project_full_path, $service_path . $project_new_name)) {
            //    throw new Exception ("Copy project failed to ". $service_path);
            //}
        }
        catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            redirect('/projects/services/'.$project_id);
        }

    }

    public function stop_service($project_id = FALSE, $name = null, $type = null)
    {
        if (!$project_id || !$name || !$type) {
            redirect('/projects');
        }

        $project = $this->project_model->get_project($project_id);
        if (!$project) {
            redirect('/');
        }

        $data['project'] = (array)$project;
        $data['clients'] = $this->client_model->get_clients();

        $this->qgisinfo($data);

        $project_full_path = $data['qgis_check']['name'];

        $service = self::check_service($name, $type, basename($project_full_path));
        $service_path = set_realpath($service['path']);
        $project_new_name = $service['file_name'];

        try {
            if(!unlink($service_path . $project_new_name)) {
                throw new Exception ("Stop service failed in ". $service_path);
            }
        }
        catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            redirect('/projects/services/'.$project_id);
        }
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

    private function get_project_services($project_full_path)
    {
        $ret = [];

        $main_dir = $this->config->item('main_services_dir');
        if (!self::check_dir($main_dir)) {
            return $ret;
        }

        $file = basename($project_full_path);
        //TODO add client code to file from db
        //WMS private
        $wms_private = self::check_service('wms', 'private', $file);
        if ($wms_private) {
            $ret['wms'] = $wms_private;
        }
        //WMS public only if private is not published
        if (!$wms_private['published']) {
            $wms_public = self::check_service('wms', 'public', $file);
            if ($wms_public) {
                $ret['wms'] = $wms_public;
            }
        }

        //WFS private
        $wfs_private = self::check_service('wfs', 'private', $file);
        if ($wfs_private) {
            $ret['wfs'] = $wfs_private;
        }
        //WFS public only if private is not published
        if (!$wfs_private['published']) {
            $wfs_public = self::check_service('wfs', 'public', $file);
            if ($wfs_public) {
                $ret['wfs'] = $wfs_public;
            }
        }

        return $ret;
    }

    private function check_dir($path) {
        if(!is_dir($path) || !is_writable($path)) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Directory does not exist or not writable: <strong>'.$path.'</strong></div>');
            return false;
        }
        return true;
    }

    private function check_service($name, $type, $fn) {
        $main_dir = $this->config->item('main_services_dir');

        $dir1 = set_realpath($main_dir . DIRECTORY_SEPARATOR . $type);
        $dir2 = set_realpath($dir1 . $name);

        if(!self::check_dir($dir2)) {
            return false;   //['name' => $name, 'type' => $type, 'published' => false];
        }

        $project = basename($fn,'.qgs');
        if($type=='private') {
            $project .= '__'.crc32($project);
            $fn = $project.'.qgs';
        }
        $url = $name.'-'.$type.'/'.$project;
        $cap = http_build_query([
            "SERVICE" => strtoupper($name),
            "VERSION" => $name=='wms' ? '1.3.0' : '1.1.0',
            "REQUEST" => 'GetCapabilities'
        ]);

        $icon = $type =='public' ? 'fa fa-group' : 'glyphicon glyphicon-lock';

        if(file_exists($dir2.$fn) && is_readable($dir2.$fn)) {
            $info = get_file_info($dir2.$fn);
            return ['name' => $name, 'file_name' => $fn, 'type' => $type, 'icon' => $icon, 'path' => $dir2, 'published' => true, 'date' => $info["date"], 'url' => $url, 'capabilities' => $url.'?'.$cap];
        } else {
            return ['name' => $name, 'file_name' => $fn, 'type' => $type, 'icon' => $icon, 'path' => $dir2, 'published' => false, 'url' => $url];
        }
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
            //'base_layers_ids'           => $this->input->post('base_layers_ids'),
            //'extra_layers_ids'          => $this->input->post('extra_layers_ids'),
            //'client_id'                 => set_null($this->input->post('client_id')), //on update we are not changing client
            'public'                    => set_bool($this->input->post('public')),
            'display_name'              => $this->input->post('display_name'),
            //'crs'                       => $this->input->post('crs'),
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
            'ordr'                      => ($this->input->post('ordr')),
            'plugin_ids'                => $this->input->post('plugin_ids'),
            'project_group_id'          => $this->input->post('project_group_id')
            //'project_path'              => $this->input->post('project_path')
        );

        if ($this->input->post('plugin_ids') != null){
            $blids = implode($this->input->post('plugin_ids'),',');
            if ($blids != ''){
                $data['plugin_ids'] = '{' . $blids . '}';
            }
        }

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




//    private function extractUserProjectData(){
//
//        if ($this->input->post('user_projects_ids') != null){
//            return implode($this->input->post('user_projects_ids'),',');
//        }
//
//        return null;
//    }

    private function loadmeta(&$data){
        //$data['user_projects'] = $this->user_model->get_users_with_project_flag($data['project']['id']);

        //we need baselayer for overview layer
        //$data['base_layers'] = $this->layer_model->get_layers_with_project_flag($data['project']['base_layers_ids']);
        $data['base_layers'] = $this->layer_model->get_layers_with_project_flag(null);
        //$data['extra_layers'] = $this->layer_model->get_layers_with_project_flag($data['project']['extra_layers_ids']);
        $data['plugins'] = $this->plugin_model->get_plugins_with_project_flag($data['project']['plugin_ids']);

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

        $project_path = null;
        if(isset($data['project']['project_path'])) {
            $project_path = $data['project']['project_path'];
        }
        //TODO client_id on project!
        $client_key = array_search($data['project']['client_id'], array_column($data['clients'], 'id'));
        $client_name = $data['clients'][$client_key]['name'];

        $data['qgis_check'] = check_qgis_project($project_name, $project_path, $client_name);
    }

    private function clearCurrentUserProjectSession() {
        $sess_items = array(
            'client_path',
            'project',
            'project_path',
            'data',
            'settings',
            'description',
            'gis_projects',
            'qgs'
        );

        $this->session->unset_userdata($sess_items);
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Clients extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('client_model');
        $this->load->model('project_model');
        $this->load->model('project_group_model');
        $this->load->model('qgisproject_model');
        $this->load->helper(array('form', 'url', 'html', 'path', 'eqwc_dir', 'file', 'date', 'number'));
    }

    public function index()
    {
        $task = 'clients_table_view';

        if (!$this->ion_auth->can_execute_task($task)){
            $this->session->set_flashdata('message', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/auth/login');
        }

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope();
        $filter = $user_role->filter;
        if(empty($filter)) {
            $data['clients'] = $this->client_model->get_clients();
        } else {
            $data['clients'] = [(array)$this->client_model->get_client($filter)];
        }

        $data['title'] = $this->lang->line('gp_clients_title');
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['current_role_filter'] = $filter;  //filter for current logged in user
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;

        $this->load->view('templates/header', $data);
        $this->load->view('clients_admin', $data);
        $this->load->view('templates/footer', $data);
    }

    //TODO decide what to do with this page and if it is open to all users, currently there is no link to this page
    public function view($client_id = false)
    {
        if ($client_id === FALSE) {
            redirect("/");
        }

        if (!$this->ion_auth->logged_in()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $client = $this->client_model->get_client($client_id);
        $client_name = $client->name;
        $upload_dir = set_realpath($this->config->item('main_upload_dir'), false);
        $user_role = $this->ion_auth->admin_scope();

        $data['client'] = $client;
        $data['title'] = $client->display_name;
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;

        $this->load->view('templates/header', $data);

        //client info
        $this->load->view('client_view', $data);

        //folder uploads
        $upload['main'] = $this->lang->line('gp_uploaded_files');
        $upload['test'] = check_main_upload_dir();

        $upload['files'] = get_dir_file_info($upload_dir.$client_name);
        $upload['dir'] = $this->config->item('main_upload_web') . $client_name . DIRECTORY_SEPARATOR;

        if($upload['files']) {
            $this->load->view('client_files', $upload);
        }

        //upload form if main folder exists
        if ($upload['test'] === '') {
            $this->load->view('client_upload_form', array('client_id' => $client_id));
        }
        else {
            $this->load->view('message_view', array('message' => $upload['test'], 'type' => 'danger'));
        }
        $this->load->view('templates/footer', $data);

    }

    public function edit($client_id = false)
    {
        $task = 'clients_edit';

        if (!$this->ion_auth->can_execute_task($task)){
            redirect('/auth/login?ru=/' . uri_string());
        }

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope();
        $filter = $user_role->filter;
        if(!empty($filter) && $filter !== (integer)$client_id) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/clients/');
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('name', 'lang:gp_name', 'trim|required|alpha_dash|callback__unique_name');
        $this->form_validation->set_rules('display_name', 'lang:gp_display_name', 'trim|required');
        //$this->form_validation->set_rules('ordr','lang:gp_order','integer');
        $this->form_validation->set_rules('url', 'lang:gp_url', 'valid_url');

        if ($this->form_validation->run() == FALSE)
        {
            $data['title'] = $this->lang->line('gp_create').' '.$this->lang->line('gp_new').' '.$this->lang->line('gp_client');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['creating'] = true;

            $em = $this->extractPostData();
            if(sizeof($_POST) > 0){
                $data['title'] = $this->lang->line('gp_edit').' '.$this->lang->line('gp_client') .' '. $em['display_name'];
                $data['creating'] = false;
            } else {
                if ($client_id !== false){
                    $dq = $this->client_model->get_client($client_id);
                    if ($dq->id != null){
                        $em = (array)$dq;
                        $data['title'] = $this->lang->line('gp_edit').' '.$this->lang->line('gp_client') .' '. $em['display_name'];
                        $data['creating'] = false;
                    }
                }
            }
            $data['items'] = $this->build_child_groups($em['id'], null);
            $data['client'] = $em;
            $data['image'] = $this->getImage($em['name']);
            $data['logged_in'] = true;
            $data['is_admin'] = $user_role->admin;
            $data['role'] = $user_role->role_name;
            $data['register'] = '/signup?client='.$em['name'];

            $this->load->view('templates/header', $data);
            $this->load->view('client_edit', $data);
            //$this->load->view('templates/footer', $data);
        } else {

            $client = $this->extractPostData();
            try {
                $client_id = $this->client_model->upsert_client($client);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_client').' <strong>' . $client['name'] . '</strong>'.$this->lang->line('gp_saved').'</div>');
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
            }

            if($this->input->post('return') == null){
                redirect('/clients/edit/' . $client_id);
            } else {
                redirect('/clients');
            }
        }

    }

    /*
     * Deleting client
     */
    function remove($id)
    {
        if (!$this->ion_auth->is_admin()){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/clients/');
        }

        $client = (array)$this->client_model->get_client($id);

        // check if the client exists before trying to delete it
        if(isset($client['id']))
        {
            try {
                //filter for client administrator
                $filter = $this->ion_auth->admin_scope()->filter;
                if(!empty($filter)) {
                    throw new Exception('No permission!');
                }

                //before deleting check if client has any projects
                if($client['count']>0)  {
                    throw new Exception('Cannot delete. Client has ' . $client['count'] . ' projects.');
                }

                $this->client_model->delete_client($id);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_client').' <strong>' . $this->input->post('name') . '</strong>'.$this->lang->line('gp_deleted').'</div>');
                redirect('/clients');
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
                redirect('/clients/edit/'.$id);
            }
        }
        else
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">The client you are trying to delete does not exist.</div>');
    }

    public function upload($client_id = false)
    {
        if ($client_id === FALSE) {
            redirect("/");
        }

        $client = $this->client_model->get_client($client_id);
        $client_name = $client->name;

        $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'),false) . $client_name, false);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $config['upload_path']          = $dir;
        $config['allowed_types']        = 'gif|jpg|png|pdf|zip';
        $config['overwrite']            = true;
        $config['file_ext_tolower']     = true;
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

    public function import($client_id = false)
    {
        try {
            $client = $this->client_model->get_client($client_id);
            if ($client == null) {
                throw new Exception('Client not found!');
            }

            $client_name = $client->name;
            $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'), false) . $client_name, false);

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'kml|dxf|geojson|zip';
            $config['overwrite'] = true;
            $config['file_ext_tolower'] = true;
            //$config['max_size']             = 100;
            //$config['max_width']            = 1024;
            //$config['max_height']           = 768;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('userfile')) {
                throw new Exception ($this->upload->display_errors('', ''));
            }

            //proceed to import
            $count = $this->importData($client_name);


            $this->output
                ->set_content_type('text/html')
                ->set_status_header(200)
                ->set_output(json_encode(array(
                    'success'   => true,
                    'message'   => $this->lang->line('gp_upload_success'),
                    'file'      => $this->upload->file_name,
                    'count'     => $count
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

    private function importData($client_name) {
        $file_name = $this->upload->file_name;

        $dir = $this->upload->upload_path;
        $project_id = $this->input->post('project_id');
        $layer_id = $this->input->post('layer_id');
        $file_crs = $this->input->post('crs_code');

        $project = $this->project_model->get_project($project_id);
        $qgs_file = '';
        $check = check_qgis_project($project->name, $project->project_path, $client_name);

        if($check['valid']) {
            $qgs_file = $check["name"];
        } else {
           throw new Exception($check['name']);
        }

        $qgs = $this->qgisproject_model;
        $qgs->qgs_file = $qgs_file;
        if(!$qgs->read_qgs_file()) {
            throw new Exception($qgs->error);
        }

        //get layer xml element from qgis file
        if(!$qgs_lay = $qgs->get_layer_by_id($layer_id)) {
            throw new Exception($qgs->error);
        }

        $qgs_lay_info = $qgs->get_layer_info($qgs_lay);

        $format_name = 'PostgreSQL'; //TODO
        $conn = $qgs_lay->datasource;
        $srid = (string)$qgs_lay->srs->spatialrefsys->srid;
        //removing text sslmode and all after that
        $conn = "PG:" . rtrim(substr($conn, 0, strpos($conn, 'sslmode')));

        $table = $qgs_lay_info['table'];
        //$sql = $qgs_lay_info['sql'];

        $cnt_before = $qgs->get_layer_feature_count($conn, $table);
        if($cnt_before == -1) {
            throw new Exception($qgs->error);
        }

        $user_file = $dir . $file_name;
        //special case for zip files, assuming inside .shp, .shx, .dbf
        if ($this->upload->file_ext=='.zip') {
            $user_file = '/vsizip/' . $dir . $file_name . DIRECTORY_SEPARATOR . str_replace('.zip', '.shp', $file_name);
        }

        $source_srs = ' -s_srs ' . $file_crs . ' ';
        $target_srs = ' -t_srs EPSG:' . $srid . ' ';

        //$mycmd = get_ogr() . 'ogr2ogr -t_srs EPSG:' . $srid . ' -append -f "' . $format_name . '" "' . $conn . '" "' . $user_file . '" -nln ' . $table;
        $mycmd = get_ogr() . 'ogr2ogr ' . $target_srs . $source_srs . '-append -f "' . $format_name . '" "' . $conn . '" "' . $user_file . '" -nln ' . $table;
        $output = shell_exec($mycmd);

        $cnt_after = $qgs->get_layer_feature_count($conn, $table);
        if($cnt_after == -1) {
            throw new Exception($qgs->error);
        }

        if($cnt_after<=$cnt_before) {
            throw new Exception('No data imported!');
        }

        return $cnt_after - $cnt_before;

        //if ($output==null) {
        //    error_log("EQWC Data Import Failed: ".$mycmd);
        //    throw new Exception("Import failed. Details in Apache error log!");
        //}
    }

    /*
     * Returns array of available clients for dropdown list
     */
    public function get_list()
    {
        $filter = $this->ion_auth->admin_scope()->filter;

        $list_only = true;

        if(empty($filter)) {
            $groups = $this->client_model->get_clients(FALSE, TRUE, TRUE, $list_only);
        } else {
            $groups = [$this->client_model->get_client($filter, $list_only)];
        }

        $this->output
            ->set_content_type('text/html')
            ->set_status_header(200)
            ->set_output(json_encode($groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function extractPostData(){
        return array(
            'id' => $this->input->post('id'),
            //'theme_id' => '1',
            'name' => $this->input->post('name'),
            'url' => $this->input->post('url'),
            //'ordr' => $this->input->post('ordr'),
            'display_name' => $this->input->post('display_name'),
            'description' => $this->input->post('description')
        );
    }

    private function getImage($name) {
        $path = 'assets/img/clients/'.$name.'.png';
        $fn = set_realpath(FCPATH.$path, false);

        if (is_file($fn)) {
            return "<img class='img-responsive' src='" . base_url($path) . "'>";
        }
        else {
            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-alert' aria-hidden='true'></span> Image missing (300x200px)</br>".$fn."</div>";
        }
    }


    public function _unique_name($name) {

        //test if we already have name in database
        $exist = $this->client_model->client_exists($name);
        $id = $this->input->post('id');

        if ($exist) {
            if(!empty($id)) {
                //have to check if user is editing name to another existing name
                $client = $this->client_model->get_client($id);
                if($name != $client->name) {
                    $this->form_validation->set_message('_unique_name', $this->lang->line('gp_client').' '.$name.$this->lang->line('gp_exists').'!');
                    return false;
                } else {
                    return true;
                }
            }
            $this->form_validation->set_message('_unique_name', $this->lang->line('gp_client').' '.$name.$this->lang->line('gp_exists').'!');
            return false;
        }

        return true;
    }

    private function build_child_groups($client_id, $group_id)
    {
        if(empty($client_id)) {
            return [];
        }

        $ret = $this->project_group_model->get_child_groups($client_id,$group_id);

        //TODO currently only one level below main
        $i=0;
        foreach ($ret as $el) {
            if($el['type'] == SUB_GROUP) {
                $ret2 =  $this->project_group_model->get_child_groups(null,$el['id']);
                $ret[$i]['items'] = $ret2;
            } else {
                $ret[$i]['items'] = [];
            }
            $i++;
        }

        return $ret;
    }

}
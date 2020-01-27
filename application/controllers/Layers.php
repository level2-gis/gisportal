<?php

class Layers extends CI_Controller{
    function __construct()
    {
        parent::__construct();
        $this->load->model('layer_model');
        $this->load->model('client_model');
        $this->load->model('project_group_model');
        $this->load->model('user_model');
        $this->load->helper(array('form', 'url', 'eqwc_parse'));
    } 

    /*
     * Listing of layers
     */
    function index()
    {
        if (!$this->ion_auth->is_admin()){
            redirect('/auth/login?ru=/' . uri_string());
        }

        $user_role = $this->ion_auth->admin_scope();

        $data['title'] = $this->lang->line('gp_layers_title');
        $data['layers'] = $this->layer_model->get_layers($user_role->filter);
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;

        $this->load->view('templates/header', $data);
        $this->load->view('layers_admin', $data);
        $this->load->view('templates/footer', $data);
    }

    /*
     * Adding a new layer
     */
//    function add()
//    {
//        if (!$this->ion_auth->is_admin()){
//            redirect('/');
//        }
//
//        $this->load->helper('form');
//        $this->load->library('form_validation');
//
//		$this->form_validation->set_rules('type','Type','required');
//		$this->form_validation->set_rules('name','Name','required|max_length[100]|is_unique[true]');
//		$this->form_validation->set_rules('definition','Definition','required');
//
//		if($this->form_validation->run())
//        {
//            $params = array(
//				'type' => $this->input->post('type'),
//				'name' => $this->input->post('name'),
//				'display_name' => $this->input->post('display_name'),
//				'definition' => $this->input->post('definition'),
//            );
//
//            $layer_id = $this->layer_model->add_layer($params);
//            redirect('layers');
//        }
//        else
//        {
//            $this->load->view('templates/header', $data);
//            $this->load->view('layer_edit', $data);
//        }
//    }

    /*
     * Adding/Editing a layer
     */
    function edit($layer_id = false, $copy = false)
    {
        if (!$this->ion_auth->is_admin()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('type', 'Type', 'required');
        $this->form_validation->set_rules('name', 'lang:gp_name', 'required|alpha_dash|callback__unique_name');
        $this->form_validation->set_rules('display_name', 'lang:gp_display_name', 'trim|required');
        $this->form_validation->set_rules('definition', 'Definition', 'required|callback__check_definition');

        $data['types'] = array('Bing', 'Google', 'OSM', 'WMS', 'WMTS', 'XYZ');

        if ($this->form_validation->run() == FALSE) {
            $data['title'] = $this->lang->line('gp_create') . ' ' . $this->lang->line('gp_new') . ' ' . $this->lang->line('gp_layer');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['creating'] = true;

            $em = $this->extractPostData();
            if (sizeof($_POST) > 0) {
                $data['title'] = $this->lang->line('gp_layer') . ' ' . $em['display_name'];
                $data['creating'] = false;
            } else {
                if ($layer_id !== false) {
                    $dq = $this->layer_model->get_layer($layer_id);
                    if ($dq->id != null) {
                        $em = (array)$dq;
                        if(!$copy) {
                            $data['title'] = $this->lang->line('gp_layer') . ' ' . $em['display_name'];
                            $data['creating'] = false;
                        } else {
                            $em['id'] = null;
                        }
                    }
                }
            }
            $data['layer'] = $em;

            //filter for client administrator
            $user_role = $this->ion_auth->admin_scope();
            $filter = $user_role->filter;
            if(!empty($filter) && ($filter !== (integer)$em['client_id']) && !empty($em['id'])) {
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
                redirect('/layers/');
            }

            if(empty($filter)) {
                $data['clients'] = $this->client_model->get_clients();
            } else {
                $data['clients'] = [(array)$this->client_model->get_client($filter)];
            }

            $data['groups'] = $this->project_group_model->get_project_groups_with_layer($layer_id, $filter);
            $data['destination'] = array(
                ['id' => BASE_LAYER, 'name' => $this->lang->line('gp_base_layers')],
                ['id' => EXTRA_LAYER, 'name' => $this->lang->line('gp_overlay_layers')]
            );
            $data['logged_in'] = true;
            $data['is_admin'] = $user_role->admin;
            $data['role'] = $user_role->role_name;

            $this->load->view('templates/header', $data);
            $this->load->view('layer_edit', $data);
            //$this->load->view('templates/footer', $data);
        } else {
            $layer = $this->extractPostData();
            try {
                $layer_id = $this->layer_model->upsert_layer($layer);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">' . $this->lang->line('gp_layer') . ' <strong>' . $layer['name'] . '</strong>' . $this->lang->line('gp_saved') . '</div>');
                $this->user_model->clear_gisapp_session();
            } catch (Exception $e) {
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
            }

            if ($this->input->post('return') == null) {
                redirect('/layers/edit/' . $layer_id);
            } else {
                redirect('/layers');
            }
        }
    }

    /*
     * Deleting layer
     */
    function remove($id)
    {
        if (!$this->ion_auth->is_admin()){
            redirect('/');
        }

        $layer = (array)$this->layer_model->get_layer($id);

        // check if the layer exists before trying to delete it
        if(isset($layer['id']))
        {
            try {
                //before deleting check if layers exist as as base or extra layer in project groups
                $test = $this->project_group_model->get_project_groups_with_layer($id);
                if(count($test)>0)  {
                    throw new Exception('Cannot delete. Layer exists in ' . count($test) . ' project groups.');
                }

                //filter for client administrator
                $user_role = $this->ion_auth->admin_scope();
                $filter = $user_role->filter;
                if(!empty($filter) && ($filter !== (integer)$layer['client_id'])) {
                    throw new Exception('No permission!');
                }

                $this->layer_model->delete_layer($id);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_layer').' <strong>' . $this->input->post('name') . '</strong>'.$this->lang->line('gp_deleted').'</div>');
                redirect('/layers');
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
                redirect('/layers/edit/'.$id);
            }
        }
        else
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">The layer you are trying to delete does not exist.</div>');
    }

    public function search() {

        $query = $this->input->get('query');

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        if(empty($query)) {
            return;
        }

        $result = $this->layer_model->search(urldecode($query), $filter);

        $this->output
            ->set_content_type('text/html')
            ->set_status_header(200)
            ->set_output(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function extractPostData() {
        //remove crs or srs  property from WMS definition
        $type = $this->input->post('type');
        $def = $this->input->post('definition');
        $obj = json_decode($def);
        if($type == 'WMS') {
            unset($obj->params->{"srs"});
            unset($obj->params->{"SRS"});
            unset($obj->params->{"crs"});
            unset($obj->params->{"CRS"});
        }
        $def = json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return array(
            'id' => $this->input->post('id'),
            'type' => $type,
            'name' => $this->input->post('name'),
            'display_name' => $this->input->post('display_name'),
            'definition' => $def,
            'client_id' => set_null($this->input->post('client_id'))
        );
    }

    public function _unique_name($name) {

        //test if we already have name in database
        $exist = $this->layer_model->layer_exists($name);
        $id = $this->input->post('id');

        if ($exist) {
            if(!empty($id)) {
                //have to check if user is editing name to another existing name
                $layer = $this->layer_model->get_layer($id);
                if($name != $layer->name) {
                    $this->form_validation->set_message('_unique_name', $this->lang->line('gp_layer').' '.$name.$this->lang->line('gp_exists').'!');
                    return false;
                } else {
                    return true;
                }
            }
            $this->form_validation->set_message('_unique_name', $this->lang->line('gp_layer').' '.$name.$this->lang->line('gp_exists').'!');
            return false;
        }

        return true;
    }

    public function _check_definition($text) {
        //if we have to check definition by type
        //$type = $this->input->post('type');
        $x = json_decode($text);

        if (empty($x)) {
            $this->form_validation->set_message('_check_definition', 'Definition not valid JSON string');
            return false;
        }

        return true;
    }
}

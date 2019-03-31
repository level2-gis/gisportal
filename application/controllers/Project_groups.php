<?php

class Project_groups extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('client_model');
        $this->load->model('project_group_model');
        $this->load->model('project_model');
        $this->load->model('layer_model');
        $this->load->model('user_model');
        $this->load->helper(array('eqwc_parse'));
    }

    public function index()
    {
        if (!$this->ion_auth->is_admin()){
            redirect('/auth/login?ru=/' . uri_string());
        }

        $data['title'] = $this->lang->line('gp_groups_title');
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['groups'] = $this->project_group_model->get_project_groups();

        $this->load->view('templates/header', $data);
        $this->load->view('project_groups_admin', $data);
        $this->load->view('templates/footer', $data);
    }


    public function edit($id = FALSE)
    {
        if (!$this->ion_auth->is_admin()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $group = $this->project_group_model->get_project_group($id);

        if(!isset($group)) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Group does not exist.</div>');
            redirect('/projects/');
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
        $this->form_validation->set_rules('type', 'Type', 'required|integer');
        $this->form_validation->set_rules('name', 'lang:gp_name', 'required|alpha_dash|callback__unique_name');

        $data['creating'] = false;

        if ($this->form_validation->run() == FALSE) {
            if(sizeof($_POST) > 0) {
                $group = $this->extractPostData();
            }
            $data['title'] = $this->lang->line('gp_edit') . ' ' . $this->lang->line('gp_group') . ' ' . $group->name;
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['group'] = (array)$group;
            $data['clients'] = $this->client_model->get_clients();
            $data['projects'] = $this->project_model->get_projects($group->client_id, '{'.$id.'}', FALSE);
            $data['users'] = $this->user_model->get_project_group_users($id);
            $data['types'] = array(
                ['id' => PROJECT_GROUP, 'name' => $this->lang->line('gp_project_group')]
                //TODO not implemented ['id' => SUB_GROUP,     'name' => $this->lang->line('gp_sub_group')]
            );

            $this->loadmeta($data);

            $this->load->view('templates/header', $data);
            $this->load->view('project_group_edit', $data);
        } else {
            $group = $this->extractPostData();

            try {
                $group_id = $this->project_group_model->upsert_project_group($group);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">' . $this->lang->line('gp_group') . ' <strong>' . $group->name . '</strong>' . $this->lang->line('gp_saved') . '</div>');
            } catch (Exception $e) {
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
            }

            if ($this->input->post('return') == null) {
                redirect('/project_groups/edit/' . $group_id);
            } else {
                //TODO save on group succesful, where redirect?
                redirect('/projects');
            }
        }

    }

    public function create()
    {
        if (!$this->ion_auth->is_admin()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
        $this->form_validation->set_rules('type', 'Type', 'required|integer');
        $this->form_validation->set_rules('name', 'lang:gp_name', 'required|alpha_dash|callback__unique_name');

        $data['creating'] = true;

        $group = new stdClass();
        $group->name = $this->input->post('name');
        $group->client_id = $this->input->post('client_id');
        $group->display_name = $this->input->post('display_name');
        $group->type = $this->input->post('type');

        if ($this->form_validation->run() == FALSE) {

            $data['title'] = $this->lang->line('gp_create') . ' ' . $this->lang->line('gp_new') . ' ' . $this->lang->line('gp_group');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['group'] = (array)$group;
            $data['clients'] = $this->client_model->get_clients();
            $data['types'] = array(
                ['id' => PROJECT_GROUP, 'name' => $this->lang->line('gp_project_group')]
                //TODO not implemented ['id' => SUB_GROUP,     'name' => $this->lang->line('gp_sub_group')]
            );

            $this->load->view('templates/header', $data);
            $this->load->view('project_group_create', $data);
        } else {
            try {
                $group_id = $this->project_group_model->upsert_project_group($group);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">' . $this->lang->line('gp_group') . ' <strong>' . $group->name . '</strong>' . $this->lang->line('gp_saved') . '</div>');
            } catch (Exception $e) {
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
            }

            if ($this->input->post('return') == null) {
                redirect('/project_groups/edit/' . $group_id);
            } else {
                //TODO save on group succesful, where redirect?
                redirect('/projects');
            }
        }
    }

    /*
     * Returns array of client project groups for dropdown list
     */
    public function get_list($client_id = FALSE)
    {
        $groups = [];
        $list_only = true;

        if (!empty($client_id)) {
            $groups = $this->project_group_model->get_project_groups($client_id, $list_only);
        }

//        $filter = ['id', 'name'];
//        $out = [];
//        foreach ($groups as $group) {
//
//            array_push($out, array_filter(
//                $group,
//                function ($key) use ($filter) {
//                    return in_array($key, $filter);
//                },
//                ARRAY_FILTER_USE_KEY
//            ));
//        }

        $this->output
            ->set_content_type('text/html')
            ->set_status_header(200)
            ->set_output(json_encode($groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    function remove($id)
    {
        if (!$this->ion_auth->is_admin()){
            redirect('/');
        }

        $group = (array)$this->project_group_model->get_project_group($id);

        // check if exists before trying to delete it
        if(isset($group['id']))
        {
            try {
                //before deleting check if layers exist as as base or extra layer in project groups
                //$test = $this->project_group_model->get_project_groups_with_layer($id);
                //if(count($test)>0)  {
                //    throw new Exception('Cannot delete. Layer exists in ' . count($test) . ' project groups.');
                //}

                $this->project_group_model->delete_project_group($id);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_layer').' <strong>' . $this->input->post('name') . '</strong>'.$this->lang->line('gp_deleted').'</div>');
                redirect('/project_groups');
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
                redirect('/project_groups/edit/'.$id);
            }
        }
        else
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">The group you are trying to delete does not exist.</div>');
    }

    public function _unique_name($name) {

        //test if we already have name in database
        $exist = $this->project_group_model->project_group_exists($name);
        $id = $this->input->post('id'); //if id exists it means user is editing object

        if ($exist) {
            if(!empty($id)) {
                //have to check if user is editing name to another existing name
                $group = $this->project_group_model->get_project_group($id);
                if($name != $group->name) {
                    $this->form_validation->set_message('_unique_name', $this->lang->line('gp_group').' '.$name.$this->lang->line('gp_exists').'!');
                    return false;
                } else {
                    return true;
                }
            }
            $this->form_validation->set_message('_unique_name', $this->lang->line('gp_group').' '.$name.$this->lang->line('gp_exists').'!');
            return false;
        }

        return true;
    }

    private function extractPostData() {

        return (object)array(
            'id' => $this->input->post('id'),
            'name' => $this->input->post('name'),
            'display_name' => set_null($this->input->post('display_name')),
            'parent_id' => $this->input->post('parent_id'),
            'type' => $this->input->post('type'),
            'client_id' => $this->input->post('client_id'),
            'base_layers_ids' => $this->input->post('base_layers_ids'),
            'extra_layers_ids' => $this->input->post('extra_layers_ids'),
        );
    }

    private function loadmeta(&$data){

        $data['base_layers'] = $this->layer_model->get_layers_with_project_flag($data['group']['base_layers_ids']);
        $data['extra_layers'] = $this->layer_model->get_layers_with_project_flag($data['group']['extra_layers_ids']);

    }
}

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
        $data['logged_in'] = true;
        $data['is_admin'] = true;

        $this->load->view('templates/header', $data);
        $this->load->view('project_groups_admin', $data);
        $this->load->view('templates/footer', $data);
    }


    public function edit($id = FALSE)
    {
        if (!$this->ion_auth->is_admin()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $group = (array)$this->project_group_model->get_project_group($id);

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
            $g_type = $group['type'];
            $title = empty($group['display_name']) ? $group['name'] : $group['display_name'];


            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['group'] = $group;

            $data['roles'] = $this->user_model->get_roles();
            $data['parents'] = $this->project_group_model->get_parents($group['client_id'], $id);
            $data['types'] = [];

            if($g_type == PROJECT_GROUP) {

                $data['title'] = $this->lang->line('gp_edit') . ' ' . $this->lang->line('gp_group') . ' ' . $title;
                $data['projects'] = $this->project_model->get_projects($group['client_id'], '{'.$id.'}', FALSE);
                $data['users'] = $this->user_model->get_project_group_users($id);

                array_push($data['types'],['id' => PROJECT_GROUP, 'name' => $this->lang->line('gp_project_group')]);
                //only allow edit type if there are none projects on the group
                if(count($data['projects']) == 0) {
                    array_push($data['types'],['id' => SUB_GROUP,     'name' => $this->lang->line('gp_sub_group')]);
                }

                //if group has parent_id don't allow to change client
                if(empty($group['parent_id'])) {
                    $data['clients'] = $this->client_model->get_clients();
                } else {
                    $data['clients'] = [];
                    array_push($data['clients'], (array)$this->client_model->get_client($group['client_id']));
                }

            } else if ($g_type == SUB_GROUP) {

                $data['title'] = $this->lang->line('gp_edit') . ' ' . ucwords($this->lang->line('gp_sub_group')) . ' ' . $title;
                $data['items'] = $this->build_child_groups(null, $group['id']);

                //only allow edit type and client if there are none child groups for this subgroup
                if(count($data['items']) == 0) {
                    array_push($data['types'],['id' => PROJECT_GROUP, 'name' => $this->lang->line('gp_project_group')]);
                    $data['clients'] = $this->client_model->get_clients();
                } else {
                    $data['clients'] = [];
                    array_push($data['clients'], (array)$this->client_model->get_client($group['client_id']));
                }
                array_push($data['types'],['id' => SUB_GROUP,     'name' => $this->lang->line('gp_sub_group')]);
            }

            $data['admin_navigation'] = $this->build_admin_navigation($group);
            $data['logged_in'] = true;
            $data['is_admin'] = true;

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
                redirect('/project_groups');
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

        if(sizeof($_POST) > 0) {
            $group = $this->extractPostData();
        } else {
            $group = $this->project_group_model->new_group();
        }

        if ($this->form_validation->run() == FALSE) {

            $data['title'] = $this->lang->line('gp_create') . ' ' . $this->lang->line('gp_new') . ' ' . $this->lang->line('gp_group');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['group'] = $group;
            $data['clients'] = $this->client_model->get_clients();
            if($group['client_id']) {
                $data['parents'] = $this->project_group_model->get_parents($group['client_id'], null);
            } else {
                $data['parents'] = [];
            }
            $data['types'] = array(
                ['id' => PROJECT_GROUP, 'name' => $this->lang->line('gp_project_group')],
                ['id' => SUB_GROUP,     'name' => $this->lang->line('gp_sub_group')]
            );
            $data['logged_in'] = true;
            $data['is_admin'] = true;

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


    /**
     * Creates new group by parameters and add info for session
     */
    public function add_group($client_id, $name) {

        $x = $client_id;

        try {
            if (!$this->ion_auth->is_admin()){
                throw new Exception('User not Admin!');
            }

            $back = $this->input->get('back');

            $this->load->library('form_validation');

            $data = [
                "client_id" => $client_id,
                "name"      => urldecode($name),
                "type"      => PROJECT_GROUP
            ];
            $this->form_validation->set_data($data);

            $this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
            $this->form_validation->set_rules('type', 'Type', 'required|integer');
            $this->form_validation->set_rules('name', 'lang:gp_name', 'required|alpha_dash|callback__unique_name');

            if ($this->form_validation->run() == FALSE) {
                throw new Exception('Create group error: ' . $this->form_validation->error_string());
            }

            $id = $this->project_group_model->upsert_project_group($data);

            //TODO after insert get group id and add it to session ,ali client id so it is selected in form

            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }
        }
        catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            $this->session->set_flashdata('client_id', $client_id);
            if(!empty($id)) {
                $this->session->set_flashdata('project_group_id', $id);
            }
            redirect($back);
        }
    }

    public function get_parents($client_id = FALSE, $id = FALSE)
    {
        $groups = [];

        if($id=='null') {
            $id = null;
        }

        if (!empty($client_id)) {
            $groups = $this->project_group_model->get_parents($client_id, $id);
        }

        $this->output
            ->set_content_type('text/html')
            ->set_status_header(200)
            ->set_output(json_encode($groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /*
     * Returns array of client project groups for dropdown list
     */
    public function get_list($client_id = FALSE, $skip_no_access = FALSE)
    {
        $groups = [];
        $list_only = true;

        if (!empty($client_id)) {
            $groups = $this->project_group_model->get_project_groups($client_id, $list_only, $skip_no_access);
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
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_group').' <strong>' . $this->input->post('name') . '</strong>'.$this->lang->line('gp_deleted').'</div>');
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

    public function add_layer($groups, $layer_id, $destination)
    {
        try {
            if (!$this->ion_auth->is_admin()) {
                throw new Exception('User not Admin!');
            }

            $groups = urldecode($groups);

            $res = $this->project_group_model->add_layer($groups, $layer_id, $destination);
            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
        } finally {
            redirect('layers/edit/' . $layer_id . '#edit-access');
        }
    }

    public function remove_layer($group, $layer_id)
    {
        try {
            if (!$this->ion_auth->is_admin()) {
                throw new Exception('User not Admin!');
            }

            $res = $this->project_group_model->remove_layer($group, $layer_id);
            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
        } finally {
            redirect('layers/edit/' . $layer_id . '#edit-access');
        }
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

        return array(
            'id' => $this->input->post('id'),
            'name' => $this->input->post('name'),
            'display_name' => set_null($this->input->post('display_name')),
            'parent_id' => set_null($this->input->post('parent_id')),
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

    private function get_name($el) {
        return empty($el['display_name']) ? $el['name'] : $el['display_name'];
    }

    private function build_parent_link($id, $sep, &$result) {
        $new_group = (array)$this->project_group_model->get_project_group($id);
        $new_id = $new_group['parent_id'];
        $result = anchor('project_groups/edit/'.$new_group['id'], $this->get_name($new_group)) . $sep . $result;
        return $new_id;
    }

    private function build_admin_navigation($group)
    {
        $sep = ' > ';

        //this is current group, last in the tree, does not have link
        $group_full = $this->get_name($group);
        $parent_id = $group['parent_id'];

        while (!empty($parent_id)) {
            $parent_id = $this->build_parent_link($parent_id, $sep, $group_full);
        }

        $client = $this->client_model->get_client($group['client_id']);
        $client_full = anchor('clients/edit/'.$client->id, $client->display_name);

        return $client_full . $sep . $group_full;
    }

    private function build_child_groups($client_id, $group_id)
    {
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

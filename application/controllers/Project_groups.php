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
        $this->load->helper(array('eqwc_parse','path'));
    }

    public function index()
    {
        $task = 'project_groups_table_view';

        if (!$this->ion_auth->can_execute_task($task)){
            $this->session->set_flashdata('message', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/auth/login');
        }

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope();
        $filter = $user_role->filter;

        $data['title'] = $this->lang->line('gp_groups_title');
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['groups'] = $this->project_group_model->get_project_groups($filter);
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;

        $this->load->view('templates/header', $data);
        $this->load->view('project_groups_admin', $data);
        $this->load->view('templates/footer', $data);
    }

    /*
     * Portal view
     */
    public function view($client_id = false, $parent_id = null)
    {
        if ($client_id === FALSE) {
            redirect("/");
        }

        if (!$this->ion_auth->logged_in()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $client = $this->client_model->get_client($client_id);
        $user_role = $this->ion_auth->admin_scope();

        $data['title'] = $this->lang->line('gp_groups_title');
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;

		//rss
		if(!empty($this->config->item('rss_feed_url'))) {
			$rss_config['url'] = $this->config->item('rss_feed_url');
			$rss_config['limit'] = empty($this->config->item('rss_feed_limit')) ? 10 : $this->config->item('rss_feed_limit');
			$this->load->library('rss_parser', $rss_config);
			$rss['rss'] = $this->rss_parser->parse();
			$rss['rss']['last_login'] = $this->session->userdata('old_last_login');
		}

        try {
            $data['items'] = $this->get_user_groups($client_id,$parent_id,$data['is_admin']);

            //if there is only one group, open it
            if(count($data['items']) == 1) {
				if ($data['items'][0]['type'] === SUB_GROUP) {
					redirect('project_groups/view/' . $client_id . '/' . $data['items'][0]['id']);
				} else {
					redirect('projects/view_group/' . $client_id . '/' . $data['items'][0]['id']);
				}
            }

            $data['navigation'] = $this->build_user_navigation($client,$parent_id);
        } catch (Exception $e) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
            redirect("/");
        }

        $data['client_id'] = $client_id;

        $this->load->view('templates/header', $data);
        $this->load->view('project_groups', $data);
		if(!empty($rss)) {
			$this->load->view('rss_short', $rss);
		}
        $this->load->view('templates/footer', $data);

    }

    public function send_email($id = FALSE)
    {
        if(empty($id)) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Group missing.</div>');
            redirect('/project_groups/');
        }

        $task = 'project_groups_send_email';

        $group = (array)$this->project_group_model->get_project_group($id);

        if(empty($group)) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Group does not exist.</div>');
            redirect('/project_groups/');
        }

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope();
        $filter = $user_role->filter;
        if(!empty($filter) && $filter !== (integer)$group['client_id']) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/project_groups/');
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('subject', 'lang:gp_email_subject', 'required');
        $this->form_validation->set_rules('body', 'lang:gp_email_body', 'required');

        try {
            if (!$this->ion_auth->can_execute_task($task)){
                throw new Exception('No permission!');
            }

            $emails = array_column($this->user_model->get_project_group_users($id), 'user_email');

            if(count($emails) === 0) {
                throw new Exception('No users!');
            }

            if ($this->form_validation->run() == FALSE) {
                $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
                $data['group'] = $group;
                $data['title'] = lang('gp_send_email');
                $data['subtitle'] = $this->get_name($group);
                $data['logged_in'] = true;
                $data['is_admin'] = $user_role->admin;
                $data['own_email'] = $user_role->email;
                $data['role'] = $user_role->role_name;
                $data['emails'] = $emails;

                $this->load->view('templates/header', $data);
                $this->load->view('email/send_form', $data);

            }

            else {
                $subject = $this->input->post('subject');
                $body = $this->input->post('body');
                $include = $this->input->post('include');

                if(!empty($include)) {
                    array_push($emails,$include);
                }

                $data = array('body' => nl2br($body));

                $message = $this->load->view($this->config->item('email_templates', 'ion_auth') . 'send_email.tpl.php', $data, TRUE);
                $this->ion_auth->send_email($subject,$message,$emails);

                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">' . lang('gp_email_sent') . '</div>');
                redirect('project_groups/edit/' . $id);
            }

        } catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
            redirect('project_groups/edit/' . $id);
        }
    }

    public function edit($id = FALSE)
    {
        if(empty($id)) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Group missing.</div>');
            redirect('/project_groups/');
        }

        $task = 'project_groups_edit';

        if (!$this->ion_auth->can_execute_task($task)){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/project_groups/');
        }

        $group = (array)$this->project_group_model->get_project_group($id);

        if(empty($group)) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Group does not exist.</div>');
            redirect('/project_groups/');
        }

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope();
        $filter = $user_role->filter;
        if(!empty($filter) && $filter !== (integer)$group['client_id']) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/project_groups/');
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
        $this->form_validation->set_rules('type', 'Type', 'required|integer');
        $this->form_validation->set_rules('name', 'lang:gp_name', 'required|alpha_dash|callback__unique_name');
        $this->form_validation->set_rules('contact_email', 'lang:gp_email', 'valid_email');

        if(!empty($this->config->item('project_group_custom1_rules'))) {
            $this->form_validation->set_rules('custom1', $this->config->item('project_group_custom1_name'), $this->config->item('project_group_custom1_rules'));
        }
        if(!empty($this->config->item('project_group_custom2_rules'))) {
            $this->form_validation->set_rules('custom2', $this->config->item('project_group_custom2_name'), $this->config->item('project_group_custom2_rules'));
        }


        $data['creating'] = false;

        if ($this->form_validation->run() == FALSE) {
            if(sizeof($_POST) > 0) {
                $group = $this->extractPostData();
            }
            $g_type = $group['type'];
            $title = empty($group['display_name']) ? $group['name'] : $group['display_name'];


            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

            //handle contact information
            if(!empty($group['contact_id'])) {
                $user = $this->user_model->get_user_by_id($group['contact_id']);
                $group['contact'] = $user->first_name . ' ' . $user->last_name;
                $group['contact_email'] = $user->user_email;
                $group['contact_phone'] = $user->phone;
            }

            $data['group'] = $group;

            $data['roles'] = $this->user_model->get_roles();
            $data['parents'] = $this->project_group_model->get_parents($group['client_id'], $id);
            $data['types'] = [];

            if($g_type == PROJECT_GROUP) {

                $data['title'] = $this->lang->line('gp_group') . ' ' . $title;
                $data['projects'] = $this->project_model->get_projects($group['client_id'], '{'.$id.'}', FALSE);
                $data['users'] = $this->user_model->get_project_group_users($id);

                array_push($data['types'],['id' => PROJECT_GROUP, 'name' => $this->lang->line('gp_project_group')]);
                //only allow edit type if there are none projects on the group
                if(count($data['projects']) == 0) {
                    array_push($data['types'],['id' => SUB_GROUP,     'name' => $this->lang->line('gp_sub_group')]);
                }

                if(empty($filter)) {
                    //if group has parent_id don't allow to change client
                    if(empty($group['parent_id'])) {
                        $data['clients'] = $this->client_model->get_clients();
                    } else {
                        $data['clients'] = [(array)$this->client_model->get_client($group['client_id'])];
                    }
                } else {
                    $data['clients'] = [(array)$this->client_model->get_client($filter)];
                }

            } else if ($g_type == SUB_GROUP) {

                $data['title'] = ucwords($this->lang->line('gp_sub_group')) . ' ' . $title;
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
            $data['image'] = $this->getImage($group['name']);
            $data['custom1'] = $this->config->item('project_group_custom1_name');
            $data['custom2'] = $this->config->item('project_group_custom2_name');
            $data['custom3'] = $this->config->item('project_group_custom3_name');
            $data['custom4'] = $this->config->item('project_group_custom4_name');
            $data['link1'] = $this->config->item('project_group_link1_name');
            $data['link2'] = $this->config->item('project_group_link2_name');
            $data['link3'] = $this->config->item('project_group_link3_name');
            $data['logged_in'] = true;
            $data['is_admin'] = $user_role->admin;
            $data['role'] = $user_role->role_name;
            $data['can_edit_properties'] = $this->ion_auth->can_execute_task('project_groups_edit_properties');
            $data['can_edit_contacts'] = $this->ion_auth->can_execute_task('project_groups_edit_contacts');
            $data['can_edit_layers'] = $this->ion_auth->can_execute_task('project_groups_edit_layers');
            $data['can_edit_access'] = $this->ion_auth->can_execute_task('project_groups_edit_access');
            $data['return'] = $this->get_group_return_url($group);

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
                $this->user_model->clear_gisapp_session();
            } catch (Exception $e) {
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
            }

            if ($this->input->post('return') == null) {
                redirect('/project_groups/edit/' . $group_id);
            } else {
                redirect($this->get_group_return_url($group));
            }
        }

    }

    public function create()
    {
        if (!$this->ion_auth->is_admin()) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/project_groups/');
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

            $data['title'] = $this->lang->line('gp_new_group');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['group'] = $group;

            //filter for client administrator
            $user_role = $this->ion_auth->admin_scope();
            if(empty($filter)) {
                $data['clients'] = $this->client_model->get_clients();
            } else {
                $data['clients'] = [(array)$this->client_model->get_client($filter)];
            }

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
            $data['is_admin'] = $user_role->admin;
            $data['role'] = $user_role->role_name;

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
                redirect($this->get_group_return_url($group));
            }
        }
    }


    /**
     * Creates new group by parameters and add info for session
     */
    public function add_group($client_id, $name) {

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        try {
            if (!$this->ion_auth->is_admin()){
                throw new Exception('User not Admin!');
            }

            if(!empty($filter) && $filter !== (integer)$client_id) {
                throw new Exception('No permission!');
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
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/project_groups/');
        }

        $group = (array)$this->project_group_model->get_project_group($id);

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        // check if exists before trying to delete it
        if(isset($group['id']))
        {
            try {
                if(!empty($filter) && $filter !== (integer)$group['client_id']) {
                    throw new Exception('No permission!');
                }

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
                redirect($this->get_group_return_url($group));
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
                redirect('/project_groups/edit/'.$id);
            }
        }
        else
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">The group you are trying to delete does not exist.</div>');
    }

    public function add_layer($groups, $layer_id, $destination, $client_id, $back)
    {
        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        try {
            if (!$this->ion_auth->is_admin()) {
                throw new Exception('User not Admin!');
            }

            if(!empty($filter) && $filter !== (integer)$client_id) {
                throw new Exception('No permission!');
            }

            $groups = urldecode($groups);

            $res = $this->project_group_model->add_layer($groups, $layer_id, $destination);
            $db_error = $this->db->error();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }
            $this->user_model->clear_gisapp_session();
        } catch (Exception $e) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
        } finally {
            if($back === 'layers') {
                redirect('layers/edit/' . $layer_id . '#edit-access');
            } else {
                redirect('project_groups/edit/' . $groups . '#'.$back);
            }
        }
    }

    public function remove_layer($group, $layer_id, $destination, $client_id, $back)
    {
        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        try {
            if (!$this->ion_auth->is_admin()) {
                throw new Exception('User not Admin!');
            }

            if(!empty($filter) && $filter !== (integer)$client_id) {
                throw new Exception('No permission!');
            }

            $res = $this->project_group_model->remove_layer($group, $layer_id, $destination);
            $db_error = $this->db->error();
            $this->user_model->clear_gisapp_session();
            if (!empty($db_error['message'])) {
                throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
        } finally {
            if($back === 'layers') {
                redirect('layers/edit/' . $layer_id . '#edit-access');
            } else {
                redirect('project_groups/edit/' . $group . '#'.$back);
            }
        }
    }

    public function remove_contact($id = FALSE)
    {
        if ($id === false) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Project group parameter missing.</div>');
            redirect('/project_groups/');
        }

        try {
            $this->project_group_model->remove_contact($id);
        } catch (Exception $e){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
        }
        finally {
            redirect('/project_groups/edit/'.$id);
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

        $data = array(
            'id'                        => $this->input->post('id'),
            'name'                      => $this->input->post('name'),
            'display_name'              => set_null($this->input->post('display_name')),
            'parent_id'                 => set_null($this->input->post('parent_id')),
            'type'                      => $this->input->post('type'),
            'client_id'                 => $this->input->post('client_id'),
            'base_layers_ids'           => set_null($this->input->post('base_layers_ids')),
            'extra_layers_ids'          => set_null($this->input->post('extra_layers_ids')),
            'contact_id'                => set_null($this->input->post('contact_id')),
            'custom1'                   => set_null($this->input->post('custom1')),
            'custom2'                   => set_null($this->input->post('custom2'))
        );

        //other optional custom fields
		if(!empty($this->input->post('custom3'))) {
			$data['custom3'] = $this->input->post('custom3');
		}
		if(!empty($this->input->post('custom4'))) {
			$data['custom4'] = $this->input->post('custom4');
		}
		if(!empty($this->input->post('link1'))) {
			$data['link1'] = $this->input->post('link1');
		}
		if(!empty($this->input->post('link2'))) {
			$data['link2'] = $this->input->post('link2');
		}
		if(!empty($this->input->post('link3'))) {
			$data['link3'] = $this->input->post('link3');
		}

        if(empty($this->input->post('contact_id'))) {
            $data['contact'] = $this->input->post('contact');
            $data['contact_email'] = $this->input->post('contact_email');
            $data['contact_phone'] = $this->input->post('contact_phone');

        }

        return $data;
    }

    private function loadmeta(&$data){

        $data['base_layers'] = $this->layer_model->get_layers_filtered($data['group']['base_layers_ids']);
        $data['extra_layers'] = $this->layer_model->get_layers_filtered($data['group']['extra_layers_ids']);
        //old methods loading all layers with flag if exists for project/group
        //$data['base_layers'] = $this->layer_model->get_layers_with_project_flag($data['group']['base_layers_ids']);
        //$data['extra_layers'] = $this->layer_model->get_layers_with_project_flag($data['group']['extra_layers_ids']);
    }

    private function get_name($el) {
        return empty($el['display_name']) ? $el['name'] : $el['display_name'];
    }

    private function build_parent_link($id, $sep, &$result, $mode = NULL) {

        if(empty($id)) {
            return;
        }

        $new_group = (array)$this->project_group_model->get_project_group($id);
        $new_id = $new_group['parent_id'];
        if($mode === 'edit') {
            $result = anchor('project_groups/edit/' . $new_group['id'], $this->get_name($new_group)) . $sep . $result;
        } else {
            $result = anchor('project_groups/view/' . $new_group['client_id'] . '/' . $new_group['id'], $this->get_name($new_group)) . $sep . $result;
        }
        return $new_id;
    }

    private function build_admin_navigation($group)
    {
        $sep = ' > ';

        //this is current group, last in the tree, does not have link
        $group_full = $this->get_name($group);
        $parent_id = $group['parent_id'];

        $mode = 'edit';

        while (!empty($parent_id)) {
            $parent_id = $this->build_parent_link($parent_id, $sep, $group_full, $mode);
        }

        $client = $this->client_model->get_client($group['client_id']);
        $client_full = anchor('clients/edit/'.$client->id, $client->display_name);

        return $client_full . $sep . $group_full;
    }

    private function build_user_navigation($client, $parent_id)
    {
        if(empty($parent_id)) {
            $sep = '';
            $group_full = '';
            $client_full =  $client->display_name;
        } else {
            $sep = ' > ';
            //this is current group, last in the tree, does not have link
            $group = (array)$this->project_group_model->get_project_group($parent_id);

            //compare client from group and from parameter
            if($client->id !== $group['client_id']) {
                throw new Exception('Data parameters mismatch!');
            }

            $group_full = $this->get_name($group);
            $client_full = anchor('project_groups/view/'.$client->id, $client->display_name);

            //update parent_id, because current group we already have
            $parent_id = $group['parent_id'];
        }

        while (!empty($parent_id)) {
            $parent_id = $this->build_parent_link($parent_id, $sep, $group_full);
        }



        return $client_full . $sep . $group_full;
    }

    private function build_child_groups($client_id, $group_id, $user_groups = NULL)
    {
        $ret = $this->project_group_model->get_child_groups($client_id,$group_id,$user_groups);

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

    private function get_user_groups($client_id, $parent_id, $is_admin)
    {
        $filter = $this->ion_auth->admin_scope()->filter;
        $user = $this->user_model->get_user_by_id($this->session->userdata('user_id'));
        $user_groups = NULL;
        $ret = [];

        if(!empty($filter)) {
            //we have client administrator only if filter and client id match
            if($filter !== (integer)$client_id) {
                //normal user, return only groups with access
                $user_groups = $this->user_model->get_project_group_ids($user->user_id, TRUE);
            }
            $ret = $this->build_child_groups($client_id, $parent_id, $user_groups);

        } else {
            if(!$is_admin) {
                //normal user, return only groups with access
                $user_groups = $this->user_model->get_project_group_ids($user->user_id, TRUE);
            }
            $ret = $this->build_child_groups($client_id, $parent_id, $user_groups);
        }

        return $ret;
    }

    private function get_group_return_url(array $group)
    {
        $type = (integer)$group['type'];

        if($type === SUB_GROUP)
        {
            return 'clients/edit/'.$group['client_id'].'#edit-client-items';
        }

        return 'project_groups';
    }

    private function getImage($name)
    {
        $path = 'assets/img/groups/'.$name.'.png';
        $fn = set_realpath(FCPATH.$path, false);

        if (is_file($fn)) {
			return "<img title='".$fn."' class='img-responsive' src='" . base_url($path) . "'>";
        }
        else {
            return "<div class='alert alert-danger'><span class='glyphicon glyphicon-alert' aria-hidden='true'></span> Image missing (300x200px)</br>".$fn."</div>";
        }
    }
}

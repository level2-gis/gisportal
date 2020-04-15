<?php

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('client_model');
        $this->load->model('user_model');
        $this->load->model('project_model');
        $this->load->helper(array('url', 'html', 'eqwc_parse_helper'));
    }

    function index()
    {
        if (!$this->ion_auth->logged_in())
        {
            redirect('auth/login', 'refresh');
        }

        $user_role = $this->ion_auth->admin_scope();

        $data['title'] = $this->lang->line('gp_clients_title');
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;
        $data['clients'] = $this->get_user_clients($data['is_admin']);
        $data['open_groups'] = empty($this->config->item('portal_show_groups_for_client')) ? FALSE : $this->config->item('portal_show_groups_for_client');

		//rss
		if(!empty($this->config->item('rss_feed_url'))) {
			$rss_config['url'] = $this->config->item('rss_feed_url');
			$rss_config['limit'] = empty($this->config->item('rss_feed_limit')) ? 10 : $this->config->item('rss_feed_limit');
			$this->load->library('rss_parser', $rss_config);
			$rss['rss'] = $this->rss_parser->parse();
			$rss['rss']['last_login'] = $this->session->userdata('old_last_login');
		}

        $this->load->view('templates/header', $data);

        if (($data['clients'] === null) or (empty($data['clients']))) {
            $data['projects_public'] = $this->project_model->get_public_projects();
            if ($this->session->userdata('user_name') !== 'guest') {
                $this->load->view('message_view', array('message' => $this->lang->line('gp_no_projects'), 'type' => 'warning'));
            }
            if (($data['projects_public'] === null) or (empty($data['projects_public']))) {
                $this->load->view('message_view', array('message' => $this->lang->line('gp_no_public_projects'), 'type' => 'info'));
            } else {
                $this->load->view('public_projects_view', $data);
            }
        } else if (count($data['clients']) === 1) {
            if( $data['open_groups']) {
                redirect('project_groups/view/' . $data['clients'][0]['id']);
            } else {
                redirect('projects/view/' . $data['clients'][0]['id']);
            }
        } else {
            $this->load->view('clients', $data);
        }
		if(!empty($rss)) {
			$this->load->view('rss_short', $rss);
		}
        $this->load->view('templates/footer', $data);
    }

    private function get_user_clients($is_admin)
    {
        $filter = $this->ion_auth->admin_scope()->filter;
        $user = $this->user_model->get_user_by_id($this->session->userdata('user_id'));
        $groups = [];
        $ret = [];

        if(!empty($filter)) {
            if($is_admin) {
                //we have client administrator
                array_push($ret, (array)$this->client_model->get_client($filter));
            }
            $groups = $this->user_model->get_project_group_ids($user->user_id, TRUE);
            if(!empty($groups)) {
                $ret = array_unique(array_merge($ret, $this->client_model->get_clients($groups, false, false)), SORT_REGULAR);
            }
        } else {
            if(!$is_admin) {
                $groups = $this->user_model->get_project_group_ids($user->user_id, TRUE);
            }
            $ret = $this->client_model->get_clients($groups, $is_admin, false);
        }

        return $ret;
    }
}



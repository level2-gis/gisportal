<?php
class Profile extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url','html'));
		$this->load->library('session');
		$this->load->database();
		$this->load->model('user_model');
		$this->load->model('projects_model');
        $this->lang->load('gisportal_lang');
	}
	
	function index()
	{
		if ($this->session->userdata('user_is_logged_in')){

            $details = $this->user_model->get_user_by_id($this->session->userdata('uid'));

            $data['title'] = $this->lang->line('gp_profile_title');
            $data['projects_public'] = $this->projects_model->get_public_projects();

            $this->load->view('templates/header', $data);

            if($this->session->userdata('uid') !== null) {
                $data['user'] = $details[0];
                $data['projects'] = $this->projects_model->get_projects(false, $details[0]->project_ids);

                $this->load->view('profile_view', $data);
            } else {
                $data['user'] = null;
                $data['projects'] = null;
            }

            if (($data['projects'] === null) or (empty($data['projects']))) {
                if ($this->session->userdata('user_name') !== 'guest') {
                    $this->load->view('message_view', array('message' => $this->lang->line('gp_no_projects'), 'type' => 'warning'));
                }
            } else {
                $this->load->view('user_projects_view', $data);
            }

            if (($data['projects_public'] === null) or (empty($data['projects_public'])) ) {
                $this->load->view('message_view', array('message' => $this->lang->line('gp_no_public_projects'), 'type' =>'info'));
            } else {
                $this->load->view('public_projects_view', $data);
            }

            $this->load->view('templates/footer', $data);

        } else {

            $data['title'] = $this->lang->line('gp_home');

            $this->load->view('templates/header', $data);
            $this->load->view('home_view');
            $this->load->view('templates/footer', $data);

        }
	}
}
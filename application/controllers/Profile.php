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

            $data['user'] = $details[0];
            $data['title'] = $this->lang->line('gp_profile_title');
            $data['projects'] = $this->projects_model->get_projects(false, $details[0]->project_ids);

            $this->load->view('templates/header', $data);
            $this->load->view('profile_view', $data);
            $this->load->view('templates/footer', $data);
            //TODO
            //last_login
            //count_login
            //project_ids

        } else {

            $data['title'] = $this->lang->line('gp_home');

            $this->load->view('templates/header', $data);
            $this->load->view('home_view');
            $this->load->view('templates/footer', $data);

        }
	}
}
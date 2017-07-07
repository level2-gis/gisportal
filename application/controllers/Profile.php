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
	}
	
	function index()
	{
		$details = $this->user_model->get_user_by_id($this->session->userdata('uid'));
		$data['uname'] = $details[0]->user_name;
		$data['uemail'] = $details[0]->user_email;

        $data['title'] = 'My profile';

        $this->load->view('templates/header', $data);
        $this->load->view('profile_view');
        $this->load->view('templates/footer', $data);
        //TODO
        //last_login
        //count_login
        //project_ids
	}
}
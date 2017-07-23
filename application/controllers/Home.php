<?php
class Home extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->load->model('clients_model');
        $this->load->model('user_model');
        $this->load->helper(array('url', 'html'));
		$this->load->library('session');
        $this->lang->load('gisportal_lang');
	}
	
	function index()
	{
        if ($this->session->userdata('user_is_logged_in')){
            $data['title'] = $this->lang->line('gp_clients_title');

            $user = $this->user_model->get_user_by_id($this->session->userdata('uid'));

            $data['clients'] = $this->clients_model->get_clients($user[0]->project_ids);
            $this->load->view('templates/header', $data);
            $this->load->view('clients', $data);
            $this->load->view('templates/footer', $data);
        } else {
            $data['title'] = $this->lang->line('gp_home');

            $this->load->view('templates/header', $data);
            $this->load->view('home_view');
            $this->load->view('templates/footer', $data);
        }
 	}
	
	function logout()
	{
		// destroy session
        $data = array('login' => '', 'uname' => '', 'uid' => '');
        $this->session->unset_userdata($data);
        $this->session->sess_destroy();
		redirect('home/index');
	}
}



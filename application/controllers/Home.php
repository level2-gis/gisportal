<?php
class Home extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->load->model('clients_model');
		$this->load->helper(array('url', 'html'));
		$this->load->library('session');
	}
	
	function index()
	{
        if ($this->session->userdata('user_is_logged_in')){
            $data['title'] = 'Clients';

            $data['clients'] = $this->clients_model->get_clients();
            $this->load->view('templates/header', $data);
            $this->load->view('clients', $data);
            $this->load->view('templates/footer', $data);
        } else {
            $data['title'] = 'Home';

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



<?php
class Home extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->load->model('clients_model');
        $this->load->model('user_model');
        $this->load->model('projects_model');
        $this->load->helper(array('url', 'html'));
		$this->load->library('session');
        $this->lang->load('gisportal_lang');
	}
	
	function index()
	{
        if ($this->session->userdata('user_is_logged_in')){
            $data['title'] = $this->lang->line('gp_clients_title');

            if($this->session->userdata('uid') !== null) {
                $user = $this->user_model->get_user_by_id($this->session->userdata('uid'));
                $data['clients'] = $this->clients_model->get_clients($user[0]->project_ids, $user[0]->admin);
            } else {
                $data['clients'] = null;
            }

            $this->load->view('templates/header', $data);

            if (($data['clients'] === null) or (empty($data['clients'])) ) {
                $data['projects_public'] = $this->projects_model->get_public_projects();
                if ($this->session->userdata('user_name') !== 'guest') {
                    $this->load->view('message_view', array('message' => $this->lang->line('gp_no_projects'), 'type' => 'warning'));
                }
                if (($data['projects_public'] === null) or (empty($data['projects_public'])) ) {
                    $this->load->view('message_view', array('message' => $this->lang->line('gp_no_public_projects'), 'type' =>'info'));
                } else {
                    $this->load->view('public_projects_view', $data);
                }
            }
            else if (count($data['clients']) === 1) {
                redirect('projects/view/' . $data['clients'][0][id]);
            }
            else {
                $this->load->view('clients', $data);
            }
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



<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Files extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url', 'html', 'path', 'eqwc_dir', 'file', 'date', 'number'));
	}

	public function index()
	{

	}

	public function view360img($client_name = FALSE, $folder = FALSE, $filename = FALSE)
	{
		try {
			if ($client_name === FALSE) {
				throw new Exception('Client required!');
			}

			//todo check filename on server?

			$data['title'] = $filename;
			$data['panorama'] = 'uploads/' . $client_name . '/' . $folder . '/' . $filename;

			$this->load->view('image360', $data);

		} catch (Exception $e) {
			$data['title'] = $client_name;
			$data['logged_in'] = false;
			$data['is_admin'] = false;
			$data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');

			$data['message'] = $e->getMessage();
			$data['type'] = 'danger';
			$this->load->view('templates/header', $data);
			$this->load->view('templates/header_navigation', $data);
			$this->load->view('message_view', $data);
			$this->load->view('templates/footer');
			return;
		}
	}

}

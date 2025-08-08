<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends CI_Controller
{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('client_model');
        $this->load->model('project_model');
        $this->load->model('project_group_model');
        $this->load->model('user_model');
        $this->load->model('layer_model');
        $this->load->model('plugin_model');
        $this->load->model('qgisproject_model');
        $this->load->helper(array('url', 'html', 'path', 'eqwc_parse', 'eqwc_dir', 'file', 'download', 'number'));
    }

    public function index()
    {
        //allow viewing of projects to every logged in user, not only admin (user sees only projects with permission)
        if (!$this->ion_auth->logged_in()){
            redirect('/auth/login?ru=/' . uri_string());
        }

        $task = 'projects_table_view';
        $user_role = $this->ion_auth->admin_scope();

		$data['title'] = $this->lang->line('gp_projects_title');
		$data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
		$data['logged_in'] = true;
		$data['is_admin'] = $user_role->admin;;
		$data['role'] = $user_role->role_name;
		$data['projects'] = $this->get_user_projects($data['is_admin']);
		$data['can_edit'] = $this->ion_auth->can_execute_task($task);

		$this->load->view('templates/header', $data);
		$this->load->view('templates/header_navigation', $data);

		if (empty($data['projects'])) {
			if ($user_role->admin) {
				$this->load->view('projects_admin', $data);
			} else {
				$this->load->view('message_view', array('message' => $this->lang->line('gp_no_projects'), 'type' => 'warning'));
			}
		} else {
			$this->load->view('projects_admin', $data);
		}

		$this->load->view('templates/footer');
	}

    /*
     * Portal view user projects by client
     */
    public function view($client_id = false)
    {
        if ($client_id === FALSE) {
            redirect("/");
        }

        if (!$this->ion_auth->logged_in()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $client = $this->client_model->get_client($client_id);
        $user_role = $this->ion_auth->admin_scope();

		$data['title'] = $this->lang->line('gp_projects_title');
		$data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
		$data['logged_in'] = true;
		$data['is_admin'] = $user_role->admin;
		$data['role'] = $user_role->role_name;
		$data['projects'] = $this->get_user_projects($data['is_admin'], $client_id);
		$data['navigation'] = $this->build_user_navigation($client);

		//rss with caching support
		$this->load->helper('rss');
		$rss = load_rss_feed($this);

		$this->load->view('templates/header', $data);
		$this->load->view('templates/header_navigation', $data);
		$this->load->view('projects', $data);
		if (!empty($rss)) {
			$this->load->view('rss_short', $rss);
		}
		$this->load->view('templates/footer');

	}

    /*
     * Portal view user projects by group
     */
    public function view_group($client_id = FALSE, $group_id = FALSE)
    {
        if ($group_id === FALSE || $client_id === FALSE) {
            redirect("/");
        }

        if (!$this->ion_auth->logged_in()) {
            redirect('/auth/login?ru=/' . uri_string());
        }

        $client = $this->client_model->get_client($client_id);
        $user_role = $this->ion_auth->admin_scope();

        $data['title'] = $this->lang->line('gp_projects_title');
        //$data['scheme'] = $_SERVER["REQUEST_SCHEME"];
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;

		//rss with caching support
		$this->load->helper('rss');
		$rss = load_rss_feed($this);

		try {
			$data['projects'] = $this->get_user_projects_for_group($data['is_admin'], $client_id, $group_id);
			$data['navigation'] = $this->build_user_navigation($client, $group_id);

			//get modules only if model exists first (extension to gisportal)
			$modules['modules'] = [];
			if (file_exists(APPPATH . "models/modules/Module_model.php")) {
				$this->load->model('modules/module_model');
				$modules['modules'] = $this->module_model->get_modules($client_id, $group_id);
				$modules['client'] = $client->name;
			}

			//if there is single project and no modules, just open it
			if(count($data['projects']) == 1 && count($modules['modules']) == 0) {
				redirect(site_url($this->config->item('web_client_url').$data['projects'][0]['name']));
			}

		} catch (Exception $e) {
			$this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
			redirect("/");
		}

		$this->load->view('templates/header', $data);
		$this->load->view('templates/header_navigation', $data);
		$this->load->view('projects', $data);
		if (!empty($modules['modules'])) {
			$this->load->view('modules/view', $modules);
		}
		if (!empty($rss)) {
			$this->load->view('rss_short', $rss);
		}
		$this->load->view('templates/footer');

	}


    /**
	 * Upload QGIS project or ZIP file
	 */
    public function upload_admin($client_id = false, $group_id = false) {

        if (!$this->ion_auth->is_admin()){
            redirect('/');
        }

        try {
            if ($client_id === FALSE) {
                throw new Exception('Client not found!');
            }

            if ($group_id === FALSE) {
                throw new Exception('Group not found!');
            }

            $client = $this->client_model->get_client($client_id);
            if ($client == null) {
                throw new Exception('Client not found!');
            }

            $group = $this->project_group_model->get_project_group($group_id);
            if ($group == null) {
                throw new Exception('Group not found!');
            }
            $client_name = $client->name;
            $group_name = $group->name;

            //put project to which subfolder, from config
            $dir = set_realpath($this->qgisproject_model->get_default_qgs_project_path($client_name,$group_name));

            $project_id = $this->input->post('project_id');
            if ($project_id) {
                //editing existing project, get project directory
                //$project = $this->project_model->get_project($project_id);
				$qgis = $this->qgisproject_model->check_qgs_file($project_id);
				if ($qgis["valid"]) {
					$dir = set_realpath(dirname($qgis["name"]));
				}
			}

			$config['upload_path'] = $dir;
			$config['allowed_types'] = ['qgs', 'zip'];
			$config['overwrite'] = true;
			$config['file_ext_tolower'] = true;
			$config['max_size'] = round($this->file_upload_max_size() / 1024);    //convert bytes to KB, still I don't get correct error message if this exceeds!

			$this->load->library('upload', $config);

			if (!$this->upload->do_upload('userfile')) {
				$this->session->set_flashdata('alert', '<div class="alert alert-danger">' . $dir . $this->upload->display_errors() . ' (' . $this->upload->file_name . ')</div>');
				if (!empty($project_id)) {
					redirect('projects/edit/' . $project_id);
				} else {
					redirect('projects/');
				}
            } else {
                //pass qgis project name and client_id
                $file_name = $this->upload->file_name;
                $ext = $this->upload->file_ext;
                $project_name = str_replace($ext,'',$file_name);

				$this->session->set_flashdata('project_name', $project_name);
				$this->session->set_flashdata('client_id', $client_id);
				$this->session->set_flashdata('project_group_id', $group_id);

				//set permission to 777
				if (is_file($dir . $file_name)) {
					chmod($dir . $file_name, 0777);
				}

				//echo $dir . $file_name;

				if ($ext == '.zip') {
					$this->load->library('Ziparchivesub');
					$zip = new ZipArchiveSub();
					$zipMsg =  [];
					if ($zip->open($dir . $file_name) === TRUE) {

						if (dirname($zip->getNameIndex(0)) == $project_name) {
							$zipMsg = $zip->extractSubdirTo($dir, $project_name . DIRECTORY_SEPARATOR);
						}
						else {
							throw new Exception('ZIP file must contain folder: '.$project_name);
						}

						$zip->close();
						unlink($dir . $file_name);

						if (!empty($zipMsg)) {
							throw new Exception(implode('<br>',$zipMsg));
						} else {
							$this->session->set_flashdata('upload_msg', '<div class="alert alert-success">' . 'Unzip OK' . ' (' . $this->upload->file_name . ')</div>');
						}

					} else {
						throw new Exception('ZIP Unpack error: ' . $this->upload->file_name);
					}
				} else {
					$this->session->set_flashdata('upload_msg', '<div class="alert alert-success">' . $this->lang->line('gp_upload_success') . ' (' . $this->upload->file_name . ')</div>');
				}

				$data['file_name'] = $project_name;
				$id = $this->session->userdata('user_id');
				$data['user'] = (array)$this->user_model->get_user_by_id($id);
				$data['client'] = (array)$client;

				//send email to all admins that project was uploaded
				$message = $this->load->view($this->config->item('email_templates', 'ion_auth') . 'upload_project.tpl.php', $data, TRUE);
				$admin_emails = $this->user_model->get_client_admins($client_id);
				$this->ion_auth->send_email('Nova verzija projekta '.$project_name, $message, $admin_emails);

				$this->user_model->clear_gisapp_session();
				if (!empty($project_id)) {
					redirect('projects/edit/' . $project_id);
				} else {
					redirect('projects/create/' . NEW_UPLOAD);
				}
			}

        } catch (Exception $e) {

            $this->session->set_flashdata('upload_msg', '<div class="alert alert-danger">' . $e->getMessage() . '</div>');
            if(!empty($project_id)) {
                redirect('projects/edit/'.$project_id);
            } else {
                redirect('projects/create/'.NEW_UPLOAD);
            }
        }
    }


    /**
     * Public upload files
     * Used in Editor plugin
     */
    public function upload($project_id = false)
    {
        if ($project_id === FALSE) {
            redirect("/");
        }

        try {
			if (!$this->ion_auth->logged_in()){
				throw new Exception(lang('gp_session_timeout'));
			}

			$project = $this->project_model->get_project($project_id);
            if ($project == null) {
                throw new Exception('Project not found!');
            }
            //TODO client_id on project!
            $client = $this->client_model->get_client($project->client_id);
            if ($client == null) {
                throw new Exception('Client not found!');
            }
            $client_name = $client->name;
            $project_name = $project->name;

            $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'), false) . $client_name, false);
            $dir .= DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $config['upload_path'] = $dir;
            $config['allowed_types'] = 'gif|jpg|png|pdf';
            $config['overwrite'] = true;
            $config['file_ext_tolower'] = true;
            //$config['max_size']             = 100;
            //$config['max_width']            = 1024;
            //$config['max_height']           = 768;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('userfile')) {
                throw new Exception ($this->upload->display_errors('', ''));
            }

            //create thumb if upload is image
            if (strpos($this->upload->file_type, 'image/') > -1) {
				$this->load->library('image_lib', $config);
				$res = self::imageResize($dir, $this->upload->file_name);
				if (!$res) {
					throw new Exception ($dir . $this->upload->file_name . ' ' . $this->image_lib->display_errors('', ''));
				}
			}

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(200)
                ->set_output(json_encode(array(
                    'success' => true,
                    'message' => $this->lang->line('gp_upload_success'),
                    'file' => $this->upload->file_name
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));


        } catch (Exception $e) {

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(500)
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    public function files($project_id = false)
    {
        $scheme = $_SERVER["REQUEST_SCHEME"];

        if ($project_id === FALSE) {
            redirect("/");
        }

        try {
			if (!$this->ion_auth->logged_in()){
				throw new Exception(lang('gp_session_timeout'));
			}

			$project = $this->project_model->get_project($project_id);
            if ($project == null) {
                throw new Exception('Project not found!');
            }
            //TODO client_id on project!
            $client = $this->client_model->get_client($project->client_id);
            if ($client == null) {
                throw new Exception('Client not found!');
            }
            $client_name = $client->name;
            $project_name = $project->name;

            $dir = set_realpath(set_realpath($this->config->item('main_upload_dir'), false) . $client_name, false);
            $dir .= DIRECTORY_SEPARATOR . $project_name . DIRECTORY_SEPARATOR;

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $files = get_dir_file_info($dir);

            $webdir = $this->config->item('main_upload_web') . $client_name . DIRECTORY_SEPARATOR. $project_name . DIRECTORY_SEPARATOR;

            $report = new stdClass();
            $report->files = [];
            foreach ($files as $key => $value)
            {
                if (is_dir($value["server_path"])) {
                    continue;
                }

                $extension = strtoupper(substr(strrchr($value["name"], '.'), 1));

                $thumb='';
                $url = base_url($webdir.$value['name']);
                if(file_exists($dir.'thumb'.DIRECTORY_SEPARATOR . $value["name"])) {
                    $thumb = base_url($webdir.'thumb'.DIRECTORY_SEPARATOR .$value['name']);
                } else {
                    $thumb = $scheme . "://dummyimage.com/225x150/e0e0e0/706e70?text=".$extension;
                }

                $newVal = array(
                    "name" => $value["name"],
                    "size" => $value["size"],
                    "lastmod" => $value["date"],
                    "thumb" => $thumb
                );

                array_push($report->files, $newVal);
            }

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(200)
                ->set_output(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));

        } catch (Exception $e) {

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(500)
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

	/**
	 * Method to download QGIS project file or ZIP file with /data subfolder for administrators
	 */
	public function download($type, $project_id = false)
	{
		$this->load->library('zip');
		//extending default 128M limit
		ini_set('memory_limit', '512M');

		if (!$this->ion_auth->is_admin()) {
			redirect('/');
		}

		if ($project_id === FALSE) {
			redirect("/");
		}

		try {
//            $project = $this->project_model->get_project($project_id);
//            //TODO client_id on project!
//            $client = $this->client_model->get_client($project->client_id);
//
//            if ($client == null) {
//                throw new Exception('Client not found!');
//            }
//
//            $client_name = $client->name;


			$qgs_file = '';
			$check = $this->qgisproject_model->check_qgs_file($project_id);

			if ($check['valid']) {
				$qgs_file = $check["name"];
			} else {
				throw new Exception($check['name']);
			}

			if ($type == 'qgs') {
				force_download($qgs_file, NULL);
			} elseif ($type == 'zip') {
				$dir = dirname($qgs_file);
				$path = set_realpath($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
				$base = pathinfo($qgs_file)['filename'];
				if (is_dir($path)) {
					$this->zip->read_dir($path, FALSE);
				}
				$list = array_diff(scandir($dir), array('..', '.'));
				foreach ($list as $fn) {
					$full = $dir . DIRECTORY_SEPARATOR . $fn;
					//we add to zip project.* files
					if (is_file($full) && pathinfo($full)['filename'] == $base) {
						$this->zip->read_file($full);
					}
				}

				$this->zip->download($base . '.zip');
			}

		} catch (Exception $e) {

            $this->output
                ->set_content_type('text/html')
                ->set_status_header(500)
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }


    }

    public function edit($project_id = false)
    {
        $task = 'projects_edit';

        if (!$this->ion_auth->can_execute_task($task)){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/projects/');
        }

        if ($project_id === false) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Project parameter missing.</div>');
            redirect('/projects/');
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        //$this->form_validation->set_rules('name', 'lang:gp_name', 'trim|required|alpha_dash|callback__unique_name');
        //$this->form_validation->set_rules('display_name', 'lang:gp_display_name', 'trim|required');
        //$this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
        $this->form_validation->set_rules('project_group_id', 'lang:gp_group', 'required');
        $this->form_validation->set_rules('feedback_email', 'lang:gp_feedback_email', 'valid_email');


        if ($this->form_validation->run() === FALSE)
        {
            $data['title'] = $this->lang->line('gp_new_project');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['creating'] = true;

            $em = $this->project_model->new_project();
            //pass data from uploaded project
            $em["name"] = $this->session->flashdata('project_name') ? $this->session->flashdata('project_name') : '';
            $em["client_id"] = $this->session->flashdata('client_id') ? $this->session->flashdata('client_id') : null;

            $user_role = $this->ion_auth->admin_scope();

            if(sizeof($_POST) > 0){
                $em = $this->extractProjectData();
                $data['title'] = $this->lang->line('gp_project') .' '. $em['display_name'];
                $data['creating'] = false;
            } else {
                try {
                    $prj = $this->project_model->get_project($project_id);
                    if (empty($prj)) {
                        throw new Exception('Project does not exist!');
                    }

                    //filter for client administrator
                    $filter = $user_role->filter;
                    if (!empty($filter) && $filter !== (integer)$prj->client_id) {
                        throw new Exception('No permission!');
                    }

                } catch (Exception $e) {
                    $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
                    redirect('/projects');
                }

                $em = (array)$prj;
                //overwrite project_group_id with session value, not saved!!!
                if (!empty($this->session->flashdata('project_group_id'))) {
                    $em['project_group_id'] = $this->session->flashdata('project_group_id');
                }
                $data['title'] = $this->lang->line('gp_project') . ' ' . $em['display_name'];
                $data['creating'] = false;
            }

            $data['project'] = $em;
            $data['image'] = $this->getImage($em['name']);
            $data['clients'] = [(array)$this->client_model->get_client($em['client_id'])];
            $data['groups'] = $this->project_group_model->get_project_groups($em["client_id"], true);
			$data['plugins'] = $this->plugin_model->get_plugins_with_project_flag($data['project']['plugin_ids']);
			$data['layers'] = $this->layer_model->get_layers($filter, TRUE); //used only for overview layer selection
			$data['admin_navigation'] = $this->build_admin_navigation($em);
			$data['logged_in'] = true;
			$data['is_admin'] = $user_role->admin;
			$data['role'] = $user_role->role_name;
			$data['can_edit_plugins'] = $this->ion_auth->can_execute_task('projects_edit_plugins');

			$data['qgis_check'] = $this->qgisproject_model->check_qgs_file($project_id);

			$data['upload_size'] = round($this->file_upload_max_size());
			$data['upload_size_text'] = byte_format($this->file_upload_max_size());

			//$this->qgisinfo($data);

			$this->load->view('templates/header', $data);
			$this->load->view('templates/header_navigation', $data);
			$this->load->view('project_title', $data);
			$this->load->view('project_check', $data);
			$this->load->view('project_upload_form', $data);
			$this->load->view('project_edit', $data);
			//$this->load->view('templates/footer');
		} else {

            $project = $this->extractProjectData();
            //$users = $this->extractUserProjectData();
            try {
                $project_id = $this->project_model->upsert_project($project);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }
                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_project').' <strong>' . $project['name'] . '</strong>'.$this->lang->line('gp_saved').'</div>');
                $this->user_model->clear_gisapp_session();
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
            }
            if($this->input->post('return') == null){
                redirect('/projects/edit/' . $project_id);
            } else {
                redirect('/projects');
            }
        }
    }

    public function create($action = FALSE) {

        if (!$this->ion_auth->is_admin()){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/projects/');
        }

        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('name', 'lang:gp_name', 'trim|required|alpha_dash|callback__unique_name');
        $this->form_validation->set_rules('client_id', 'lang:gp_client', 'required');
        $this->form_validation->set_rules('project_group_id', 'lang:gp_group', 'required');

        if ($this->form_validation->run() === FALSE) {
            $data['title'] = $this->lang->line('gp_new_project');
            $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
            $data['creating'] = true;

            $em = $this->project_model->new_project();

            //pass data from uploaded project or session
            $em["name"] = $this->session->flashdata('project_name') ? $this->session->flashdata('project_name') : $this->input->post('name');
            $em["client_id"] = $this->session->flashdata('client_id') ? $this->session->flashdata('client_id') : $this->input->post('client_id');
            $em["display_name"] = $this->input->post('display_name');
            $em["project_group_id"] = $this->session->flashdata('project_group_id') ? $this->session->flashdata('project_group_id') : $this->input->post('project_group_id');

            $data['action'] = $action;

            //filter for client administrator
            $user_role = $this->ion_auth->admin_scope();
            $filter = $user_role->filter;
            if(empty($filter)) {
				$data['clients'] = $this->client_model->get_clients();
				$data['groups'] = $this->project_group_model->get_project_groups($em["client_id"], true);
				$data['templates'] = $this->get_templates($em["client_id"], true);
			} else {
				$data['clients'] = [(array)$this->client_model->get_client($filter)];
				$data['groups'] = $this->project_group_model->get_project_groups($filter, true);
				$data['templates'] = $this->get_templates($filter, true);
				$data['project']['client_id'] = $filter;
			}

			//set client_id if there is only one client available
			if (count($data['clients']) == 1) {
				$em['client_id'] = $data['clients'][0]['id'];
			}

			$data['project'] = $em;

			$data['logged_in'] = true;
			$data['is_admin'] = $user_role->admin;
			$data['role'] = $user_role->role_name;

			$data['upload_size'] = round($this->file_upload_max_size());
			$data['upload_size_text'] = byte_format($this->file_upload_max_size());

			$this->load->view('templates/header', $data);
			$this->load->view('templates/header_navigation', $data);
			$this->load->view('project_title', $data);

			if ($action == NEW_UPLOAD) {
				$this->load->view('project_upload_form', $data);
			}

			$this->load->view('project_create', $data);

		} else {

            $project = $this->project_model->new_project();

            //pass data from uploaded project or session
            $project["name"] = $this->session->flashdata('project_name') ? $this->session->flashdata('project_name') : $this->input->post('name');
            $project["client_id"] = $this->session->flashdata('client_id') ? $this->session->flashdata('client_id') : $this->input->post('client_id');
            $project["display_name"] = $this->input->post('display_name');
            $project["project_group_id"] = $this->session->flashdata('project_group_id') ? $this->session->flashdata('project_group_id') : $this->input->post('project_group_id');
            $project["template"] = $this->input->post('template');

            //TODO client_id on project!
            $client = $this->client_model->get_client($project["client_id"]);

            //$users = $this->extractUserProjectData();
            $project_id = null;
            try {
                //copy template if selected
                if(!empty($project["template"])) {
                    $this->copy_template($project["template"],$project["name"],$client->name);
                }

				$project_id = $this->project_model->upsert_project($project);
				$db_error = $this->db->error();
				if (!empty($db_error['message'])) {
					throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
				}
				$this->session->set_flashdata('alert', '<div class="alert alert-success text-center">' . $this->lang->line('gp_project') . ' <strong>' . $project['name'] . '</strong>' . $this->lang->line('gp_saved') . '</div>');
				$this->user_model->clear_gisapp_session();
			} catch (Exception $e) {
				$this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
			}
			if ($this->input->post('return') == null) {
				redirect('/projects/edit/' . $project_id);
			} else {
				redirect('/projects');
			}
		}
	}

	function public_map($project_name = FALSE)
	{
		try {
			if ($project_name === FALSE) {
				throw new Exception('Project required!');
			}

			$project = $this->project_model->get_project_by_name($project_name);

			if (empty($project)) {
				throw new Exception('Project not correct!');
			}

			$project = (array)$project[0];

			$wms = self::get_project_wms_definition($project['id']);
			if (empty($wms)) {
				throw new Exception('Project not valid');
			}

			$wms_public = self::check_service('wms', 'public', basename($this->qgisproject_model->qgs_file));
			if (!$wms_public['published']) {
				throw new Exception('Project not published');
			}

			$client = $this->client_model->get_client($project['client_id']);
			if (empty($client)) {
				throw new Exception('Client not valid');
			}

			$group = (array)$this->project_group_model->get_project_group($project['project_group_id']);
			if (empty($group)) {
				throw new Exception('Project group not valid');
			}

		} catch (Exception $e) {
			$data['title'] = $project_name;
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

		//fix public URL
		$wms['url'] = str_replace('proxy', 'wms-public', $wms['url']);

		$project['definition'] = json_encode($wms);
		$project['type'] = 'WMS';

		$data['title'] = $project['display_name'];
		$data['subtitle'] = $project['description'];
		$data['logged_in'] = false;
		$data['is_admin'] = false;

		$base = $this->layer_model->get_layers_filtered($group['base_layers_ids'], 1);
		$extra = $this->layer_model->get_layers_filtered($group['extra_layers_ids'], 0);
		$data['baselayers'] = array_merge($base, [$project], $extra);

		$data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
		//$data['overview'] = $overview;

		$data['url'] = $client->url;
		$data['logo'] = $this->getGisappClientLogo($client->name);

		$data['extent'] = $wms['extent'];
		$data['crs'] = $wms['crs'];
		$data['proj4'] = $wms['proj4'];
		$data['showCoords'] = true;
		$data['showProjection'] = true;
		$data['showLayerSwitcher'] = true;

		$this->load->view('templates/header_map', $data);
		$this->load->view('templates/header_public', $data);
		$this->load->view('map', $data);
		$this->load->view('templates/footer');
	}

	/*
	 * Show Openlayers map project preview
	 */
	function map($id = FALSE)
	{

		if ($id === FALSE) {
			redirect("/");
		}

		if (!$this->ion_auth->logged_in()) {
			redirect('/auth/login?ru=/' . uri_string());
		}

		$project = (array)$this->project_model->get_project($id);
		$user_role = $this->ion_auth->admin_scope();

		if(empty($project)) {
			$this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No project!</div>');
			redirect("/");
		}

		$wms = self::get_project_wms_definition($id);

		if (empty($wms)) {
			$this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Project not valid</div>');
			redirect("/projects/edit/" . $id);
		}

		$group = (array)$this->project_group_model->get_project_group($project['project_group_id']);

		$project['definition'] = json_encode($wms);
		$project['type'] = 'WMS';

		$data['title'] = $this->lang->line('gp_project') . ' ' . $project['display_name'];
		$data['subtitle'] = $wms['version'];

		$base = $this->layer_model->get_layers_filtered($group['base_layers_ids'], 1);
		$extra = $this->layer_model->get_layers_filtered($group['extra_layers_ids'], 0);
		$data['baselayers'] = array_merge($base, [$project], $extra);

		$data['logged_in'] = true;
		$data['is_admin'] = $user_role->admin;
		$data['role'] = $user_role->role_name;
		$data['edit_url'] = 'projects/edit/' . $project['id'];
		$data['extent'] = $wms['extent'];
		$data['crs'] = $wms['crs'];
		$data['proj4'] = $wms['proj4'];
		$data['showCoords'] = true;
		$data['showProjection'] = true;
		$data['showLayerSwitcher'] = true;

		$this->load->view('templates/header_map', $data);
		$this->load->view('templates/header_navigation', $data);
		$this->load->view('map_edit', $data);
		$this->load->view('map', $data);
		$this->load->view('templates/footer');
	}

    public function remove($id, $remove_files = FALSE)
    {
        if (!$this->ion_auth->is_admin()){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/projects/');
        }

        $remove_files = (boolean)$remove_files;

        $project = (array)$this->project_model->get_project($id);

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        // check if the project exists before trying to delete it
        if(isset($project['id']))
        {
            try {
                if(!empty($filter) && $filter !== (integer)$project['client_id']) {
                    throw new Exception('No permission!');
                }

                $check = [];
                if($remove_files) {
                    $check = $this->qgisproject_model->check_qgs_file($id);
                }

                $this->project_model->delete_project($id);
                $db_error = $this->db->error();
                if (!empty($db_error['message'])) {
                    throw new Exception('Database error! Error Code [' . $db_error['code'] . '] Error: ' . $db_error['message']);
                }

                $delete_report = '';
                if($check['valid']) {
                    $qgs = $check['name'];
                    $json = str_replace('qgs','json',$qgs);
                    $html = str_replace('qgs','html',$qgs);

                    if (unlink($qgs)){
                        $delete_report .= '</br>' . $qgs . ' deleted!';
                    }
                    if(file_exists($json)) {
                        if(unlink($json)) {
                            $delete_report .= '</br>' . $json . ' deleted!';
                        }
                    }
                    if(file_exists($html)) {
                        if(unlink($html)) {
                            $delete_report .= '</br>' . $html . ' deleted!';
                        }
                    }
                }

                $this->session->set_flashdata('alert', '<div class="alert alert-success text-center">'.$this->lang->line('gp_project').' <strong>' . $this->input->post('name') . '</strong>'.$this->lang->line('gp_deleted') . $delete_report .'</div>');
                redirect('/projects');
            }
            catch (Exception $e){
                $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">'.$e->getMessage().'</div>');
                redirect('/projects/edit/'.$id);
            }
        }
        else
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">The project you are trying to delete does not exist.</div>');
    }

    /**
     * Method to list available services for project
     */
    public function services($project_id = false)
    {
        if (!$this->ion_auth->is_admin()){
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/projects/');
        }

        $project = $this->project_model->get_project($project_id);
        if(!$project) {
            redirect('/projects');
        }

        //filter for client administrator
        $user_role = $this->ion_auth->admin_scope();
        $filter = $user_role->filter;
        if(!empty($filter) && $filter !== (integer)$project->client_id) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">No permission!</div>');
            redirect('/projects/');
        }

        $data['title'] = $this->lang->line('gp_publish').' '. strtolower($this->lang->line('gp_project')) .' '.$project->display_name;
        $data['lang'] = $this->session->userdata('lang') == null ? get_code($this->config->item('language')) : $this->session->userdata('lang');
        $data['project'] = (array)$project;
        $data['clients'] = [(array)$this->client_model->get_client($project->client_id)];
        $data['services'] = [];
        $data['logged_in'] = true;
        $data['is_admin'] = $user_role->admin;
        $data['role'] = $user_role->role_name;

		$data['qgis_check'] = $this->qgisproject_model->check_qgs_file($project_id);

		//$this->qgisinfo($data);
		if ($data['qgis_check']['valid']) {
			$data['services'] = self::get_project_services($data['qgis_check']['name']);
		} else {
			$this->session->set_flashdata('alert', '<div class="alert alert-danger"><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> ' . $data['qgis_check']['name'] . '</div>');
		}

		$this->load->view('templates/header', $data);
		$this->load->view('templates/header_navigation', $data);
		$this->load->view('project_services', $data);
		$this->load->view('templates/footer');

	}

    public function publish_service($project_id = FALSE, $name = null, $type = null) {
        if(!$project_id || !$name || !$type) {
            redirect('/projects');
        }

        $project = $this->project_model->get_project($project_id);
        if (!$project) {
            redirect('/');
        }

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        try {
            if (!$this->ion_auth->is_admin()) {
                throw new Exception('User not Admin!');
            }

            if(!empty($filter)) {
                throw new Exception('No permission!');
            }

            $this->load->model('qgisproject_model');

            $data['project'] = (array)$project;
            $data['clients'] = [(array)$this->client_model->get_client($project->client_id)];

            $data['qgis_check'] = $this->qgisproject_model->check_qgs_file($project_id);

            //$this->qgisinfo($data);

            $project_full_path = $data['qgis_check']['name'];

            $service = self::check_service($name, $type, basename($project_full_path));
            $service_path = set_realpath($service['path']);
            $project_new_name = $service['file_name'];

            $keep_wfst = $this->config->item('keep_wfs-t_from_qgs');


            $qgs = $this->qgisproject_model;
            $qgs->qgs_file = $project_full_path;
            if (!$qgs->read_qgs_file()) {
                throw new Exception($qgs->error);
            }

            //setting advertised service url in the project qgs
            if ($name == 'wms') {
                $qgs->qgs_xml->properties->WMSUrl = base_url($service['url']);
            } else if ($name == 'wfs') {
                $qgs->qgs_xml->properties->WFSUrl = base_url($service['url']);
                //remove transactions in case of setting
                if ($keep_wfst === FALSE) {
                    $qgs->qgs_xml->properties->WFSTLayers->Insert = null;
                    $qgs->qgs_xml->properties->WFSTLayers->Update = null;
                    $qgs->qgs_xml->properties->WFSTLayers->Delete = null;
                }
            }

            //write qgs to new location
            if (!$qgs->write_qgs_file($service_path . $project_new_name)) {
                throw new Exception($qgs->error);
            }

            //set permission to 777
            if (is_file($service_path . $project_new_name)) {
                chmod($service_path . $project_new_name, 0777);
            }

            //if(!copy($project_full_path, $service_path . $project_new_name)) {
            //    throw new Exception ("Copy project failed to ". $service_path);
            //}
        } catch (Exception $e) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
        } finally {
            redirect('/projects/services/' . $project_id);
        }
    }

    public function stop_service($project_id = FALSE, $name = null, $type = null)
    {
        if (!$project_id || !$name || !$type) {
            redirect('/projects');
        }

        $project = $this->project_model->get_project($project_id);
        if (!$project) {
            redirect('/');
        }

        //filter for client administrator
        $filter = $this->ion_auth->admin_scope()->filter;

        try {
            if (!$this->ion_auth->is_admin()) {
                throw new Exception('User not Admin!');
            }

            if(!empty($filter)) {
                throw new Exception('No permission!');
            }

            $data['project'] = (array)$project;
            $data['clients'] = [(array)$this->client_model->get_client($project->client_id)];

            $data['qgis_check'] = $this->qgisproject_model->check_qgs_file($project_id);

            //$this->qgisinfo($data);

            $project_full_path = $data['qgis_check']['name'];

            $service = self::check_service($name, $type, basename($project_full_path));
            $service_path = set_realpath($service['path']);
            $project_new_name = $service['file_name'];


            if (!unlink($service_path . $project_new_name)) {
                throw new Exception ("Stop service failed in " . $service_path);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">' . $e->getMessage() . '</div>');
        } finally {
            redirect('/projects/services/' . $project_id);
        }
    }

    /*
    * Returns array of client project templates when creating new project
    * Templates should be in client_name/_templates subfolder
    */
    public function get_templates($client_id = FALSE, $return_array = FALSE)
    {
        $groups = [];

        if (!empty($client_id)) {
            $client = $this->client_model->get_client($client_id);
            if(!empty($client)) {
                $dir = $this->get_templates_path($client->name);
                if($dir) {
                    $arr = get_dir_file_info($dir);
                    foreach($arr as $name => $fileinfo) {
                        $fn = $fileinfo["server_path"];
                        if(is_readable($fn)) {
                            $ext = pathinfo($fn, PATHINFO_EXTENSION);
                            if(strtolower($ext) == 'qgs') {
                                array_push($groups,$name);
                            }
                        }
                    }
                }
            }
        }

        if($return_array) {
            return $groups;
        } else {
            $this->output
                ->set_content_type('text/html')
                ->set_status_header(200)
                ->set_output(json_encode($groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }


    public function _unique_name($name) {

        //test if we already have name in database
        $exist = $this->project_model->project_exists($name);
        $id = $this->input->post('id');

        if ($exist && empty($id)) {
            $this->form_validation->set_message('_unique_name', $this->lang->line('gp_project').' '.$name.$this->lang->line('gp_exists').'!');
            return false;
        }

        return true;
    }

    private function get_templates_path($client_name) {
        $this->load->model('qgisproject_model');
        $main = $this->qgisproject_model->main_path;

        $dir = $main . $client_name . DIRECTORY_SEPARATOR . '_templates';

        //create templates folder if it doesn't exist
        if(!file_exists($dir)) {
            mkdir($dir,0777, true);
        }

        return is_dir($dir) ? $dir : FALSE;
    }

    private function copy_template($template, $project_name, $client_name)
    {
        $this->load->model('qgisproject_model');
        $main = $this->qgisproject_model->main_path;

        $dir = set_realpath($main . $client_name . DIRECTORY_SEPARATOR . '_templates');
        $dir2= set_realpath($this->qgisproject_model->get_default_qgs_project_path($client_name));

        $source = $dir . $template;
        $target = $dir2. $project_name;
        if(is_readable($source)) {
            copy($source,$target.'.qgs');
        }
    }

    private function get_project_wms_definition($project_id) {

		$properties = null;
		$ret = [];
		$qgs = $this->qgisproject_model;

		$check = $qgs->check_qgs_file($project_id);
		if ($check["valid"]) {
			$project_full_path = $check['name'];
			$qgs->qgs_file = $project_full_path;
			$qgs->read_qgs_file();
			$properties = $qgs->get_project_properties();
			$project_name = $qgs->name;

			$this->user_model->clear_gisapp_session();
			$this->session->set_userdata('map', $project_name);
			$this->session->set_userdata('project_path', $project_full_path);

			//default gisapp url, user must be logged in, cannot use outside of session
			$ret = [
				"url" => str_replace('gisportal/', '', base_url('/proxy/' . $project_name)),
				"params" => [
					"LAYERS" => implode(',', $properties->visible_layers)
				],
				"extent" => $properties->extent,
				"singleTile" => TRUE,
				"crs" => $properties->crs,
				"proj4" => $properties->proj4,
				"version" => $properties->version
			];
		}

		return $ret;
	}

    private function get_project_services($project_full_path)
    {
        $ret = [];

        $main_dir = $this->config->item('main_services_dir');
        if (!self::check_dir($main_dir)) {
            return $ret;
        }

        $file = basename($project_full_path);
        //TODO add client code to file from db
        //WMS private
        $wms_private = self::check_service('wms', 'private', $file);
        if ($wms_private) {
            $ret['wms'] = $wms_private;
        }
        //WMS public only if private is not published
        if (!$wms_private['published']) {
            $wms_public = self::check_service('wms', 'public', $file);
            if ($wms_public) {
                $ret['wms'] = $wms_public;
            }
        }

        //WFS private
        $wfs_private = self::check_service('wfs', 'private', $file);
        if ($wfs_private) {
            $ret['wfs'] = $wfs_private;
        }
        //WFS public only if private is not published
        if (!$wfs_private['published']) {
            $wfs_public = self::check_service('wfs', 'public', $file);
            if ($wfs_public) {
                $ret['wfs'] = $wfs_public;
            }
        }

        return $ret;
	}

	private function check_dir($path)
	{
		if (!is_dir($path) || !is_writable($path)) {
			$this->session->set_flashdata('alert', '<div class="alert alert-danger text-center">Directory does not exist or not writable: <strong>' . $path . '</strong></div>');
			return false;
		}
		return true;
	}

	private function check_service($name, $type, $fn)
	{
		$main_dir = set_realpath($this->config->item('main_services_dir'));

		$dir1 = set_realpath($main_dir . $type);
		$dir2 = set_realpath($dir1 . $name);

		if (!self::check_dir($dir1)) {
			return false;   //['name' => $name, 'type' => $type, 'published' => false];
		}

		if (!self::check_dir($dir2)) {
			return false;   //['name' => $name, 'type' => $type, 'published' => false];
		}

		$project = basename($fn, '.qgs');
		if ($type == 'private') {
			$project .= '__' . crc32($project);
			$fn = $project . '.qgs';
		}
		$url = $name . '-' . $type . '/' . $project;
		$cap = http_build_query([
			"SERVICE" => strtoupper($name),
			"VERSION" => $name == 'wms' ? '1.3.0' : '1.1.0',
			"REQUEST" => 'GetCapabilities'
		]);

		$icon = $type == 'public' ? 'fa fa-group' : 'glyphicon glyphicon-lock';

		if (file_exists($dir2 . $fn) && is_readable($dir2 . $fn)) {
			$info = get_file_info($dir2 . $fn);
			return ['name' => $name, 'file_name' => $fn, 'type' => $type, 'icon' => $icon, 'path' => $dir2, 'published' => true, 'date' => $info["date"], 'url' => $url, 'capabilities' => $url . '?' . $cap];
		} else {
			return ['name' => $name, 'file_name' => $fn, 'type' => $type, 'icon' => $icon, 'path' => $dir2, 'published' => false, 'url' => $url];
		}
	}

	private function imageExtract($path, $sec)
	{
		if (false === $exifData = @exif_read_data($path, $sec, false, false)) {
			try {
				if (false === $exifData = @exif_read_data($path, $sec, false, false)) {
					return array();
				}
			} catch (\Exception $e) {
				return array();
			}
		}

		return exif_read_data($path, $sec, false, false);
	}

	private function imageResize($dir, $fn)
	{

		try {
			if (!file_exists($dir . 'thumb')) {
				mkdir($dir . 'thumb', 0777, true);
			}

			$config = array();
			$config['image_library'] = 'gd2';
			$config['source_image'] = $dir . $fn;
			$config['new_image'] = $dir . 'thumb' . DIRECTORY_SEPARATOR;    //only have to specify new folder
			$config['maintain_ratio'] = TRUE;
			$config['master_dim'] = 'width';

			$config['width'] = 300;

			$this->image_lib->clear();
			$this->image_lib->initialize($config);

			if (!$this->image_lib->resize()) {
				return false;
			} else {
				$imgdata = self::imageExtract($config['source_image'], 'IFD0');

				$this->image_lib->clear();
				$config2 = array();

				$config2['image_library'] = 'gd2';
				$config2['source_image'] = $dir . 'thumb' . DIRECTORY_SEPARATOR . $fn;

				if (array_key_exists('Orientation', $imgdata)) {
					switch ($imgdata['Orientation']) {
						case 2:
							//mirror?
							break;
						case 3:
							$config2['rotation_angle'] = '180';
							break;
						case 4:
							$config2['rotation_angle'] = '180';
							//mirror?
							break;
						case 5:
							$config2['rotation_angle'] = '270';
							//mirror?
							break;
						case 6:
							$config2['rotation_angle'] = '270';
							break;
						case 7:
							$config2['rotation_angle'] = '90';
							//mirror?
							break;
						case 8:
							$config2['rotation_angle'] = '90';
							break;
					}
				}

				$this->image_lib->initialize($config2);
				if (!empty($config2['rotation_angle'])) {
					if (!$this->image_lib->rotate()) {
						return false;
					}
				}
			}
			return true;
		}
        catch (Exception $e){
            return false;
        }
    }

    private function extractProjectData(){
        $data = array(
            'id'                        => $this->input->post('id'),
            'name'                      => $this->input->post('name'),
            'overview_layer_id'         => set_null($this->input->post('overview_layer_id')),
            //'base_layers_ids'           => $this->input->post('base_layers_ids'),
            //'extra_layers_ids'          => $this->input->post('extra_layers_ids'),
            'client_id'                 => set_null($this->input->post('client_id')), //on update we are not changing client
            'public'                    => set_bool($this->input->post('public')),
            'display_name'              => $this->input->post('display_name'),
            'crs'                       => $this->input->post('crs'),
            'description'               => $this->input->post('description'),
            //'contact_id'                => set_null($this->input->post('contact_id')),
            'restrict_to_start_extent'  => set_bool($this->input->post('restrict_to_start_extent')),
            'geolocation'               => set_bool($this->input->post('geolocation')),
            'feedback'                  => set_bool($this->input->post('feedback')),
            'measurements'              => set_bool($this->input->post('measurements')),
            'feedback_email'            => $this->input->post('feedback_email'),
            'print'                     => set_bool($this->input->post('print')),
            'zoom_back_forward'         => set_bool($this->input->post('zoom_back_forward')),
            'identify_mode'             => set_bool($this->input->post('identify_mode')),
            'permalink'                 => set_bool($this->input->post('permalink')),
            //'ordr'                      => ($this->input->post('ordr')),
            'plugin_ids'                => $this->input->post('plugin_ids'),
            'project_group_id'          => $this->input->post('project_group_id')
            //'project_path'              => $this->input->post('project_path')
        );

        if ($this->input->post('plugin_ids') != null){
            $blids = implode(',', $this->input->post('plugin_ids'));
            if ($blids != ''){
                $data['plugin_ids'] = '{' . $blids . '}';
            }
        }

        return $data;
    }


    private function getImage($name) {
		$path = 'assets/img/projects/' . $name . '.png';
		$fn = set_realpath(FCPATH . $path, false);

		if (is_file($fn)) {
			return "<img title='" . $fn . "' class='img-responsive' src='" . base_url($path) . "'>";
		} else {
			return "<div class='alert alert-danger'><span class='glyphicon glyphicon-alert' aria-hidden='true'></span> Image missing (250x177px)</br>" . $fn . "</div>";
		}
	}

	private function getGisappClientLogo($name)
	{
		$path = 'gisapp/admin/resources/images/' . $name . '.png';
		$fn = set_realpath(dirname(FCPATH) . DIRECTORY_SEPARATOR . $path, false);
		$url_arr = parse_url(base_url());
		$base = $url_arr['scheme'] . '://' . $url_arr['host'];
		if (!empty($url_arr['port'])) {
			$base .= ':' . $url_arr['port'];
		}

		if (is_file($fn)) {
			return "<img height='32px' src='" . $base . '/' . $path . "?v=". $this->config->item('header_logo_version') . "'>";
		} else {
			return "<div class='alert alert-danger'><span class='glyphicon glyphicon-alert' aria-hidden='true'></span> Image missing, using default logo: _temp.png</br>" . $fn . "</div>";
		}
	}

//    private function qgisinfo(&$data){
//        if ($data['project']['name'] == '') {
//            $data['qgis_check'] =  ["valid" => false, "name" => ""];
//            return;
//        }
//
//        $project_name = $data['project']['name'];
//
//        $project_path = null;
//        if(isset($data['project']['project_path'])) {
//            $project_path = $data['project']['project_path'];
//        }
//
//        //$client_key = array_search($data['project']['client_id'], array_column($data['clients'], 'id'));
//        $client_name = $data['clients'][0]['name'];
//
//        $data['qgis_check'] = check_qgis_project($project_name, $project_path, $client_name);
//    }

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
            $result = anchor('project_groups/edit/'.$new_group['id'], $this->get_name($new_group)) . $sep . $result;
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
        $parent_id = $group['project_group_id'];

        $mode = 'edit';

        while (!empty($parent_id)) {
            $parent_id = $this->build_parent_link($parent_id, $sep, $group_full, $mode);
        }

        $client = $this->client_model->get_client($group['client_id']);
        $client_full = anchor('clients/edit/'.$client->id, $client->display_name);

        return $client_full . $sep . $group_full;
    }

    private function get_user_projects($is_admin, $client_id = FALSE)
    {
        $filter = $this->ion_auth->admin_scope()->filter;
        $user = $this->user_model->get_user_by_id($this->session->userdata('user_id'));
        $groups = [];
        $ret = [];

        if(!empty($filter)) {
            if($is_admin) {
                //we have client administrator
                $projects_1 = $this->project_model->get_projects($filter, $groups, TRUE);
                if($client_id) {
                    if ($filter === (integer)$client_id) {
                        return $projects_1;
                    } else {
                        $projects_1 = [];
                    }
                }
            } else {
                $projects_1 = [];
            }

            $groups = $this->user_model->get_project_group_ids($user->user_id, TRUE);
            $projects_2 = $this->project_model->get_projects($client_id, $groups,  FALSE);

            if(empty($projects_2)) {
                $ret = $projects_1;
            } else {
                $ret = array_unique(array_merge($projects_1, $projects_2), SORT_REGULAR);
            }
        } else {
            if(!$is_admin) {
                $groups = $this->user_model->get_project_group_ids($user->user_id, TRUE);
            }
            $ret = $this->project_model->get_projects($client_id, $groups,  $is_admin);
        }

        return $ret;
    }

    private function get_user_projects_for_group($is_admin, $client_id, $group_id)
    {
        $filter = $this->ion_auth->admin_scope()->filter;
        $user = $this->user_model->get_user_by_id($this->session->userdata('user_id'));
        $groups = '{'.$group_id.'}';
        $ret = [];

        if(!empty($filter)) {
            if ($filter !== (integer)$client_id) {
                $check = $this->user_model->has_project_group_role($user->user_id,$group_id);
                if(!$check) {
                    return $ret;
                }
            }
        } else {
            if(!$is_admin) {
                $check = $this->user_model->has_project_group_role($user->user_id,$group_id);
                if(!$check) {
                    return $ret;
                }
            }
        }

        $ret = $this->project_model->get_projects(FALSE, $groups, FALSE);

        return $ret;
    }

    private function build_user_navigation($client, $parent_id = NULL)
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

	// Returns a file size limit in bytes based on the PHP upload_max_filesize
	// and post_max_size
	private function file_upload_max_size()
	{
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$post_max_size = $this->parse_size(ini_get('post_max_size'));
			if ($post_max_size > 0) {
				$max_size = $post_max_size;
			}

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}
		return $max_size;
	}

	private function parse_size($size)
	{
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		} else {
			return round($size);
		}
	}

}

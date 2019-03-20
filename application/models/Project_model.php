<?php
class Project_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    /**
     * create blank project as array with default values to use it on form
     */
    public function new_project() {
        return array(
            'id'                        => null,
            'name'                      => '',
            'overview_layer_id'         => null,
            //'base_layers_ids'           => null,
            //'extra_layers_ids'          => null,
            'client_id'                 => null,
            'public'                    => false,
            'display_name'              => '',
            'crs'                       => '',
            'description'               => '',
            'contact'                   => '',
            'restrict_to_start_extent'  => false,
            'geolocation'               => true,
            'feedback'                  => true,
            'measurements'              => true,
            'feedback_email'            => '',
            'print'                     => true,
            'zoom_back_forward'         => true,
            'identify_mode'             => false,
            'permalink'                 => true,
            'ordr'                      => 0,
            'project_path'              => '',
            'plugin_ids'                => null,
            'project_group_id'          => null
        );
    }

    public function get_projects($client_id = FALSE, $groups = FALSE, $user_admin = FALSE)
    {
        if($groups === NULL && !$user_admin) {
            return null;
        }

        $this->db->order_by('client', 'ASC');
        $this->db->order_by('ordr', 'ASC');
        $this->db->order_by('display_name', 'ASC');
        if ($client_id === FALSE)
        {
			if (!$user_admin){
		        $this->db->where("group_id = ANY('".$groups."')");
			}
            $query = $this->db->get('projects_view');
            return $query->result_array();
        }

		$where = "client_id = ".$client_id;
        //$user_projects
		if (!$user_admin){
			$where = $where . " AND group_id = ANY('".$groups."')";
		}

		$this->db->where($where);
        $query = $this->db->get('projects_view');
        return $query->result_array();
    }

    public function get_project($id)
    {
        $this->db->where('id', $id);
        $query = $this->db->get('projects');
        return $query->result()[0];
    }

    function project_exists($name)
    {
        $this->db->where('name', $name);
        $query = $this->db->get('projects');
        $row = $query->row();

        return isset($row);
    }

    public function get_public_projects()
    {
        $this->db->order_by('client', 'ASC');
        $this->db->order_by('display_name', 'ASC');
        $this->db->where("public = TRUE");
        $query = $this->db->get('projects_view');

        return $query->result_array();
    }

    public function upsert_project($data)
    {
        $id = $data['id'];

        if ($id != null){
            $this->db->where('id',$id);
            $q = $this->db->get('projects');
            if ( $q->num_rows() > 0 )
            {
                $this->db->where('id',$id);
                $this->db->update('projects',$data);
                //todo remove
                //TODO move this to user model and call from project controller
                //$this->db->query('update users set project_ids = array_remove(project_ids,' . $id. ')');
                //if ($users != null){
                //    $this->db->query('update users set project_ids = array_append(project_ids, ' . $id. ') where user_id in (' . $users . ')');
                //}
                return $id;
            }
        }

        unset($data['id']);
        unset($data['template']);
        $this->db->insert('projects', $data);

        $id = $this->db->insert_id();
        //TODO move this to user model and call from project controller
        //$this->db->query('update users set project_ids = array_remove(project_ids,' . $id. ')');
        //if ($users != null){
        //    $this->db->query('update users set project_ids = array_append(project_ids, ' . $id. ') where user_id in (' . $users . ')');
        // }

        return $id;
    }

    public function delete_project($id)
    {
        $this->db->where('id', $id);
        //$this->db->query('update users set project_ids = array_remove(project_ids,' . $id. ')');
        $query = $this->db->delete('projects');
    }

    /*
     * get array of QGIS project templates for use when creating new project in database
     */
    function get_templates()
    {
        $ret = [];
        $dir = get_qgis_project_templates_path();
        if(is_dir($dir)) {
            $arr = get_dir_file_info($dir);

            foreach($arr as $name => $fileinfo) {
                $fn = $fileinfo["server_path"];
                if(is_readable($fn)) {
                    $ext = pathinfo($fn, PATHINFO_EXTENSION);
                    if(strtolower($ext) == 'qgs') {
                        array_push($ret,$name);
                    }
                }
            }
        }
        return $ret;
    }

    function copy_template($template, $project_name, $client_name)
    {
        $dir = set_realpath(get_qgis_project_templates_path());
        $dir2= set_realpath(get_qgis_project_path($client_name));

        $source = $dir . $template;
        $target = $dir2. $project_name;
        if(is_readable($source)) {
            copy($source,$target.'.qgs');
        }
    }
}
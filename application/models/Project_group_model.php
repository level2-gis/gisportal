<?php
class Project_group_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    /*
    * Get project_group by id
    */
    function get_project_group($id)
    {
        $this->db->where('id', $id);
        $query = $this->db->get('project_groups');
        return $query->result()[0];
    }

    function get_project_groups($client_id = FALSE, $list_only = FALSE, $skip_no_access = FALSE)
    {
        //$this->db->order_by('type', 'ASC');
        $this->db->order_by('name', 'ASC');

        if($list_only) {
            $this->db->select("id, CASE WHEN display_name IS NULL THEN name ELSE display_name || ' (' || name || ')' END AS name", FALSE);
        }

        if($client_id) {
            $this->db->where('client_id', $client_id);
        }

        if($skip_no_access) {
            $this->db->where('users >', 0);
        }

        $query = $this->db->get('project_groups_view');
        return $query->result_array();
    }

    function get_project_groups_with_layer($id) {

        if (empty($id)) {
            return [];
        }

        $sql = "SELECT * FROM ";
        $sql.= "(SELECT id,name, ";
        $sql.= "idx(base_layers_ids,".$id.") AS is_base, ";
        $sql.= "idx(extra_layers_ids,".$id.") AS is_extra ";
        //$sql.= "CASE when overview_layer_id=".$id." THEN true ELSE false END AS is_overview ";
        $sql.= "FROM public.project_groups) AS test ";
        $sql.= "WHERE is_base>0 or is_extra>0 ORDER BY name;";

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    function upsert_project_group($data) {
        $id = null;
        if(isset($data->id)) {
            $id = $data->id;
        }

        if ($id != null){
            $this->db->where('id',$id);
            $q = $this->db->get('project_groups');
            if ( $q->num_rows() > 0 )
            {
                $this->db->where('id',$id);
                $this->db->update('project_groups',$data);

                //todo updating project group also needs update client_id field on projects table
                $this->db->query('UPDATE projects SET client_id='.$data->client_id.' WHERE project_group_id='.$data->id.';');

                unset($data->id);

                return $id;
            }
        }

        $this->db->insert('project_groups', $data);

        return $this->db->insert_id();
    }

    function delete_project_group($id)
    {
        return $this->db->delete('project_groups',array('id'=>$id));
    }

    function project_group_exists($name)
    {
        $this->db->where('name', $name);
        $query = $this->db->get('project_groups');
        $row = $query->row();

        return isset($row);
    }
//
//    /*
//    * function to insert/update
//    */
//    function upsert_layer($data)
//    {
//        $id = $data['id'];
//
//        if ($id != null){
//            $this->db->where('id',$id);
//            $q = $this->db->get('layers');
//            if ( $q->num_rows() > 0 )
//            {
//                $this->db->where('id',$id);
//                $this->db->update('layers',$data);
//                return $id;
//            }
//        }
//
//        unset($data['id']);
//        $this->db->insert('layers', $data);
//
//        return $this->db->insert_id();
//    }
//
//    /*
//     * function to delete layer
//     */
//    function delete_layer($id)
//    {
//        return $this->db->delete('layers',array('id'=>$id));
//    }
//
//
//    public function get_layers_with_project_flag($ids) {
//
//        $sql = "SELECT id, name, display_name, display_name || ' ('||name||', '||type||')' AS full_name, type, ";
//        if (empty($ids)) {
//            $sql.="0 AS idx ";
//        } else {
//            $sql.= "idx('".$ids."',id) ";
//        }
//        $sql.= "FROM layers ORDER by idx,name;";
//        $query = $this->db->query($sql);
//
//        return $query->result_array();
//    }
}
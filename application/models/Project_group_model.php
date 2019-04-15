<?php
class Project_group_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    public function new_group() {
        return array(
            'id'                        => null,
            'name'                      => null,
            'display_name'              => null,
            'parent_id'                 => null,
            'type'                      => 0,
            'client_id'                 => null,
            'base_layers_ids'           => null,
            'extra_layers_ids'          => null,
        );
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

    /**
     * Return only project groups for display in single table
     *
     * @param bool $client_id
     * @param bool $list_only
     * @param bool $skip_no_access
     * @return mixed
     */

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

        $this->db->where('type', PROJECT_GROUP);
        $query = $this->db->get('project_groups_view');
        return $query->result_array();
    }

    function get_project_groups_with_layer($id) {

        if (empty($id)) {
            return [];
        }

        $sql = "SELECT * FROM ";
        $sql.= "(SELECT p.id AS project_group_id, CASE WHEN p.display_name IS NULL THEN p.name ELSE p.display_name || ' (' || p.name || ')' END AS name, c.display_name AS client, ";
        $sql.= "idx(base_layers_ids,".$id.") AS is_base, ";
        $sql.= "idx(extra_layers_ids,".$id.") AS is_extra ";
        //$sql.= "CASE when overview_layer_id=".$id." THEN true ELSE false END AS is_overview ";
        $sql.= "FROM project_groups p, clients c WHERE p.client_id=c.id) AS test ";
        $sql.= "WHERE is_base>0 or is_extra>0 ORDER BY name;";

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    function get_parents($client_id, $id) {
        if(!empty($id)) {
            $this->db->where('id <>', $id);
        }
        $this->db->where('type', SUB_GROUP);
        $this->db->where('client_id', $client_id);

        $this->db->select("id, CASE WHEN display_name IS NULL THEN name ELSE display_name || ' (' || name || ')' END AS name", FALSE);

        $query = $this->db->get('project_groups');
        return $query->result_array();
    }

    /**
     * Get all child groups
     * @param $id
     * @param $client_id
     * @return mixed
     */
    function get_child_groups($client_id, $id) {
        if(empty($id)) {
            $this->db->where('parent_id IS NULL');
        } else {
            $this->db->where('parent_id', $id);
        }

        if(!empty($client_id)) {
            $this->db->where('client_id', $client_id);
        }

        $this->db->select('id, name, display_name, parent_id, type', FALSE);
        $query = $this->db->get('project_groups');
        return $query->result_array();
    }

    function upsert_project_group($data) {
        $id = null;
        if(isset($data['id'])) {
            $id = $data['id'];
        }

        if ($id != null){
            $this->db->where('id',$id);
            $q = $this->db->get('project_groups');
            if ( $q->num_rows() > 0 )
            {
                $this->db->where('id',$id);
                $this->db->update('project_groups',$data);

                //todo updating project group also needs update client_id field on projects table
                $this->db->query('UPDATE projects SET client_id='.$data['client_id'].' WHERE project_group_id='.$data['id'].';');

                return $id;
            }
        }

        unset($data['id']);
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

    function add_layer($groups, $layer_id, $destination) {
        if($destination != BASE_LAYER && $destination != EXTRA_LAYER) {
            return false;
        }
        $dst_field = $destination == BASE_LAYER ? 'base_layers_ids' : 'extra_layers_ids';

        $sql = 'UPDATE project_groups SET '.$dst_field.' = uniq(array_prepend('.$layer_id.','.$dst_field.')) WHERE id IN('.$groups.');';

        return $this->db->query($sql);
    }

    function remove_layer($group, $layer_id) {

        $sql = 'UPDATE project_groups SET base_layers_ids = array_remove(base_layers_ids,'.$layer_id.'), extra_layers_ids = array_remove(extra_layers_ids,'.$layer_id.') ';
        $sql.= 'WHERE id = '.$group.';';

        return $this->db->query($sql);
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
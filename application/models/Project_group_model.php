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

    function get_project_groups($client_id = NULL, $list_only = FALSE, $skip_no_access = FALSE)
    {
        //$this->db->order_by('type', 'ASC');
        $this->db->order_by('client_name', 'ASC');
        $this->db->order_by('name', 'ASC');

        if($list_only) {
            $this->db->select("id, CASE WHEN display_name IS NULL THEN name ELSE display_name || ' (' || name || ')' END AS name", FALSE);
        }

        if(!empty($client_id)) {
            $this->db->where('client_id', $client_id);
        }

        if($skip_no_access) {
            $this->db->where('users >', 0);
        }

        $this->db->where('type', PROJECT_GROUP);
        $query = $this->db->get('project_groups_view');
        return $query->result_array();
    }

    function get_project_groups_with_layer($id, $filter = NULL) {

        if (empty($id)) {
            return [];
        }

        $sql = "SELECT * FROM ";
        $sql.= "(SELECT p.id AS project_group_id, CASE WHEN p.display_name IS NULL THEN p.name ELSE p.display_name || ' (' || p.name || ')' END AS name, c.id AS client_id, c.display_name AS client, ";
        $sql.= "idx(base_layers_ids,".$id.") AS is_base, ";
        $sql.= "idx(extra_layers_ids,".$id.") AS is_extra ";
        //$sql.= "CASE when overview_layer_id=".$id." THEN true ELSE false END AS is_overview ";
        $sql.= "FROM project_groups p, clients c WHERE p.client_id=c.id) AS test ";
        $sql.= "WHERE (is_base>0 OR is_extra>0)";
        if(!empty($filter)) {
            $sql.= " AND client_id = ".$filter;
        }
        $sql.= " ORDER BY name;";

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    function get_parents($client_id, $id) {

        $this->db->order_by('name', 'ASC');

        if(!empty($id)) {
            $this->db->where('id <>', $id);
            $this->db->where('(parent_id <> '.$id . ' OR parent_id IS NULL)');
            $this->db->where('(idx((SELECT get_child_menus('.$id.')),"id") = 0 OR idx((SELECT get_child_menus('.$id.')),"id") IS NULL)');
        }
        $this->db->where('type', SUB_GROUP);
        $this->db->where('client_id', $client_id);

        $this->db->select("id, CASE WHEN display_name IS NULL THEN name ELSE display_name || ' (' || name || ')' END AS name", FALSE);

        $query = $this->db->get('project_groups');
        return $query->result_array();
    }

    /**
     * Get all child groups
     * @param $client_id
     * @param $id
     * @param $user_groups
     * @return mixed
     */
    function get_child_groups($client_id, $id, $user_groups = NULL) {

        $this->db->order_by('name', 'ASC');

        if(empty($id)) {
            $this->db->where('parent_id IS NULL');
        } else {
            $this->db->where('parent_id', $id);
        }

        if(!empty($client_id)) {
            $this->db->where('client_id', $client_id);
        }

        if(!empty($user_groups)) {
            $this->db->where("(id = ANY('".$user_groups."') OR children && ('".$user_groups."'))");
        }

        $this->db->select('* FROM (SELECT id, name, client_id, display_name, parent_id, type, CASE WHEN type=1 THEN get_child_groups(id) ELSE null END AS children FROM project_groups) p', FALSE);
        $query = $this->db->get();
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

    function remove_layer($group, $layer_id, $destination) {
        if($destination != BASE_LAYER && $destination != EXTRA_LAYER) {
            return false;
        }
        $dst_field = $destination == BASE_LAYER ? 'base_layers_ids' : 'extra_layers_ids';

        $sql = 'UPDATE project_groups SET '.$dst_field.' = array_remove('.$dst_field.','.$layer_id.') ';
        $sql.= 'WHERE id = '.$group.';';

        return $this->db->query($sql);
    }
}
<?php
class Layer_model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    /*
    * Get layer by id
    */
    function get_layer($id)
    {
        return $this->db->get_where('layers',array('id'=>$id))->row_array();
    }

    function get_layers()
    {
        $this->db->order_by('type', 'ASC');
        $this->db->order_by('display_name', 'ASC');

        $query = $this->db->get('layers');
        return $query->result_array();
    }

    /*
     * function to add new layer
     */
    function add_layer($params)
    {
        $this->db->insert('layers',$params);
        return $this->db->insert_id();
    }

    /*
     * function to update layer
     */
    function update_layer($id,$params)
    {
        $this->db->where('id',$id);
        return $this->db->update('layers',$params);
    }

    /*
     * function to delete layer
     */
    function delete_layer($id)
    {
        return $this->db->delete('layers',array('id'=>$id));
    }


    public function get_layers_with_project_flag($ids) {

        $sql = "SELECT id, name, display_name, display_name || ' ('||name||', '||type||')' AS full_name, type, ";
        if (empty($ids)) {
            $sql.="0 AS idx ";
        } else {
            $sql.= "idx('".$ids."',id) ";
        }
        $sql.= "FROM layers ORDER by idx,name;";
        $query = $this->db->query($sql);

        return $query->result_array();
    }
}
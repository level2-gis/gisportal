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
        $this->db->where('id', $id);
        $query = $this->db->get('layers');
        return $query->result()[0];
    }

    function get_layers()
    {
        $this->db->order_by('type', 'ASC');
        $this->db->order_by('display_name', 'ASC');

        $query = $this->db->get('layers');
        return $query->result_array();
    }

    function layer_exists($name)
    {
        $this->db->where('name', $name);
        $query = $this->db->get('layers');
        $row = $query->row();

        return isset($row);
    }

    /*
    * function to insert/update
    */
    function upsert_layer($data)
    {
        $id = $data['id'];

        if ($id != null){
            $this->db->where('id',$id);
            $q = $this->db->get('layers');
            if ( $q->num_rows() > 0 )
            {
                $this->db->where('id',$id);
                $this->db->update('layers',$data);
                return $id;
            }
        }

        unset($data['id']);
        $this->db->insert('layers', $data);

        return $this->db->insert_id();
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
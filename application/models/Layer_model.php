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
		$result = $query->result();
		return $result ? $result[0] : null;
	}

	function get_layer_by_name($name)
	{
		$this->db->where('name', $name);
		$query = $this->db->get('layers');

		return $query->result();
	}

	function get_layers($client_id = NULL, $list_only = FALSE)
	{
		//$this->db->order_by('type', 'ASC');
		$this->db->order_by('name', 'ASC');

		if ($list_only) {
			$this->db->select("id, name, display_name, display_name || ' ('||name||', '||type||')' AS full_name, type", FALSE);
		}

        if(!empty($client_id)) {
            $this->db->where('client_id', null);
            $this->db->or_where('client_id', $client_id);
        }

        $query = $this->db->get('layers_view');
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


    public function get_layers_filtered($ids, $base = 0)
	{

		if (empty($ids)) {
			return [];
		}

		$this->db->order_by('idx', 'ASC');
		$this->db->select("id,name,display_name,type,definition, " . $base . "::boolean AS base FROM (SELECT *,idx('" . $ids . "',id) FROM layers) l", FALSE);
		$this->db->where('idx>0');

		$query = $this->db->get();
		return $query->result_array();
	}

    function search($text, $filter) {

		$like_text = '%' . $this->db->escape_like_str($text) . '%';

        //for ilike search we have to use direct sql
        $where = "(name ILIKE " . $this->db->escape($like_text) . " ESCAPE '!' OR ";
        $where.= "display_name ILIKE " . $this->db->escape($like_text) . " ESCAPE '!' OR ";
        $where.= "type ILIKE " . $this->db->escape($like_text) . " ESCAPE '!')";

        $this->db->select("id, trim(coalesce(display_name,'') || ' (' || coalesce(name,'')) || ', ' || type || ')' AS name", FALSE);
		$this->db->where($where, null, false);

        if(!empty($filter)) {
            $this->db->where('(client_id = '.$filter.' OR client_id IS NULL)', null, FALSE);
        }

        //$this->db->order_by('name', 'DESC');

        $query = $this->db->get('(SELECT * FROM layers_view ORDER BY name) l');

        return $query->result_array();
    }


//    public function get_layers_with_project_flag($ids = NULL) {
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

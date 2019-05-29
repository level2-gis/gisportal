<?php
class Client_model extends CI_Model {

    function __construct()
    {
        $this->load->database();
    }

    /*
     * Get client by id
     */
    function get_client($id, $list_only = FALSE)
    {
        if($list_only) {
            $this->db->select("id, display_name || ' (' || name || ')' AS name", FALSE);
        }

        $this->db->where('id', $id);
        $query = $this->db->get('clients_view');
        $result = $query->result();
        return $result ? $result[0] : null;
    }

    function get_client_by_name($name)
    {
        $this->db->where('name', $name);
        $query = $this->db->get('clients');

        return $query->result();
    }

    function client_exists($name)
    {
        $this->db->where('name', $name);
        $query = $this->db->get('clients');
        $row = $query->row();

        return isset($row);
    }

    /*
    * Get clients with projects count that user is allowed to get or get all in case of admin
    */
    function get_clients($groups = FALSE, $user_admin = TRUE, $blanks = TRUE, $list_only = FALSE)
    {
        if($groups === NULL && !$user_admin) {
            return null;
        }

        $this->db->order_by('ordr', 'ASC');
        $this->db->order_by('display_name', 'ASC');

        if($list_only) {
            $this->db->select("id, display_name || ' (' || name || ')' AS name", FALSE);
        }

        //$user_groups
		if (!$user_admin){
			$this->db->where("project_group_ids && '".$groups."'");
		}

        if (!$blanks) {
            $this->db->where("count>0");
        }

        $query = $this->db->get('clients_view');
        return $query->result_array();
    }

    /*
    * function to insert/update client
    */
    function upsert_client($data)
    {
        $id = $data['id'];
//        if ($data['ordr'] == '') {
//            $data['ordr'] = 0;
//        }

        if ($id != null){
            $this->db->where('id',$id);
            $q = $this->db->get('clients');
            if ( $q->num_rows() > 0 )
            {
                $this->db->where('id',$id);
                $this->db->update('clients',$data);
                return $id;
            }
        }

        unset($data['id']);
        $this->db->insert('clients', $data);

        return $this->db->insert_id();
    }

    /*
    * function to delete client
    */
    function delete_client($id)
    {
        return $this->db->delete('clients',array('id'=>$id));
    }
}
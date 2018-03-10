<?php

class Layers extends CI_Controller{
    function __construct()
    {
        parent::__construct();
        $this->load->model('layer_model');
        $this->load->helper(array('url'));
    } 

    /*
     * Listing of layers
     */
    function index()
    {
        if (!$this->session->userdata('admin')){
            redirect('/');
        }

        $data['title'] = $this->lang->line('gp_layers_title');
        $data['layers'] = $this->layer_model->get_layers();

        $this->load->view('templates/header', $data);
        $this->load->view('layers_admin', $data);
        $this->load->view('templates/footer', $data);
    }

    /*
     * Adding a new layer
     */
    function add()
    {   
        $this->load->library('form_validation');

		$this->form_validation->set_rules('type','Type','required');
		$this->form_validation->set_rules('name','Name','required|max_length[100]|is_unique[true]');
		$this->form_validation->set_rules('definition','Definition','required');
		
		if($this->form_validation->run())     
        {   
            $params = array(
				'type' => $this->input->post('type'),
				'name' => $this->input->post('name'),
				'display_name' => $this->input->post('display_name'),
				'definition' => $this->input->post('definition'),
            );
            
            $layer_id = $this->Layer_model->add_layer($params);
            redirect('layer/index');
        }
        else
        {            
            $data['_view'] = 'layer/add';
            $this->load->view('layouts/main',$data);
        }
    }  

    /*
     * Editing a layer
     */
    function edit($id)
    {   
        // check if the layer exists before trying to edit it
        $data['layer'] = $this->Layer_model->get_layer($id);
        
        if(isset($data['layer']['id']))
        {
            $this->load->library('form_validation');

			$this->form_validation->set_rules('type','Type','required');
			$this->form_validation->set_rules('name','Name','required|max_length[100]|is_unique[true]');
			$this->form_validation->set_rules('definition','Definition','required');
		
			if($this->form_validation->run())     
            {   
                $params = array(
					'type' => $this->input->post('type'),
					'name' => $this->input->post('name'),
					'display_name' => $this->input->post('display_name'),
					'definition' => $this->input->post('definition'),
                );

                $this->Layer_model->update_layer($id,$params);            
                redirect('layer/index');
            }
            else
            {
                $data['_view'] = 'layer/edit';
                $this->load->view('layouts/main',$data);
            }
        }
        else
            show_error('The layer you are trying to edit does not exist.');
    } 

    /*
     * Deleting layer
     */
    function remove($id)
    {
        $layer = $this->Layer_model->get_layer($id);

        // check if the layer exists before trying to delete it
        if(isset($layer['id']))
        {
            $this->Layer_model->delete_layer($id);
            redirect('layer/index');
        }
        else
            show_error('The layer you are trying to delete does not exist.');
    }
    
}

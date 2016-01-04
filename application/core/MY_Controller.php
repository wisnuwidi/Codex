<?php
/**
 * Filename: MY_Controller.php
 * Created at: 4:38:27 PM
 */


class MY_Controller extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        $this->load->database();
        $this->load->helper('url');
    }
    
    public function crud() {
        $db_driver = $this->db->platform();
        $model_name = 'grocery_crud_model_' . $db_driver;
        $model_alias = 'm' . substr(md5(rand()), 0, rand(4, 15));

        unset($this->{$model_name});
        $this->load->library('grocery_CRUD');
        $crud = new Grocery_CRUD();
        if (file_exists(APPPATH . '/models/' . $model_name . '.php')) {
            $this->load->model('grocery_crud_model');
            $this->load->model('grocery_crud_generic_model');
            $this->load->model($model_name, $model_alias);
            $crud->basic_model = $this->{$model_alias};
        }
        return $crud;
    }
    
    public function render($file, $output = null) {
        return $this->load->view($file, $output);
    }
}

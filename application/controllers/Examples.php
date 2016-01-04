<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Examples extends MY_Controller {
    protected $prefix = 'concept_';
    protected $file = 'example.php';

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('url');
    }

    public function output($output = null) {
        $this->render($this->file, $output);
    }

    public function offices() {
        $crud = $this->crud();
        $crud->set_table('concept_offices');
        $output = $crud->render();

        $this->output($output);
    }

    public function index() {
        $this->output((object) array(
            'output' => '',
            'js_files' => array(),
            'css_files' => array()
        ));
    }

    public function offices_management() {
        try {
            $crud = $this->crud();

            $crud->set_theme('datatables');
            $crud->set_table('concept_offices');
            $crud->set_subject('Office');
            $crud->required_fields('city');
            $crud->columns('city', 'country', 'phone', 'addressline1', 'postalcode');

            $output = $crud->render();

            $this->output($output);
        } catch (Exception $e) {
            show_error($e->getMessage() . ' --- ' . $e->getTraceAsString());
        }
    }

    public function employees_management() {
        $crud = $this->crud();

        $crud->set_theme('datatables');
        $crud->set_table('concept_employees');
        $crud->set_relation('officecode', 'concept_offices', 'city');
        $crud->display_as('officecode', 'Office City');
        $crud->set_subject('Employee');

        $crud->required_fields('lastname', 'firstname', 'extension', 'email', 'officecode', 'file_url', 'jobtitle');
        $crud->set_field_upload('file_url', 'assets/uploads/files');

        $output = $crud->render();
        $this->output($output);
    }

    public function customers_management() {
        $crud = $this->crud();
        $crud->set_table('concept_customers');
        $crud->columns('customername', 'contactlastname', 'phone', 'city', 'country', 'salesrepemployeenumber', 'creditlimit');
        $crud->display_as('salesrepemployeenumber', 'from Employeer')
            ->display_as('customername', 'name')
            ->display_as('contactlastname', 'Last Name');
        $crud->set_subject('Customer');
        $crud->set_relation('salesrepemployeenumber', 'concept_employees', 'lastname');

        $output = $crud->render();
        $this->output($output);
    }

    public function orders_management() {
        $crud = $this->crud();

        $crud->set_table('concept_orders');
        $crud->set_subject('Order');
        //$crud->set_relation('customernumber', 'concept_customers', '{contactlastname} {contactfirstname}');
        $crud->set_relation('customernumber', 'concept_customers', '{contactlastname} {contactfirstname}');
        $crud->display_as('customernumber', 'Customer');
        $crud->unset_add();
        $crud->unset_delete();

        $output = $crud->render();
        $this->output($output);
    }

    public function products_management() {
        $crud = $this->crud();

        $crud->set_table('concept_products');
        $crud->set_subject('Product');
        $crud->unset_columns('productdescription');
        $crud->callback_column('buyprice', array($this, 'valueToEuro'));

        $output = $crud->render();
        $this->output($output);
    }

    public function valueToEuro($value, $row) {
        return $value . ' &euro;';
    }

    public function film_management() {
        $crud = $this->crud();

        $crud->set_table('concept_film');
        $crud->set_relation_n_n('actors', 'concept_film_actor', 'concept_actor', 'film_id', 'actor_id', 'fullname', 'priority');
        $crud->set_relation_n_n('category', 'concept_film_category', 'concept_category', 'film_id', 'category_id', 'name');
        $crud->unset_columns('special_features', 'description', 'actors');
        $crud->fields('title', 'description', 'actors', 'category', 'release_year', 'rental_duration', 'rental_rate', 'length', 'replacement_cost', 'rating', 'special_features');

        $output = $crud->render();
        $this->output($output);
    }

    public function film_management_twitter_bootstrap() {
        try {
            $crud = $this->crud();

            $crud->set_theme('twitter-bootstrap');
            $crud->set_table('concept_film');
            $crud->set_relation_n_n('actors', 'concept_film_actor', 'actor', 'film_id', 'actor_id', 'fullname', 'priority');
            $crud->set_relation_n_n('category', 'concept_film_category', 'category', 'film_id', 'category_id', 'name');
            $crud->unset_columns('special_features', 'description', 'actors');

            $crud->fields('title', 'description', 'actors', 'category', 'release_year', 'rental_duration', 'rental_rate', 'length', 'replacement_cost', 'rating', 'special_features');

            $output = $crud->render();
            $this->output($output);
        } catch (Exception $e) {
            show_error($e->getMessage() . ' --- ' . $e->getTraceAsString());
        }
    }

    function multigrids() {
        $this->config->load('grocery_crud');
        $this->config->set_item('grocery_crud_dialog_forms', true);
        $this->config->set_item('grocery_crud_default_per_page', 10);

        $output1 = $this->offices_management2();
        $output2 = $this->employees_management2();
        $output3 = $this->customers_management2();

        $js_files = $output1->js_files + $output2->js_files + $output3->js_files;
        $css_files = $output1->css_files + $output2->css_files + $output3->css_files;
        $output = "<h1>List 1</h1>" . $output1->output . "<h1>List 2</h1>" . $output2->output . "<h1>List 3</h1>" . $output3->output;

        $this->output((object) array(
            'js_files' => $js_files,
            'css_files' => $css_files,
            'output' => $output
        ));
    }

    public function offices_management2() {
        $crud = $this->crud();
        $crud->set_table('concept_offices');
        $crud->set_subject('Office');
        $crud->required_fields('city');
        $crud->columns('city', 'country', 'phone', 'addressline1', 'postalcode');
        $crud->set_crud_url_path(site_url(strtolower(__CLASS__ . "/" . __FUNCTION__)), site_url(strtolower(__CLASS__ . "/multigrids")));

        $output = $crud->render();

        if ($crud->getState() != 'list') {
            $this->output($output);
        } else {
            return $output;
        }
    }

    public function employees_management2() {
        $crud = $this->crud();

        $crud->set_theme('datatables');
        $crud->set_table('concept_employees');
        $crud->set_relation('officecode', 'concept_offices', 'city');
        $crud->display_as('officecode', 'Office City');
        $crud->set_subject('Employee');

        $crud->required_fields('lastName');
        $crud->set_field_upload('file_url', 'assets/uploads/files');
        $crud->set_crud_url_path(site_url(strtolower(__CLASS__ . "/" . __FUNCTION__)), site_url(strtolower(__CLASS__ . "/multigrids")));

        $output = $crud->render();

        if ($crud->getState() != 'list') {
            $this->output($output);
        } else {
            return $output;
        }
    }

    public function customers_management2() {
        $crud = $this->crud();

        $crud->set_table('concept_customers');
        $crud->columns('customername', 'contactlastname', 'phone', 'city', 'country', 'salesrepemployeenumber', 'creditlimit');
        $crud->display_as('salesrepemployeenumber', 'from Employeer')
            ->display_as('customername', 'Name')
            ->display_as('contactlastname', 'Last Name');
        $crud->set_subject('Customer');
        $crud->set_relation('salesrepemployeenumber', 'concept_employees', 'lastname');
        $crud->set_crud_url_path(site_url(strtolower(__CLASS__ . "/" . __FUNCTION__)), site_url(strtolower(__CLASS__ . "/multigrids")));

        $output = $crud->render();

        if ($crud->getState() != 'list') {
            $this->output($output);
        } else {
            return $output;
        }
    }
}

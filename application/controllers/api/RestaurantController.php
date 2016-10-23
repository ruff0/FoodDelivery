<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    // This can be removed if you use __autoload() in config.php OR use Modular Extensions
    require 'MyRestController.php';

    /**
    * This is an example of a few basic user interaction methods you could use
    * all done with a hardcoded array
    *
    * @package         CodeIgniter
    * @subpackage      Rest Server
    * @category        Controller
    * @author          Phil Sturgeon, Chris Kacerguis
    * @license         MIT
    * @link            https://github.com/chriskacerguis/codeigniter-restserver
    */
    class RestaurantController extends MyRestController {
        function __construct()
        {
            // Construct the parent class
            parent::__construct();                  

            $this->load->model('RestaurantModel');
            $this->load->model('UserProfileModel'); 
        } 

        private function validate() {
            $this->messages = array();
            $valid = true;

            if(!$this->form_validation->required($this->post("mobile_no"))) {
                $this->messages[] = $this->lang->line("mobile_no_required");  
                $valid = false;
            }

            if(!$this->form_validation->required($this->post("f_name"))) {
                $this->messages[] = $this->lang->line("first_name_required");
                $valid = false;
            }

            if(!$this->form_validation->required($this->post("l_name"))) {
                $this->messages[] = $this->lang->line("last_name_required");  
                $valid = false;
            }

            if(!$this->form_validation->required($this->post("password"))) {
                $this->messages[] = $this->lang->line("password_required");  
                $valid = false;
            }

            if($this->post("email") && !$this->form_validation->valid_email($this->post("email"))) {
                $this->messages[] = $this->lang->line("email_invalid");  
                $valid = false;
            }

            return $valid;
        }

        public function index_get($id=null)
        {                 
            try {                
                $this->validateAccessToken();


                if ($id === NULL)
                {               
                    $params = array();
                    if($this->get('area')) $params["area"] = $this->get('area');                                    // Single Id
                    if($this->get('cuisines')) $params["cuisines"] = $this->get('cuisines');                           // Multiple Ids
                    if($this->get('food_types')) $params["food_types"] = $this->get('food_types');                     // Multiple Ids
                    if($this->get('restro_categories')) $params["restro_categories"] = $this->get('restro_categories');   // Multiple Ids                  
                    if($this->get('service_type')) $params["service_type"] = $this->get('service_type');   // Service Type
                    
                    $resource = $this->RestaurantModel->find($params); 
                } else {                         
                    $resource = $this->RestaurantModel->findById($id); 
                }

                if(!$resource) {
                    throw new Exception($this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND); 
                }  
                $this->response(array(
                    "code"=>RESULT_SUCCESS,    
                    "resource"=>$resource
                    ), REST_Controller::HTTP_OK);

            } catch (Exception $e) {
                $this->response(array(
                    "code"=>$e->getCode(),
                    "message"=>$e->getMessage()
                    ), REST_Controller::HTTP_OK);
            }
        }  
}
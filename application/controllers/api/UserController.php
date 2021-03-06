<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    // This can be removed if you use __autoload() in config.php OR use Modular Extensions
    require 'MyRestController.php';
    require APPPATH . '/libraries/CryptoLib.php';

    require APPPATH . '/libraries/Twilio/autoload.php';

    // Use the REST API Client to make requests to the Twilio REST API
    use Twilio\Rest\Client;

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
    class UserController extends MyRestController {
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key  

        $this->load->model('UserSmsModel');
        $this->load->model('RestroCustomerAddressModel');
        $this->load->model('UserAddressModel');
        $this->load->model('TwilioStatusModel');

        $this->load->config('twilio');
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
                $users = $this->UserModel->find(null);                     

                $resource = array();
                foreach($users as $user) {                        
                    $user->profile = $this->UserProfileModel->findByUserId($user->id);
                    $resource[] = $user;//$this->UserModel->getPublicFields($user);    
                }
            } else {                         
                $user = $this->UserModel->findById($id);                    
                if($user) {
                    $user->profile = $this->UserProfileModel->findByUserId($user->id);
                    $resource = $user;//$this->UserModel->getPublicFields($user);
                }
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


    public function index_post()
    {
        try { 
            if(!$this->validate()) {
                throw new Exception(implode(",", $this->messages), RESULT_ERROR_PARAMS_INVALID);
            }

            // Check duplicate
            if($this->post('email')) {
                $users = $this->UserModel->find(array("email"=>$this->post('email')));
                if(count($users)) {
                    throw new Exception($this->lang->line('email_duplicated'), RESULT_ERROR_PARAMS_INVALID);
                }
            }  

            $users = $this->UserModel->find(array("mobile_no"=>$this->post('mobile_no')));
            if(count($users)) {
                throw new Exception($this->lang->line('mobile_no_duplicated'), RESULT_ERROR_PARAMS_INVALID);
            }


            $data = array();

            $hasher = new PasswordHash(
                $this->config->item('phpass_hash_strength', 'tank_auth'),
                $this->config->item('phpass_hash_portable', 'tank_auth'));

            $data["mobile_no"] = $this->post('mobile_no');
            $data["password"] = $hasher->HashPassword($this->post('password'));
            if($this->post('email')) {                                        
                $data["email"] = $this->post('email');
            }
            $data["user_role"] = 3; //Register as customer role
            $id = $this->UserModel->create($data);

            $this->UserProfileModel->save($id, array(
                "user_id"=>$id,
                "f_name"=>$this->post("f_name"),
                "l_name"=>$this->post("l_name")
            ));

            $user = $this->UserModel->findById($id);
            $user->profile = $this->UserProfileModel->findByUserId($id);

            
            $token_data["user_id"] = $user->id;   
            $token = CryptoLib::randomString(50);
            $token_data["access_token"] = $token;          
        
            if(isset($_SERVER['HTTP_CLIENT_IP'])) $token_data['ip_address1'] = $_SERVER['HTTP_CLIENT_IP'];
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $token_data['ip_address2'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if(isset($_SERVER['HTTP_X_FORWARDED'])) $token_data['ip_address3'] = $_SERVER['HTTP_X_FORWARDED'];
            if(isset($_SERVER['HTTP_FORWARDED_FOR'])) $token_data['ip_address4'] = $_SERVER['HTTP_FORWARDED_FOR'];
            if(isset($_SERVER['HTTP_FORWARDED'])) $token_data['ip_address5'] = $_SERVER['HTTP_FORWARDED'];
            if(isset($_SERVER['REMOTE_ADDR'])) $token_data['ip_address6'] = $_SERVER['REMOTE_ADDR'];
            
            $accessTokenId = $this->UserAccessTokenModel->create($token_data);
            $accessToken = $this->UserAccessTokenModel->findById($accessTokenId);
            $accessToken->user = $user;
            
            
            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$accessToken   
                ), REST_Controller::HTTP_CREATED); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        }                 
    }


    public function index_put($id=null)
    {
        try{
            $this->validateAccessToken();
            if($id == null) {
                throw new Exception($this->lang->line('id_required'), RESULT_ERROR_ID_REQUIRED);
            }

            $data = array();

            if($this->put("mobile_no")) $data["mobile_no"] = $this->put("mobile_no");
            if($this->put("f_name")) $data["f_name"] = $this->put("f_name");
            if($this->put("l_name")) $data["l_name"] = $this->put("l_name");
            if($this->put("email")) $data["email"] = $this->put("email");

            // Check duplicate
            if(isset($data["email"])) {
                $users = $this->UserModel->find(array("email"=>$data["email"]));
                if(count($users) && $users[0]->id!=$id) {
                    throw new Exception($this->lang->line('email_duplicated'), RESULT_ERROR_PARAMS_INVALID);
                }
            }  

            if(isset($data["mobile_no"])) {
                $users = $this->UserModel->find(array("mobile_no"=>$data["mobile_no"]));
                if(count($users) && $users[0]->id!=$id) {                        
                    throw new Exception($this->lang->line('mobile_no_duplicated'), RESULT_ERROR_PARAMS_INVALID);
                }
            }

            if(!empty($data)) { 
                $this->UserModel->update($id, $data);
            }

            $user = $this->UserModel->findById($id);
            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$this->UserModel->getPublicFields($user)
                ), REST_Controller::HTTP_ACCEPTED); 
        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        }

    }

    public function index_delete($id=null)
    {
        try{
            $this->validateAccessToken();

            if($id == null) {
                throw new Exception($this->lang->line('id_required'), RESULT_ERROR_ID_REQUIRED);
            }

            $user = $this->UserModel->findById($id);
            if(!$user) {
                throw new Exception($this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND);
            }
            $this->UserModel->delete($id);

            $this->response(array("code"=>RESULT_SUCCESS), REST_Controller::HTTP_ACCEPTED);
        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }

    public function sendSmsCode_post() {
        try { 
            $this->validateAccessToken();
            $mobile_no = $this->post('mobile_no');
            if(!isset($mobile_no)) {
                throw new Exception($this->lang->line('parameter_incorrect'), RESULT_ERROR_PARAMS_INVALID);
            }

            $code = generateRandomCode(6); 
            $client = new Client($this->config->item('twilio_account_sid'), $this->config->item('twilio_auth_token')); 
            $client->messages->create(
                $mobile_no,
                array(
                    'from' => $this->config->item('twilio_phone_number'),
                    'body' => "Mataam register verification code! ".$code,
                    'statusCallback' => 'http://82.223.68.80/api/twilio_sms_status_callback'
                )
            );

            $data["user_id"] = $this->user->id;
            $data["mobile_no"] = $mobile_no;
            $data["code"] = $code;

            $id = $this->UserSmsModel->create($data);

            $this->response(array("code"=>RESULT_SUCCESS), REST_Controller::HTTP_CREATED); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }

    public function verifySmsCode_post() {
        try { 
            $this->validateAccessToken();
            
            $mobile_no = $this->post('mobile_no');
            $code = $this->post('code');
            if(!isset($mobile_no) || !isset($code)) {
                throw new Exception($this->lang->line('parameter_incorrect'), RESULT_ERROR_PARAMS_INVALID);
            }

            $sms = $this->UserSmsModel->findOne(array(
                'user_id'=>$this->user->id,
                'mobile_no'=>$mobile_no,
                'code'=>$code
            ));        

            if(!$sms) {
                throw new Exception($this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND);
            }

            if(strtotime($sms->expires_at) < time()) {
                throw new Exception($this->lang->line('code_expired'), RESULT_ERROR_PARAMS_INVALID);
            }

            $this->UserModel->update($this->user->id, array('mobile_no'=>$mobile_no, 'sms_verified'=>true));

            $this->response(array("code"=>RESULT_SUCCESS), REST_Controller::HTTP_ACCEPTED); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }

    public function twilioSmsStatusCallback_post() {
        $message_status = $this->post('MessageStatus');
        $error_code = $this->post('ErrorCode');
        
        $this->TwilioStatusModel->create(array('data'=>serialize($this->post())));
    }
    
    public function login_post() {
        try { 
            $mobile_no = $this->post('mobile_no');
            $email = $this->post('email');
            $password = $this->post('password'); 
            $ttl = $this->post('ttl'); 

            if((!isset($mobile_no) && !isset($email)) || !isset($password)) {
                throw new Exception($this->lang->line('parameter_incorrect'), RESULT_ERROR_PARAMS_INVALID);
            }

            $params = array();
            if(isset($mobile_no)) {
                $params['mobile_no'] = $mobile_no;
            } 
            if(isset($email)) {
                $params['email'] = $email;
            } 
            $user = $this->UserModel->findOne($params);        

            if(!$user) {
                throw new Exception($this->lang->line('credential_invalid'), RESULT_ERROR_RESOURCE_NOT_FOUND);
            }

            $hasher = new PasswordHash(
                $this->config->item('phpass_hash_strength', 'tank_auth'),
                $this->config->item('phpass_hash_portable', 'tank_auth'));

            if(!$hasher->CheckPassword($password, $user->password)) {
                throw new Exception($this->lang->line('credential_invalid'), RESULT_ERROR_PARAMS_INVALID);
            }

            $data["user_id"] = $user->id;   
            $token = CryptoLib::randomString(50);
            $data["access_token"] = $token;
            if($ttl && $this->form_validation->numeric($ttl)) $data["ttl"] = $ttl;           
        
            if(isset($_SERVER['HTTP_CLIENT_IP'])) $data['ip_address1'] = $_SERVER['HTTP_CLIENT_IP'];
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $data['ip_address2'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if(isset($_SERVER['HTTP_X_FORWARDED'])) $data['ip_address3'] = $_SERVER['HTTP_X_FORWARDED'];
            if(isset($_SERVER['HTTP_FORWARDED_FOR'])) $data['ip_address4'] = $_SERVER['HTTP_FORWARDED_FOR'];
            if(isset($_SERVER['HTTP_FORWARDED'])) $data['ip_address5'] = $_SERVER['HTTP_FORWARDED'];
            if(isset($_SERVER['REMOTE_ADDR'])) $data['ip_address6'] = $_SERVER['REMOTE_ADDR'];
            
            $accessTokenId = $this->UserAccessTokenModel->create($data);
            $accessToken = $this->UserAccessTokenModel->findById($accessTokenId);


            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$accessToken
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }        

    public function me_get() {
        try { 
            $this->validateAccessToken();

            $profile = $this->UserProfileModel->findByUserId($this->user->id);

            $user = $this->user;
            $user->profile = $profile;
            $user->addresses = $this->UserAddressModel->find(array("user_id"=>$this->user->id));

            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$user
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }        

    public function profile_post() {
        try { 
            $this->validateAccessToken();

            $email = $this->post('email'); 
            $mobile_no = $this->post('mobile_no'); 


            // Check duplicate
            if(isset($email)) {
                $users = $this->UserModel->find(array("email"=>$email));
                if(count($users) && $users[0]->id!=$this->user->id) {
                    throw new Exception($this->lang->line('email_duplicated'), RESULT_ERROR_PARAMS_INVALID);
                }
            }  

            if(isset($mobile_no)) {
                $users = $this->UserModel->find(array("mobile_no"=>$mobile_no));
                if(count($users) && $users[0]->id!=$this->user->id) {                        
                    throw new Exception($this->lang->line('mobile_no_duplicated'), RESULT_ERROR_PARAMS_INVALID);
                }
            } 

            $user = array();
            if(isset($email) && $this->user->email!=$email) {
                $user["email"] = $email;
                $user["email_verified"] = false;
            }
            if(isset($mobile_no) && $this->user->mobile_no!=$mobile_no) {
                $user["mobile_no"] = $mobile_no; 
                $user["sms_verified"] = false;
            }                        

            if(!empty($user))
                $this->UserModel->update($this->user->id, $user);


            $f_name = $this->post('f_name');
            $l_name = $this->post('l_name'); 
            $home_number = $this->post('home_number'); 
            $gender = $this->post('gender'); 
            $birthdate = $this->post('birthdate'); 

            $profile = array();
            if(isset($f_name)) $profile["f_name"] = $f_name; 
            if(isset($l_name)) $profile["l_name"] = $l_name;                  
            if(isset($home_number)) $profile["home_number"] = $home_number;
            if(isset($gender)) $profile["gender"] = $gender;
            if(isset($birthdate)) $profile["birthdate"] = $birthdate;

            $profile["user_id"] = $this->user->id;

            if(!empty($profile))
                $this->UserProfileModel->save($this->user->id, $profile);

            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$this->UserProfileModel->findByUserId($this->user->id)
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }       

    public function address_post() {
        try { 
            $this->validateAccessToken();

            $id = $this->post('id'); 
            $address_name = $this->post('address_name'); 
            if(!isset($address_name)) {
                throw new Exception('address_name ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
            }
            $city_id = $this->post('city_id');             
            if(!isset($city_id)) {
                throw new Exception('city_id ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
            }
            $area_id = $this->post('area_id');             
            if(!isset($area_id)) {
                throw new Exception('area_id ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
            } 
            $street = $this->post('street'); 
            $block = $this->post('block'); 
            $house = $this->post('house'); 
            $floor = $this->post('floor'); 
            $appartment = $this->post('appartment'); 
            $extra_directions = $this->post('extra_directions'); 

            $data = array();
            $data["address_name"] = $address_name; 
            $data["city_id"] = $city_id;                  
            $data["area_id"] = $area_id;
            if(isset($street)) $data["street"] = $street;
            if(isset($block)) $data["block"] = $block;
            if(isset($house)) $data["house"] = $house;
            if(isset($floor)) $data["floor"] = $floor;
            if(isset($appartment)) $data["appartment"] = $appartment;
            if(isset($extra_directions)) $data["extra_directions"] = $extra_directions;
            $data["user_id"] = $this->user->id;
            
            if(isset($id)) {
                $this->UserAddressModel->update($id, $data);
            } else {
                $id = $this->UserAddressModel->create($data);
            }


            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$this->UserAddressModel->findById($id)
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    } 
     
    public function address_get($id=null) {
        try { 
            $this->validateAccessToken();
            
            
            if($id === null) {
                $resource = $this->UserAddressModel->find(array('user_id'=>$this->user->id));
            } else {
                $resource = $this->UserAddressModel->findById($id);
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

    public function sub_address_post() {
        try { 
            $this->validateAccessToken();

            $billing_full_name = $this->post('billing_full_name'); 
            $billing_addres_1 = $this->post('billing_addres_1'); 
            $billing_address_2 = $this->post('billing_address_2'); 
            $billing_city = $this->post('billing_city'); 
            $billing_state = $this->post('billing_state'); 
            $billing_zip_code = $this->post('billing_zip_code'); 
            $billing_phoneno = $this->post('billing_phoneno'); 
            $shipping_full_name = $this->post('shipping_full_name'); 
            $shipping_address_1 = $this->post('shipping_address_1'); 
            $shipping_address_2 = $this->post('shipping_address_2'); 
            $shipping_city = $this->post('shipping_city'); 
            $shipping_state = $this->post('shipping_state'); 
            $shipping_zip_code = $this->post('shipping_zip_code'); 
            $shipping_phoneno = $this->post('shipping_phoneno'); 

            $data = array();
            if(isset($billing_full_name)) $data["billing_full_name"] = $billing_full_name; 
            if(isset($billing_addres_1)) $data["billing_addres_1"] = $billing_addres_1;                  
            if(isset($billing_address_2)) $data["billing_address_2"] = $billing_address_2;                  
            if(isset($billing_city)) $data["billing_city"] = $billing_city;
            if(isset($billing_state)) $data["billing_state"] = $billing_state;
            if(isset($billing_zip_code)) $data["billing_zip_code"] = $billing_zip_code;
            if(isset($billing_phoneno)) $data["billing_phoneno"] = $billing_phoneno;
            if(isset($shipping_full_name)) $data["shipping_full_name"] = $shipping_full_name;
            if(isset($shipping_address_1)) $data["shipping_address_1"] = $shipping_address_1;
            if(isset($shipping_address_2)) $data["shipping_address_2"] = $shipping_address_2;
            if(isset($shipping_city)) $data["shipping_city"] = $shipping_city;
            if(isset($shipping_state)) $data["shipping_state"] = $shipping_state;
            if(isset($shipping_zip_code)) $data["shipping_zip_code"] = $shipping_zip_code;
            if(isset($shipping_phoneno)) $data["shipping_phoneno"] = $shipping_phoneno;

            $data["user_id"] = $this->user->id;
            if(!empty($data))
                $insert_id = $this->RestroCustomerAddressModel->create($data);


            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$this->RestroCustomerAddressModel->findById($insert_id)
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }        


    public function language_post() {
        try { 
            $this->validateAccessToken();

            $language = $this->post('language'); 

            $profile = array();
            if(isset($language)) $profile["language"] = $language;  

            $profile["user_id"] = $this->user->id;
            if(!empty($profile))
                $this->UserProfileModel->save($this->user->id, $profile);


            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$this->UserProfileModel->findByUserId($this->user->id)
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }         

    public function subscription_post() {
        try { 
            $this->validateAccessToken();

            $notification_subscription = $this->post('notification_subscription'); 
            $sms_subscription = $this->post('sms_subscription'); 
            $email_subscription = $this->post('email_subscription'); 

            $settings["notification_subscription"] = (isset($notification_subscription) && $notification_subscription == 1) ? 1 : 0; 
            $settings["sms_subscription"] = (isset($sms_subscription) && $sms_subscription == 1) ? 1 : 0; 
            $settings["email_subscription"] = (isset($email_subscription) && $email_subscription == 1) ? 1 : 0;


            $this->UserModel->update($this->user->id, $settings);                                                 

            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$this->UserModel->findById($this->user->id)
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }

    public function change_mobile_no_post() {
        try { 
            $this->validateAccessToken();

            $old_mobile_no = $this->post('old_mobile_no'); 
            $new_mobile_no = $this->post('new_mobile_no'); 


            // Check duplicate
            if(!isset($old_mobile_no) || !isset($new_mobile_no)) {
                throw new Exception("old_mobile_no and new_mobile_no ".$this->lang->line('parameters_required'), RESULT_ERROR_PARAMS_INVALID);
            }  

            if($old_mobile_no != $this->user->mobile_no) {
                throw new Exception($this->lang->line('mobile_no_not_matched'), RESULT_ERROR_PARAMS_INVALID);
            }

            $users = $this->UserModel->find(array("mobile_no"=>$new_mobile_no));
            if(count($users) && $users[0]->id!=$this->user->id) {                        
                throw new Exception($this->lang->line('mobile_no_duplicated'), RESULT_ERROR_PARAMS_INVALID);
            }

            $this->UserModel->update($this->user->id, array(
                "mobile_no"=>$new_mobile_no
            ));

            $this->response(array(
                "code"=>RESULT_SUCCESS,
                "resource"=>$this->UserModel->findById($this->user->id)
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }       

    public function change_password_post() {
        try { 
            $this->validateAccessToken();

            $old_password = $this->post('old_password'); 
            $new_password = $this->post('new_password'); 


            // Check duplicate
            if(!isset($old_password) || !isset($new_password)) {
                throw new Exception("old_password and new_password ".$this->lang->line('parameters_required'), RESULT_ERROR_PARAMS_INVALID);
            }  

            $hasher = new PasswordHash(
                $this->config->item('phpass_hash_strength', 'tank_auth'),
                $this->config->item('phpass_hash_portable', 'tank_auth'));

            if(!$hasher->CheckPassword($old_password, $this->user->password)) {
                throw new Exception($this->lang->line('password_not_matched'), RESULT_ERROR_PARAMS_INVALID);
            } 


            $this->UserModel->update($this->user->id, array(
                "password"=>$hasher->HashPassword($new_password)
            ));

            $this->response(array(
                "code"=>RESULT_SUCCESS
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }       

    public function check_token_post() {
        try { 
            $this->validateAccessToken();                

            $this->response(array(
                "code"=>RESULT_SUCCESS
                ), REST_Controller::HTTP_OK); 

        } catch (Exception $e) {
            $this->response(array(
                "code"=>$e->getCode(),
                "message"=>$e->getMessage()
                ), REST_Controller::HTTP_OK);
        } 
    }

    public function upload_image_post() {
        try { 
            $this->validateAccessToken();                
            $this->load->library('upload');
            $data['image_errors']='Couldn\'t upload the file(s)';
            $config['upload_path'] = FCPATH . 'profile_images/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            $this->upload->initialize($config);
            
            
            $number_of_files = sizeof($_FILES['profile_pic']['tmp_name']);
            $files = $_FILES['profile_pic'];
            
            $_FILES['profile_pic']['name'] = $files['name'];
            $_FILES['profile_pic']['type'] = $files['type'];
            $_FILES['profile_pic']['tmp_name'] = $files['tmp_name'];
            $_FILES['profile_pic']['error'] = $files['error'];
            $_FILES['profile_pic']['size'] = $files['size'];
            
            if ($this->upload->do_upload('profile_pic')) {
                $upload_data = $this->upload->data();
                
                $imageUrl = '/profile_images/'.$upload_data['file_name']; 
                
                $this->UserProfileModel->save($this->user->id, array('image'=>$imageUrl));
                
                $resource = $this->UserProfileModel->findByUserId($this->user->id);
            } else {
                throw new Exception($this->upload->display_errors(), RESULT_ERROR);
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
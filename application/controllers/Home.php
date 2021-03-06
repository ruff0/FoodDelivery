<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
    @ob_start();

    require APPPATH . '/libraries/CryptoLib.php';
    class Home extends CI_Controller
    {
        function __construct()
        {
            parent::__construct();

            $this->load->helper(array('form', 'url','date','captcha'));
            $this->load->library('form_validation');
            $this->load->model("Customer/Home_Restro");
            $this->load->helper("restaurant_helper");
            $this->load->library('cart');
            $this->load->model("Customer/Home_site");
            $this->load->model("Customer_management");
            $this->load->library('session');
            $this->load->model("Administration/Advertise_management");
            $this->load->model("Administration/Contact_us_management");
            $this->load->model("Administration/Dashboard_management");
            $this->load->model('CartModel'); 
            $this->load->model('UserAddressModel'); 
            $this->load->model('RestaurantModel'); 
            $this->load->model('RatingModel'); 
            $this->load->model('RestroItemCategoryModel'); 
            $this->load->model('RestroItemModel'); 
            $this->load->model('UserProfileModel'); 
            $this->load->model('OrderModel'); 
            $this->load->model('OrderDetailModel'); 
            $this->load->model('RestroCityAreaModel'); 
            $this->load->model('LoyaltyPointModel'); 
            $this->load->model('MataamPointModel'); 
            $this->load->model('CouponModel'); 
            $this->load->model('RestroSeatingHourModel'); 
            $this->load->model('RestroTableOrderModel'); 
            $this->load->model('PointLogModel'); 
            $this->load->model('CuisineModel'); 
            $this->load->model('FoodTypeModel'); 
            $this->load->model('RestroCategoryModel'); 
            $this->load->model('RestroLocationModel'); 
            $this->load->model('RestroServiceCommissionModel'); 
            $this->load->model('RestroWorkingHourModel'); 
            $this->load->model('RestroPaymentMethodModel');             
            $this->load->model('AreaModel');  
            $this->load->model('RestroPromotionModel');  
            $this->load->model('RestroPromotionItemModel');  
            $this->load->model('RestroItemVariationModel');  

            $this->load->helper('captcha');
            $this->load->helper('utils');
            $this->load->helper('order');

            $this->load->library('tank_auth');
            $this->lang->load('tank_auth');

            $this->load->model('Custom_function');
            $this->load->model('UserAccessTokenModel');
        }

        public function index()
        {

            $_SESSION['filter_service'] = '';
            $_SESSION['filter_city'] = '';
            $_SESSION['res_date'] = '';
            $_SESSION['res_user'] = '';
            $_SESSION['search_txt'] = '';
            $_SESSION['cat_date'] = '';
            $_SESSION['cat_time'] = '';

            $data['opening_soon_count'] = $this->Home_Restro->front_restro_count_by_type(1);
            $data['luxury_count'] = $this->Home_Restro->front_restro_count_by_type(2);
            $data['newly_count'] = $this->Home_Restro->front_restro_count_by_type(3);
            $data['trending_count'] = $this->Home_Restro->front_restro_count_by_type(4);

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit3(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit3(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit3(6,3);
            $data['city'] = $this->Home_site->show_all_city();


            $this->load->view('home',$data);
        }





        public function login(){
            if ($this->tank_auth->is_logged_in()) {
                // logged in


                $logined_role_id=$this->Custom_function->role_by_id($this->tank_auth->get_user_id());

                if($logined_role_id['user_role']==1)
                {
                    redirect('/Dashboard/');        
                }
                else if($logined_role_id['user_role']==2)
                {
                    redirect('/restro_dashboard/');  

                }
                else if($logined_role_id['user_role']==3)
                {
                    redirect('/customer_dashboard/');  

                }

            } elseif ($this->tank_auth->is_logged_in(FALSE)) {						// logged in, not activated
                redirect('/auth/send_again/');

            } else {
                $data['login_by_username'] = ($this->config->item('login_by_username', 'tank_auth') AND
                    $this->config->item('use_username', 'tank_auth'));
                $data['login_by_email'] = $this->config->item('login_by_email', 'tank_auth');

                $this->form_validation->set_rules('login', 'Login', 'trim|required');
                $this->form_validation->set_rules('password', 'Password', 'trim|required');
                $this->form_validation->set_rules('remember', 'Remember me', 'integer');


                // Get login for counting attempts to login
                if ($this->config->item('login_count_attempts', 'tank_auth') AND
                    ($login = $this->input->post('login'))) {
                    $login = $login;
                } else {
                    $login = '';
                }

                $data['use_recaptcha'] = $this->config->item('use_recaptcha', 'tank_auth');
                if ($this->tank_auth->is_max_login_attempts_exceeded($login)) {

                    if ($data['use_recaptcha'])
                        $this->form_validation->set_rules('recaptcha_response_field', 'Confirmation Code', 'trim|required|callback__check_recaptcha');
                    else
                        $this->form_validation->set_rules('captcha', 'Confirmation Code', 'trim|required|callback__check_captcha');
                }
                $data['errors'] = array();

                if ($this->form_validation->run()) {


                    // validation ok
                    if ($this->tank_auth->login(
                        $this->form_validation->set_value('login'),
                        $this->form_validation->set_value('password'),
                        $this->form_validation->set_value('remember'),
                        $data['login_by_username'],
                        $data['login_by_email'])) {

                        $role_id=$this->Custom_function->role_id($this->input->post("login"));
                        $this->session->set_userdata(array('user_role'=>$role_id['user_role']));

                        // Set Access Token

                        $token_data["user_id"] = $this->session->userdata('user_id');   
                        $token = CryptoLib::randomString(50);
                        $token_data["access_token"] = $token;        

                        if(isset($_SERVER['HTTP_CLIENT_IP'])) $token_data['ip_address1'] = $_SERVER['HTTP_CLIENT_IP'];
                        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $token_data['ip_address2'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
                        if(isset($_SERVER['HTTP_X_FORWARDED'])) $token_data['ip_address3'] = $_SERVER['HTTP_X_FORWARDED'];
                        if(isset($_SERVER['HTTP_FORWARDED_FOR'])) $token_data['ip_address4'] = $_SERVER['HTTP_FORWARDED_FOR'];
                        if(isset($_SERVER['HTTP_FORWARDED'])) $token_data['ip_address5'] = $_SERVER['HTTP_FORWARDED'];
                        if(isset($_SERVER['REMOTE_ADDR'])) $token_data['ip_address6'] = $_SERVER['REMOTE_ADDR'];

                        $this->UserAccessTokenModel->create($token_data);

                        $_SESSION['access_token'] = $token;

                        $return_url= $this->input->post("return_url");

                        if($return_url != '')
                        {

                            redirect($return_url);
                        }


                        if($role_id['user_role']==1)
                        {


                            redirect('/Dashboard/');        
                        }
                        else if($role_id['user_role']==2)
                        {


                            redirect('/restro_dashboard/');  

                        }
                        else if($role_id['user_role']==3)
                        {
                            redirect('/customer_dashboard/');  

                        }


                        // success
                    } else {
                        $errors = $this->tank_auth->get_error_message();
                        if (isset($errors['banned'])) {								// banned user
                            $this->_show_message($this->lang->line('auth_message_banned').' '.$errors['banned']);

                        } elseif (isset($errors['not_activated'])) {				// not activated user
                            redirect('/auth/send_again/');

                        } else {													// fail
                            foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
                        }
                    }
                }
                $data['show_captcha'] = FALSE;
                if ($this->tank_auth->is_max_login_attempts_exceeded($login)) {
                    $data['show_captcha'] = TRUE;
                    if ($data['use_recaptcha']) {
                        $data['recaptcha_html'] = $this->_create_recaptcha();
                    } else {
                        //$data['captcha_html'] = $this->_create_captcha();
                    }
                }
                $this->load->view('login', $data);
            }
        }



        function forgot_pass()
        {
            if ($this->tank_auth->is_logged_in()) {									// logged in
                redirect('');



            } elseif ($this->tank_auth->is_logged_in(FALSE)) {						// logged in, not activated
                redirect('/auth/send_again/');

            } else {
                $this->form_validation->set_rules('login', 'Email or login', 'trim|required');

                $data['errors'] = array();

                if ($this->form_validation->run()) {								// validation ok
                    if (!is_null($data = $this->tank_auth->forgot_password(
                        $this->form_validation->set_value('login')))) {

                        $data['site_name'] = $this->config->item('website_name', 'tank_auth');

                        // Send email with password activation link

                        $this->_send_email('forgot_password', $data['email'], $data);

                        $this->_show_message($this->lang->line('auth_message_new_password_sent'));



                    } else {
                        $errors = $this->tank_auth->get_error_message();
                        foreach ($errors as $k => $v)	$data['errors'][$k] = $this->lang->line($v);
                    }
                }
                $this->load->view('auth/forgot_password_form', $data);
            }
        }





        function reset_pass()
        {
            $user_id	= $this->uri->segment(3);
            $new_pass_key	= $this->uri->segment(4);

            $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|min_length['.$this->config->item('password_min_length', 'tank_auth').']|max_length['.$this->config->item('password_max_length', 'tank_auth').']|alpha_dash');
            $this->form_validation->set_rules('confirm_new_password', 'Confirm new Password', 'trim|required|matches[new_password]');

            $data['errors'] = array();

            if ($this->form_validation->run()) {								// validation ok
                if (!is_null($data = $this->tank_auth->reset_password(
                    $user_id, $new_pass_key,
                    $this->form_validation->set_value('new_password')))) {	// success

                    $data['site_name'] = $this->config->item('website_name', 'tank_auth');

                    // Send email with new password
                    $this->_send_email('reset_password', $data['email'], $data);
                    $this->session->set_flashdata("change_pass_msg","Password Changed Successfully.Check Your Mail"); 
                    redirect('home/login/');


                    $this->_show_message($this->lang->line('auth_message_new_password_activated').' '.anchor('auth/login/', 'Login'));

                } else {														// fail
                    $this->_show_message($this->lang->line('auth_message_new_password_failed'));
                }
            } else {
                // Try to activate user by password key (if not activated yet)
                if ($this->config->item('email_activation', 'tank_auth')) {
                    $this->tank_auth->activate_user($user_id, $new_pass_key, FALSE);
                }

                if (!$this->tank_auth->can_reset_password($user_id, $new_pass_key)) {
                    $this->_show_message($this->lang->line('auth_message_new_password_failed'));
                }
            }
            $this->load->view('auth/reset_password_form', $data);
        }









        function _show_message($message)
        {
            $this->session->set_flashdata('message', $message);
            //redirect('/forgot_password/');
        }


        function _send_email($type, $email, &$data)
        {
            $this->load->library('email');
            $this->email->from($this->config->item('webmaster_email', 'tank_auth'), $this->config->item('website_name', 'tank_auth'));
            $this->email->reply_to($this->config->item('webmaster_email', 'tank_auth'), $this->config->item('website_name', 'tank_auth'));
            $this->email->to($email);
            $this->email->subject(sprintf($this->lang->line('auth_subject_'.$type), $this->config->item('website_name', 'tank_auth')));
            $this->email->message($this->load->view('email/'.$type.'-html', $data, TRUE));
            $this->email->set_alt_message($this->load->view('email/'.$type.'-txt', $data, TRUE));
            $this->email->send();
        }










        public function restaurant_list(){


            $data['errors']=array();
            $data['retro_list']=$this->Home_Restro->all_restro();
            $data['service_list']=$this->Home_Restro->all_service();
            $data['city'] = $this->Home_site->show_all_city();
            $data['cuisin_list']=$this->Home_Restro->all_cuisin();


            $this->load->view('restaurant_list',$data);
        }
        public function ajax_restaurants_fetch(){

            $data['errors']=array();
            $service_id =$this->input->post('ids');
            $type = $this->input->post('act');
            $cuineIds = $this->input->post('cuineIds'); 
            $_SESSION['filter_service'] = $service_id;
            $area = $_SESSION['filter_city'];

            if($cuineIds != '')
            {
                if(($service_id == 1) || ($service_id == 2))
                {
                    $data['retro_list'] = $this->Home_Restro->all_restro_by_service_city($service_id,$cuineIds,$area);
                }
                elseif($service_id == 3)
                {
                    $data['retro_list'] = $this->Home_Restro->all_restro_table_by_service($service_id,$cuineIds,$area);


                }
                else
                {
                    $data['retro_list'] = $this->Home_Restro->all_restro_by_service($service_id,$cuineIds,$area);

                }
            }
            else
            {

                if(($service_id == 1) || ($service_id == 2))
                {
                    $data['retro_list'] = $this->Home_Restro->all_restro_by_service_city($service_id,0,$area);
                }
                elseif($service_id == 3)
                {

                    $data['retro_list'] = $this->Home_Restro->all_restro_table_by_service($service_id,0,$area);


                }
                else
                {
                    $data['retro_list'] = $this->Home_Restro->all_restro_by_service($service_id,0,$area);

                }
            }





            if($type == 'DELIVERY')
            {
                $this->load->view('ajax_restaurants_fetch_service',$data);
            }
            if($type == 'PICKUP')
            {
                $this->load->view('ajax_restaurants_fetch_service_pickup',$data);
            }
            if($type == 'TABLE')
            {
                $this->load->view('ajax_restaurants_fetch_service_table',$data);	
            }
            if($type == 'CATERING')
            {
                $this->load->view('ajax_restaurants_fetch_service_catering',$data);	
            }
        } 

        public function ajax_restaurants_fetch_cuisine(){

            $data['errors']=array();
            $cuisine_val = NULL;
            $cuisine_id =$this->input->post('ids');
            $type =$this->input->post('act');
            if($cuisine_id  != '')
            {
                foreach($cuisine_id as $cd)
                {
                    if($cuisine_val == '')
                    {
                        $cuisine_val = $cd;
                    }
                    else
                    {
                        $cuisine_val = $cuisine_val.'-'.$cd;
                    }
                }
            }
            $data['retro_list'] = $this->Home_Restro->catering_all_restro_by_cuisine($cuisine_val);

            $data1['retro_list'] = $this->Home_Restro->catering_all_restro_by_cuisine1($cuisine_val);

            $data2['retro_list'] = $this->Home_Restro->catering_all_restro_by_cuisine2($cuisine_val);

            if($_SESSION['filter_service'] == 1)
            {
                $this->load->view('ajax_restaurants_fetch_service',$data1);
            }
            if($_SESSION['filter_service'] == 4)
            {
                $this->load->view('ajax_restaurants_fetch_service_pickup',$data);
            }
            if($_SESSION['filter_service'] == 3)
            {
                $this->load->view('ajax_restaurants_fetch_service_table',$data2);	
            }
            if($_SESSION['filter_service'] == 2)
            {
                $this->load->view('ajax_restaurants_fetch_service_catering',$data1);	
            }

        }

        public function ajax_show_all_restro(){
            $data['errors']=array();
            $type =$this->input->post('act');


            $filter_id =$this->input->post('filter_id');

            $_SESSION['filter_id'] = $filter_id;

            $service_id = $_SESSION['filter_service'];

            if($filter_id == 1)
            {


                if($service_id == 1)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro();
                    $this->load->view('ajax_restaurants_fetch_service',$data);
                }
                if($service_id == 4)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro_pick();
                    $this->load->view('ajax_restaurants_fetch_service_pickup',$data);
                }
                if($service_id == 3)
                {
                    $data1['retro_list']=$this->Home_Restro->all_restro_tables();
                    $this->load->view('ajax_restaurants_fetch_service_table',$data1);	
                }
                if($service_id == 2)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro();
                    $this->load->view('ajax_restaurants_fetch_service_catering',$data);	
                }

            }
            elseif($filter_id == 2)
            {
                if($service_id == 1)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro_fetured();
                    $this->load->view('ajax_restaurants_fetch_service',$data);
                }
                if($service_id == 4)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro_pick_fetured();
                    $this->load->view('ajax_restaurants_fetch_service_pickup',$data);
                }
                if($service_id == 3)
                {
                    $data1['retro_list']=$this->Home_Restro->all_restro_tables_fetured();
                    $this->load->view('ajax_restaurants_fetch_service_table',$data1);	
                }
                if($service_id == 2)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro_fetured();
                    $this->load->view('ajax_restaurants_fetch_service_catering',$data);	
                }

            }
            elseif($filter_id == 3)
            {
                if($service_id == 1)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro_promo();
                    $this->load->view('ajax_restaurants_fetch_service',$data);
                }
                if($service_id == 4)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro_pick_promo();
                    $this->load->view('ajax_restaurants_fetch_service_pickup',$data);
                }
                if($service_id == 3)
                {
                    $data1['retro_list']=$this->Home_Restro->all_restro_tables_promo();
                    $this->load->view('ajax_restaurants_fetch_service_table',$data1);	
                }
                if($service_id == 2)
                {
                    $data['retro_list']=$this->Home_Restro->all_restro_promo();
                    $this->load->view('ajax_restaurants_fetch_service_catering',$data);	
                }

            }



        }

        public function restaurant_view(){
            //$data['advt'] = $this->Advertise_management->GetAdevrtise_limit2();

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit2(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit2(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit2(6,3);

            $data['errors']=array();
            $restro_id =$this->uri->segment('2');
            $location_id =$this->uri->segment('3');

            if($this->input->get('service_id')) $_SESSION["filter_service"] = $this->input->get('service_id');

            $service_id = $_SESSION["filter_service"];
            //$data['restroInfo'] = $this->Home_Restro->view_delivery_restro_details($restro_id);

            if(!isset($restro_id) || !isset($location_id) || !isset($service_id)) {
                redirect('/');
            }
            $restro = $this->RestaurantModel->findByRestroLocationService($restro_id, $location_id, $service_id); 

            if($restro==null) {
                redirect('404_override');
            }
            $restro->reviews = $this->RatingModel->find(array('location_id'=>$location_id));
            $data['restroInfo'] = $restro;
            $data['restroCat'] = $this->RestroItemCategoryModel->find(array('location_id'=>$location_id,'service_id'=>$service_id));
            $data['restro_item'] = $this->RestroItemModel->find(array('location_id'=>$location_id,'service_id'=>$service_id));

            $this->load->view('restaurant_view',$data);
        }

        public function ajax_show_item_by_cat(){
            $data['errors']=array();
            $category_id = $this->input->post('category_id');

            $data['restro_item'] = $this->RestroItemModel->find(array('category_id'=>$category_id));

            $data['restro_id'] = $this->input->post('restro_id');
            $data['location_id'] = $this->input->post('location_id');


            $this->load->view('ajax_show_item_by_cat',$data);
        }
        public function career(){
            $data['errors']=array();

            $this->load->library('image_lib');

            $data['type']=$this->Contact_us_management->get_job_type();

            $this->form_validation->set_rules('fname', 'First Name', 'required');
            $this->form_validation->set_rules('lname', 'Last Name', 'required');
            $this->form_validation->set_rules('job_title', 'Job Title', 'required');
            $this->form_validation->set_rules('email', 'Email Address', 'required');

            $val1 = rand(0,9);
            $val2 = rand(0,9);
            $val3 = rand(0,9);
            $val4 = rand(0,9);






            $capcha_val = $val1.$val2.$val3.$val4;




            if ($this->form_validation->run() == FALSE)
            {
                $data['capctha_code'] = $capcha_val;
                $_SESSION['capcha_val'] = $capcha_val;
            }
            else
            {


                $carrer['fname']=$this->input->post('fname');
                $carrer['lname']=$this->input->post('lname');
                $carrer['job_title']=$this->input->post('job_title');
                $carrer['email']=$this->input->post('email');
                $carrer['telephone']=$this->input->post('telephone');
                $captcha_text =$this->input->post('captcha_text');

                if($_SESSION['capcha_val'] != $captcha_text)
                {
                    $data['successMsg'] = '<span style="color:red">Your Capctha Code Wrong ! , Please try again</span>';
                }
                else
                {




                    $this->load->library('upload');
                    $files = $_FILES['uploadedimages'];

                    if($_FILES['uploadedimages']['error'] != 0)
                    {

                        $data['image_errors']='Couldn\'t upload the file(s)';


                    }

                    $config['upload_path'] = FCPATH . 'images/';
                    $config['allowed_types'] = 'gif|jpg|png|jpeg';

                    $_FILES['uploadedimage']['name'] = $files['name'];
                    $_FILES['uploadedimage']['type'] = $files['type'];
                    $_FILES['uploadedimage']['tmp_name'] = $files['tmp_name'];
                    $_FILES['uploadedimage']['error'] = $files['error'];
                    $_FILES['uploadedimage']['size'] = $files['size'];

                    //now we initialize the upload library
                    $this->upload->initialize($config);
                    if ($this->upload->do_upload('uploadedimage'))
                    {


                        $image_data = $this->upload->data();
                        $carrer['image'] = $image_data['full_path'];
                    }
                    else
                    {
                        $data['image_errors']=$this->upload->display_errors();



                    }


                    $this->Home_Restro->add_career($carrer);

                    $data['successMsg'] = '<span style="color:green">Success</span>';

                    $data['capctha_code'] = $capcha_val;
                    $_SESSION['capcha_val'] = $capcha_val;
                }

            }



            $this->load->view('career',$data);
        }

        public function contact(){
            $data['errors']=array();

            $this->form_validation->set_rules('fname', 'First Name', 'required');
            $this->form_validation->set_rules('lname', 'Last Name', 'required');
            $this->form_validation->set_rules('email', 'Email Address', 'required');
            $this->form_validation->set_rules('message', 'Message', 'required');



            if ($this->form_validation->run() == FALSE)
            {

            }
            else
            {
                $contact['fname']=$this->input->post('fname');
                $contact['lname']=$this->input->post('lname');
                $contact['email']=$this->input->post('email');
                $contact['message']=$this->input->post('message');
                $contact['telephone']=$this->input->post('telephone');
                $contact['date']=date("Y-m-d");


                $this->Home_Restro->add_contact($contact);

                $data['successMsg'] = '<span style="color:green">Thank You For Contacting Us.Our Team Will Get Back To You Soon</span>';
            }

            $this->load->view('contact_us',$data);
        }

        function Home_filter(){ 
            $data['errors']=array();

            $filter_service = $this->input->get('service');
            $filter_kind = $this->input->get('kind');

            $filter_area = $this->input->post('filter_area');
            $filter_cuisines = $this->input->post('filter_cuisines');


            if(isset($filter_service)) $_SESSION['filter_service'] = $filter_service;
            if(isset($filter_area)) $_SESSION['order_area_id'] = $filter_area;

            if(isset($_SESSION['order_area_id'])) $filter_area = $_SESSION['order_area_id'];

            $params = array();
            if(isset($filter_service)) {
                $params['service_type'] = $filter_service;
            }
            if(isset($filter_area)) {
                $params['area'] = $filter_area;
            }
            if(isset($filter_cuisines)) {
                $params['cuisines'] = $filter_cuisines;
            }

            if(isset($filter_kind)) {
                $params['kind'] = $filter_kind;

                $data['selected_kind'] = $filter_kind;
            }

            $data['restro_list'] = $this->RestaurantModel->find($params);  

            if($filter_service == 3) {
                $reserve_time = $this->input->post('reserve_time');
                $people_number = $this->input->post('people_number');
                $reserve_time = strtotime($reserve_time);
                foreach($data['restro_list'] as $restro) {
                    $restro->slots = getTimeSlots($restro->restro_id, $restro->location_id, $reserve_time, $people_number);
                }

                $_SESSION['reserve_date'] = date('Y-m-d', $reserve_time);
                $_SESSION['people_number'] = $people_number;
            }

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit(6,3);

            $data['city'] = $this->Home_site->show_all_city();


            $data['service_list']=$this->Home_Restro->all_service();
            $data['cuisin_list']=$this->Home_Restro->all_cuisin();



            if(isset($_POST['filter_type']))
            {
                redirect('/filter/');
            }
            $this->load->view('restaurant_list',$data);
        }
        function Home_coupon_filter(){
            $filter_id =$this->input->post('filter_id');
            if($filter_id == 4 ) {
                $data['coupons_list']=$this->Home_Restro->all_restro_coupons();
            }

            $this->load->view('coupon_list',$data);

        }



        function Home_reservation_filter(){
            $data['errors']=array();
            //$data['advt'] = $this->Advertise_management->GetAdevrtise_limit();

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit(6,3);


            $data['city'] = $this->Home_site->show_all_city();

            $filter_type =$this->input->post('filter_type');
            $res_date =$this->input->post('res_date');
            $user_limit =$this->input->post('user_limit'); 
            $reservation_search_txt =$this->input->post('reservation_search_txt');

            $city_id = $this->input->post('filter_city_id');

            if($res_date != '')
            {

                $_SESSION['res_date'] = $res_date;
                $_SESSION['filter_service'] = $filter_type;
            }


            if($user_limit != '')
            {

                $_SESSION['res_user'] = $user_limit;
            }


            if($filter_type != '')
            {

                $_SESSION['filter_city'] = $city_id;

                if($_SESSION['res_user'] != '')
                {
                    $data['userlimit'] = $_SESSION['res_user'];
                }
                else
                {
                    $_SESSION['res_user'] = 1;
                    $data['userlimit'] = $_SESSION['res_user'];
                }

                if($_SESSION['res_date'] != '')
                {
                    $data['resdate'] = $_SESSION['res_date'];
                }
                else
                {
                    $_SESSION['res_date'] = date('Y-m-d');
                    $data['resdate'] = $_SESSION['res_date'];
                }

                if($reservation_search_txt != '')
                {
                    $_SESSION['search_txt'] = $reservation_search_txt;
                }
                else
                {
                    $_SESSION['search_txt'] = NULL;
                }
            }

            $data['retro_list']=$this->Home_Restro->all_restro_table($_SESSION['filter_service'],$_SESSION['search_txt']);

            $data['service_list']=$this->Home_Restro->all_service();
            $data['cuisin_list']=$this->Home_Restro->all_cuisin();



            if(isset($_POST['filter_type']))
            {
                redirect('/reservation_filter/');
            }

            if($_SESSION['filter_service'] == '')
            {
                redirect('/');
            }

            $this->load->view('restaurant_table_list',$data);


        }
        public function reservation_restaurant_view(){
            $data['errors']=array();
            $restro_id = $this->uri->segment('2');
            $res_date = $_SESSION['res_date'];
            $res_user_limit = $_SESSION['res_user'];
            //$data['advt'] = $this->Advertise_management->GetAdevrtise_limit2();

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit2(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit2(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit2(6,3);

            $data['restroInfo'] = $this->Home_Restro->view_restro_details($restro_id);
            $data['restroCat'] = $this->Home_Restro->view_restro_cat_filter($restro_id);
            $data['restro_tables'] = $this->Home_Restro->restro_table_list($restro_id);


            $this->load->view('reservation_restaurant_view',$data);
        }

        function view_reservation_restro_table(){
            $data['errors']=array();
            $restro_id =$this->uri->segment('2');
            $table_id =$this->uri->segment('3');
            $res_date = $_SESSION['res_date'];
            $res_user_limit = $_SESSION['res_user'];

            $data['restroInfo'] = $this->Home_Restro->view_restro_details($restro_id);
            $data['restroCat'] = $this->Home_Restro->view_restro_cat_filter($restro_id);
            $data['restro_table_info'] = $this->Home_Restro->restro_table_details($restro_id,$table_id);
            //$this->cart->destroy();
            if(isset($_POST['btnaddtocart'])){

                $table_id =$this->input->post('table_id');

                $quantity =$this->input->post('quantity'); 
                $table_name =$this->input->post('table_name');


                $data2 = array(
                    'id' => time(),
                    'table_id'      => $table_id,
                    'qty'     => 1,
                    'name'    => $table_name,
                    'price' => 0.00,
                    'restro_id' => $restro_id,
                    'data' => "TABLE",
                    'User_limit' => $quantity,
                    'res_date' => $res_date

                );


                $this->cart->insert($data2);

            }


            $data['cartData'] = $this->cart->contents();

            foreach($data['cartData'] as $Dcar => $dataCart1){
                if($dataCart1['data'] == 'TABLE')
                {


                }
            }


            if(isset($_POST['addtocartbtn']))
            {
                $notes = $this->input->post('order_notes');

                foreach($data['cartData'] as $Dcart => $dataCart){
                    if($dataCart['data'] == 'TABLE')
                    {
                        $CartArray['table_id'] = $dataCart['table_id'];
                        $CartArray['quantity'] = $dataCart['qty'];
                        $CartArray['price'] = $dataCart['price']; 
                        $CartArray['restro_id'] = $dataCart['restro_id'];
                        $CartArray['notes'] = $notes; 
                        $CartArray['res_date'] = $dataCart['res_date'];
                        $CartArray['user_limit'] = $dataCart['User_limit'];
                        $CartArray['user_id'] = $_SESSION['Customer_User_Id']; 


                        $this->Home_Restro->insert_table_cart($CartArray);
                        $_SESSION['table_id'] = $dataCart['table_id'];
                    }	

                }
                $this->cart->destroy();
                $_SESSION['order_restro_id'] = $restro_id;

                redirect("/reservation_checkout/");
            }

            $this->load->view('view_restro_table',$data);
        }

        function view_restro_item(){ 
            $user_id = $_SESSION['Customer_User_Id'];

            if(!isset($user_id)) redirect('customer_login');

            $data['errors']=array();

            $service_id = $this->input->get('service_id');
            if(!isset($service_id)) $service_id = $_SESSION["filter_service"]; 
            if(!isset($service_id)) redirect('/');

            $restro_id =$this->uri->segment('2');
            $location_id =$this->uri->segment('3');            
            $item_id =$this->uri->segment('4');            

            $restro = $this->RestaurantModel->findByRestroLocationService($restro_id, $location_id, $service_id);             
            $restro->reviews = $this->RatingModel->find(array('location_id'=>$location_id)); 
            $data['restroInfo'] = $restro;
            $data['restroCat'] = $this->RestroItemCategoryModel->find(array('location_id'=>$location_id,'service_id'=>$service_id));
            $RestroUserId = $this->Home_Restro->getRestroUserId($restro_id);
            $data['restro_item_info'] = $this->Home_Restro->restro_item_details($RestroUserId,$item_id);

            $cart_item_id = $this->input->get('cart_item_id');
            if(isset($_POST['btnaddtocart'])){                

                if($this->input->post('variation_ids') != '')
                {
                    $variation_ids = implode(',',$this->input->post('variation_ids'));
                }
                else
                {
                    $variation_ids = 0;
                }
                $CartArray['product_id'] = $this->input->post('item_id');
                $CartArray['quantity'] = $this->input->post('quantity');
                //$CartArray['price'] = $this->input->post('last_price'); 
                $CartArray['restro_id'] = $restro_id;
                $CartArray['location_id'] = $location_id;
                $CartArray['user_id'] = $user_id;
                $CartArray['spacial_request'] = $this->input->post('spacial_request'); 
                $CartArray['variation_ids'] = $variation_ids;
                $CartArray["status"] = CART_STATUS_ACTIVE;

                $item = $this->RestroItemModel->findById($item_id);
                if(isset($variation_ids)) {
                    $variation_ids = explode(",", $variation_ids); 

                    $variations = $this->RestroItemVariationModel->findByIds($variation_ids); 

                    $price = 0;
                    if($item->price_type == ITEM_PRICE_TYPE_BY_MAIN) $price = $item->price;

                    foreach($variations as $v) {
                        $price += $v->price;
                    }
                    $CartArray["price"] = $price;
                } else {
                    $CartArray["price"] = $item->price;   
                }
                if(isset($cart_item_id)) {
                    $this->CartModel->update($service_id, $cart_item_id, $CartArray);
                } else {
                    $this->CartModel->create($service_id, $CartArray);
                }
            }

            if(isset($cart_item_id)) {
                $data['cartItem'] = $this->CartModel->findById($service_id, $cart_item_id);
            }            

            $data['cartData'] = $this->CartModel->find($service_id, array(
                'user_id'=>$user_id,
                'restro_id'=>$restro_id,
                'location_id'=>$location_id
                ), true); 

            if(isset($_POST['addtocartbtn']))
            {
                $notes = $this->input->post('order_notes');                

                $_SESSION['order_restro_id'] = $restro_id;
                $_SESSION['order_location_id'] = $location_id;
                redirect("/checkout/");
            }
            $this->load->view('view_restro_item',$data);
        }

        function reorder() {
            $service_id = $this->input->get('service_id');
            $area_id = $this->input->get('area_id');
            $order_id = $this->input->get('order_id');


            $order_details = $this->OrderDetailModel->find($service_id, array('order_id'=>$order_id)); 

            foreach($order_details as $detail) {
                $cart_item = array();

                $item = $this->RestroItemModel->findById($detail->product_id);
                $cart_item["product_id"] = $detail->product_id;
                $cart_item['quantity'] = $detail->quantity;
                $cart_item['price'] = $item->price*$detail->quantity;
                $cart_item['restro_id'] = $detail->restro_id;
                $cart_item['location_id'] = $detail->location_id;
                $cart_item['notes'] = $detail->notes;
                $cart_item['variation_ids'] = $detail->variation_ids;
                $cart_item['user_id'] = $_SESSION['Customer_User_Id'];
                $cart_item['date'] = date('Y-m-d H:i:s');
                $cart_item['status'] = 0;

                $this->CartModel->create($service_id, $cart_item);
            }

            $order = $this->OrderModel->findById($service_id, $order_id);
            $_SESSION['filter_service'] = $service_id;
            $_SESSION['order_restro_id'] = $order->restro_id;
            $_SESSION['order_location_id'] = $order->location_id;
            $_SESSION['order_area_id'] = $area_id;

            redirect("/checkout/");
        }
        function add_promoitem_to_cart() {
            $service_id = $this->input->get('service_id');
            $area_id = $this->input->get('area_id');
            $promo_id = $this->input->get('promo_id');

            $promotion = $this->RestroPromotionModel->findById($promo_id);
            $promo_items = $this->RestroPromotionItemModel->find(array('promo_id'=>$promo_id)); 

            foreach($promo_items as $promo) {
                $cart_item = array();

                $item = $this->RestroItemModel->findById($promo->item_id); 

                if($item) {
                    $cart_item["product_id"] = $item->id;
                    $cart_item['quantity'] = 1;
                    $cart_item['price'] = $item->price;
                    $cart_item['restro_id'] = $promotion->restro_id;
                    $cart_item['location_id'] = $promotion->location_id;
                    $cart_item['notes'] = '';
                    $cart_item['variation_ids'] = '';
                    $cart_item['user_id'] = $_SESSION['Customer_User_Id'];
                    $cart_item['date'] = date('Y-m-d H:i:s');
                    $cart_item['status'] = 0;

                    $this->CartModel->create($service_id, $cart_item);   
                }
            }

            $_SESSION['filter_service'] = $service_id;
            $_SESSION['order_restro_id'] = $promotion->restro_id;
            $_SESSION['order_location_id'] = $promotion->location_id;
            $_SESSION['order_area_id'] = $area_id;

            redirect("/checkout/");
        }
        function checkout(){
            $data['errors']=array();
            $user_id = $_SESSION['Customer_User_Id'];

            if($this->input->get('service_id')) $_SESSION['filter_service'] = $this->input->get('service_id');
            if($this->input->get('restro_id')) $_SESSION['order_restro_id'] = $this->input->get('restro_id');
            if($this->input->get('location_id')) $_SESSION['order_location_id'] = $this->input->get('location_id');
            if($this->input->get('area_id')) $_SESSION['order_area_id'] = $this->input->get('area_id');

            $service_type = $_SESSION['filter_service'];
            $restro_id = $_SESSION['order_restro_id'];
            $location_id = $_SESSION['order_location_id'];

            $Mnumber = $this->Customer_management->get_user_mobile_number($user_id);
            $mobileNumber = $Mnumber['mobile_no'];

            $customer_points = $this->Customer_management->get_customer_points($user_id);

            $data['cust_point'] = $customer_points;                       

            $data['cartData'] = $this->CartModel->find($service_type, array(
                'user_id'=>$user_id,
                'restro_id'=>$restro_id,
                'location_id'=>$location_id,
                ), true); 



            $data['getPaymentgateways'] = $this->Home_Restro->getPaymentgatewaysByService($restro_id, $service_type);

            //$data['deliveryCharges'] = $this->Home_Restro->getDeliveryChargesbyrestrolocation($restro_id, $service_type, $_SESSION['filter_city']);

            if(($data['cartData'] == '') or ($user_id == ''))
            {
                redirect('/');
            }

            if(isset($_POST['btnUserAddressSave'])) {
                $address = array();
                $address['area_id'] = $this->input->post('area_id');
                $address['city_id'] = $this->AreaModel->findById($address['area_id'])->city_id;
                $address['street'] = $this->input->post('street');
                $address['block'] = $this->input->post('block');
                $address['appartment'] = $this->input->post('appartment');
                $address['floor'] = $this->input->post('floor');
                $address['extra_directions'] = $this->input->post('extra_directions');
                $address['house'] = $this->input->post('house');
                $address['address_name'] = $this->input->post('address_name');
                $address['is_primary'] = $this->input->post('is_primary')&&$this->input->post('is_primary')=='on' ? 1 : 0;
                $address['user_id'] = $user_id; 

                if($address['is_primary']) {
                    $this->UserAddressModel->updateByParams(array('user_id'=>$user_id), array('is_primary'=>false));
                }
                $this->UserAddressModel->create($address);

                unset($_POST['btnUserAddressSave']);
            }


            $datestring = "%Y-%m-%d";
            $timestring = "%h:%i %a";
            $time = time();

            $Mdate = mdate($datestring, $time);
            $Mtime = mdate($timestring, $time);

            $restro = $this->RestaurantModel->findByRestroLocationService($restro_id, $location_id, $service_type); 
            $restro->reviews = $this->RatingModel->find(array('location_id'=>$location_id));

            if($service_type == SERVICE_DELIVERY || $service_type == SERVICE_CATERING) {

                $restroCityArea = $this->RestroCityAreaModel->findOne(array("restro_id"=>$restro_id,"location_id"=>$location_id, "service_id"=>$service_type));

                $area_ids = explode(',', $restroCityArea->area);
                $restro->areas = $this->AreaModel->find(array('ids'=>$area_ids)); 

                $data['addressData'] = $this->UserAddressModel->find(array('user_id'=>$user_id, 'area_ids'=>$area_ids));
                if(count($data['addressData']) == 0) {
                    $data['errors']['address_not_exist'] = "There are no addresses matched to restaurant's service areas";
                }
            } else {
                $data['addressData'] = $this->UserAddressModel->find(array('user_id'=>$user_id));
            }
            $data['restroInfo'] = $restro; 


            if(isset($_POST['btncheckout']))
            {
                $this->form_validation->set_rules('address_id', 'Address', 'required');
                $this->form_validation->set_rules('hd_orderTime', 'Delivery Time', 'required');
                $this->form_validation->set_rules('payment_type', 'Payment Option', 'required');

                if ($this->form_validation->run() == FALSE)
                {

                }
                else
                {
                    $area_id = $this->input->post('area_id');//$_SESSION['order_area_id'];
                    $order['restro_id'] = $restro_id;
                    $order['location_id'] = $location_id;
                    // Get Sum Info
                    $sum = getSum($user_id, $service_type, $restro_id, $location_id, $area_id);

                    $total = $sum['total_amount'];
                    if($restro->min_order>0 && $total<$restro->min_order) {
                        throw new Exception("Cart list total amount should be greater than min_order($restro->min_order)", RESULT_ERROR_TOTAL_INVALID);
                    }

                    $order['total'] = $total;
                    $order['delivery_charges'] = $sum['charge_amount'];

                    // Get Discount Amount
                    $redeem_type = $this->input->post('redeem_type');
                    $coupon_code = $this->input->post('coupon_code'); 

                    $point = getPoint($user_id, $service_type, $restro_id, $location_id); 
                    if(isset($redeem_type)) {
                        $discount = getDiscount($redeem_type, $user_id, $service_type, $restro_id, $location_id, $coupon_code);
                        $order['discount_amount'] = $discount['discount_amount'];
                        $order['coupon_point_apply'] = $redeem_type;                  
                        if($redeem_type == 1) { //  Redeem Coupon
                            $order['coupon_code'] = $coupon_code;
                        } else if($redeem_type == 2) {  // Loyalty Point
                            $order['used_points'] = $point['loyalty']['used_points'];
                        } else if($redeem_type == 3) {  // Mataam Point
                            $order['used_points'] = $point['mataam']['used_points'];
                        }   
                    }  

                    $order['order_points'] = $point['loyalty']['gained_points'];       
                    $order['mataam_order_points'] = $point['mataam']['gained_points'];     


                    $schedule_date = $this->input->post('schedule_date');
                    if(!isset($schedule_date)) {
                        throw new Exception('schedule_date '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                    }
                    $schedule_time = $this->input->post('schedule_time');
                    if(!isset($schedule_time)) {
                        throw new Exception('schedule_time '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                    }

                    if(time()>strtotime("$schedule_date $schedule_time")) {
                        $data['errors']['order_time_invalid'] = $this->lang->line('order_time_should_be_greater_than_now');
                        redirect('/checkout');
                    }

                    $weekday = strtolower(date('l', strtotime($schedule_date)));                
                    if(
                        $restro->{$weekday.'_from'} && strtotime($schedule_time)<strtotime($restro->{$weekday.'_from'}) ||
                        $restro->{$weekday.'_to'} && strtotime($schedule_time)>strtotime($restro->{$weekday.'_to'})
                    ) {
                        throw new Exception('schedule_time '.$this->lang->line('parameter_invalid'), RESULT_ERROR_PARAMS_INVALID);
                    }


                    if($service_type==1 || $service_type==4) {                    
                        $order['delivery_date'] = $schedule_date;  // Y-m-d
                        $order['delivery_time'] = $schedule_time;  // H:i:s
                    }
                    $order['date'] = $schedule_date;  // Y-m-d
                    $order['time'] = $schedule_time;  // H:i:s
                    $order['user_id'] = $user_id;

                    $payment_method = $this->input->post('payment_type');
                    if(!isset($payment_method)) {
                        throw new Exception('payment_method '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                    } 
                    $order['payment_method'] = $payment_method;

                    $order['status'] = 1; 
                    $address_id = $this->input->post('address_id');
                    if(!isset($address_id)) {
                        throw new Exception('address_id '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                    }
                    $order['address_id'] = $address_id;
                    $order['extra_direction'] = $this->input->post('extra_direction'); 
                    $order['restro_location_id'] = $area_id;
                    $order['created_time'] = $order['updated_time'] = date('Y-m-d H:i:s');
                    $order_id = $this->OrderModel->create($service_type, $order);
                    $this->OrderModel->update($service_type, $order_id, array("order_no"=>$this->config->item('Start_order_id').$order_id));
                    $order_details['order_id'] = $order_id;
                    $carts = $this->CartModel->find($service_type, array("user_id"=>$user_id, "restro_id"=>$restro_id, "location_id"=>$location_id));
                    foreach($carts as $cart){
                        $order_details['product_id'] = $cart->product_id;
                        $order_details['price'] = $cart->price;
                        $order_details['quantity'] = $cart->quantity;
                        $order_details['restro_id'] = $cart->restro_id;
                        $order_details['location_id'] = $cart->location_id;
                        $order_details['notes'] = $cart->notes;
                        $order_details['user_id'] = $cart->user_id;
                        $order_details['variation_ids'] = $cart->variation_ids;
                        $this->OrderDetailModel->create($service_type, $order_details);
                    }
                    $this->CartModel->deleteAll($service_type, array('user_id'=>$user_id,'restro_id'=>$restro_id, 'location_id'=>$location_id));

                    $order = $this->OrderModel->findById($service_type, $order_id);
                    $order->details = $this->OrderDetailModel->find($service_type, array('order_id'=>$order_id));

                    // Update user points on profile                    
                    $user_profile = $this->UserProfileModel->findByUserId($user_id);
                    $user_loyalty_points = $user_profile->points; $user_mataam_points = $user_profile->mataam_points;
                    if($redeem_type == 2) {
                        $user_loyalty_points -= $point['loyalty']['used_points'];
                    } else if ($redeem_type == 3) {
                        $user_mataam_points -= $point['mataam']['used_points'];
                    }
                    $user_loyalty_points += $point['loyalty']['gained_points'];
                    $user_mataam_points += $point['mataam']['gained_points'];
                    $this->UserProfileModel->save($user_id, array(
                        'points'=>$user_loyalty_points,
                        'mataam_points'=>$user_mataam_points
                    ));

                    // Create Points Log
                    $this->PointLogModel->create(array(
                        'user_id'=>$user_id,
                        'service_id'=>$service_type,
                        'order_id'=>$order_id,
                        'gained_loyalty_point'=>$point['loyalty']['gained_points'],
                        'used_loyalty_point'=>$redeem_type==2 ? $point['loyalty']['used_points'] : 0,
                        'balance_loyalty_point'=>$user_loyalty_points,
                        'gained_mataam_point'=>$point['mataam']['gained_points'],
                        'used_mataam_point'=>$redeem_type==3 ? $point['mataam']['used_points'] : 0,
                        'balance_mataam_point'=>$user_mataam_points
                    ));


                    //order msg send here

                    $orderNumber = $order->order_no;
                    $otpMSG =urlencode("Your Order Confirmed Successfully done, Your Order ID #$orderNumber");

                    $apiData = $this->Customer_management->getApiDetails(1);

                    $usernameApi = $apiData['username'];
                    $usernamePass = $apiData['password'];
                    $mobilenumber = @$mobileNumber;
                    $usernameSource = $apiData['username_source'];

                    $url = file_get_contents("http://103.16.101.52/sendsms/bulksms?username=$usernameApi&password=$usernamePass&destination=$mobilenumber&source=$usernameSource&message=$otpMSG");  


                    //order msg send here
                    if($payment_type == 4)
                    {
                        $_SESSION['pay_type'] = 1;
                        $_SESSION['pay_order_id'] = $order_id;
                        $_SESSION['pay_amount'] = $sum['total_amount'];
                        $_SESSION['pay_method'] = 4;
                        $_SESSION['pay_discount'] = $order['discount_amount'];
                        $_SESSION['pay_order_no'] = $updatedata['order_no'];

                        redirect('/Paypal');

                    }
                    else
                    {
                        redirect('/');
                    }


                }
            }

            $this->load->view('checkout',$data);


        }
        function ajax_cart_item_remove(){
            $data['errors']=array();
            $cart_id =$this->uri->segment('2');
            $user_id = $_SESSION['Customer_User_Id'];
            $this->Home_Restro->ajax_cart_item_remove($cart_id);
            $data['cartData'] = $this->Home_Restro->view_my_cart($user_id);
            $this->load->view('ajax_cart_item_remove',$data);
        }

        function delivery_cart_item_remove(){
            $data['errors']=array();
            $cart_id = $this->input->post('item');
            $user_id = $_SESSION['Customer_User_Id'];
            $this->Home_Restro->ajax_cart_item_remove($cart_id);
            $data['DcartData'] = $this->Home_Restro->view_my_cart($user_id);
            $this->load->view('delivery_cart_item_remove',$data);
        } 

        function pickup_cart_item_remove(){
            $data['errors']=array();
            $cart_id = $this->input->post('item');
            $user_id = $_SESSION['Customer_User_Id'];
            $this->Home_Restro->ajax_cart_pickup_remove($cart_id);
            $data['PcartData'] = $this->Home_Restro->view_my_pickup_cart($user_id);
            $this->load->view('pickup_cart_item_remove',$data);
        }

        function catering_cart_item_remove(){
            $data['errors']=array();
            /*$cart_id = $this->input->post('item');
            $user_id = $_SESSION['Customer_User_Id'];
            $this->Home_Restro->ajax_cart_pickup_remove($cart_id);
            $data['PcartData'] = $this->Home_Restro->view_my_pickup_cart($user_id);
            $this->load->view('pickup_cart_item_remove',$data);*/
        }

        function about_us(){
            $data['errors']=array();

            $data['about_data']=$this->Dashboard_management->get_about_us();
            $this->load->view('about_us',$data);

        }
        function home_policy(){
            $data['errors']=array();

            $data['privacy_data']=$this->Dashboard_management->get_privacy();
            $this->load->view('home_policy',$data);

        }
        function home_terms(){
            $data['errors']=array();

            $data['tearms_data']=$this->Dashboard_management->get_tearms();
            $this->load->view('home_terms',$data);

        }

        function home_opening_soon(){
            $data['errors']=array();

            $data['retro_list']=$this->Home_Restro->all_restro();
            $this->load->view('restro_listing',$data);

        }


        function restaurant_registration(){
            $data['errors']=array();
            $data['cuisin_list']=$this->Home_Restro->all_cuisin();
            $data['city'] = $this->Home_site->show_all_city();
            if(isset($_POST['restro_reg']))
            {




                $this->form_validation->set_rules('restro_name', 'Restro Name', 'required');
                $this->form_validation->set_rules('contact_name', 'Contact Name', 'required');
                $this->form_validation->set_rules('restro_phone', 'Restro Phone', 'required');
                $this->form_validation->set_rules('cell_phone', 'Cell Phone', 'required');
                $this->form_validation->set_rules('restro_address', 'Restro Address', 'required');
                $this->form_validation->set_rules('contact_email', 'Contact Email', 'required|valid_email');
                $this->form_validation->set_rules('restro_email', 'Restro Email', 'required|valid_email');
                $this->form_validation->set_rules('main_cuisine', 'Main_Cuisine', 'required');
                $this->form_validation->set_rules('secondary_cuisine', 'Secondary Cuisine', 'required');
                $this->form_validation->set_rules('about_us', 'About Us', 'required');

                $this->form_validation->set_rules('pickup_min_order', 'Min Order', 'required');
                $this->form_validation->set_rules('delivery_min_order', 'Min Order', 'required');
                $this->form_validation->set_rules('delivery_charge', 'Delivery Charge', 'required');
                $this->form_validation->set_rules('catering_min_order', 'Min Order', 'required');
                $this->form_validation->set_rules('menu_link', 'Menu Link', 'required');
                $this->form_validation->set_rules('message', 'Message', 'required');
                $this->form_validation->set_rules('time_from', 'From Time', 'required');
                $this->form_validation->set_rules('time_to', 'To Time', 'required');
                /*$this->form_validation->set_rules('services', 'Services', 'required');
                $this->form_validation->set_rules('work_time', 'Work Time', 'required');
                $this->form_validation->set_rules('menu_link', 'Eenu Link', 'required');
                $this->form_validation->set_rules('message', 'Message', 'required'); */


                if ($this->form_validation->run() == FALSE)
                {


                }
                else
                {
                    $restro_email=$this->input->post('restro_email');  

                    $restoREG['restro_name']=$this->input->post('restro_name');
                    $restoREG['contact_name']=$this->input->post('contact_name');
                    $restoREG['restro_phone']=$this->input->post('restro_phone');
                    $restoREG['cell_phone']=$this->input->post('cell_phone');
                    $restoREG['restro_address']=$this->input->post('restro_address');
                    $restoREG['contact_email']=$this->input->post('contact_email');
                    $restoREG['restro_email']=$this->input->post('restro_email');
                    $restoREG['main_cuisine']=$this->input->post('main_cuisine');
                    $restoREG['secondary_cuisine']=$this->input->post('secondary_cuisine');
                    $restoREG['about_us']=$this->input->post('about_us');
                    $restoREG['pickup_min_order']=$this->input->post('pickup_min_order');
                    $restoREG['delivery_min_order']=$this->input->post('delivery_min_order');
                    $restoREG['delivery_charge']=$this->input->post('delivery_charge');
                    $restoREG['catering_min_order']=$this->input->post('catering_min_order');
                    //$restoREG['work_time']=$this->input->post('work_time');
                    $restoREG['menu_link']=$this->input->post('menu_link');
                    $restoREG['message']=$this->input->post('message');
                    $restoREG['from']=$this->input->post('time_from');
                    $restoREG['to']=$this->input->post('time_to');


                    $restoREG['reg_date']= date("Y-m-d");

                    $work=implode($this->input->post('work_time'),',');
                    $restoREG['work_time']=$work;
                    $a=implode($this->input->post('services'),',');

                    $restoREG['services']=  str_replace(",,",",",trim($a,","));

                    if($restoREG['services'] =="")
                    {
                        $data['servicesMsg'] = '<span style="color:red">Select Service</span>';
                    }
                    else 
                    {	




                        $this->load->library('upload');
                        $files = $_FILES['restro_logo'];
                        if($_FILES['restro_logo']['error'] != 0)
                        {
                            $data['image_errors']='Couldn\'t upload the file(s)';
                        }
                        $config['upload_path'] = FCPATH . 'images/';
                        $config['allowed_types'] = 'gif|jpg|png|jpeg';
                        $_FILES['restro_logo']['name'] = $files['name'];
                        $_FILES['restro_logo']['type'] = $files['type'];
                        $_FILES['restro_logo']['tmp_name'] = $files['tmp_name'];
                        $_FILES['restro_logo']['error'] = $files['error'];
                        $_FILES['restro_logo']['size'] = $files['size'];
                        $this->upload->initialize($config);
                        if ($this->upload->do_upload('restro_logo'))
                        {
                            $this->_uploaded = $this->upload->data();
                            $restoREG['image']=$this->_uploaded['full_path'];
                        }
                        else
                        {
                            $data['image_errors']=$this->upload->display_errors();
                        }




                        // print_r($restoREG);die;




                        $this->Home_Restro->restro_registrotion($restoREG);

                        $this->session->set_flashdata('successMsg', '<span style="color:green">Thank You For Registration</span>');

                        $mess = "<head><title>Welcome to http://restro.powersoftware.in !</title></head>

                        <div style='max-width: 800px; margin: 0; padding: 30px 0;'>
                        <table width='80%' border='0' cellpadding='0' cellspacing='0'>
                        <tr>
                        <td width='5%'></td>
                        <td align='left' width='95%' style='font: 13px/18px Arial, Helvetica, sans-serif;'>
                        <h2 style='font: normal 20px/23px Arial, Helvetica, sans-serif; margin: 0; padding: 0 0 18px; color: black;'>Welcome to http://restro.powersoftware.in !</h2>
                        Thanks for joining http://restro.powersoftware.in <br />

                        <br />


                        Have fun!<br />
                        The http://restro.powersoftware.in Team
                        </td>
                        </tr>
                        </table>
                        </div>";

                        $ci = get_instance();
                        $ci->load->library('email');
                        $config['protocol'] = "smtp";
                        $config['smtp_host'] = "powersoftware.eu";
                        $config['smtp_port'] = "25";
                        $config['smtp_user'] = "enquiry@powersoftware.eu"; 
                        $config['smtp_pass'] = "powersoftware";
                        $config['charset'] = "utf-8";
                        $config['mailtype'] = "html";
                        $config['newline'] = "\r\n";

                        $ci->email->initialize($config);

                        $ci->email->from('enquiry@powersoftware.eu', 'Mataam');
                        //$list = array('xxx@gmail.com');
                        $ci->email->to($restro_email);
                        //$this->email->reply_to('my-email@gmail.com', 'Explendid Videos');
                        $ci->email->subject('Login');
                        $ci->email->message($mess);
                        $ci->email->attach(""); // attach file
                        $ci->email->send();		

                        redirect("/restaurant_registration/");
                    }
                } 
            }
            $this->load->view('restaurant_registration',$data);

        }


        function reservation_checkout(){
            $data['errors']=array();

            @$user_id = $_SESSION['Customer_User_Id'];

            $restro_id =$this->uri->segment('2');
            $location_id =$this->uri->segment('3');
            $_SESSION['order_restro_id'] = $restro_id;
            $_SESSION['order_location_id'] = $location_id;


            if(($restro_id == '') or ($location_id == ''))
            {
                redirect('/');
            }


            $restro = $this->RestaurantModel->findByRestroLocationService($restro_id, $location_id, 3); 
            $restro->reviews = $this->RatingModel->find(array('location_id'=>$location_id));
            $data['restroInfo'] = $restro;

            $data['reserve_date'] = $reserve_date = $_SESSION['reserve_date'];
            $data['reserve_time'] = $reserve_time = $this->input->get('reserve_time');
            $data['people_number'] = $_SESSION['people_number'];

            $weekday = strtolower(date('l', strtotime($reserve_date)));
            $data['seating_info'] = getSeatingInfo($restro_id, $location_id, $weekday, $reserve_time);


            if(isset($_POST['btncheckout']))
            {
                if(!isset($user_id)) redirect('customer_login');

                $this->form_validation->set_rules('people_number', 'Number of people', 'required');
                $this->form_validation->set_rules('reserve_date', 'Reservation data', 'required');
                $this->form_validation->set_rules('reserve_time', 'Reservation time', 'required');

                if ($this->form_validation->run() == FALSE)
                {

                }
                else
                {
                    $people_number = $this->input->post('people_number');
                    $reserve_date = $this->input->post('reserve_date');
                    $reserve_time = date('G:i', strtotime($this->input->post('reserve_time')));

                    $weekday = strtolower(date('l', strtotime($reserve_date)));
                    $seating_info = getSeatingInfo($restro_id, $location_id, $weekday, $reserve_time);
                    if($seating_info === null || !isAvailableTime($reserve_date, $reserve_time, $seating_info, $people_number, $restro_id, $location_id))  {
                        echo json_encode($seating_info);
                        echo json_encode($this->input->post());
                        echo "$restro_id:$location_id:$weekday:$reserve_time";
                        //throw new Exception($this->lang->line('time_invalid'), RESULT_ERROR_PARAMS_INVALID);
                        $data['errors'] = 'asdfasdf';$this->load->view('reservation_checkout',$data);
                    }
                    $largest_party_size = $seating_info['largest_party_size'];
                    if($largest_party_size > 0) {             
                        $order_table_count = floor($people_number / $largest_party_size) + 1;   
                    } else {
                        $order_table_count = 1;
                    }
                    $order['table_count'] = $order_table_count;
                    $order['number_of_people'] = $people_number;
                    $order['restro_id'] = $restro_id;
                    $order['location_id'] = $location_id;
                    $order['date'] = $reserve_date;  // Y-m-d
                    $order['time'] = $reserve_time;  // H:i
                    $order['user_id'] = $user_id;
                    $order['order_points'] = $seating_info['point'];
                    $order['total'] = $seating_info['deposit'];
                    /*$payment_method = $this->post('payment_method');
                    if(!isset($payment_method)) {
                    throw new Exception('payment_method '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                    } 
                    $order['payment_method'] = $payment_method;*/
                    $order['status'] = 1;                  
                    $order['created_time'] = $order['updated_time'] = date('Y-m-d H:i:s');
                    $order_id = $this->RestroTableOrderModel->create($order);
                    $this->RestroTableOrderModel->update($order_id, array("order_no"=>$this->config->item('Start_order_id').$order_id));
                    $order_details['order_id'] = $order_id;
                    $order = $this->RestroTableOrderModel->findById($order_id);

                    $orderNumber = $order->order_no;
                    $otpMSG =urlencode("Your Order Confirmed Successfully done, Your Order ID #$orderNumber");

                    $apiData = $this->Customer_management->getApiDetails(1);

                    $usernameApi = $apiData['username'];
                    $usernamePass = $apiData['password'];
                    $mobilenumber = @$mobileNumber;
                    $usernameSource = $apiData['username_source'];

                    $url = file_get_contents("http://103.16.101.52/sendsms/bulksms?username=$usernameApi&password=$usernamePass&destination=$mobilenumber&source=$usernameSource&message=$otpMSG");  


                    //order msg send here


                    // Update user points on profile                    
                    $user_profile = $this->UserProfileModel->findByUserId($user_id);
                    $user_loyalty_points = $user_profile->points; $user_mataam_points = $user_profile->mataam_points;                    

                    $user_loyalty_points += $seating_info['point'];

                    $mataam_point = getMataamPoint($user_id, 3, $restro_id, $location_id, $seating_info['deposit']); 
                    $user_mataam_points += $mataam_point['gained_points'];
                    $this->UserProfileModel->save($user_id, array(
                        'points'=>$user_loyalty_points,
                        'mataam_points'=>$user_mataam_points
                    ));
                    // Create Points Log
                    $this->PointLogModel->create(array(
                        'user_id'=>$user_id,
                        'service_id'=>3,
                        'order_id'=>$order_id,
                        'gained_loyalty_point'=>$seating_info['point'],
                        'used_loyalty_point'=>0,
                        'balance_loyalty_point'=>$user_loyalty_points,
                        'gained_mataam_point'=>$mataam_point['gained_points'],
                        'used_mataam_point'=>0,
                        'balance_mataam_point'=>$user_mataam_points
                    ));

                    redirect('/');
                }

            }
            $this->load->view('reservation_checkout',$data);

        }

        function ajax_cart_table_remove(){
            $data['errors']=array();
            $cart_id =$this->uri->segment('2');
            $user_id = $this->tank_auth->get_user_id();;
            $this->Home_Restro->ajax_cart_table_remove($cart_id);
            $data['cartData'] = $this->Home_Restro->view_my_cart($user_id);
            $this->load->view('ajax_cart_table_remove',$data);
        }

        function ajax_search_restaurants(){
            $data['errors']=array();

            $area = $this->input->post('area');
            $cusines = $this->input->post('cusines');
            $area = $this->input->post('area');
            $area = $this->input->post('area');

            $area = $_SESSION['order_area_id'] = $this->input->post('filter_area');
            $service =$this->input->post('filter_service');

            $restro_list = $this->RestaurantModel->find(array('service_type'=>$service,'area'=>$area));

            $data['restro_list'] = $restro_list;
            if($service == 1)
            {
                $this->load->view('ajax_restaurants_fetch_service',$data);
            }
            if($service == 2)
            {
                $this->load->view('ajax_restaurants_fetch_service_catering',$data);    
            }
            if($service == 3)
            {
                $this->load->view('ajax_restaurants_fetch_service_table',$data);	
            }
            if($service == 4)
            {
                $this->load->view('ajax_restaurants_fetch_service_pickup',$data);   
            }
        }


        //pickup all functions start here


        function Home_pickup_filter(){
            $data['errors']=array();
            $_SESSION['filter_service'] = 4;
            //$data['advt'] = $this->Advertise_management->GetAdevrtise_limit();

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit(6,3);


            $data['city'] = $this->Home_site->show_all_city();

            $filter_type =$this->input->post('filter_type');
            $pickup_search_txt =$this->input->post('pickup_search_txt');
            $city_id = $this->input->post('filter_city_id');

            if($filter_type != '')
            {


                if($filter_type != '')
                {
                    $_SESSION['filter_service'] = $filter_type;
                    $_SESSION['filter_city'] = $city_id;
                }

                if($pickup_search_txt != '')
                {
                    $_SESSION['search_txt'] = $pickup_search_txt;
                }
                else
                {
                    $_SESSION['search_txt'] = NULL;
                }
            }

            $data['retro_list']=$this->Home_Restro->all_restro_pickup($_SESSION['filter_service'],$_SESSION['search_txt'],$_SESSION['filter_city']);

            $data['service_list']=$this->Home_Restro->all_service();
            $data['cuisin_list']=$this->Home_Restro->all_cuisin();


            if(isset($_POST['filter_type']))
            {
                redirect('/pickup_filter/');
            }

            $this->load->view('restaurant_pickup_list',$data);


        }

        public function pickup_restaurant_view(){

            $data['errors']=array();
            $restro_id = $this->uri->segment('2');
            //$data['advt'] = $this->Advertise_management->GetAdevrtise_limit2();

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit2(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit2(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit2(6,3);

            $data['restroInfo'] = $this->Home_Restro->view_pickup_restro_details($restro_id);
            $data['restroCat'] = $this->Home_Restro->view_restro_cat_filter($restro_id);
            $data['restro_item'] = $this->Home_Restro->restro_item_list($restro_id);


            $this->load->view('pickup_restaurant_view',$data);
        }

        public function view_restro_pickup(){
            $data['errors']=array();
            $restro_id =$this->uri->segment('2');
            $item_id =$this->uri->segment('3');

            $data['restroInfo'] = $this->Home_Restro->view_restro_details($restro_id);
            $data['restroCat'] = $this->Home_Restro->view_restro_cat_filter($restro_id);
            $RestroUserId = $this->Home_Restro->getRestroUserId($restro_id);
            $data['restro_item_info'] = $this->Home_Restro->restro_item_details($RestroUserId,$item_id);

            if(isset($_POST['btnaddtocart'])){


                $item_id =$this->input->post('item_id');

                $quantity =$this->input->post('quantity'); 
                $item_name =$this->input->post('item_name');
                $item_price =$this->input->post('last_price');
                $img =$this->input->post('item_img');
                $spacial_request =$this->input->post('spacial_request');

                if($this->input->post('variation_ids') != '')
                {
                    $variation_ids = implode(',',$this->input->post('variation_ids'));
                }
                else
                {
                    $variation_ids = 0;
                }

                $data1 = array(
                    'id'      => $item_id,
                    'qty'     => $quantity,
                    'price'   => $item_price,
                    'name'    => $item_name,
                    'img'	=> $img,
                    'restro_id' => $restro_id,
                    'data' => "PICKUP",
                    'spacial_request' => $spacial_request,
                    'variation_id' => $variation_ids

                );

                $this->cart->insert($data1);




            }


            $data['cartData'] = $this->cart->contents();

            if(isset($_POST['addtocartbtn']))
            {
                $notes = $this->input->post('order_notes');

                foreach($data['cartData'] as $Dcart => $dataCart){
                    if($dataCart['data'] == 'PICKUP')
                    {
                        if($dataCart['variation_id'] == '')
                        {
                            $variation_id = 0;
                        }
                        else
                        {
                            $variation_id = $dataCart['variation_id'];
                        }

                        $CartArray['product_id'] = $dataCart['id'];
                        $CartArray['quantity'] = $dataCart['qty'];
                        $CartArray['price'] = $dataCart['price']; 
                        $CartArray['restro_id'] = $dataCart['restro_id'];
                        $CartArray['notes'] = $notes;
                        $CartArray['user_id'] = $_SESSION['Customer_User_Id'];
                        $CartArray['spacial_request'] = $dataCart['spacial_request']; 
                        $CartArray['variation_ids'] = $variation_id;

                        $this->Home_Restro->insert_picup_cart($CartArray);

                    }	


                }
                $this->cart->destroy();

                $_SESSION['order_restro_id'] = $restro_id;
                redirect("/pickup_checkout/");
            }
            $this->load->view('view_restro_pickup',$data);
        }
        public function pickup_checkout(){
            $data['errors']=array();
            $user_id = $_SESSION['Customer_User_Id'];
            $_SESSION['filter_service'] = 4;

            $Mnumber = $this->Customer_management->get_user_mobile_number($user_id);
            $mobileNumber = $Mnumber['mobile_no'];

            $customer_points = $this->Customer_management->get_customer_points($user_id);

            $data['cust_point'] = $customer_points;

            $data['cartData'] = $this->Home_Restro->view_my_pickup_cart($user_id);
            $data['addressData'] = $this->Home_Restro->get_customer_address_data($user_id);
            $data['getPaymentgateways'] = $this->Home_Restro->getPaymentgatewaysByService($_SESSION['order_restro_id'],$_SESSION['filter_service']);

            $data['deliveryCharges'] = $this->Home_Restro->getpickupChargesbyrestro($_SESSION['order_restro_id'],$_SESSION['filter_service']);

            $Loc = $this->Home_Restro->getrestroOrderLocationId2($_SESSION['order_restro_id'],$_SESSION['filter_service'],$_SESSION['filter_city']);


            if(($data['cartData'] == '') or ($user_id == ''))
            {
                redirect('/');
            }

            $datestring = "%Y-%m-%d";
            $timestring = "%h:%i %a";
            $time = time();

            $Mdate = mdate($datestring, $time);
            $Mtime = mdate($timestring, $time);

            if(isset($_POST['btncheckout']))
            {


                $this->form_validation->set_rules('useraddress', 'Address', 'required');
                $this->form_validation->set_rules('Ddate', 'Date', 'required');
                $this->form_validation->set_rules('Dtime', 'Time', 'required');
                $this->form_validation->set_rules('payment_type', 'Payment option', 'required');

                if ($this->form_validation->run() == FALSE)
                {

                }
                else
                {

                    $hd_total = $this->input->post('hd_total');
                    $hd_charges = $this->input->post('hd_charges');
                    $Ddate = $this->input->post('Ddate');
                    $Dtime = $this->input->post('Dtime');
                    $payment_type = $this->input->post('payment_type');
                    $addressid = $this->input->post('useraddress'); 
                    $extra_direction = $this->input->post('extra_direction');
                    $hd_points = $this->input->post('hd_points');
                    $discount_type = $this->input->post('discount_opt'); 
                    $hd_discount = $this->input->post('hd_discount');
                    $coupon_code = $this->input->post('coupon_code');
                    $hd_used_points = $this->input->post('hd_used_points');


                    if($discount_type == 1)
                    {
                        $order['coupon_point_apply'] = $discount_type;
                        $order['discount_amount'] = $hd_discount;
                        $order['coupon_code'] = $coupon_code;
                    }
                    if($discount_type == 2)
                    {
                        $order['coupon_point_apply'] = $discount_type; 
                        $order['discount_amount'] = $hd_discount;
                        $order['used_points'] = $hd_used_points;

                        $update_points['points'] = $data['cust_point'] - $hd_used_points;
                        $this->Customer_management->update_customer_points($user_id,$update_points);

                    }

                    $customer_points = $this->Customer_management->get_customer_points($user_id);

                    $order['delivery_date'] = date('Y-m-d',strtotime($Ddate));
                    $order['delivery_time'] = date('H:i:s',strtotime($Dtime));

                    $order['total'] = $hd_total;
                    $order['delivery_charges'] = $hd_charges;
                    $order['date'] = $Mdate;
                    $order['time'] = date('H:i:s',strtotime($Mtime));
                    $order['user_id'] = $user_id;
                    $order['payment_method'] = $payment_type;
                    $order['status'] = 1;
                    $order['address_id'] = $addressid;
                    $order['extra_direction'] = $extra_direction;
                    $order['order_points'] = $hd_points;
                    $order['restro_location_id'] = $Loc['location_id'];



                    $getId = $this->Home_Restro->add_pickup_order($order);


                    $orderDetails['order_id'] = $getId;

                    $updatedata['order_no'] = $this->config->item('Start_Pickup_id').$getId;

                    $this->Home_Restro->orderNo_update_pickup($updatedata,$getId);

                    foreach($data['cartData'] as $DA => $DA_Cart){
                        $orderDetails['product_id'] = $DA_Cart->product_id;
                        $orderDetails['price'] = $DA_Cart->price;
                        $orderDetails['quantity'] = $DA_Cart->quantity;
                        $orderDetails['restro_id'] = $DA_Cart->restro_id;
                        $orderDetails['notes'] = $DA_Cart->notes;
                        $orderDetails['user_id'] = $DA_Cart->user_id;
                        $orderDetails['variation_ids'] = $DA_Cart->variation_ids;

                        $this->Home_Restro->add_order_details_pickup($orderDetails);
                    }

                    $updatePoint['points'] = $customer_points+$hd_points;
                    $this->Customer_management->update_customer_points($user_id,$updatePoint);

                    //order msg send here

                    $orderNumber = $updatedata['order_no'];
                    $otpMSG =urlencode("Your Order Confirmed Successfully done, Your Order ID #$orderNumber");

                    $apiData = $this->Customer_management->getApiDetails(1);

                    $usernameApi = $apiData['username'];
                    $usernamePass = $apiData['password'];
                    $mobilenumber = @$mobileNumber;
                    $usernameSource = $apiData['username_source'];

                    $url = file_get_contents("http://103.16.101.52/sendsms/bulksms?username=$usernameApi&password=$usernamePass&destination=$mobilenumber&source=$usernameSource&message=$otpMSG");  


                    //order msg send here

                    $this->Home_Restro->empty_my_cart_pickup($user_id);


                    if($payment_type == 4)
                    {
                        $_SESSION['pay_type'] = 4;
                        $_SESSION['pay_order_id'] = $getId;
                        $_SESSION['pay_amount'] = $hd_total+$hd_charges;
                        $_SESSION['pay_method'] = 4;
                        $_SESSION['pay_discount'] = $order['discount_amount'];
                        $_SESSION['pay_order_no'] = $updatedata['order_no'];

                        redirect('/Paypal');


                    }
                    else
                    {
                        redirect('/');
                    }
                }
            }

            $this->load->view('pickup_checkout',$data);
        }

        public function ajax_cart_pickup_remove(){
            $data['errors']=array();
            $cart_id = $this->input->post('item');
            $user_id = $this->tank_auth->get_user_id();
            $this->Home_Restro->ajax_cart_pickup_remove($cart_id);
            $data['cartData'] = $this->Home_Restro->view_my_pickup_cart($user_id);
            $this->load->view('ajax_cart_pickup_remove',$data);
        }
        //pickup all functions End here


        //catering all functions start here 

        function Home_catering_filter(){
            $data['errors']=array();
            $_SESSION['filter_service'] =2;
            //$data['advt'] = $this->Advertise_management->GetAdevrtise_limit();

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit(6,3);

            $data['city'] = $this->Home_site->show_all_city();

            $filter_type =$this->input->post('filter_type');
            $catering_search_txt =$this->input->post('catering_search_txt'); 
            $filter_city_id =$this->input->post('filter_city_id');
            $cat_date = $this->input->post('cat_date');
            $cat_time = $this->input->post('cat_time');

            if($filter_type != '')
            {
                $_SESSION['filter_service'] = $filter_type;
            }

            if($catering_search_txt != '')
            {
                $_SESSION['search_txt'] = $catering_search_txt;
            }
            else
            {
                $_SESSION['search_txt'] = NULL;
            }
            if($filter_city_id != '')
            {
                $_SESSION['filter_city'] = $filter_city_id;
            }
            if($cat_date != '')
            {
                $_SESSION['cat_date'] = $cat_date;
            }

            if($cat_time != '')
            {
                $_SESSION['cat_time'] = $cat_time;
            }


            $data['retro_list']=$this->Home_Restro->all_restro_catering($_SESSION['filter_service'],$_SESSION['search_txt'],$_SESSION['filter_city']);

            $data['service_list']=$this->Home_Restro->all_service();
            $data['cuisin_list']=$this->Home_Restro->all_cuisin();


            if(isset($_POST['filter_type']))
            {
                redirect('/catering_filter/');
            }
            $this->load->view('restaurant_catering_list',$data);



        } 

        public function catering_restaurant_view(){
            //$data['advt'] = $this->Advertise_management->GetAdevrtise_limit2();

            $data['advt1'] = $this->Advertise_management->GetAdevrtise_limit2(0,3);
            $data['advt2'] = $this->Advertise_management->GetAdevrtise_limit2(3,3);
            $data['advt3'] = $this->Advertise_management->GetAdevrtise_limit2(6,3);

            $data['errors']=array();
            $restro_id = $this->uri->segment('2');

            $data['restroInfo'] = $this->Home_Restro->view_catering_restro_details($restro_id);
            $data['restroCat'] = $this->Home_Restro->view_restro_cat_filter($restro_id);
            $data['restro_item'] = $this->Home_Restro->restro_item_list($restro_id);



            $this->load->view('catering_restaurant_view',$data);
        } 

        public function view_restro_catering(){
            $data['errors']=array();
            $restro_id =$this->uri->segment('2');
            $item_id =$this->uri->segment('3');
            $RestroUserId = $this->Home_Restro->getRestroUserId($restro_id);
            $data['restroInfo'] = $this->Home_Restro->view_restro_details($restro_id);
            $data['restroCat'] = $this->Home_Restro->view_restro_cat_filter($restro_id);
            $data['restro_item_info'] = $this->Home_Restro->restro_item_details($RestroUserId,$item_id);

            if(isset($_POST['btnaddtocart'])){


                $item_id =$this->input->post('item_id');

                $quantity =$this->input->post('quantity'); 
                $item_name =$this->input->post('item_name');
                $item_price =$this->input->post('last_price');
                $img =$this->input->post('item_img');
                $spacial_request =$this->input->post('spacial_request');

                if($this->input->post('variation_ids') != '')
                {
                    $variation_ids = implode(',',$this->input->post('variation_ids'));
                }
                else
                {
                    $variation_ids = 0;
                }


                $data1 = array(
                    'id'      => $item_id,
                    'qty'     => $quantity,
                    'price'   => $item_price,
                    'name'    => $item_name,
                    'img'	=> $img,
                    'restro_id' => $restro_id,
                    'data' => "CATERING",
                    'spacial_request' => $spacial_request,
                    'variation_id' => $variation_ids

                );

                $this->cart->insert($data1);




            }


            $data['cartData'] = $this->cart->contents();


            if(isset($_POST['addtocartbtn']))
            {
                $notes = $this->input->post('order_notes');

                foreach($data['cartData'] as $Dcart => $dataCart){
                    if($dataCart['data'] == 'CATERING')
                    {
                        if($dataCart['variation_id'] == '')
                        {
                            $variation_id = 0;
                        }
                        else
                        {
                            $variation_id = $dataCart['variation_id'];
                        }

                        $CartArray['product_id'] = $dataCart['id'];
                        $CartArray['quantity'] = $dataCart['qty'];
                        $CartArray['price'] = $dataCart['price']; 
                        $CartArray['restro_id'] = $dataCart['restro_id'];
                        $CartArray['notes'] = $notes;
                        $CartArray['user_id'] = $_SESSION['Customer_User_Id'];
                        $CartArray['spacial_request'] = $dataCart['spacial_request']; 
                        $CartArray['variation_ids'] = $variation_id;

                        $this->Home_Restro->insert_catering_cart($CartArray);

                    }	

                }
                $this->cart->destroy();
                $_SESSION['order_restro_id'] = $restro_id;
                redirect("/catering_checkout/");
            }
            $this->load->view('view_restro_catering',$data);
        }

        function catering_checkout(){
            $data['errors']=array();
            $user_id = $_SESSION['Customer_User_Id'];
            $_SESSION['filter_service'] = 2;

            $Mnumber = $this->Customer_management->get_user_mobile_number($user_id);
            $mobileNumber = $Mnumber['mobile_no'];

            $customer_points = $this->Customer_management->get_customer_points($user_id);

            $data['cust_point'] = $customer_points;

            $data['cartData'] = $this->Home_Restro->view_my_catering_cart($user_id);
            $data['addressData'] = $this->Home_Restro->get_customer_address_data($user_id);

            $data['getPaymentgateways'] = $this->Home_Restro->getPaymentgatewaysByService($_SESSION['order_restro_id'],$_SESSION['filter_service']);

            $data['deliveryCharges'] = $this->Home_Restro->getDeliveryChargesbyrestrolocation($_SESSION['order_restro_id'],$_SESSION['filter_service'],$_SESSION['filter_city']);

            $Loc = $this->Home_Restro->getrestroOrderLocationId($_SESSION['order_restro_id'],$_SESSION['filter_service'],$_SESSION['filter_city']);



            if(($data['cartData'] == '') or ($user_id == ''))
            {
                redirect('/');
            }

            $datestring = "%Y-%m-%d";
            $timestring = "%h:%i %a";
            $time = time();

            $Mdate = mdate($datestring, $time);
            $Mtime = mdate($timestring, $time);

            if(isset($_POST['btncheckout']))
            {


                $this->form_validation->set_rules('useraddress', 'Address', 'required');
                $this->form_validation->set_rules('Ddate', 'Delivery Date', 'required');
                $this->form_validation->set_rules('Dtime', 'Delivery Time', 'required');
                $this->form_validation->set_rules('payment_type', 'Payment Option', 'required');

                if ($this->form_validation->run() == FALSE)
                {

                }
                else
                {

                    $hd_total = $this->input->post('hd_total');
                    $hd_charges = $this->input->post('hd_charges');
                    $Ddate = $this->input->post('Ddate');
                    $Dtime = $this->input->post('Dtime');
                    $payment_type = $this->input->post('payment_type');
                    $addressid = $this->input->post('useraddress'); 
                    $extra_direction = $this->input->post('extra_direction');
                    $hd_points = $this->input->post('hd_points');
                    $discount_type = $this->input->post('discount_opt'); 
                    $hd_discount = $this->input->post('hd_discount');
                    $coupon_code = $this->input->post('coupon_code');
                    $hd_used_points = $this->input->post('hd_used_points');


                    if($discount_type == 1)
                    {
                        $order['coupon_point_apply'] = $discount_type;
                        $order['discount_amount'] = $hd_discount;
                        $order['coupon_code'] = $coupon_code;
                    }
                    if($discount_type == 2)
                    {
                        $order['coupon_point_apply'] = $discount_type; 
                        $order['discount_amount'] = $hd_discount;
                        $order['used_points'] = $hd_used_points;

                        $update_points['points'] = $data['cust_point'] - $hd_used_points;
                        $this->Customer_management->update_customer_points($user_id,$update_points);

                    }

                    $customer_points = $this->Customer_management->get_customer_points($user_id);




                    $order['total'] = $hd_total;
                    $order['delivery_charges'] = $hd_charges;
                    $order['date'] = $Ddate;
                    $order['time'] = $Dtime;
                    $order['user_id'] = $user_id;
                    $order['payment_method'] = $payment_type;
                    $order['status'] = 1;
                    $order['address_id'] = $addressid;
                    $order['extra_direction'] = $extra_direction;
                    $order['order_points'] = $hd_points;

                    if($Loc['location_id'] != '')
                    {
                        $order['restro_location_id'] = $Loc['location_id'];
                    }



                    $getId = $this->Home_Restro->add_catering_order($order);


                    $orderDetails['order_id'] = $getId;

                    $updatedata['order_no'] = $this->config->item('Start_Catering_id').$getId;

                    $this->Home_Restro->orderNo_update_catering($updatedata,$getId);

                    foreach($data['cartData'] as $DA => $DA_Cart){
                        $orderDetails['product_id'] = $DA_Cart->product_id;
                        $orderDetails['price'] = $DA_Cart->price;
                        $orderDetails['quantity'] = $DA_Cart->quantity;
                        $orderDetails['restro_id'] = $DA_Cart->restro_id;
                        $orderDetails['notes'] = $DA_Cart->notes;
                        $orderDetails['user_id'] = $DA_Cart->user_id;
                        $orderDetails['variation_ids'] = $DA_Cart->variation_ids;

                        $this->Home_Restro->add_order_details_catering($orderDetails);
                    }

                    $updatePoint['points'] = $customer_points+$hd_points;
                    $this->Customer_management->update_customer_points($user_id,$updatePoint);


                    //order msg send here

                    $orderNumber = $updatedata['order_no'];
                    $otpMSG =urlencode("Your Order Confirmed Successfully done, Your Order ID #$orderNumber");

                    $apiData = $this->Customer_management->getApiDetails(1);

                    $usernameApi = $apiData['username'];
                    $usernamePass = $apiData['password'];
                    $mobilenumber = @$mobileNumber;
                    $usernameSource = $apiData['username_source'];

                    $url = file_get_contents("http://103.16.101.52/sendsms/bulksms?username=$usernameApi&password=$usernamePass&destination=$mobilenumber&source=$usernameSource&message=$otpMSG");  


                    //order msg send here


                    $this->Home_Restro->empty_my_cart_catering($user_id);

                    if($payment_type == 4)
                    {
                        $_SESSION['pay_type'] = 2;
                        $_SESSION['pay_order_id'] = $getId;
                        $_SESSION['pay_amount'] = $hd_total+$hd_charges;
                        $_SESSION['pay_method'] = 4;
                        $_SESSION['pay_discount'] = $order['discount_amount'];
                        $_SESSION['pay_order_no'] = $updatedata['order_no'];

                        redirect('/Paypal');


                    }
                    else
                    {
                        redirect('/');
                    }
                }
            }

            $this->load->view('catering_checkout',$data);
        }

        //catering all functions end here 


        //Custome custommer data

        public function ajaxaddressFetch(){
            $data['errors']=array();
            $user_id = $_SESSION['Customer_User_Id'];

            $address_id = $this->input->post('address');

            $data['addressData'] = $this->Home_Restro->ajaxaddressFetch_checkout($user_id,$address_id);

            //print_r($data['addressData']);

            $this->load->view('ajaxaddressFetch_checkout',$data);	
        }

        //Custome custommer data

        function ajax_item_variation_price(){
            $data['errors']=array();
            $variation_id = $this->input->post('variation_id');
            $item_id = $this->input->post('item_id');


            echo $price = $this->Home_Restro->ajax_item_variation_price($variation_id,$item_id);

        }

        function ajax_suggestions(){
            $data['errors']=array();
            $searchtext = $this->input->post('textsearch');
            $divid = $this->input->post('divid');

            $data['cuisin_list'] = $this->Home_Restro->find_cuisine_by_name($searchtext);
            $data['restro_list'] = $this->Home_Restro->find_restaurant_by_name($searchtext); 
            $data['food_list'] = $this->Home_Restro->find_food_by_name($searchtext);
            $data['divid'] = $divid;

            $this->load->view('ajax_suggestions',$data);	
        }



        function search_area_by_name(){
            $data['errors']=array();
            $searchtext = $this->input->post('textsearch');
            $divid = $this->input->post('divid');

            $data['area_list'] = $this->Home_Restro->search_area_by_name($searchtext);

            $data['divid'] = $divid;

            $this->load->view('ajax_search_area_by_name',$data);	
        }

        function search_restro_by_name(){
            $data['errors']=array();
            $searchtext = $this->input->post('textsearch');
            $urltype = $this->input->post('urltype');


            if($urltype == 1)
            {
                $data['r_url'] = 'restaurant_view';
            }
            elseif($urltype == 2)
            {
                $data['r_url'] = 'catering_restaurant';
            }
            elseif($urltype == 3)
            {
                $data['r_url'] = 'reservation_tabel';
            }
            elseif($urltype == 4)
            {
                $data['r_url'] = 'pickup_restaurant';
            }

            $data['restro_list'] = $this->Home_Restro->search_restro_by_name($searchtext, $_SESSION['filter_service']);

            $this->load->view('search_restro_by_name',$data);	
        }

        public function ajax_resrvation_booking_time(){
            $data['errors']=array();
            $res_time = $this->input->post('res_time');
            $restro_id = $_SESSION['order_restro_id'];
            $data['time'] = $res_time;


            $this->load->view('ajax_reservation_booking_time',$data);
        }

        public function Home_Restro_Filter(){

            $filter_id = $this->uri->segment('2');

            $_SESSION['filter_id'] = $filter_id;
            $_SESSION['filter_service'] = 1;

            redirect('/filter');


        }
        public function getCaptch(){

            $val1 = rand(0,9);
            $val2 = rand(0,9);
            $val3 = rand(0,9);
            $val4 = rand(0,9);

            $capcha_val = $val1.$val2.$val3.$val4;

            echo $_SESSION['capcha_val'] = $capcha_val;
        }


        public function customer_otp(){

            $uid = $this->session->userdata('user_id'); 

            $mob=$this->Customer_management->get_mobile($uid);
            $mobileNumber = $mob;                   
            $alphabet = '1234567890';    
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            $number = '';
            for ($i = 0; $i < 6; $i++) {
                $n = rand(0, $alphaLength);           
                $number = $number."".$alphabet[$n];
            }  
            $number;

            $otp_gen = $number;
            $otpMSG =urlencode("MATTAM OTP is $otp_gen");

            $apiData = $this->Customer_management->getApiDetails(1);

            $usernameApi = $apiData['username'];
            $usernamePass = $apiData['password'];
            $mobilenumber = @$mobileNumber;
            $usernameSource = $apiData['username_source'];

            $url = file_get_contents("http://103.16.101.52/sendsms/bulksms?username=$usernameApi&password=$usernamePass&destination=$mobilenumber&source=$usernameSource&message=$otpMSG");

            $user['otp'] = $otp_gen;
            $user['otp_status'] = 1;



            $this->Customer_management->update_otp($user,$mob);


            $this->load->view('customer_otp',$mob);
        }

        public function check_otp(){
            $uid = $this->session->userdata('user_id');
            $mob = $this->Customer_management->get_mobile($uid);
            $checkotp = $this->Customer_management->get_otp($mob);
            $otp = $this->input->post('otp');	
            if($otp !=$checkotp)
            {
                echo 1;
            }else 
            { 
                echo 2;
            }

        }

        public function restaurant_profile($restro_id, $location_id=null){
            //$data['advt'] = $this->Advertise_management->GetAdevrtise_limit2();


            $restaurant = $this->RestaurantModel->findById($restro_id);

            $cuisines = $this->CuisineModel->findByRestroId($restro_id);
            $cuisine_names = array();
            foreach($cuisines as $cuisine) {
                $cuisine_names[] = $cuisine->name;
            }
            $restaurant->cuisines = $cuisine_names;

            $food_types = $this->FoodTypeModel->findByRestroId($restro_id);            
            $type_names = array();
            foreach($food_types as $type) {
                $type_names[] = $type->name;
            } 
            $restaurant->food_types = $type_names;

            $categories = $this->RestroCategoryModel->findByRestroId($restro_id);            
            $category_names = array();
            foreach($categories as $category) {
                $category_names[] = $category->name;
            }
            $restaurant->categories = $category_names;

            $restaurant->locations = $this->RestroLocationModel->findByRestroId($restro_id);

            $data['restaurant'] = $restaurant;

            // Config Map Data
            $this->load->library('googlemaps');

            $center = '0,0';
            if($location_id!==null) {
                $location = $this->RestroLocationModel->findById($location_id);

                $services = $this->RestroServiceCommissionModel->findByRestroLocationId($restro_id, $location_id);

                foreach($services as $service) {
                    $service->working_hour = $this->RestroWorkingHourModel->findOne(array('restro_id'=>$restro_id,'location_id'=>$location_id,'service_id'=>$service->id));
                    $service->payment = $this->RestroPaymentMethodModel->findOne(array('restro_id'=>$restro_id,'location_id'=>$location_id,'service_id'=>$service->id));
                    if($service->id==1 || $service->id==2) {
                        $restro_area = $this->RestroCityAreaModel->findOne(array('restro_id'=>$restro_id,'location_id'=>$location_id,'service_id'=>$service->id));   
                        $area_ids = explode(',', $restro_area->area);
                        $charges = explode(',', $restro_area->delivery_price); 

                        $areas = array();
                        foreach($area_ids as $index=>$area_id) {
                            $area = $this->AreaModel->findOne(array('id'=>$area_id));
                            $areas[] = array('area_id'=>$area_id, 'area_name'=>$area->name.', '.$area->city_name, 'charge_amount'=>$charges[$index]);
                        }

                        $service->areas = $areas;
                    }

                    if($service->id == 3) {
                        $seatings = $this->RestroSeatingHourModel->find(array('restro_id'=>$restro_id, 'location_id'=>$location_id));                       
                        $seating_infos = array();
                        foreach($seatings as $seating) {
                            $seating_infos[$seating->category] = $seating;
                        }

                        $service->seating_infos = $seating_infos;
                    }
                }

                $location->services = $services;
                $data['location'] = $location;
                $center = $location->latitude.','.$location->longitude;
            }
            $config['center'] = $center;
            $config['zoom'] = '15';
            $this->googlemaps->initialize($config);

            $marker = array();
            $marker['position'] = $center;
            $marker['onclick'] = 'alert("You just clicked me!!")';
            $this->googlemaps->add_marker($marker);
            $data['map'] = $this->googlemaps->create_map();


            $this->load->view('restaurant_profile',$data);
        }

















}
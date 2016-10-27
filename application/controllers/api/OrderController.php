<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    // This can be removed if you use __autoload() in config.php OR use Modular Extensions
    require 'MyRestController.php';


    class OrderController extends MyRestController {
        function __construct()
        {
            // Construct the parent class
            parent::__construct();                  


            $this->load->model('RestroItemModel'); 
            $this->load->model('CartModel'); 
            $this->load->model('RestaurantModel');  
            $this->load->model('CouponModel'); 
            $this->load->model('LoyaltyPointModel'); 
            $this->load->model('MataamPointModel'); 
            $this->load->model('RestroCityAreaModel'); 
            $this->load->model('OrderModel'); 
            $this->load->model('OrderDetailModel'); 
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

                $service_type = $this->get('service_type');
                if(!isset($service_type)) {
                    throw new Exception('service_type '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                if ($id === NULL)
                {               
                    $params = array();
                    $params["user_id"] = $this->user->id;

                    $resource = $this->OrderModel->find($service_type, $params); 
                } else {                         
                    $resource = $this->OrderModel->findById($service_type, $id); 
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

        public function details_get($id)
        {                 
            try {                
                $this->validateAccessToken();

                $service_type = $this->get('service_type');
                if(!isset($service_type)) {
                    throw new Exception('service_type '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                if(!isset($id)) {
                    throw new Exception('id '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $resource = $this->OrderDetailModel->find($service_type, array('order_id'=>$id));

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
                $this->validateAccessToken();

                $service_type = $this->input->get('service_type');
                if(!isset($service_type)) {
                    throw new Exception("service_type ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }
                $area_id = $this->input->get('area_id');
                if(!isset($area_id)) {
                    throw new Exception("area_id ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $restro_id = $this->input->get('restro_id');
                if(!isset($restro_id)) {
                    throw new Exception("restro_id ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $location_id = $this->input->get('location_id');
                if(!isset($location_id)) {
                    throw new Exception("location_id ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $redeem_type = $this->post('redeem_type');
                $coupon_code = $this->post('coupon_code');

                $sum = $this->getSum($this->user->id, $service_type, $restro_id, $area_id);

                $order['total'] = $sum['total_amount'];
                $order['delivery_charges'] = $sum['charge_amount'];;

                if(isset($redeem_type)) {

                    $discount = $this->getDiscount($redeem_type, $this->user->id, $service_type, $restro_id, $location_id, $coupon_code);
                    $order['discount_amount'] = $discount['discount_amount'];

                    $order['coupon_point_apply'] = $redeem_type;                

                    $point = $this->getPoint($this->user->id, $service_type, $restro_id, $location_id);                

                    if($redeem_type == 1) { //  Redeem Coupon
                        if(!isset($coupon_code)) {
                            throw new Exception('coupon_code '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                        }
                        $order['coupon_code'] = $coupon_code;
                    } else if($redeem_type == 2) {
                        $order['used_points'] = $point['loyalty']['used_points'];
                    } else if($redeem_type == 3) {
                        $order['used_points'] = $point['mataam']['used_points'];
                    }   
                }  

                $order['order_points'] = $point['loyalty']['gained_points'];       



                $schedule_date = $this->post('schedule_date');
                if(!isset($schedule_date)) {
                    throw new Exception('schedule_date '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }
                $schedule_time = $this->post('schedule_time');
                if(!isset($schedule_time)) {
                    throw new Exception('schedule_time '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                if($service_type==1) {                    
                    $order['delivery_date'] = $schedule_date;  // Y-m-d
                    $order['delivery_time'] = $schedule_time;  // H:i:s
                }
                $order['date'] = $schedule_date;  // Y-m-d
                $order['time'] = $schedule_time;  // H:i:s


                $order['user_id'] = $this->user->id;

                $payment_method = $this->post('payment_method');
                if(!isset($payment_method)) {
                    throw new Exception('payment_method '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                } 
                $order['payment_method'] = $payment_method;

                $order['status'] = 1; 

                $address_id = $this->post('address_id');
                if(!isset($address_id)) {
                    throw new Exception('address_id '.$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }
                $order['address_id'] = $address_id;

                $order['extra_direction'] = $this->post('extra_direction'); 



                $location = $this->RestroCityAreaModel->getRestroOrderLocation($restro_id, $service_type, $area_id);
                if($location['location_id'] != '')
                {
                    $order['restro_location_id'] = $location['location_id'];
                }

                $order['created_time'] = $order['updated_time'] = date('Y-m-d H:i:s');
                $order_id = $this->OrderModel->create($service_type, $order);

                $this->OrderModel->update($service_type, $order_id, array("order_no"=>$this->config->item('Start_order_id').$order_id));


                $order_details['order_id'] = $order_id;

                $carts = $this->CartModel->find($service_type, array("user_id"=>$this->user->id, "restro_id"=>$restro_id));

                foreach($carts as $cart){
                    $order_details['product_id'] = $cart->product_id;
                    $order_details['price'] = $cart->price;
                    $order_details['quantity'] = $cart->quantity;
                    $order_details['restro_id'] = $cart->restro_id;
                    $order_details['notes'] = $cart->notes;
                    $order_details['user_id'] = $cart->user_id;
                    $order_details['variation_ids'] = $cart->variation_ids;

                    $this->OrderDetailModel->create($service_type, $order_details);
                }

                $this->CartModel->deleteAll($service_type, $this->user->id);

                $params = array();

                $order = $this->OrderModel->findById($service_type, $order_id);
                $order->details = $this->OrderDetailModel->find($service_type, array('order_id'=>$order_id));

                // Update user loyalty points on profile
                $gained_points = $discount["gained_points"];
                $remain_points = $this->user->profile->points - $used_points + $gained_points;
                // Update user mataam points on profile
                $gained_mataam_points = 
                $remain_points = $this->user->profile->mataam_points - $used_points + $gained_points;
                $this->UserProfileModel->save($this->user->id, array('mataam_points'=>$remain_points));
                $this->UserProfileModel->save($this->user->id, array('points'=>$remain_points));

                $this->response(array(
                    "code"=>RESULT_SUCCESS,    
                    "resource"=>$order
                    ), REST_Controller::HTTP_OK);

            } catch (Exception $e) {
                $this->response(array(
                    "code"=>$e->getCode(),
                    "message"=>$e->getMessage()
                    ), REST_Controller::HTTP_OK);
            }
        } 

        public function cart_post()
        {                 
            try {                
                $this->validateAccessToken();

                $service_type = $this->input->get('service_type');
                if(!isset($service_type)) {
                    throw new Exception("service_type ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }
                $product_id = $this->post('product_id');
                if(!isset($product_id)) {
                    throw new Exception("product_id ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }
                $quantity = $this->post('quantity');
                if(!isset($quantity) || $quantity<=0) {
                    throw new Exception("quantity ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                } 
                $variation_ids = $this->post('variation_ids');

                $item = $this->RestroItemModel->findById($product_id);
                if(!$item) {
                    throw new Exception("Product ".$this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND);
                }
                $params = array();


                $params["user_id"] = $this->user->id;                                                        
                $params["product_id"] = $product_id;
                $params["quantity"] = $quantity;
                $params["price"] = $item->price;
                $params["restro_id"] = $item->restro_id;

                $params["spacial_request"] = $this->post('spacial_request');
                $params["variation_ids"] = isset($variation_ids) ? $variation_ids : 0;    // variation ids string delimited by comma(,)
                $params["date"] = date("Y-m-d H:i:s");

                $insert_id = $this->CartModel->create($service_type, $params);
                $resource = $this->CartModel->findById($service_type, $insert_id);

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

        public function cart_put($id)
        {                 
            try {                
                $this->validateAccessToken();

                if(!isset($id)) {
                    throw new Exception("id ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $service_type = $this->input->get('service_type');
                if(!isset($service_type)) {
                    throw new Exception("service_type ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }
                $product_id = $this->put('product_id');
                if(!isset($product_id)) {
                    throw new Exception("product_id ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }
                $quantity = $this->put('quantity');
                if(!isset($quantity) || $quantity<=0) {
                    throw new Exception("quantity ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                } 

                $item = $this->RestroItemModel->findById($product_id);

                $params = array();


                $params["user_id"] = $this->user->id;                                                        
                $params["product_id"] = $product_id;
                $params["quantity"] = $quantity;
                $params["price"] = $item->price;
                $params["restro_id"] = $item->restro_id;

                $params["spacial_request"] = $this->put('spacial_request');
                $params["variation_ids"] = $this->put('variation_ids');    // variation ids string delimited by comma(,)
                $params["date"] = date("Y-m-d H:i:s");

                $this->CartModel->update($service_type, $id, $params);
                $resource = $this->CartModel->findById($service_type, $id);

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

        public function cart_delete($id)
        {                 
            try {                
                $this->validateAccessToken();

                if(!isset($id)) {
                    throw new Exception("id ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $service_type = $this->input->get('service_type');
                if(!isset($service_type)) {
                    throw new Exception("service_type ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $this->CartModel->delete($service_type, $id); 

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

        public function cart_get($id=null)
        {                 
            try {                
                $this->validateAccessToken();

                $service_type = $this->get('service_type');

                if($id == null) {
                    $params = array();
                    $params["user_id"] = $this->user->id;

                    $restro_id = $this->get('restro_id');
                    if(isset($restro_id)) $params["restro_id"] = $restro_id;

                    if(isset($service_type)) {
                        $carts = $this->CartModel->find($service_type, $params);
                    } else {
                        $carts = array_merge($this->CartModel->find(1, $params), $this->CartModel->find(2, $params), $this->CartModel->find(3, $params), $this->CartModel->find(4, $params));                        
                    }       

                    if(!$carts) {
                        throw new Exception($this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND); 
                    }

                    foreach($carts as $cart) {
                        $cart->item = $this->RestroItemModel->findById($cart->product_id);
                        $cart->restaurant = $this->RestaurantModel->findById($cart->restro_id);
                    }
                    $resource = $carts;  
                } else {
                    if(!isset($service_type)) {
                        throw new Exception("service_type ".$this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                    }
                    $cart = $this->CartModel->findById($service_type, $id);
                    if(!$cart) {
                        throw new Exception($this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND); 
                    }

                    $cart->item = $this->RestroItemModel->findById($cart->product_id);
                    $cart->restaurant = $this->RestaurantModel->findById($cart->restro_id);

                    $resource = $cart;
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

        public function sum_get()
        {                 
            try {                
                $this->validateAccessToken();

                $service_type = $this->get('service_type');                 
                if(!isset($service_type)) {
                    throw new Exception('service_type ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $restro_id = $this->get('restro_id');
                if(!isset($restro_id)) {
                    throw new Exception('restro_id ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $area_id = $this->get('area_id');
                if(!isset($area_id)) {
                    throw new Exception('area_id ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $this->response(array(
                    "code"=>RESULT_SUCCESS,    
                    "resource"=>$this->getSum($this->user->id, $service_type, $restro_id, $area_id)
                    ), REST_Controller::HTTP_OK);

            } catch (Exception $e) {
                $this->response(array(
                    "code"=>$e->getCode(),
                    "message"=>$e->getMessage()
                    ), REST_Controller::HTTP_OK);
            }
        } 

        public function discount_get()
        {                 
            try {                
                $this->validateAccessToken();

                $redeem_type = $this->get('redeem_type');                 
                if(!isset($redeem_type)) {
                    throw new Exception('redeem_type ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $service_type = $this->get('service_type');                 
                if(!isset($service_type)) {
                    throw new Exception('service_type ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $restro_id = $this->get('restro_id');
                if(!isset($restro_id)) {
                    throw new Exception('restro_id ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $location_id = $this->get('location_id');
                if(!isset($restro_id)) {
                    throw new Exception('location_id ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $coupon_code = $this->get('coupon_code');

                $this->response(array(
                    "code"=>RESULT_SUCCESS,    
                    "resource"=>$this->getDiscount($redeem_type, $this->user->id, $service_type, $restro_id, $location_id, $coupon_code)
                    ), REST_Controller::HTTP_OK);

            } catch (Exception $e) {
                $this->response(array(
                    "code"=>$e->getCode(),
                    "message"=>$e->getMessage()
                    ), REST_Controller::HTTP_OK);
            }
        } 

        public function point_get()
        {                 
            try {                
                $this->validateAccessToken();

                $service_type = $this->get('service_type');                 
                if(!isset($service_type)) {
                    throw new Exception('service_type ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $restro_id = $this->get('restro_id');
                if(!isset($restro_id)) {
                    throw new Exception('restro_id ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $location_id = $this->get('location_id');
                if(!isset($restro_id)) {
                    throw new Exception('location_id ' . $this->lang->line('parameter_required'), RESULT_ERROR_PARAMS_INVALID);
                }

                $this->response(array(
                    "code"=>RESULT_SUCCESS,    
                    "resource"=>$this->getPoint($this->user->id, $service_type, $restro_id, $location_id)
                    ), REST_Controller::HTTP_OK);

            } catch (Exception $e) {
                $this->response(array(
                    "code"=>$e->getCode(),
                    "message"=>$e->getMessage()
                    ), REST_Controller::HTTP_OK);
            }
        } 

        public function getPoint($user_id, $service_type, $restro_id, $location_id) {                       

            $carts = $this->CartModel->find($service_type, array(
                "user_id"   => $user_id, 
                "restro_id" => $restro_id
            ));       

            if(!$carts) {
                throw new Exception('Cart list ' . $this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND); 
            }

            $total_amount = 0; 
            $loyalty_gained_points = 0;
            foreach($carts as $cart) {
                $total_amount += $cart->price * $cart->quantity;

                $item = $this->RestroItemModel->findById($cart->product_id);    
                $loyalty_gained_points += $item->redeem_point * $cart->quantity;
            }

            // Calculate Loyalty Point 
            $user_loyalty_points = $this->user->profile->points;

            $loyalty_point = $this->LoyaltyPointModel->findOne(array(
                "restro_id"     => $restro_id,
                "location_id"   => $location_id,
                "service_id"    => $service_type
            ));

            $loyalty_discount = 0; $loyalty_used_points = 0;  
            if($loyalty_point) {
                if($user_loyalty_points >= $loyalty_point->from1) {
                    $loyalty_discount = $loyalty_point->discount1;
                    $loyalty_used_points = $loyalty_point->from1;
                }
                if($user_loyalty_points >= $loyalty_point->from2 && $loyalty_discount < $loyalty_point->discount2) {
                    $loyalty_discount = $loyalty_point->discount2;
                    $loyalty_used_points = $loyalty_point->from2;
                }
                if($user_loyalty_points >= $loyalty_point->from3 && $loyalty_discount < $loyalty_point->discount3) {
                    $loyalty_discount = $loyalty_point->discount3;
                    $loyalty_used_points = $loyalty_point->from3;
                }
            }

            $loyalty_discount_amount = $total_amount * $loyalty_discount / 100;


            // Calculate Mataam Point
            $user_mataam_points = $this->user->profile->mataam_points;

            $mataam_point = $this->MataamPointModel->findByServiceId($service_type);                    

            $mataam_discount = 0; $mataam_used_points = 0; $mataam_gained_points = 0;

            if($mataam_point) {                
                if($user_mataam_points >= $mataam_point->from) {
                    $mataam_discount = $mataam_point->discount;
                    $mataam_used_points = $mataam_point->from;    
                }
                if($mataam_point->amount > 0) {
                    $mataam_gained_points = round(($total_amount / $mataam_point->amount) * $mataam_point->point);
                }
            }

            $mataam_discount_amount = ($total_amount * $mataam_discount) / 100;


            $result = array(
                'loyalty'=>array(
                    'gained_points'     => $loyalty_gained_points, 
                    'used_points'       => $loyalty_used_points,
                    'discount_amount'   => $loyalty_discount_amount,
                    'balance'           => $user_loyalty_points
                ), 
                'mataam'=>array(
                    'gained_points'     => $mataam_gained_points, 
                    'used_points'       => $mataam_used_points,
                    'discount_amount'   => $mataam_discount_amount,
                    'balance'           => $user_mataam_points
                )
            );

            return $result;
        }

        public function getSum($user_id, $service_type, $restro_id, $area_id=null) {                       

            $params = array();
            $params["user_id"] = $user_id; 
            $params["restro_id"] = $restro_id;

            $carts = $this->CartModel->find($service_type, array(
                'user_id'   => $user_id,
                'restro_id' => $restro_id
            ));       

            if(!$carts) {
                throw new Exception('Cart list ' . $this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND); 
            }

            $total_amount = 0; 
            foreach($carts as $cart) {
                $total_amount += $cart->price * $cart->quantity;
            }

            $result = array('total_amount'=>$total_amount);
            if(($service_type==1 || $service_type==2) && $area_id) { // service type is "DELIVERY" or "CATERING"      
                //throw new Exception('Cart list ' . $this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND);        
                $charge_amount = $this->RestroCityAreaModel->getCharge($restro_id, $area_id, $service_type);
                $result = array_merge($result, array('charge_amount'=>$charge_amount));
            }

            return $result;
        }

        public function getDiscount($redeem_type, $user_id, $service_type, $restro_id, $location_id, $coupon_code) {
            $carts = $this->CartModel->find($service_type, array(
                "user_id"   => $user_id, 
                "restro_id" => $restro_id
            ));     

            if(!$carts) {
                throw new Exception('Cart list ' . $this->lang->line('resource_not_found'), RESULT_ERROR_RESOURCE_NOT_FOUND); 
            }

            $total_amount = 0; 
            foreach($carts as $cart) {
                $total_amount += $cart->price * $cart->quantity;
            }

            if($redeem_type == 1) { // Coupon
                $coupon = $this->CouponModel->findOne(array(
                    "coupon_code"   => $coupon_code, 
                    "location_id"   => $location_id, 
                    "restro_id"     => $restro_id
                ));
                if(!$coupon) {
                    throw new Exception($this->lang->line('coupon_code_invalid'), RESULT_ERROR_PARAMS_INVALID);
                }
                if($coupon->from_date != '')
                {
                    $today = date('Y-m-d');
                    if($today >= $coupon->from_date && $today <= $coupon->to_date)
                    {                                 
                        return array('discount_amount'=>($total_amount * $coupon->discount) / 100);
                    } else {
                        throw new Exception($this->lang->line('coupon_code_expired'), RESULT_ERROR_PARAMS_INVALID);
                    }
                } else {
                    throw new Exception($this->lang->line('coupon_code_invalid'), RESULT_ERROR_PARAMS_INVALID);
                }
            } else if($redeem_type == 2) {  // Loyalty Point
                // Calculate Loyalty Point 
                $user_points = $this->user->profile->points;

                $loyalty_point = $this->LoyaltyPointModel->findOne(array(
                    "restro_id"     => $restro_id,
                    "location_id"   => $location_id,
                    "service_id"    => $service_type
                ));

                $discount = 0;  
                if($loyalty_point) {
                    if($user_points >= $loyalty_point->from1) {
                        $discount = $loyalty_point->discount1;
                    }
                    if($user_points >= $loyalty_point->from2 && $discount < $loyalty_point->discount2) {
                        $discount = $loyalty_point->discount2;
                    }
                    if($user_points >= $loyalty_point->from3 && $discount < $loyalty_point->discount3) {
                        $discount = $loyalty_point->discount3;
                    }
                }

                return array('discount_amount'=>($total_amount * $discount) / 100);
            } else if($redeem_type == 3) {  // Mataam Point
                // Calculate Mataam Point
                $user_points = $this->user->profile->mataam_points;

                $mataam_point = $this->MataamPointModel->findByServiceId($service_type);                    

                $discount = 0; 

                if($mataam_point) {                
                    if($user_points >= $mataam_point->from) {
                        $discount = $mataam_point->discount;   
                    }
                }

                return array('discount_amount'=>($total_amount * $discount) / 100);
            }

        }

}
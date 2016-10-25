<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

    class RestroItemModel extends CI_Model
    {

        protected $publicFields = array();

        function __construct()
        {
            parent::__construct();     

        }

        public function getPublicFields($model) {
            foreach ($model as $key => $value) {

                if(!in_array($key, $this->publicFields)) {
                    unset($model->{$key});
                }

            }
            
            
            return $model;
        }   

        public function find($params=null){   
            $this->db->select('i.id, i.item_name AS name, i.item_description AS description, i.item_price AS price, i.image, i.loyalty_points AS redeem_point, i.order_point_amount AS redeem_amount, i.variation AS has_variation, i.price_type, p.id AS promo_id');
            $this->db->from('resto_items_category_list AS c'); 
            $this->db->join('tbl_item AS i', 'i.id=c.item_id');
            $this->db->join('restro_promotion_item AS p', 'p.item_id=i.id', 'left');
            
            if(isset($params)) { 
                if(isset($params["category_id"]) && $params["category_id"]!="") $this->db->where('c.category_id', $params["category_id"]);          
            }  
            
            $this->db->group_by('i.id');
            $result = $this->db->get()->result();

            return $result;
        }
        
        public function findById($id){   
            $this->db->select('i.id, i.item_name AS name, i.item_description AS description, i.item_price AS price, i.image, i.loyalty_points AS redeem_point, i.order_point_amount AS redeem_amount, i.variation AS has_variation, i.price_type, p.id AS promo_id, l.restro_id');
            $this->db->from('tbl_item AS i');
            $this->db->join('restro_promotion_item AS p', 'p.item_id=i.id', 'left');
            $this->db->join('restro_location AS l', 'l.id=i.location_id', 'left');
            $this->db->where('i.id', $id);
            $this->db->group_by('i.id');
            
            return $this->db->get()->row();
        }

        public function findOne($params) {
            $result = $this->find($params);
            return $result&&count($result)>0?$result[0]:null;
        }   

}
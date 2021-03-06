<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

    class UserDeviceModel extends CI_Model
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
        public function create($data)
        {
            $insert_id = null;
            $this->db->trans_start();
            if($this->db->insert('user_devices', $data)) {
                $insert_id = $this->db->insert_id();
            }
            $this->db->trans_complete();

            return $insert_id;                

        }
        public function update($id, $data){
            $this->db->trans_start();
            $this->db->where('id',  $id);
            $this->db->update('user_devices', $data);
            $this->db->trans_complete();
        }

        public function find($params=null, $fields=array()){   
            $this->db->select('d.*');
            $this->db->from('user_devices AS d');
            $this->db->join('users AS u', 'u.id=d.user_id');   
            
            if(isset($params)) {
                if(isset($params["user_id"]) && $params["user_id"]!="") $this->db->where('d.user_id', $params["user_id"]);
                if(isset($params["device_token"]) && $params["device_token"]!="") $this->db->where('d.device_token', $params["device_token"]);
                if(isset($params["device_type"]) && $params["device_type"]!="") $this->db->where('d.device_type', $params["device_type"]);
            }  
            
            $this->db->where('u.notification_subscription', 1);
            
            //$this->db->group_by('user_id');
            //$this->db->group_by('device_type');
            $this->db->order_by('created_time', 'DESC');
            
            $result = $this->db->get()->result();

            return $result;
        }

        public function findOne($params) {
            $result = $this->find($params);
            return $result&&count($result)>0?$result[0]:null;
        }
        public function findById($id){
            $this->db->select('*');
            $this->db->where('id',$id);

            return $this->db->get('user_devices')->row();
        }         



        public function delete($id){
            $this->db->trans_start();
            $ret = $this->db->delete('user_devices', array('id' => $id));
            $this->db->trans_complete();

            return $ret;
        }

}
<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

    class RestroSeatingHourModel extends CI_Model
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
            if($this->db->insert('restro_seating_hours', $data)) {
                $insert_id = $this->db->insert_id();
            }
            $this->db->trans_complete();

            return $insert_id;                

        }
        public function update($id, $data){
            $this->db->trans_start();
            $this->db->where('id',  $id);
            $this->db->update('restro_seating_hours', $data);
            $this->db->trans_complete();
        }

        public function find($params, $toArray=false){   
            $this->db->select('*');
            $this->db->from('restro_seating_hours');   

            if(isset($params)) {
                if(isset($params["restro_id"]) && $params["restro_id"]!="") $this->db->where('restro_id', $params["restro_id"]);
                if(isset($params["location_id"]) && $params["location_id"]!="") $this->db->where('location_id', $params["location_id"]);
                if(isset($params["category"]) && $params["category"]!="") $this->db->where('category', $params["category"]);
                if(isset($params["weekday"]) && isset($params["time"])) {
                    $this->db->where($params["weekday"]."_from >=", $params["time"]);   
                    $this->db->where($params["weekday"]."_to <=", $params["time"]);   
                }

            }  

            if($toArray) {
                $result = $this->db->get()->result_array();
            } else {
                $result = $this->db->get()->result();    
            }

            return $result;
        }

        public function findOne($params) {
            $result = $this->find($params);
            return $result&&count($result)>0?$result[0]:null;
        }
        public function findById($id){
            $this->db->select('*');
            $this->db->where('id',$id);

            return $this->db->get('restro_seating_hours')->row();
        }         



        public function delete($id){
            $this->db->trans_start();
            $ret = $this->db->delete('restro_seating_hours', array('id' => $id));
            $this->db->trans_complete();

            return $ret;
        }

}
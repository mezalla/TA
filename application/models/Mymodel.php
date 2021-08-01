<?php 

    
    defined('BASEPATH') OR exit('No direct script access allowed');
    
    class Mymodel extends CI_Model {
    
        // ambil data crawling
        function model_ambildatacrawling() {

            return $this->db->get('tweepy_raw');
        }
        
        
        // ambil data prepocessing
        function model_ambildataprepocessing() {

            return $this->db->get('prepro');
        }



        function model_ambildatapreprocessingByEvent( $label, $limit ) {

            $this->db->where('label', $label);
            $this->db->limit($limit);

            return $this->db->get('prepro');
        }
    }
    
    /* End of file M_crawling.php */
    
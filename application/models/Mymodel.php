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



        function prosesInsertDataExcel() {

            $config['upload_path']    = './dist/assets/excel/';
            $config['allowed_types']  = 'xlsx';
            $config['max_size']       = 20000; // 20 mb max
            $config['file_name']      = uniqid();

            $this->load->library('upload', $config);

            $file_excel = "";
            if ( $this->upload->do_upload('userfile')) {

                $file_excel = $this->upload->data('file_name');

            } else {

                $html = '<div class="alert alert-danger">'.$this->upload->display_errors().'</div>';
                $this->session->set_flashdata('pesan', $html);

                redirect('crawling');
            }
            return $file_excel;
        }



        // 
        function insert_multiple( $data ) {

            $this->db->insert_batch('tweepy_raw', $data);
            redirect('crawling');
        }
    }
    
    /* End of file M_crawling.php */
    
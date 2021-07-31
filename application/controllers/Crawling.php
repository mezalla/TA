<?php 

    
    defined('BASEPATH') OR exit('No direct script access allowed');
    
    class Crawling extends CI_Controller {
        
        function __construct(){
            
            parent::__construct();
            $this->load->model('Mymodel');
        }

        public function index(){


            $data['tweepy'] = $this->Mymodel->model_ambildatacrawling();

            // header
            $this->load->view('template/template_header');

            // content
            $this->load->view('crawling/V_crawling', $data);

            // footer
            $this->load->view('template/template_footer');
        }
    
    }
    
    /* End of file Crawling.php */
    
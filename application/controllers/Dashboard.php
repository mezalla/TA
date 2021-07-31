<?php 

    /**
    * crawling
    * prepo
    * medelling2
     */
    defined('BASEPATH') OR exit('No direct script access allowed');
    
    class Dashboard extends CI_Controller {
        

        public function index(){
            
            // header
            $this->load->view('template/template_header');

            // content
            $this->load->view('dashboard/V_dashboard');

            // footer
            $this->load->view('template/template_footer');
        }    
    }
    
    /* End of file Dashboard.php */
    
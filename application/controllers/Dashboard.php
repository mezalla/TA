<?php 

    /**
    * crawling
    * prepo
    * medelling2
     */
    defined('BASEPATH') OR exit('No direct script access allowed');
    
    class Dashboard extends CI_Controller {
        

        public function index(){
             
            echo "Mbak Mella";
        }    




        function pembuatanTemplate() {

            $this->load->view('template/template_header');

            $this->load->view('dashboard/V_dashboard');

            $this->load->view('template/template_footer');
        }
    }
    
    /* End of file Dashboard.php */
    
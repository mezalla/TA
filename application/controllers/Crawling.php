<?php 

    require('./vendor/autoload.php');

    use PhpOffice\PhpSpreadsheet\Helper\Sample;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    
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



        // import
        function doImportData() {

            // informasi akademis
            $file_excel = $this->Mymodel->prosesInsertDataExcel();

            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $direktori = './dist/assets/excel/'. $file_excel;


            /** Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($direktori);
            $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true ,true);

            $baris = 2;  // start mulai kolom ke 6
            $data = array();    
        

            if ( count($sheet) >= 2 ) {
            
                for ( $baris = 2; $baris <= count($sheet); $baris++ ) {

                    // atribut
                    $tweet  = $spreadsheet->getActiveSheet()->getCell('B'.$baris)->getValue();
                    $label  = $spreadsheet->getActiveSheet()->getCell('C'.$baris)->getValue();

                    array_push($data, array(

                        'text' => $tweet,
                        'label' => $label
                    ));
                }
            }

            $this->Mymodel->insert_multiple( $data );
        }
    
    }
    
    /* End of file Crawling.php */
    
<?php 

    
    defined('BASEPATH') OR exit('No direct script access allowed');
    
    class Preprocessing extends CI_Controller {
        
        function __construct(){
            
            parent::__construct();
            $this->load->model('Mymodel');
        }

        public function index(){
            
            $data['prepro'] = $this->Mymodel->model_ambildataprepocessing();

            // header
            $this->load->view('template/template_header');

            // content
            $this->load->view('prepocessing/V_prepocessing', $data);

            // footer
            $this->load->view('template/template_footer');
        }




        function model( $percentage ){


            $query = "vaksin covid mutasi ribu covid dunia";

            $result = $this->perhitungan( $percentage );
            
            // seleksi data 
            $data_seleksi = $result['data_information_gain'];
            $true = $result['prior_true']; // prior true
            $fake = $result['prior_fake']; // prior fake
            $CTrue = $result['countCTrue'];
            $CFake = $result['countCFake'];

            // cek ketersediaan data
            $seleksi_berdasarkan_query = array();
            $splitQuery = explode(' ', $query);

            foreach ( $splitQuery AS $word ) {

                foreach ( $data_seleksi AS $doc ) {
                    
                    /** Proses pengecekan term pada atribut yang tersedia */
                    if ( $word == $doc['word'] ) {

                        array_push( $seleksi_berdasarkan_query, $doc ); break;
                    }
                }
            }







            /** Perhitungan probabilitas posterior dan hasil map dengan threshold yg ditentukan */
            
            // data map 
            $mapTRUE = 0;
            $mapFAKE = 0;
            $jumlahDataThreshold = count( $data_seleksi );

            $proses = 0;
            foreach ( $seleksi_berdasarkan_query AS $row ) {


                $res_true = ($row['ibw1_true'] + 1) / ( $CTrue + $jumlahDataThreshold );
                $res_fake = ($row['ibw1_fake'] + 1) / ( $CFake + $jumlahDataThreshold );


                if ( $proses == 0) {

                    $mapTRUE = $res_true;
                    $mapFAKE = $res_fake;
                } else {

                    $mapTRUE *= $res_true;
                    $mapFAKE *= $res_fake;
                }


                echo 'hasil proses -'. ($proses + 1).'<br> <small>';

                echo 'Word : '. $row['word'].'<br>';
                echo 'TRUE : '. $res_true.'<br>';
                echo 'FAKE : '. $res_fake.'<br>';
                echo '<b>'.$mapTRUE.' | '.$mapFAKE.'</b></small>';
                echo '<hr>';


                // echo $row['word'].' PTrue : '. $res_true;
                // echo '<b>'.$row['word'].'</b><br>';
                // echo '<small>Nilai label true and fake ('.$row['ibw1_true'].', '.$row['ibw1_fake'].')</small><br>';
                // echo '<small>Hasil | TRUE :'.$res_true.' | FAKE : '.$res_fake.'</small>';
                // // print_r( $row );
                // echo '<hr>';

                $proses++;
            }


            // echo '<hr>';
            /** Hasil fake and true */
            $hasilMapTrue = $mapTRUE * $true; 
            $hasilMapFake = $mapFAKE * $fake; 

            echo '<b>Perbandingan Map TRUE | FAKE</b> <br>';
            echo $hasilMapTrue.' &emsp;|&emsp; '.$hasilMapFake;
            echo '<hr>';

            echo 'Berdasarkan perhitungan hasil kategori map diatas dengan threshold '.$percentage.'%: <br>';
            echo '<b>Kalimat : '.$query.'</b>';
            if ( $hasilMapTrue > $hasilMapFake ) {

                echo '<h1>Klasifikasi True</h1>';
                echo 'dengan nilai '. $hasilMapTrue;
            } else {

                echo '<h1>Klasifikasi Fake</h1>';
                echo 'dengan nilai '. $hasilMapFake;
            }
            // echo max($hasilMapTrue, $hasilMapFake);

            
            
            
            



            


            echo count( $seleksi_berdasarkan_query );
            
        }



        // perhitungan dari information gain
        function perhitungan( $percentage ) {

            /**
             * 
             *  4. 1 Dataset Clean
             * 
             */
            $dataPrepo = array(

                array(

                    'tweet' => "ragu vaksinasi covid bukti efektivitas aman",
                    'label' => true,
                ),
                array(

                    'tweet' => "who vaksin daftar uea lindung varian delta",
                    'label' => true,
                ),
                array(

                    'tweet' => "vaksin covid mutasi ribu covid dunia",
                    'label' => false,
                ),
                array(

                    'tweet' => "vaksin covid rusak sel otak sel darah",
                    'label' => false,
                ),
            );



            /**
             * 
             *  4. 2 Pembobotan Term Presence
             * 
             */
            $dataTerm = array();
            
            $totalLabelTrue = 0;
            $totalLabelFake = 0;

            foreach ( $dataPrepo AS $teksTweet ) {

                // split data teks
                $split = explode(" ", $teksTweet['tweet']);
                if ( count($split) > 0 ) {

                    foreach( $split AS $rowSplit ) {

                        // cek duplikasi kata
                        if ( count( $dataTerm ) > 0 ) {

                            if ( in_array( $rowSplit, $dataTerm ) == false ) {

                                array_push( $dataTerm, $rowSplit );
                            }

                        // first time
                        } else {

                            array_push( $dataTerm, $rowSplit );
                        }
                        
                    }
                }

                if ( $teksTweet['label'] == true ) $totalLabelTrue++;
                else $totalLabelFake++;
            }


            /** Lanjutan pembobotan 4.2  */
            $dataTermPresence = array();
            
            /** Informasi Total Perhitungan 0 dan 1 : Implementasi Bag of Words */
            $totalPerhitungan0 = 0;
            $totalPerhitungan1 = 0;

            foreach ( $dataTerm AS $teks ) {

                // echo '<h1>'.$teks.'</h1>';
                // ambil data teks tweet

                // total true dan false 
                $countingAlwaysTrue_1 = 0;
                $countingAlwaysFalse_1 = 0;

                $countingAlwaysTrue_0 = 0;
                $countingAlwaysFalse_0 = 0;

                foreach ( $dataPrepo AS $teksTweet ) {

                    // echo '<label>Tweet : '.$teksTweet['tweet'].'</label>';
                    $found_True  = 0;
                    $found_False = 0;

                    $empty_True  = 0;
                    $empty_False = 0;
                    

                    // split bagian teks tweet 
                    // perhitungan jumlah true or false 
                    $splitTeks = explode(' ', $teksTweet['tweet']);
                    $status_datateks = false;
                    foreach ( $splitTeks AS $split ) {

                        if ( $split == $teks ) { // found : 1

                            if ( $teksTweet['label'] == true ) $found_True = 1;
                            else $found_False = 1;
                            
                            $status_datateks = true;

                        }
                    }   
                    // status data teks
                    if ( $status_datateks == false ) {

                        if ( $teksTweet['label'] == true ) $empty_True = 1;
                        else $empty_False = 1;
                    }


                    // echo '<small>Output tweet : </small> <br>';
                    // echo "True : ". $found_True.' False: '. $found_False;



                    $countingAlwaysTrue_1  += $found_True;
                    $countingAlwaysFalse_1 += $found_False;

                    $countingAlwaysTrue_0  += $empty_True;
                    $countingAlwaysFalse_0 += $empty_False;

                    

                    // echo '<hr>';
                }   
                
                

                /**
                 * 
                 *  Implementasi Bag of Words : Perhitungan 
                 * 
                 */
                
                // echo '<br><small>True : '.$countingAlwaysTrue_1.' &emsp; False :'. $countingAlwaysFalse_1.'</small><br>'; 
                // echo '<br><small>True : '.$countingAlwaysTrue_0.' &emsp; False :'. $countingAlwaysFalse_0.'</small><br>'; 


                // hitung IBW : yang berstatus 1 
                array_push( $dataTermPresence, array(

                    'tweet'      => $teks, 
                    'ibw1_true'  => $countingAlwaysTrue_1,
                    'ibw1_fake' => $countingAlwaysFalse_1,
                    'ibw1_total' => $countingAlwaysTrue_1 + $countingAlwaysFalse_1,

                    'ibw0_true'  => $countingAlwaysTrue_0,
                    'ibw0_fake' => $countingAlwaysFalse_0,
                    'ibw0_total' => $countingAlwaysTrue_0 + $countingAlwaysFalse_0,
                ) );


                // increment total perhitungan 0 dan 1 pada : Implementasi Bag of Words
                $totalPerhitungan0 += ($countingAlwaysTrue_0 + $countingAlwaysFalse_0);
                $totalPerhitungan1 += ($countingAlwaysTrue_1 + $countingAlwaysFalse_1);

                // print_r( $dataTermPresence );
                // echo '<hr>';
            }



            // sampai log and information gain
            /**
             *  TRUE : - ptrue log(ptrue) - pfake log(pfake)
             *  FALSE : - pfalse log(pfalse) - pfake log(pfake)
             */

            // Prior
            $entroTrue  = $totalLabelTrue / count($dataPrepo);
            $entroFake = $totalLabelFake / count($dataPrepo);


            $entropy = -($entroTrue * log10($entroTrue) ) - ($entroFake * log10($entroFake) );
            // echo $entropy;






            /** Menhitung Information Gain dari setiap kata  */
            $data_informationGain = array();
            
            
            foreach ( $dataTermPresence AS $attr ) {

                // perhitungan nilai kata dari Perhitungan nilai : 0 
                // echo $attr['tweet'].'<br>';
                
                // untuk bagian 0
                $PTrue0 = $attr['ibw0_true'] / 2; 
                $PFake0 = $attr['ibw0_fake'] / 2; 
                $PTotal0= ($attr['ibw0_true'] + $attr['ibw0_fake']) / $totalPerhitungan0;
                $PEntropy0 = -($PTrue0 * log10($PTrue0)) - ($PFake0 * log10($PFake0));
                $PEntropy0_isNotNAN = is_nan($PEntropy0) ? 0 : $PEntropy0; // convert NaN to 0



                // format : TRUE | FAKE | TOTAL | ENTROPY
                // echo $PTrue0.' | '.$PFake0.' | '.$PTotal0.' | '.$PEntropy0_isNotNAN.'<br>';


                // untuk bagian 1
                $PTrue1 = $attr['ibw1_true'] / 2; 
                $PFake1 = $attr['ibw1_fake'] / 2; 
                $PTotal1= ($attr['ibw1_true'] + $attr['ibw1_fake']) / $totalPerhitungan1;
                $PEntropy1 = -($PTrue1 * log10($PTrue1)) - ($PFake1 * log10($PFake1));
                $PEntropy1_isNotNAN = is_nan($PEntropy1) ? 0 : $PEntropy1; // convert NaN to 0


                // format : TRUE | FAKE | TOTAL | ENTROPY
                // echo $PTrue1.' | '.$PFake1.' | '.$PTotal1.' | '.$PEntropy1_isNotNAN.'<br>';


                /** Information Gain */
                // information gain =  entropy - (total|0 * entropy|0) + (total|1 * entropy|1);
                $informationGain = $entropy - (( $PTotal0 * $PEntropy0_isNotNAN ) + ( $PTotal1 * $PEntropy1_isNotNAN ));

                // echo '<b>Information Gain '.$informationGain.'</b>';

                array_push( $data_informationGain, array(

                    'word' => $attr['tweet'],
                    
                    'p_true1'   => $PTrue1,
                    'p_fake1'   => $PFake1,
                    'p_total1'  => $PTotal1,
                    'p_entropy1'=> $PEntropy1_isNotNAN,

                    'p_true0'   => $PTrue0,
                    'p_fake0'   => $PFake0,
                    'p_total0'  => $PTotal0,
                    'p_entropy0'=> $PEntropy0_isNotNAN,

                    'information_gain' => $informationGain,
                    
                    'ibw1_true'  => $attr['ibw1_true'],
                    'ibw1_fake' => $attr['ibw1_fake'],

                    'ibw0_true'  => $attr['ibw0_true'],
                    'ibw0_fake' => $attr['ibw0_fake'],
                ) );

                
                
                
            }

            
            
            // foreach ( $data_informationGain AS $row ) {

            //     echo $row['word'].' ----------- <small>'.$row['information_gain'].'</small><br>';
            // }

            // echo '<hr>';

            // sorting value of information gain
            $data_informationGain = $this->array_sort( $data_informationGain, 'word', SORT_ASC);
            $data_informationGain = $this->array_sort( $data_informationGain, 'information_gain', SORT_DESC);
            
            
            // $percentage = 50;
            $threshold = count($data_informationGain) * ($percentage / 100);
            
            // threshold
            $data_informationGain = array_slice( $data_informationGain, 0, $threshold );


            // sorting alphabet
            $data_informationGain = $this->array_sort( $data_informationGain, 'word', SORT_ASC);

            // print_r( $data_informationGain );

            $totalTRUE = 0;
            $totalFAKE = 0;
            foreach ( $data_informationGain AS $row ) {
                
                // echo $row['word'].' ----------- <small>'.$row['information_gain'].'</small><br>';
                // echo $row['word'].' | TRUE : '.$row['ibw1_true'].' -- FAKE : '.$row['ibw1_fake'].'<br>';

                $totalTRUE += $row['ibw1_true'];
                $totalFAKE += $row['ibw1_fake'];
            }

            // echo '<hr>';
            // echo $totalTRUE.' '. $totalFAKE;
            
            // return value
            $arr = array(

                'data_information_gain' => $data_informationGain,
                'countCTrue'    => $totalTRUE,
                'countCFake'    => $totalFAKE,
                'prior_true'    => $entroTrue,
                'prior_fake'    => $entroFake
            );

            return $arr;

        }



        // sorting 
        function array_sort($array, $on, $order=SORT_ASC)
        {
            $new_array = array();
            $sortable_array = array();

            if (count($array) > 0) {
                foreach ($array as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $k2 => $v2) {
                            if ($k2 == $on) {
                                $sortable_array[$k] = $v2;
                            }
                        }
                    } else {
                        $sortable_array[$k] = $v;
                    }
                }

                switch ($order) {
                    case SORT_ASC:
                        asort($sortable_array);
                    break;
                    case SORT_DESC:
                        arsort($sortable_array);
                    break;
                }

                foreach ($sortable_array as $k => $v) {
                    $new_array[$k] = $array[$k];
                }
            }

            return $new_array;
        }

    
    }
    
    /* End of file Prepocessing.php */
    
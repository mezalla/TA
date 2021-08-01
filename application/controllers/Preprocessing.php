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




        function hasil(){

            $data['klasifikasi'] = array();
            if ( $this->input->get('threshold') ) {

                $data['klasifikasi'] = $this->proses();
            }

            // header
            $this->load->view('template/template_header');

            // content
            $this->load->view('prepocessing/prediksi', $data);
            // $this->load->view('prepocessing/prediksi');

            // footer
            $this->load->view('template/template_footer');
        }







        function proses() {

            $dataPrepro = array();

            $train = $this->input->get('training');
            $test  = $this->input->get('testing');

            $limitTrue = 150;
            $limitFake = 150;
            $threshold = $this->input->get('threshold');

            // ambil data prepro 
            $ambilDataPreproTrue = $this->Mymodel->model_ambildatapreprocessingByEvent('true', $limitTrue);
            $ambilDataPreproTFake = $this->Mymodel->model_ambildatapreprocessingByEvent('fake', $limitFake);


            foreach ( $ambilDataPreproTrue->result_array() AS $rowT ) {

                array_push( $dataPrepro, array(

                    'tweet' => $rowT['text'],
                    'label' => $rowT['label']
                ) );
            }


            foreach ( $ambilDataPreproTFake->result_array() AS $rowF ) {

                array_push( $dataPrepro, array(

                    'tweet' => $rowF['text'],
                    'label' => $rowF['label']
                ) );
            }


            // suffle 
            
            $cekTrain = $this->session->userdata('sess_train');
            if ( empty($cekTrain) ) {

                shuffle($dataPrepro);
                // save in session
                $dataSession = array(

                    'sess_train'    => $train,
                    'sess_test'     => $test,
                    'prepro'        => $dataPrepro
                );
                $this->session->set_userdata($dataSession);
                
            
            } else {

                $cekTest = $this->session->userdata('sess_test');
                if ( ($cekTrain != $train) || ($cekTest != $test) ) {


                    shuffle($dataPrepro);

                    // save in session
                    $dataSession = array(

                        'sess_train'    => $train,
                        'sess_test'     => $test,
                        'prepro'        => $dataPrepro
                    );
                    $this->session->set_userdata($dataSession);
                } 
            }


            $dataPrepro = $this->session->userdata('prepro');
            


            // pembagian train and test
            $hitungJumlahPrepro = count($dataPrepro);
            $jumlahTraining = intval( $hitungJumlahPrepro * ($train / 100) );
            $jumlahTesting = intval( $hitungJumlahPrepro * ($test / 100) );


            /** Nilai untuk data training */
            $dataTraining = array_slice( $dataPrepro, 0, $jumlahTraining );
            $dataTesting  = array_slice( $dataPrepro, $jumlahTraining, $hitungJumlahPrepro );


            // echo "Jumlah training ". count( $dataTraining );
            foreach ( $dataPrepro AS $training ) {

                // echo $training['label'].'-'.$training['tweet'].'<br>';
            }      
            // echo '<hr>';
            // echo "Jumlah testing ". count( $dataTesting );



            $result = array();
            $TN = 0;
            $TP = 0;
            $FN = 0;
            $FP = 0;
            foreach ( $dataTesting AS $test ) {

                // echo $test['tweet'].'<br>';
                // echo "Ekspekstasi ". $test['label'].'<br>';
                $prediksi = $this->model( $threshold, $dataTraining, $test['tweet'] );
                $label = false;
                if ( $test['label'] == "true" ) {

                    $label = true;
                }
                // echo '<hr>';
                array_push( $result, array(

                    'tweet' => $test['tweet'],
                    'label'     => $test['label'],
                    'prediksi'  => $prediksi ? "true" : "fake"
                ) );


                // confusion matrix 
                // echo 'Hasil '. $test['label'].' '.($prediksi ? "true" : "fake").'<br>';
                
                // if ( $label == true && $prediksi == true ) $TP++;
                // if ( $label == false && $prediksi == false ) $TN++;
                // if ( $label == true && $prediksi == false ) $FP++;
                // if ( $label == false && ($prediksi == true) ) $FN++;


                if ( $label == true && $prediksi == true ) $TP++;
                if ( $label == true && $prediksi == false ) $FN++;
                if ( $label == false && $prediksi == true ) $FP++;
                if ( $label == false && $prediksi == false ) $TN++;


                // echo '<hr>';
            }      


            // echo count( $dataTesting ).'<br>';
            // echo $TP.' '.$FP.' '.$FN.' '.$TN.'<hr>';

            // Accuracy : (TP + TN) / (TP + FP + FN + TN)
            // Precission = TP / (TP + FP)
            // Recall = TP / (TP + FN)
            // Specificity = TN / (TN + FP)

            $accuracy = ($TP + $TN) / ($TP + $FP + $FN + $TN);
            $precision = (($TP + $FP) > 0) ? $TP / ($TP + $FP) : 0;
            $recall = $TP + $FN > 0 ? $TP / ($TP + $FN) : 0;
            $specificity = ($TN + $FP) > 0 ? $TN / ($TN + $FP) : 0;


            // echo 'accuracy '. ($accuracy * 100).'<br>';
            // echo 'precission '. ($precission * 100).'<br>';
            // echo 'recall '. ($recall * 100).'<br>';
            // echo 'specificity '. ($specificity * 100).'<br>';
            // echo 'precission '. 
            


            
            return array(

                'data'      => $result,
                'accuracy'  => $accuracy * 100,
                'precision' => $precision * 100,
                'recall'    => $recall * 100
            );

        }




        



        function model( $percentage , $dataTraining, $query ){

            // $query = "vaksin covid mutasi ribu covid dunia";

            $result = $this->perhitungan( $percentage, $dataTraining );
            
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

                // echo '<h1>'.$CFake.'</h1>';


                if ( $proses == 0) {

                    $mapTRUE = $res_true;
                    $mapFAKE = $res_fake;
                } else {

                    $mapTRUE *= $res_true;
                    $mapFAKE *= $res_fake;
                }


                // echo 'hasil proses -'. ($proses + 1).'<br> <small>';

                // echo 'Word : '. $row['word'].'<br>';
                // echo 'TRUE : '. $res_true.'<br>';
                // echo 'FAKE : '. $res_fake.'<br>';
                // echo '<b>'.$mapTRUE.' | '.$mapFAKE.'</b></small>';
                // echo '<hr>';


                // echo $row['word'].' PTrue : '. $res_true;
                // echo '<b>'.$row['word'].'</b><br>';
                // echo '<small>Nilai label true and fake ('.$row['ibw1_true'].', '.$row['ibw1_fake'].')</small><br>';
                // echo '<small>Hasil | TRUE :'.$res_true.' | FAKE : '.$res_fake.'</small>';
                // print_r( $row );
                // echo '<hr>';

                $proses++;
            }


            // echo '<hr>';
            /** Hasil fake and true */
            $hasilMapTrue = $mapTRUE * $true; 
            $hasilMapFake = $mapFAKE * $fake; 

            // echo '<b>Perbandingan Map TRUE | FAKE</b> <br>';
            // echo $hasilMapTrue.' &emsp;|&emsp; '.$hasilMapFake;
            // echo '<hr>';

            // echo 'Berdasarkan perhitungan hasil kategori map diatas dengan threshold '.$percentage.'%: <br>';
            // echo '<br><b>Kalimat : '.$query.'</b><br>';
            // if ( $hasilMapTrue > $hasilMapFake ) {

            //     echo '<h1>Klasifikasi True</h1>';
            //     echo 'dengan nilai '. $hasilMapTrue.' '.$hasilMapFake;
            // } else {

            //     echo '<h1>Klasifikasi Fake</h1>';
            //     echo 'dengan nilai '. $hasilMapFake.'<br>';
            // }
            // echo "Maka ". max($hasilMapTrue, $hasilMapFake).'<br>';
            // echo '<hr>';
            // echo count( $seleksi_berdasarkan_query );


            // return value

            $prediksi = false;
            if ( $hasilMapTrue > $hasilMapFake ) {

                $prediksi = true;
            }

            return $prediksi;
            
        }



        // perhitungan dari information gain
        function perhitungan( $percentage, $dataPrepo ) {

            /**
             * 
             *  4. 1 Dataset Clean
             * 
             */

            // $dataPrepo = array(

            //     array(

            //         'tweet' => "ragu vaksinasi covid bukti efektivitas aman",
            //         'label' => true,
            //     ),
            //     array(

            //         'tweet' => "who vaksin daftar uea lindung varian delta",
            //         'label' => true,
            //     ),
            //     array(

            //         'tweet' => "vaksin covid mutasi ribu covid dunia",
            //         'label' => false,
            //     ),
            //     array(

            //         'tweet' => "vaksin covid rusak sel otak sel darah",
            //         'label' => false,
            //     ),
            // );
            


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

                // print_r($teksTweet).'<br><br>';

                if ( $teksTweet['label'] == "true" ) {
                    $totalLabelTrue++;
                } else $totalLabelFake++;
            }
            
            // echo '<hr>';

            
            /** Lanjutan pembobotan 4.2  */
            $dataTermPresence = array();
            
            /** Informasi Total Perhitungan 0 dan 1 : Implementasi Bag of Words */
            $totalPerhitungan0 = 0;
            $totalPerhitungan1 = 0;

            foreach ( $dataTerm AS $teks ) {

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
                            

                            if ( $teksTweet['label'] == "true" ) {
                                
                                $found_True = 1;
                            } else {

                                $found_False = 1;
                            }
                            
                            $status_datateks = true;

                        }
                    }   
                    // status data teks
                    if ( $status_datateks == false ) {

                        if ( $teksTweet['label'] == "true" ) $empty_True = 1;
                        else $empty_False = 1;
                    }

                    // echo 'Pencocokan '.$teksTweet['label'].'<br>';
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
                
                // echo $teks;
                // echo '<br><small>True : '.$countingAlwaysTrue_1.' &emsp; False :'. $countingAlwaysFalse_1.'</small><br>'; 
                // echo '<br><small>True : '.$countingAlwaysTrue_0.' &emsp; False :'. $countingAlwaysFalse_0.'</small><br>'; 

                // echo '<hr>';


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
            // echo '<script>alert('.$totalLabelFake.')</script>';


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



                // print_r( $data_informationGain );
                // echo '<hr>';
                
                
                
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






        function pengujian() {

            // 80 : 20 | 20 data
            // 0   vaksinasi waspada bantu lindung orang orang i...  true     true
            // 1  selamat idulfitri lupa waspada laku tindak ceg...  true     true
            // 2  libur kali hati hati covid anjur perintah jaga...  true     true
            // 3       kuak nyata jamaah haji indonesia tolak covid  fake     fake

            // Clasification report 50% :
            //             precision    recall  f1-score   support

            //         fake       1.00      1.00      1.00         1
            //         true       1.00      1.00      1.00         3

            //     accuracy                           1.00         4
            // macro avg       1.00      1.00      1.00         4
            // weighted avg       1.00      1.00      1.00         4



            $training = array(
                array(
            
                    'tweet' => "musim libur kali protokol sehat atur perintah covid nikmat kualitas dirumahaja laku temu teman teman keluarga virtual iya",
                    'label' => "true",
                ),
                array(
            
                    'tweet' => "pandemi covid juta tembakau henti ayo gabung juta orang henti tembakau jenis",
                    'label' => "true",
                ),
                array(
            
                    'tweet' => "kasir chandra karang nyata positive covid",
                    'label' => "fake",
                ),
                array(
            
                    'tweet' => "libur lupa terap tindak tindak cegah covid iya",
                    'label' => "true",
                ),
                array(
            
                    'tweet' => "kasad klarifikasi anggota tni bandung nyata ban positif kena covid",
                    'label' => "fake",
                ),
                array(
            
                    'tweet' => "fabiana souza terima perdana sunti vaksin covid tinggal dunia rs sao lucas porto alegre brazil selatan",
                    'label' => "fake",
                ),
                array(
            
                    'tweet' => "nyata demo kena covid",
                    'label' => "fake",
                ),
                array(
            
                    'tweet' => "simpul rob oswald covid khayal fiktif",
                    'label' => "fake",
                ),
                array(
            
                    'tweet' => "foto gubernur anies pegang piagam tulis harga provinsi covid ",
                    'label' => "fake",
                ),
                array(
            
                    'tweet' => "covid betapa sistem sehat kuat capai cakup sehat semesta komitmen solidaritas tara amp pesan sekretaris jenderal pbb world health assembly wha weeklyrecap",
                    'label' => "true",
                ),
                array(
            
                    'tweet' => "orang hadap hilang covid sedia proses pikir asa bantu perspektif tenang terima situasi kesehatanjiwa mentalhealth",
                    'label' => "true",
                ),
                array(
            
                    'tweet' => "vaksin covid vaksinasi iya ragu ragu vaksinasi terap tindak cegah",
                    'label' => "true",
                ),
                array(
            
                    'tweet' => "terap tindak cegah covid sakit gejala sehat dirumahaja hindar temu orang iya",
                    'label' => "true",
                ),
                array(
            
                    'tweet' => "mayat positif covid kubur daster sesuai syariat fardhu kifayah islam",
                    'label' => "fake",
                ),
                array(
            
                    'tweet' => "pesan beranta rekrutmen rawan rawat pasien covid wisma atlet dokter irna",
                    'label' => "fake",
                ),
                array(
            
                    'tweet' => "raja thailand panggil ulama islam baca doa tolak bala covid",
                    'label' => "fake",
                )
            );


            $testing = array(

                array(
                    'tweet' => "vaksinasi waspada bantu lindung orang orang iya kena masker tindak cegah covid",
                    'label' => "true",
                ),
                array(
                    'tweet' => "selamat idulfitri lupa waspada laku tindak cegah covid stay safe and healthy iya",
                    'label' => "true",
                ),
                array(
                    'tweet' => "libur kali hati hati covid anjur perintah jaga sehat laku tindak cegah lindung orang orang lengah iya",
                    'label' => "true",
                ),
                array(
                    'tweet' => "kuak nyata jamaah haji indonesia tolak covid",
                    'label' => "fake",
                ),
            );


            echo '<h1>'.$testing[3]['tweet'].'</h1>';
            $result = $this->model(50, $training, $testing[3]['tweet']);

        }

    
    }
    
    /* End of file Prepocessing.php */
    
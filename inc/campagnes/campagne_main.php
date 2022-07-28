<?php
include 'campagne_tab_header.php';

if (!empty(PRODUCT_FILE_ID) && is_numeric(PRODUCT_FILE_ID)) {
    include("product_file.php");
} else if (!empty($_GET['generate_picklists'])) {
    include("generate_picklist.php");
} else if (!empty($_GET['generate_stations_card']) || !empty($_GET['delete_station_cards'])) {
    include("generate_stations_card.php");
}
else {
//////////////////
    ?>



    <!-- Files voor producten --->
    <?php
    /*
      // DELETE PRODUCT_FILE FROM DB AND SERVER
     */

    if (!empty($_GET['delete_product_file_id']) && is_numeric($_GET['delete_product_file_id'])) {
        $product_file_id = $_GET['delete_product_file_id'];
        $query = $db->query("SELECT * FROM `campagne_product_files` WHERE `campagne_id` = " . $campagne['id'] . " && `id` = " . $product_file_id . " LIMIT 1");
        $file = $query->fetch_assoc();
        if ($file['name']) {
            $unlink = unlink(DIR_PICKLIST_EXCEL_FILES . $file['name']);
            if ($unlink || !file_exists(DIR_PICKLIST_EXCEL_FILES . $file['name'])) {
                $action = $db->query("DELETE FROM `campagne_product_files` WHERE  `id` = " . $product_file_id . " LIMIT 1");
                if ($action) {
                    $action = $db->query("DELETE FROM `campagne_products` WHERE  `campagne_product_file_id` = " . $product_file_id . "");
                    if ($action) {
                        header('Location: ' . CAMPAGNE_URL . '#product-files');
                    }
                }
            }
        }
    }

    /*
      // UPLOAD XCEL PRODUCT_FILE FOR PRODUCTS
     */


    define('SKIP_LOCATION_ROWS', ['totaal', 'besteld', 'reserve', 'leverancier']);

    if (!empty($_FILES['product_file'])) {

        $file = upload_excel_file($_FILES['product_file'], DIR_PICKLIST_EXCEL_FILES, true);

        if ($file) {

            try {

                $data = [];

                $inputFileType = PHPExcel_IOFactory::identify($file['src']);

                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($file['src']);
                $excel_data_raw = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

                $product_names = [];
                if(array_key_exists('products_name_row_index', $_POST) && !empty($_POST['products_name_row_index'])) {
                    $product_names = $excel_data_raw[$_POST['products_name_row_index']];
                }
                
                $product_version_details = [];
                if(array_key_exists('products_version_details_row_index', $_POST) && !empty($_POST['products_version_details_row_index'])) {
                    $product_version_details = $excel_data_raw[$_POST['products_version_details_row_index']];
                }
                if(!empty($product_names) && !empty($product_version_details)) {
                    foreach($product_names as $key => $value) {
                         if(array_key_exists($key, $product_version_details) && !empty($product_version_details[$key])) {
                             $product_names[$key] .= ' - ' . $product_version_details[$key];
                         }
                    }
                }
    
                foreach ($product_names as $index => $product_name) {
                    $product_names["" . $index . ""] = return_str($product_name);
                }
                $product_external_ids = $excel_data_raw["" . $_POST['products_id_row_index'] . ""];
                
                $products_packaging_types = [];
                if(array_key_exists('products_packaging_type_row_index', $_POST) && !empty($_POST['products_packaging_type_row_index'])) {
                    foreach($excel_data_raw[$_POST['products_packaging_type_row_index']] as $row_index => $value) {
                        if(empty($value)) {
                            continue;
                        }
                       
                        if(strtolower($value) === 'winkel specifiek') {
                            $excel_data_raw[$_POST['products_packaging_type_row_index']]["". $row_index .""] = 'set-item';
                            $excel_data_raw[$_POST['products_version_row_index']]["". $row_index .""] = 1;
                            $excel_data_raw[$_POST['products_version_multiplier_row_index']]["". $row_index .""] = 1;
                        }
                        else if($value === 'los item') {
                            $excel_data_raw[$_POST['products_packaging_type_row_index']]["". $row_index .""] = 'item';
                            if($excel_data_raw[$_POST['products_version_multiplier_row_index']]["". $row_index .""] > 1) {
                                $excel_data_raw[$_POST['products_version_multiplier_row_index']]["". $row_index .""] = 1;
                            }  
                        }
                        else {
                             $excel_data_raw[$_POST['products_packaging_type_row_index']][$row_index] = 'set';
                        }
                    }
                    $products_packaging_types = $excel_data_raw["" . $_POST['products_packaging_type_row_index'] . ""];
                }


                $product_unit_quantities = [];
                if(array_key_exists('products_unit_quantity_row_index', $_POST) && !empty($_POST['products_unit_quantity_row_index'])) {
                    $product_unit_quantities = $excel_data_raw["" . $_POST['products_unit_quantity_row_index'] . ""];
                }

                $product_num_versions = false;
                if(array_key_exists('products_version_row_index', $_POST) && !empty($_POST['products_version_row_index'])) {
                    $product_num_versions = $excel_data_raw["" . $_POST['products_version_row_index'] . ""];
                }
                
                $product_version_multiplier = false;
                if(array_key_exists('products_version_multiplier_row_index', $_POST) && !empty($_POST['products_version_multiplier_row_index'])) {
                    $product_version_multiplier = $excel_data_raw["" . $_POST['products_version_multiplier_row_index'] . ""];
                }

                if(empty($product_unit_quantities) && $product_num_versions && $product_version_multiplier) {
                   foreach($product_num_versions as $key => $value) {
                       $product_unit_quantities[$key] = $value;
                       if(is_numeric($value) && array_key_exists($key, $product_version_multiplier) && is_numeric($product_version_multiplier[$key])) {
                            $product_unit_quantities[$key] = (integer) $value * (integer) $product_version_multiplier[$key];
                       }
                   }
                }
                
                /*
                 * update 20-04-2022 ESL check
                 */
               
                 
                $esl_products = [];
                if($_GET['account_id'] === "1") {
                    
                    $esl_product_row_index = 12;
                    $esl_products = $excel_data_raw[$esl_product_row_index];
;
                    foreach($esl_products as $column => $value) {
                               
                        if(is_bool($value) || in_array(strtoupper($value), ['TRUE', 'FALSE'])) {
                            if($value || strtoupper($value) === 'TRUE' ) {
                                $value = 'ja';
                            }
                            else if(!$value || strtoupper($value) === 'FALSE') {
                                $value = 'nee';
                            }
                            else {
                                $value = '';
                            }
                        }
                            
                        $newValue = false;
                       
                        if(!empty($value)) {
                            if(in_array(strtoupper($value), ['JA','YES', 'Y', 'J'])) {
                                $newValue = 1;
                            }
                            else if(in_array(strtoupper($value), ['NEE','NO', 'N'])) {
                                $newValue = -1;
                            }
                            else {
                                arr('"'. $value . '"', false);
                                $newValue = 0;
                            }
                        }

                        if($value === 'ESL Item') {
                            $newValue = '';
                        }
                        $esl_products[''.$column.''] = $newValue;
                    }
                  
     

                }
                 
    
                $location_headers = $excel_data_raw["" . $_POST['locations_name_row_index'] . ""];
                $locations_start_row_index = intval($_POST['locations_start_row_index']);
                $location_columns = [];
                

                $possible_location_columns = [
                    'locations_id_column_index',
                    'locations_name_column_index',
                    'locations_address_column_index',
                    'locations_address_number_column_index',
                    'locations_postal_code_column_index',
                    'locations_city_column_index',
                    'locations_rayon_column_index',
                    'locations_formule_column_index',
                ];
                
                foreach ($possible_location_columns as $column) {
                    if (!empty($_POST['' . $column . ''])) {
                        $location_columns[] = $_POST["" . $column . ""];
                    }
                }
                
                $rows = [];
                if (is_numeric($locations_start_row_index)) {
                    for ($x = $locations_start_row_index; $x <= count($excel_data_raw); $x++) {
                        $rows[$x] = $excel_data_raw["" . $x . ""];
                    }
                }
                
                /*
                 * update 20-04-2022 Get Campgane dates
                 */
                
                $campagne_startdate = false;
                $campagne_start_row_index = 5;
                $campagne_start_column_index = 'B';
                if(!empty($excel_data_raw[$campagne_start_row_index][''.$campagne_start_column_index.''])) {
                    $campagne_startdate = $excel_data_raw[$campagne_start_row_index][''.$campagne_start_column_index.''];
                    $campagne_startdate = strtotime($campagne_startdate);
                }  
                $campagne_enddate = false;
                $campagne_end_row_index = 6;
                $campagne_end_column_index = 'B';
                if(!empty($excel_data_raw[$campagne_end_row_index][''.$campagne_end_column_index.''])) {
                    $campagne_enddate = $excel_data_raw[$campagne_end_row_index][''.$campagne_end_column_index.''];
                    $campagne_enddate = strtotime($campagne_enddate);
                }
                $campagne_duration = round(($campagne_enddate - $campagne_startdate) / (60 * 60 * 24));
                $campagne_duration++;
     
//                arr('Campagne Start: '. date('d-m-Y', $campagne_startdate) . ' ('.$campagne_startdate.')', false);
//                arr('Campagne End: '. date('d-m-Y', $campagne_enddate) . ' ('.$campagne_enddate.')', false);
//                die('---');

                /*
                 * update 20-04-2022 ESL check
                 */
                
                $esl_product_columns = array_keys(array_slice(array_filter($esl_products),1));
                $esl_product_values = array_slice(array_filter($esl_products),1);

                $esl_startdate_address_column_index = false;
                $rb_address_closing_date_column_index = false;
                $rb_address_opening_date_column_index = false;
            
                foreach($location_headers as $column_index => $header) {
                    if(strtoupper($header) === 'ESL') {
                        $esl_startdate_address_column_index = $column_index;
                    }
                    else if(strtoupper($header) === 'ESL STARTDATUM') {
                        $esl_startdate_address_column_index = $column_index;
                    }
                    else if($header === 'Winkel Sluitingsdatum') {
                        $rb_address_closing_date_column_index = $column_index;
                    }
                    else if($header === 'Winkel Openingsdatum') {
                        $rb_address_opening_date_column_index = $column_index;
                    }
                   
                }
                     
                $article_campagne_startdate_row_index = 18;
                $article_campagne_enddate_row_index = 19;
                
                $article_campagne_startdates = [];
                $article_campagne_enddates = [];

//                foreach($excel_data_raw[''.$article_campagne_startdate_row_index.''] as $column_index => $value) {
//                    foreach($esl_product_values as $article_column_index => $article_esl_value) {
//                        $article_campagne_startdates[''.$article_column_index.''] = strtotime($value);
//                    }
//                }

                foreach($esl_product_values as $article_column_index => $article_esl_value) {
                    $article_campagne_startdates[''.$article_column_index.''] = strtotime($excel_data_raw[''.$article_campagne_startdate_row_index.''][''. $article_column_index . '']);
                    $article_campagne_enddates[''.$article_column_index.''] = strtotime($excel_data_raw[''.$article_campagne_enddate_row_index.''][''. $article_column_index . '']);
                }
             
$exceptions = [];
          
                foreach($rows as $row_index => $row) {
                    
                    /*
                     * Check if row is ESL
                     */
                    
                    $esl = false;       
                    $esl_allocation = false;   
                    $external_id = $row['A'];
                    
                    if(array_key_exists(''.$esl_startdate_address_column_index.'', $row) && !empty($row[''.$esl_startdate_address_column_index.''])) {
                        if((string) $row[''.$esl_startdate_address_column_index.''] === '1') {
                           $esl = true;
                        }
                        else if($campagne_startdate && $campagne_enddate) {
                            $esl_startdate = strtotime($row[''.$esl_startdate_address_column_index.'']);  
                            if($esl_startdate <= $campagne_enddate) {
                                 $esl = true;
                             }
                             else if($esl_startdate >= $campagne_startdate && $esl_startdate < $campagne_enddate) {
                                 $esl = true;
                             }
                        } 
                        if($esl) {
                            arr($row_index . ' -  '. $external_id . ' = ESL');
                        }
                        
                    }
                    
                    
                    /*
                     * Update Add Sealpakket
                     * SEALPAKKET-ESL WHERE Weekpakket === 1 && $esl === 1
                     * SEALPAKKET-NL WHERE Land = NL && Weekpakket === 1 && Concept in ['FM','HQ','NL']
                     */
                    
//                    arr($product_version_details);
//                    arr(num2alpha(count($product_names)));
          
                    if((integer) $campagne_duration === 7) {
                        
                        $weekpakket_column_index = 'R';
                        $concept_column_index = 'M';
                        $country_column_index = 'K';

                        if(!$seals) {
                            $seals = [
                                'ESL'=> [
                                    'column_index' => false,
                                    'esl' => [
                                        '1'
                                    ],
                                    'row_indexes' => []
                                ],
                                'NL' => [
                                    'column_index' => false,
                                    'countries' => [
                                        'NL', 'Nederland'
                                    ],
                                    'concepts' => [
                                        'FM','HQ','NL'
                                    ],
                                    'row_indexes' => []
                                ]
                            ];
                        }
                        
                        $country = (string) $row[''.$country_column_index.''];
                        $weekpakket = (string) $row[''.$weekpakket_column_index.''];
                        $concept = (string) $row[''.$concept_column_index.''];


                        $gets_seal = false;
                        $seal_name = false;

                        if(!empty($weekpakket)) {
     
                            foreach($seals as $seals_name => $seal) {

                                $s = true;

                                if(array_key_exists('esl', $seal)) {
                                    if(!$esl) {
                                        $s = false;
                                    }
                                }
                        
                                if(array_key_exists('countries', $seal)) {
                                    if(empty($country) || !in_array(''.$country.'', $seal['countries'])) {
                                        $s = false;
                                    }
                                }
                                if(array_key_exists('concepts', $seal)) {                                    
                                    if(empty($concept) || !in_array(''.$concept.'', $seal['concepts'])) {
                                        $s = false;
                                    }
                                }
                                
                                if($s) {
                                    arr(''. $row['A'] . ' = '. $concept . ' = '. $seals_name , false);
                                    $gets_seal = true;
                                    $seal_name = $seals_name;
                                    break;
                                } 
                                
                            }
                            
                            if($gets_seal) {
                                
                                $gets_seal = 'SEALPAKKET-'. $seal_name;
                                
                                if(!in_array(''.$gets_seal.'', $product_names)) {

                                    $seals[''.$seal_name.'']['column_index'] = num2alpha(count($product_names));
                                    $product_names[''. $seals[''.$seal_name.'']['column_index'] .''] = $gets_seal;
                                    $product_external_ids[''. $seals[''.$seal_name.'']['column_index'] .''] = false;
                                    $product_version_details[''. $seals[''.$seal_name.'']['column_index']. ''] = 'Sealpakket (automatisch toegevoegd)';
                                    $product_unit_quantities[''. $seals[''.$seal_name.'']['column_index']. ''] = 1;
                                    $product_num_versions[''. $seals[''.$seal_name.'']['column_index']. ''] = 1;
                                    $product_version_multiplier[''. $seals[''.$seal_name.'']['column_index']. ''] = 1;
                                    $products_packaging_types[''. $seals[''.$seal_name.'']['column_index']. ''] = 'seal';
                                    $esl_products[''. $seals[''.$seal_name.'']['column_index']. ''] = false;
                                    
                                }
                                
                                $seals[''.$seal_name.'']['row_indexes'][] = $row_index;
                               
                            }
                        }
                    }
                    
                    /*
                     * If row is not ESL
                     * Disable all ESL items
                     */
             
                    if(!$esl && count($esl_product_columns) > 0) {
                        
                        $esl_changed = false;
                        foreach($esl_product_columns as $esl_product_column_index) {
                            $article_for_esl = false;
                            if($esl_product_values[$esl_product_column_index] > 0) {
                                $article_for_esl = true;
                            }
                            if($article_for_esl) {
                                $row[''.$esl_product_column_index.''] = false;
                                $esl_changed = true;
                            }
                        }
                        if($esl_changed) {
                            $rows[''. $row_index .''] = $row;
                        }
                        
                    }
 
                    /*
                     * If Row is ESL
                     * Disable all "Not ESL" items
                     */
                   
                    if($esl && count($esl_product_columns) > 0) {
                        

                        
                        $esl_changed = false;
                        foreach($esl_product_columns as $esl_product_column_index) {
                            
                            $article_for_esl = '';
                            if(array_key_exists($esl_product_column_index, $esl_product_values)) {
                                if($esl_product_values[$esl_product_column_index] > 0) {
                                    $article_for_esl = true;
                                } else if($esl_product_values[$esl_product_column_index] < 0) {
                                    $article_for_esl = false;
                                }
                            }
                            
                            
                            $startdate_article = $article_campagne_startdates[''.  $esl_product_column_index .''];
                            $enddate_article = $article_campagne_enddates[''. $esl_product_column_index. ''];
                            $esl_startdate = strtotime($row[''.$esl_startdate_address_column_index.'']);
                            
                            $opening_date = strtotime($row[''.$rb_address_opening_date_column_index.'']);
                            $closing_date = strtotime($row[''.$rb_address_closing_date_column_index.'']);
                            
                            
                            $esl_allocation = false;
                            if($esl_startdate && $startdate_article && $enddate_article) {    
                                
                                if($esl_startdate <= $startdate_article && (!$closing_date || $closing_date > $esl_startdate) && (!$opening_date || $opening_date >=$esl_startdate)) {
                                /*
                                 * RULE 1
                                 */
                                    $exceptions[$external_id][$esl_product_column_index] = [
                                        'allocation' => 'true',
                                        'reason' => '',
                                        'rule' => 1
                                    ];
                                    $esl_allocation = true;
                                }
                                else if(($esl_startdate > $startdate_article && $esl_startdate < $enddate_article) && (!$closing_date || $closing_date <= $esl_startdate)) {
                                    /*
                                    * RULE 2
                                     * Moet in een campagne naar false, deze moeten niet gealloceerd worden in de campagne 
                                     * maar in het startpakket komen
                                    */
                                    if($startdate_article < $campagne_startdate) {
                                        $esl_allocation = false;
                                        $exceptions[$external_id][$esl_product_column_index] = [
                                            'allocation' => 'false',
                                            'reason' => 'Artikel gaat in ESL startpakket',
                                            'rule' => 2
                                        ];
                                    }
                                    else if($startdate_article === $campagne_startdate) {
                                        $esl_allocation = true;
                                        $exceptions[$external_id][$esl_product_column_index] = [
                                            'allocation' => 'true',
                                            'reason' => 'Artikel moet nog gemaakt worden',
                                            'rule' => 2
                                        ];
                                    }
                                    
                                    
                                }
                                else if($esl_startdate >= $startdate_article && $esl_startdate < $enddate_article && $opening_date >= $esl_startdate) {
                                    /*
                                    * RULE 3
                                    */
                                    $esl_allocation = false;
                                    $exceptions[$external_id][$esl_product_column_index] = [
                                        'allocation' => 'false',
                                        'reason' => '',
                                        'rule' => 3
                                    ];
                                }
                                
//if($external_id === '3401') {
//    arr($external_id . ' esl_startdate: ' . date('d-m-Y', $esl_startdate), false);
//    arr($external_id . ' campagne_startdate: ' . date('d-m-Y', $campagne_startdate), false);
//    arr($external_id . ' campagne_enddate: ' . date('d-m-Y', $campagne_enddate), false);
//    
//    arr($external_id . ' esl_allocation: ' . ($esl_allocation ? 'True' : 'False'), false);
//    die();
//}
                                 
                            }
                            
                            if(is_bool($article_for_esl)) {
//                                arr('++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++', false);
//                                arr('Rij: '. $row_index, false);
//                                arr($esl_product_column_index . ' Startdate ESL: ' . date('d-m-Y', $esl_startdate), false);
//                                arr($esl_product_column_index . ' ESL Allocation: ' . ($esl_allocation ? 'True' : 'False'), false);                                
//                                arr($esl_product_column_index . ' ESL Article: '. ($article_for_esl ? 'True' : 'False'), false);
//                                arr($esl_product_column_index . ' Startdate Article: ' . date('d-m-Y', $startdate_article), false);
//                                arr($esl_product_column_index . ' Enddate Article: ' . date('d-m-Y', $enddate_article), false);
                            }
                    
                            if($esl_allocation && (is_bool($article_for_esl) && !$article_for_esl) && !empty($row[''.$esl_product_column_index.''])) {
                                arr('---------------------------------------------- Address is ESL met allocation en artikel is ESL onwaar ', false);
                                arr('---------------------------------------------- '. $row[''.$esl_product_column_index.''] . ' wordt Null', false);
                                $row[''.$esl_product_column_index.''] = false;
                                $esl_changed = true;
                            }
                            if(!$esl_allocation && (is_bool($article_for_esl) && $article_for_esl) && !empty($row[''.$esl_product_column_index.''])) {
                                arr('---------------------------------------------- Address heeft geen ESL allocation en artikel is ESL waar ', false);
                                arr('---------------------------------------------- '. $row[''.$esl_product_column_index.''] . ' wordt Null', false);
                                $row[''.$esl_product_column_index.''] = false;
                                $esl_changed = true;
                            }
                        }
                        
                        if($esl_changed) {
                            $rows[''. $row_index .''] = $row;
                        }
                    }
                    
                 
               
                }
//           arr($exceptions);
//           arr('---einde----');
                if(!empty($seals)) {
                    foreach($seals as $seal_name => $seal) {
                        foreach($rows as $row_index => $row) {
                            $rows[$row_index][''. $seal['column_index'] .''] = false;
                            if(in_array($row_index, $seal['row_indexes'])) {
                              $rows[$row_index][''. $seal['column_index'] .''] = 1;  
                            }

                        }
                    }
                }
//                die('-- dodo --');
                $excel = [
                    "sheet" => [
                        "products_data" => [
                            "name" => "products_data",
                            "names" => $product_names,
                            "version_details" => $product_version_details,
                            "external_ids" => $product_external_ids,
                            "unit_quantities" => $product_unit_quantities,
                            "num_versions" => $product_num_versions,
                            "version_multiplier" => $product_version_multiplier,
                            "packaging_type" => $products_packaging_types,
                            "ESL" => $esl_products,
                            'seals' => $seals
                        ],
                        "location_data" => [
                            "name" => "location_data",
                            "headers" => $location_headers,
                            "rows" => $rows
                        ]
                    ]
                ];

                $insert_file_query = "
                INSERT INTO 
                        `campagne_product_files` 
                                (
                                        `campagne_id`, 
                                        `name`,
                                        `type`,
                                        `products_id_row_index`,
                                        `products_name_row_index`,
                                        `products_version_details_row_index`,
                                        `products_version_row_index`,
                                        `products_version_multiplier_row_index`,
                                        `products_packaging_type_row_index`,
                                        `products_unit_quantity_row_index`,
                                        `locations_name_row_index`,
                                        `locations_start_row_index`,
                                        `locations_id_column_index`,
                                        `locations_name_column_index`,
                                        `locations_address_column_index`,
                                        `locations_address_number_column_index`,
                                        `locations_postal_code_column_index`,
                                        `locations_city_column_index`,
                                        `locations_rayon_column_index`,
                                        `locations_formule_column_index`,
                                        `excel_data_raw`,
                                        `excel_data`,
                                        `created`,
                                        `created_user_id`,
                                        `last_update`,
                                        `blame_user_id`
                                ) 
                        VALUES 
                                (
                                        '" . $campagne['id'] . "',
                                        '" . $db->real_escape_string($file['filename']) . "',
                                        'products',
                                        '" . $_POST['products_id_row_index'] . "',
                                        '" . $_POST['products_name_row_index'] . "',
                                        '" . $_POST['products_version_details_row_index'] . "',
                                        '" . $_POST['products_version_row_index'] . "',
                                        '" . $_POST['products_version_multiplier_row_index'] . "',
                                        '" . $_POST['products_packaging_type_row_index'] . "',
                                        '" . $_POST['products_unit_quantity_row_index'] . "',
                                        '" . $_POST['locations_name_row_index'] . "',
                                        '" . $_POST['locations_start_row_index'] . "',
                                        '" . $_POST['locations_id_column_index'] . "',
                                        '" . $_POST['locations_name_column_index'] . "',
                                        '" . $_POST['locations_address_column_index'] . "',
                                        '" . $_POST['locations_address_number_column_index'] . "',  
                                        '" . $_POST['locations_postal_code_column_index'] . "',
                                        '" . $_POST['locations_city_column_index'] . "',
                                        '" . $_POST['locations_rayon_column_index'] . "',
                                        '" . $_POST['locations_formule_column_index'] . "',
                                        '" . $db->real_escape_string(json_encode($excel_data_raw)) . "',
                                        '" . $db->real_escape_string(json_encode($excel)) . "',
                                        NOW(),
                                        '" . USER_ID . "',
                                        NOW(),
                                        '" . USER_ID . "'
                                )
                ";

                $action = $db->query($insert_file_query) or die($db->error);

                if ($action) {
                    header('Location: ' . CAMPAGNE_URL . '&product_file_id=' . $db->insert_id . '&dev');
                } else {
                    die($db->error);
                }
            } catch (\Exception $exc) {
                echo '<pre>';
                print_r($exc);
            }
        }
    }

    /*
      // UPDLOAD VARIATION LIST
     */

    if (!empty($_FILES['variation_list'])) {

        $skipvalues = ['Eindtotaal'];

        $file = upload_excel_file($_FILES['variation_list'], DIR_PICKLIST_EXCEL_FILES, true);

        $_POST['variations_start_row_index'] = 2;
        $_POST['locations_start_row_index'] = 2;
        $_POST['locations_address_column_index'] = 'D';
        $_POST['locations_postal_code_column_index'] = 'E';
        $_POST['locations_city_column_index'] = 'F';
        $_POST['locations_rayon_column_index'] = 'H';
        $_POST['locations_formule_column_index'] = 'G';

        $_POST['locations_name_row_index'] = 1;

        $variation_data_sheet = $_POST['variation_data_sheet_index'];
        $variation_data_header_row = 1;
        $variation_start_row = $_POST['variations_start_row_index'];
        $variation_data_variation_name_column = $_POST['variations_name_column_index'];

        $location_data_sheet = $_POST['location_data_sheet_index'];
        $location_data_header_row = $_POST['locations_name_row_index'];
        $location_start_row = $_POST['locations_start_row_index'];

        $location_data_external_id_column = $_POST['locations_id_column_index'];

        $location_data_location_name_column = $_POST['locations_name_column_index'];
        $location_data_location_address_1_column = $_POST['locations_address_column_index'];
        $location_address_number_column_index = $_POST['locations_address_number_column_index'];
        $location_data_location_postalcode_column = $_POST['locations_postal_code_column_index'];
        $location_data_location_city_column = $_POST['locations_city_column_index'];
        $location_data_location_rayon_column = $_POST['locations_rayon_column_index'];
        $location_data_location_formule_column = $_POST['locations_formule_column_index'];

        $location_data_variation_name_column = $_POST['variantions_location_match_column'];

        try {

            $inputFileType = PHPExcel_IOFactory::identify($file['src']);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($file['src']);

            $excel_variation_data = $objPHPExcel->getSheet($variation_data_sheet)->toArray(null, true, true, true);
            $excel_locations_data = $objPHPExcel->getSheet($location_data_sheet)->toArray(null, true, true, true);

            // Variations Data

            $variation_data_headers = [];
            foreach ($excel_variation_data[$variation_data_header_row] as $column => $header) {
                if (!empty($header)) {
                    $variation_data_headers["" . $column . ""] = $header;
                }
            }

            $variations = [];
            $variation_data_rows = [];
            for ($x = $variation_start_row; $x <= count($excel_variation_data); $x++) {
                $variantname = false;
                $row = $excel_variation_data[$x];
                $rowdata = [];
                foreach ($row as $column => $value) {
                    if (!array_key_exists("" . $column . "", $variation_data_headers)) {
                        continue;
                    }
                    $rowdatavalue = $value;
                    if ($column === $variation_data_variation_name_column) {
                        $rowdatavalue = return_product_variant_name($value);
                        if (!empty($rowdatavalue) && !not_empty("" . $rowdatavalue . "", $variations)) {
                            $variations["" . $rowdatavalue . ""] = 0;
                            $variantname = $rowdatavalue;
                        }
                    }
                    $rowdata[$column] = return_str($rowdatavalue);
                }
                if (!$variantname) {
                    continue;
                }
                $variation_data_rows[($x - 1)] = $rowdata;
            }

            ksort($variations);

            // Locations Data

            $location_data_headers = [];
            foreach ($excel_locations_data[$location_data_header_row] as $column => $header) {
                if (!empty($header)) {
                    $location_data_headers["" . $column . ""] = $header;
                }
            }

            $unknowns = [];
            $location_rows = [];
            for ($x = $location_start_row; $x <= count($excel_locations_data); $x++) {
                $variantname = false;
                $row = $excel_locations_data[$x];
                $rowdata = [];
                foreach ($row as $column => $value) {
                    if (!array_key_exists("" . $column . "", $location_data_headers)) {
                        continue;
                    }
                    $rowdatavalue = $value;
                    if ($column === $location_data_external_id_column) {
                        $rowdatavalue = $value;
                    }
                    if ($column === $location_data_variation_name_column) {

                        $rowdatavalue = return_product_variant_name($value);

                        if (!empty($rowdatavalue)) {
                            $variantname = $rowdatavalue;
                        }
                        if (!array_key_exists("" . $rowdatavalue . "", $variations)) {
                            $unknowns[] = [
                                "variant" => $rowdatavalue,
                                "location" => $row
                            ];
                        } else {
                            $variations["" . $rowdatavalue . ""]++;
                        }
                    }
                    $rowdata[$column] = return_str($rowdatavalue);
                }
                if (!$variantname) {
                    continue;
                }
                $location_rows[($x - 1)] = $rowdata;
            }

            // Combined Data

            $excel = [
                "sheet" => [
                    "variation_data" => [
                        "name" => "variation_data",
                        "variations" => $variations,
                        "unknown" => $unknowns,
                        "headers" => $variation_data_headers,
                        "rows" => $variation_data_rows
                    ],
                    "location_data" => [
                        "name" => "location_data",
                        "headers" => $location_data_headers,
                        "rows" => $location_rows
                    ]
                ]
            ];

            $action = $db->query("
			INSERT INTO 
				`campagne_product_files` 
					(
                                            `campagne_id`, 
                                            `name`,
                                            `type`,

                                            `location_data_sheet_index`, 
                                            `variation_data_sheet_index`,
                                            `variations_name_column_index`, 
                                            `variantions_location_match_column`, 
                                            `variations_start_row_index`, 
                                            `locations_name_row_index`, 
                                            `locations_id_column_index`, 
                                            `locations_name_column_index`, 
                                            `locations_address_column_index`, 
                                            `locations_postal_code_column_index`, 
                                            `locations_city_column_index`, 
                                            `locations_rayon_column_index`,
                                            `locations_formule_column_index`,
                                            `locations_start_row_index`,
                                            `excel_data`,
                                            `created`,
                                            `created_user_id`,
                                            `last_update`,
                                            `blame_user_id`
					) 
				VALUES 
					(
						'" . $campagne['id'] . "',
						'" . $db->real_escape_string($file['filename']) . "',
						'variation_products',

						'" . $db->real_escape_string($_POST['location_data_sheet_index']) . "',
						'" . $db->real_escape_string($_POST['variation_data_sheet_index']) . "',
						'" . $db->real_escape_string($_POST['variations_name_column_index']) . "',
						'" . $db->real_escape_string($_POST['variantions_location_match_column']) . "',
						'" . $db->real_escape_string($_POST['variations_start_row_index']) . "',
						'" . $db->real_escape_string($_POST['locations_name_row_index']) . "',
						'" . $db->real_escape_string($_POST['locations_id_column_index']) . "',
						'" . $db->real_escape_string($_POST['locations_name_column_index']) . "',
						'" . $db->real_escape_string($_POST['locations_address_column_index']) . "',
						'" . $db->real_escape_string($_POST['locations_postal_code_column_index']) . "',
						'" . $db->real_escape_string($_POST['locations_city_column_index']) . "',
                                                '" . $db->real_escape_string($_POST['locations_rayon_column_index']) . "',
                                                '" . $db->real_escape_string($_POST['locations_formule_column_index']) . "',
						'" . $db->real_escape_string($_POST['locations_start_row_index']) . "',

						'" . json_encode($excel) . "',
						NOW(),
						'" . USER_ID . "',
						NOW(),
						'" . USER_ID . "'
					)
			") or die($db->error);

            if ($action) {
                header('Location: ' . CAMPAGNE_URL . '&product_file_id=' . $db->insert_id . '&dev');
            } else {
                die($db->error);
            }

            //$var  = return_product_variant_name("dwdwdwdw efwefe cwqofdw");
            // arr($excel_locations_data);
        } catch (\Exception $exc) {

            arr($exc);
        }
    }
    ?>

    <div class="tabs" style="margin-top:15px;">
        <?php
        if ($campagne['archive'] == "1") {
            include("tab_om_campagne_picklists.php");
            //  include("tab_campagne_products_files.php");
            include("tab_campagne_settings.php");
        } else {
            if ($user['department'] === "logistics") {
                include("tab_logistics_campagne_picklist_trucks.php");
            } else if ($user['department'] === "handling") {
                include("tab_om_campagne_picklists.php");
                include("tab_container_content.php");
            } else if ($user['department'] === "om") {
                if (count($campagne['products']) > 0) {
                    include("tab_campagne_products_overview.php");
                }
                include("tab_campagne_products_files.php");
                include("tab_om_dcs_distributionlists.php");
                if (count($campagne['products']) > 0) {
                    include("tab_container_content.php");
                    include("tab_om_campagne_picklists.php");
                }
                include("tab_campagne_settings.php");
            }
        }
        ?>
    </div>
    <?php
}
?>



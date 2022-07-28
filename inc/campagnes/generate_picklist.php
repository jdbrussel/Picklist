<?php

function _string($string) {
    return htmlentities($string, ENT_QUOTES);
}

if (!empty($_GET['box_id']) && is_numeric($_GET['box_id'])) {
    $_POST['box_ids'][] = $_GET['box_id'];
}
if (!empty($_GET['truck_id']) && is_numeric($_GET['truck_id'])) {
    $_POST['truck_ids'][] = $_GET['truck_id'];
} elseif (!empty($_GET['lc_id']) && is_numeric($_GET['lc_id'])) {
    $_POST['logistic_center_id'][] = $_GET['lc_id'];
}
$logistic_center_id = 0;
if (array_key_exists('logistic_center_id', $_POST)) {
    $logistic_center_id = count($_POST['logistic_center_id']);
}
$truck_ids = 0;
if (array_key_exists('truck_ids', $_POST)) {
    $truck_ids = count($_POST['truck_ids']);
}
$box_ids = 0;
if (array_key_exists('box_ids', $_POST)) {
    $box_ids = count($_POST['box_ids']);
}
if ($box_ids > 0 && ( $truck_ids > 0 || $logistic_center_id > 0)) {
    $_POST['generate-picklist'] = true;
}


?>

<div class="tabs" data-back="<?= CAMPAGNE_URL; ?>#picklists">

    <div class="tab" data-tab-id="picklist" data-name="Picklijsten genereren" data-icon="list_alt">

        <?php
        $boxes = [];
        foreach ($campagne['distribution']['container']['boxes'] as $box) {
            if (count($box['campagne_boxes']) > 0) {
                foreach ($box['campagne_boxes'] as $campagne_box) {
                    $boxes[$campagne_box['id']] = $campagne_box;
                }
            }
        }

        $dcs = [];
        foreach ($campagne['distribution']['dcs'] as $dc) {
            $dcs[$dc['dc_id']] = [
                'id' => $dc['dc_id'],
                'name' => _string($dc['dc']['name'])
            ];

            if (count($campagne['distribution']['dcs'][$dc['dc_id']]['trucks'])) {
                foreach ($campagne['distribution']['dcs'][$dc['dc_id']]['trucks'] as $truck) {
                    $dcs[$dc['dc_id']]['trucks'][$truck['id']] = [
                        'id' => $truck['id'],
                        'name' => $truck['name'],
                        'due_datetime' => $truck['due_datetime']
                    ];
                }
            }
        }

        $truck_ids = [];
        if (!empty($_POST['truck_ids'])) {
            if (count($_POST['truck_ids']) > 0) {
                $truck_ids = $_POST['truck_ids'];
            }
        }
        $box_ids = [];
        if (!empty($_POST['box_ids'])) {
            if (count($_POST['box_ids']) > 0) {
                $box_ids = $_POST['box_ids'];
            }
        }


        if (!empty($_POST['generate-picklist'])) {

            $boxes = $campagne['distribution']['container']['boxes'];

            $picklistdata = [
                'campagne' => [
                    'account' => _string($account['name']),
                    'name' => _string($campagne['name']),
                    'container' => _string($campagne['distribution']['container']['name']),
                    'erp_id' => $campagne['erp_id'],
                    'palletlist_address' => _string($campagne['palletlist_address']),
                    'palletlist_num_items' => (integer) $campagne['palletlist_num_items']
                ],
                'locations' => []
            ];

            $station = 0;

            foreach ($boxes as $box) {

                if (count($box['campagne_boxes']) === 0) {
                    continue;
                }

                foreach ($box['campagne_boxes'] as $campagne_box) {

                    if (empty($campagne_box['campagne_products'])) {
                        continue;
                    }

                    if (!in_array($campagne_box['id'], $box_ids)) {
                        continue;
                    }

                    if (USER_ID === 1 && 1 === 2) {

                        foreach ($campagne_box['products'] as $campagne_product) {

                            $campagne_product_external_id = false;

                            $campagne_product['products'] = [];

                            if ($campagne_product['product_type'] === 'set' || $campagne_product['variations'] > 0) {

                                $campagne_product['included_products'] = [];

                                if ($campagne_product['product_type'] === 'set') {

                                    //  SET PRODUCTS

                                    $included_products = json_decode($campagne_product['included_external_products'], true);

                                    // END SET PRODUCTS
                                } else if ($campagne_product['variations'] > 0) {

                                    // VARIATION PRODUCTS

                                    $campagne_product['external_id'] = return_article_number($campagne_product['campagne_product_id'], 'BV');

                                    $included_products = json_decode($campagne_product['variations_data'], true);

                                    // $campagne_product['num_variants'] = count($included_products);
                                    // END VARIATION PRODUCTS
                                }

                                $campagne_product['num_products'] = count($included_products);

                                foreach ($included_products as $included_product) {

                                    $campagne_product['included_products']["" . $included_product['external_id'] . ""] = $included_product['name'];
                                }

                                $campagne_product['products'] = array_keys($campagne_product['included_products']);
                            } else {

                                // SINGLE PRODUCTS

                                if (not_empty('external_id', $campagne_product)) {
                                    $campagne_product['products'][] = $campagne_product['external_id'];
                                }

                                $campagne_product['num_products'] = 1;

                                // END SINGLE PRODUCTS
                            }


                            $i = 0;

                            $name = $campagne_product['name'];
                            $campagne_product['name_addition'] = '';

                            $campagne_product['station_id'] = false;

                            if ($campagne_product['set_delivery'] === 'seperate') {
                                $campagne_product['set_delivery'] = 'separate';
                            }

                            $product_type = $campagne_product['product_type'];
                            $set_delivery = $campagne_product['set_delivery'];
                            $stations = ($campagne_product['stations'] === "combined" ? "combined" : "separate");


                            // arr($campagne_product,false);

                            $singleproducts_set_deliveries = ['set', 'separate'];

                            if (($product_type === 'product') || $stations === 'combined') {

                                $campagne_product['station_id'] = station_name($station, 2);
                                $station++;

                                if ($set_delivery === 'variations') {
                                    $campagne_product['name_addition'] = count($campagne_product['products']) . " variations";
                                }

                                arr($campagne_product['station_id'] . " -1- " . $name . " - " . $campagne_product['name_addition'], false);
                            } else if (count($campagne_product['products']) > 0 && $stations === 'separate') {

                                $cp_index = 1;

                                foreach ($campagne_product['products'] as $cp) {

                                    // each included product

                                    $campagne_product['external_id'] = $cp;

                                    if ($set_delivery === 'variations') {

                                        arr($set_delivery);
                                    }
                                    if ($product_type === 'set' && $set_delivery === 'separate' && $stations === 'separate') {

                                        $campagne_product['station_id'] = station_name($station, 2) . "-" . return_index($cp_index);

                                        $variant_name = $campagne_product['included_products']["" . $cp . ""];

                                        $campagne_product['name_addition'] = $cp_index . '/' . count($campagne_product['products']) . " -  " . $variant_name;

                                        if ($cp_index === count($campagne_product['products'])) {
                                            $station++;
                                        }

                                        $cp_index++;
                                    }

                                    arr($campagne_product['station_id'] . " -2- " . $name . " - " . $campagne_product['name_addition'], false);

                                    // end each included 
                                }
                            }





// if( ($campagne_product['product_type'] === 'set' && $campagne_product['set_delivery'] === 'set') || ($campagne_product['product_type'] === 'product')) {
// 	$campagne_product['station_id'] = station_name($station, 2);
// 	$station++;
// }
// arr($campagne_product, false);
// foreach($campagne_product['products'] as $cp) {
// 	$i++;
// 	$picklist_data = json_decode($campagne_product['picklist_data'], true);
// 	if($campagne_product['product_type'] === 'set' && $campagne_product['set_delivery'] === 'seperate' && $campagne_product['stations'] === 'separate') {
// 		$campagne_product['station_id'] = station_name($station, 2);
// 		$station++;
// 	}
// 	if($campagne_product['stations'] === 'combined') {
// 		$campagne_product['station_id'] = station_name($station, 2) . "-". return_index($i);
// 	}
// 	arr($campagne_product['station_id'] . ' --- ' . $cp, false);
// }
                        }


                        arr('die');
                    }

                    foreach ($campagne_box['products'] as $campagne_product) {

                        $campagne_product_external_id = false;

                        if (strlen($campagne_product['external_id']) < 2) {

                            // arr($campagne_product, false);

                            if ($campagne_product['product_type'] === 'set') {

                                $campagne_product['external_id'] = return_article_number($campagne_product['campagne_product_id'], 'BS');
                                $campagne_product['external_id'] = '';


                                $included_products = json_decode($campagne_product['included_external_products'], true);

                                $campagne_product['included_products'] = [];

                                foreach ($included_products as $included_product) {

                                    $campagne_product['included_products']['' . $included_product['external_id'] . ''] = $included_product['name'];
                                }

                                $campagne_product['products'] = json_decode($campagne_product['included_external_ids'], true);

                                $campagne_product['num_products'] = count($campagne_product['products']);
                            } else if ($campagne_product['variations'] > 0) {

                                $campagne_product['external_id'] = return_article_number($campagne_product['campagne_product_id'], 'BV');

                                $campagne_product['num_variants'] = count(json_decode($campagne_product['variations_data'], true));
                            }
                        }


                        $campagne_product_external_id = return_article_number($campagne_product['external_id']);

                        if ($campagne_product_external_id) {

                            if (!array_key_exists('products', $campagne_product)) {
                                $campagne_product['products'] = [];
                            }

                            if (!in_array('' . $campagne_product_external_id . '', $campagne_product['products'])) {
                                $campagne_product['products'][] = $campagne_product_external_id;
                            }
                        }

                        $campagne_product['num_products'] = count($campagne_product['products']);

                        $i = 0;
                        $name = $campagne_product['name'];

                        // arr($campagne_product['name'], false);
                        // arr($campagne_product['external_id'], false);
                        // arr($campagne_product['products'], false);

                        foreach ($campagne_product['products'] as $cp) {

                            // CP START
                            /////////////// 


                            $i++;

                            $campagne_product['external_id'] = $cp;

                            $campagne_product['name_addition'] = '';

                            if (count($campagne_product['products']) > 1) {

                                $campagne_product['name_addition'] = $i . '/' . count($campagne_product['products']);

                                if (array_key_exists("" . $campagne_product['external_id'] . "", $campagne_product['included_products'])) {

                                    $campagne_product['name_addition'] = $campagne_product['included_products']["" . $campagne_product['external_id'] . ""];
                                }

                                $campagne_product['product_type'] = 'product';

                                $campagne_product['picklist_note'] = '';
                            }

                            $picklist_data = json_decode($campagne_product['picklist_data'], true);

                            $campagne_product['station_id'] = station_name($station, 2);
                            $station++;

                            foreach ($picklist_data as $location_container) {

                                $location_external_id = $location_container['external_id'];

                                if (!$location_external_id || intval($location_container['quantity']) < 1) {
                                    continue;
                                }

                                $data = [
                                    'name' => _string($location_container['name']),
                                    'external_id' => $location_external_id
                                ];

                                if (not_empty("" . $location_external_id . "", $account['locations'])) {
                                    $data = $account['locations']["" . $location_container['external_id'] . ""];
                                }


                                if (!not_empty("group_address_id", $data)) {
                                    // arr($data, false);
                                }

                                if (!not_empty("" . $location_external_id . "", $picklistdata['locations'])) {

                                    $picklistdata['locations']["" . $location_external_id . ""] = [
                                        'name' => _string($data['name']),
                                        'external_id' => $location_external_id,
                                        'data' => $data,
                                        'box' => []
                                    ];
                                }

                                if (!not_empty($campagne_box['id'], $picklistdata['locations']["" . $location_external_id . ""]['box'])) {

                                    $picklistdata['locations']["" . $location_external_id . ""]['box'][$campagne_box['id']] = [
                                        'name' => _string($campagne_box['name']),
                                        'id' => $campagne_box['id'],
                                        'products' => []
                                    ];
                                }

                                if (!not_empty("" . $campagne_product['external_id'] . "", $picklistdata['locations']["" . $location_external_id . ""]['box'][$campagne_box['id']]['products'])) {

                                    $products = json_decode($campagne_product['included_external_products'], true);

                                    $stock_location = [];

                                    if (array_key_exists('stock_data', $campagne_product)) {

                                        $stock = json_decode($campagne_product['stock_data'], true);

                                        if (array_key_exists('stock', $stock)) {

                                            if (array_key_exists('location', $stock['stock'])) {

                                                $stock_location = $stock['stock']['location'];
                                            }
                                        }
                                    }

                                    $campagne_product['content_description'] = product_content_description($campagne_product['product_type'], count($products));

                                    $unit_information = '';

                                    if ($campagne_product['unit_quantity'] > 1) {

                                        $location_container['quantity'] = ceil($location_container['quantity'] / $campagne_product['unit_quantity']);

                                        // if($location_container['quantity'] >= $campagne_product['unit_quantity']) {
                                        // 	$location_container['quantity'] = $location_container['quantity'] / $campagne_product['unit_quantity'];
                                        // }

                                        $unit_information = "Dit product is gebundeld per " . number_format($campagne_product['unit_quantity'], 0, ',', '.') . " stuks";
                                    }


                                    if ($campagne_product['value_product'] === "1") {

                                        $campagne_product['name_addition'] = "Waardeproduct";
                                    }

                                    $num_products = count($products);

                                    $campagne_product['variation_product'] = "0";

                                    $variant_name = '';

                                    if (is_numeric($campagne_product['variations']) && $campagne_product['variations'] > 1) {

                                        $num_products = 1;

                                        $campagne_product['variation_product'] = "1";

                                        $campagne_product['name_addition'] = "Variatieproduct";
                                    }


                                    $picklistdata['locations']["" . $location_external_id . ""]['box'][$campagne_box['id']]['products']["" . $campagne_product['external_id'] . ""] = [
                                        'station_id' => $campagne_product['station_id'],
                                        'external_id' => $campagne_product['external_id'],
                                        'product_type' => $campagne_product['product_type'],
                                        'num_products' => $num_products,
                                        'included_external_ids' => $products,
                                        'name' => _string($campagne_product['name']),
                                        'name_addition' => _string($campagne_product['name_addition']),
                                        'unit_information' => $unit_information,
                                        'content_description' => $campagne_product['content_description'],
                                        'picklist_note' => $campagne_product['picklist_note'],
                                        'stock_location' => $stock_location,
                                        'unit_quantity' => $campagne_product['unit_quantity'],
                                        'quantity' => $location_container['quantity'],
                                        'products' => $campagne_product['products'],
                                        'variation_product' => $campagne_product['variation_product'],
                                        'value_product' => $campagne_product['value_product']
                                    ];

                                    if (array_key_exists('variant', $location_container)) {

                                        $picklistdata['locations']["" . $location_external_id . ""]['box'][$campagne_box['id']]['products']["" . $campagne_product['external_id'] . ""]['variant'] = $location_container['variant'];
                                    }
                                }
                            }
                            ///////////////
                            // CP END 
                        }
                    }

                    // arr('die');
                }
            }


            $ret = [];

            $ret['campagne'] = [
                'id' => $campagne['id'],
                'name' => _string($campagne['name']),
                'erp_id' => $campagne['erp_id'],
                'palletlist_address' => _string($campagne['palletlist_address']),
                'palletlist_num_items' => (integer) $campagne['palletlist_num_items']
            ];

            if (array_key_exists('logistic_center_id', $_POST) && is_array($_POST['logistic_center_id'])) {
// arr($campagne['distribution']['logistic_centers']);
                $lc = $campagne['distribution']['logistic_centers']['' . $_POST['logistic_center_id'][0] . ''];

                $ret['destination'] = [];
                $ret['destination'][$lc['data']['id']]['type'] = "logistic_center";
                $ret['destination'][$lc['data']['id']]['name'] = _string($lc['data']['name']);
                $ret['destination'][$lc['data']['id']]['id'] = $lc['data']['id'];
                $ret['destination'][$lc['data']['id']]['external_id'] = $lc['data']['external_id'];
                $ret['destination'][$lc['data']['id']]['color_cmyk'] = "cmyk " . $lc['data']['color_cmyk'] . "";
                $ret['destination'][$lc['data']['id']]['trucks'] = [];
                $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'] = [];

                $locations = json_decode($lc['locations'], true);

                $location_ids = json_decode($lc['location_ids'], true);

                $variations = [];

                foreach ($location_ids as $external_location_id) {

                    if (empty($external_location_id)) {
                        continue;
                    }


                    if (array_key_exists("" . $external_location_id . "", $picklistdata['locations'])) {

                        $location = $picklistdata['locations']['' . $external_location_id . ''];

                        foreach ($location['data'] as $key => $value) {
                            $location['data']["" . $key . ""] = _string($value);
                        }

                        $location_data = $locations['' . $external_location_id . ''];

                        foreach ($location['box'] as $box) {

                            if (!in_array($box['id'], $box_ids)) {
                                continue;
                            }

                            if (!array_key_exists('' . $box['id'] . '', $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'])) {
                                $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']] = [];
                                $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['id'] = $box['id'];
                                $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['name'] = _string($box['name']);
                                $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products'] = [];
                                $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container'] = [];
                            }

                            if (count($box['products']) > 0) {

                                foreach ($box['products'] as $campagne_product) {

                                    if (not_empty('variant', $campagne_product)) {

                                        if (!array_key_exists("" . $campagne_product['external_id'] . "", $variations)) {

                                            $variations["" . $campagne_product['external_id'] . ""] = [];
                                        }

                                        if (!array_key_exists("" . $campagne_product['variant']['id'] . "", $variations["" . $campagne_product['external_id'] . ""])) {

                                            $variations["" . $campagne_product['external_id'] . ""]["" . $campagne_product['variant']['id'] . ""] = $campagne_product['variant']['id'] . ' - ' . $campagne_product['variant']['name'];
                                        }

                                        ksort($variations["" . $campagne_product['external_id'] . ""]);
                                    }


                                    if (!array_key_exists("" . $external_location_id . "", $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container'])) {

                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container']["" . $external_location_id . ""] = [];
                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container']["" . $external_location_id . ""]['name'] = _string($location['name']);
                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container']["" . $external_location_id . ""]['data'] = json_encode($location['data']);
                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container']["" . $external_location_id . ""]['delivery'] = [];

                                        if (array_key_exists('campagne_logistic_centers_container', $location_data)) {
                                            $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container']["" . $external_location_id . ""]['delivery'] = $location_data['campagne_logistic_centers_container'];
                                        }

                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container']["" . $external_location_id . ""]['products'] = [];
                                    }


                                    if ($campagne_product['quantity'] > 0) {

                                        $location_campagne_product = $campagne_product;

                                        unset($location_campagne_product['picklist_note']);
                                        unset($location_campagne_product['included_external_ids']);

                                        if (not_empty('variant', $location_campagne_product)) {
                                            $location_campagne_product['external_id'] = $location_campagne_product['variant']['external_id'] . '-' . $location_campagne_product['variant']['id'];
                                            $location_campagne_product['name_addition'] = $location_campagne_product['variant']['name'];
                                        }

                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['container']["" . $external_location_id . ""]['products'][] = $location_campagne_product;
                                    }

                                    if (!array_key_exists("" . $campagne_product['external_id'] . "", $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products'])) {
                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""] = $campagne_product;
                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""]['locations'] = [];
                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""]['quantity'] = 0;
                                    }

                                    if ($campagne_product['quantity'] > 0) {
                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""]['quantity'] += intval($campagne_product['quantity']);
                                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""]['locations']["" . $external_location_id . ""] = intval($campagne_product['quantity']);
                                    }
                                }
                            }
                        }
                    }
                }

                if (count($variations) > 0) {
                    foreach ($variations as $external_id => $variants) {
                        $ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products']["" . $external_id . ""]['variations'] = $variants;
                        unset($ret['destination'][$lc['data']['id']]['trucks'][0]['boxes'][$box['id']]['products']["" . $external_id . ""]['variant']);
                    }
                }
            }


            if (array_key_exists('truck_ids', $_POST) && is_array($_POST['truck_ids'])) {

                $ret['locations'] = [];
                $ret['destination'] = [];
                $variations = [];

                foreach ($campagne['distribution']['dcs'] as $dc) {

                    foreach ($dc['trucks'] as $truck) {


                        if (!in_array($truck['id'], $truck_ids)) {
                            continue;
                        }

                        if (!array_key_exists($dc['dc_id'], $ret['destination'])) {
                            $ret['destination'][$dc['dc_id']] = [];
                            $ret['destination'][$dc['dc_id']]['type'] = 'distribution_center';
                            $ret['destination'][$dc['dc_id']]['name'] = _string($dc['dc']['name']);
                            $ret['destination'][$dc['dc_id']]['id'] = $dc['dc_id'];
                            $ret['destination'][$dc['dc_id']]['external_id'] = $dc['dc']['external_id'];
                            $ret['destination'][$dc['dc_id']]['color_cmyk'] = "cmyk " . $dc['dc']['color_cmyk'] . "";
                            $ret['destination'][$dc['dc_id']]['trucks'] = [];
                        }
                        if (!array_key_exists($truck['id'], $ret['destination'][$dc['dc_id']]['trucks'])) {

                            $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['id'] = $truck['id'];

                            if (!empty($truck['due_datetime'])) {
                                $datetime = explode(' ', $truck['due_datetime']);
                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['due_date'] = $datetime[0];
                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['due_time'] = $datetime[1];
                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['due_datetime'] = $truck['due_datetime'];
                            }

                            $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['delivery_note'] = _string($truck['delivery_note']);
                            $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'] = [];
                        }

                        //echo '<div class="picklist_line box">DC: ' . $dc['dc_id'] . ' - ' . $dc['dc']['name'] . '</div>';
                        //echo '<div class="picklist_line box">Truck '. $truck['id'] . ': ' . $truck['due_time'] . '</div>';

                        $location_ids = json_decode($truck['location_ids'], true);

                        // arr($location_ids);

                        foreach ($location_ids as $external_location_id) {

                            if (array_key_exists("" . $external_location_id . "", $picklistdata['locations'])) {

                                $location = $picklistdata['locations']["" . $external_location_id . ""];

                                foreach ($location['data'] as $key => $value) {
                                    $location['data']["" . $key . ""] = _string($value);
                                }

                                //echo '<div class="picklist_line box"><strong>'.return_index($i). ' | Filiaal: '.$external_location_id.' - '. $location['name'] . '</strong><div class="truck_id">DC '.$dc['dc']['name'] .' -  Truck '. $truck['id'] . ': ' . $truck['due_time'] . '</div></div>';

                                foreach ($location['box'] as $box) {

                                    if (!in_array($box['id'], $box_ids)) {
                                        continue;
                                    }

                                    if (!array_key_exists($box['id'], $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'])) {
                                        $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']] = [
                                            "id" => $box['id'],
                                            "name" => _string($box['name']),
                                            "products" => [],
                                            "container" => []
                                        ];
                                    }

                                    // echo '<div class="picklist_line">'. $box['name'] . '</div>';

                                    if (count($box['products']) > 0) {

                                        // arr(count($box['products']), false);

                                        foreach ($box['products'] as $campagne_product) {

                                            if ($campagne_product['num_products'] < 1) {
                                                $campagne_product['num_products'] = 1;
                                            }
                                            // arr($campagne_product, false);

                                            if (!array_key_exists("" . $external_location_id . "", $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['container'])) {
                                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['container']["" . $external_location_id . ""] = [
                                                    "name" => _string($location['name']),
                                                    "data" => $location['data'],
                                                    "products" => []
                                                ];
                                                // arr($ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['container']["" . $external_location_id . ""]);
                                            }

                                            if ($campagne_product['quantity'] > 0) {

                                                ///arr( $external_location_id . ' =  '. $campagne_product['station_id'] . ' = '. $campagne_product['quantity'], false);

                                                $location_campagne_product = $location['box'][$box['id']]['products']["" . $campagne_product['external_id'] . ""];

                                                unset($location_campagne_product['included_external_ids']);

                                                if (array_key_exists('variant', $location_campagne_product)) {

                                                    $location_campagne_product['product_type'] = 'variant';

                                                    $variant_external_id = return_article_number($location_campagne_product['variant']['external_id']);

                                                    if ($variant_external_id) {
                                                        $location_campagne_product['external_id'] = $variant_external_id;
                                                        $location_campagne_product['name_addition'] = $location_campagne_product['variant']['name'];
                                                    }

                                                    $location_campagne_product['external_id'] .= '-' . $location_campagne_product['variant']['id'];

                                                    if (is_array($variations) && !array_key_exists('' . $campagne_product['external_id'] . '', $variations)) {
                                                        $variations['' . $campagne_product['external_id'] . ''] = [];
                                                    }

                                                    if (!array_key_exists("" . $location_campagne_product['variant']['id'] . "", $variations["" . $campagne_product['external_id'] . ""])) {

                                                        $variations["" . $campagne_product['external_id'] . ""]["" . $location_campagne_product['variant']['id'] . ""] = $location_campagne_product['variant']['id'] . " - " . $location_campagne_product['variant']['name'];
                                                        ksort($variations['' . $campagne_product['external_id'] . '']);
                                                    }
                                                }

                                                unset($location_campagne_product['variant']);
                                                unset($location_campagne_product['picklist_note']);

                                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['container']["" . $external_location_id . ""]['products'][] = $location_campagne_product;
                                            }

                                            if (!array_key_exists("" . $campagne_product['external_id'] . "", $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['products'])) {
                                                unset($campagne_product['variant']);
                                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""] = $campagne_product;
                                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""]['locations'] = [];
                                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""]['quantity'] = intval($campagne_product['quantity']);
                                            } else {
                                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""]['quantity'] += intval($campagne_product['quantity']);
                                            }

                                            if ($campagne_product['quantity'] > 0) {
                                                $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['products']["" . $campagne_product['external_id'] . ""]['locations']["" . $external_location_id . ""] = intval($campagne_product['quantity']);
                                            }
                                        }

                                        foreach ($variations as $external_id => $variation) {
                                            $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['products']["" . $external_id . ""]['variations'] = $variation;
                                            $ret['destination'][$dc['dc_id']]['trucks'][$truck['id']]['boxes'][$box['id']]['products']["" . $external_id . ""]['picklist_note'] = '';
                                        }
                                    }
                                }
                            }
                        }
                        //arr($ret);
                    }
                }
            }

            $locations = [];

            if (!empty($_POST['logistic_center_id'])) {

                foreach ($_POST['logistic_center_id'] as $logistic_center_id) {

                    if (!array_key_exists("" . $logistic_center_id . "", $campagne['distribution']['logistic_centers'])) {
                        continue;
                    }

                    $destination = $campagne['distribution']['logistic_centers']["" . $logistic_center_id . ""]['data'];
                    $destination['type'] = 'logistic_center';

                    $truck = false;

                    $location_data = json_decode($lc['locations'], true);
                    // arr($location_data);
                    foreach ($location_data as $location) {

                        if (array_key_exists("" . $location['external_id'] . "", $locations)) {
                            continue;
                        }
                        if (strpos($location['external_id'], 'V') > -1) {
                            //  continue;
                        }

                        $item = [
                            'delivery_address' => [
                                'external_id' => $location['external_id'],
                                'name' => _string($location['name']),
                                'address_1' => _string($location['address_1']),
                                'postal_code' => _string($location['postal_code']),
                                'city' => _string($location['city']),
                                'country' => _string($location['country'])
                            ],
                            'destination' => [
                                'id' => $destination['dcs_logistic_center_id'],
                                'type' => $destination['type'],
                                'name' => _string($destination['name']),
                                'external_id' => $destination['external_id'],
                                'color_cmyk' => 'cmyk ' . $destination['color_cmyk']
                            ]
                        ];

                        if (array_key_exists('campagne_logistic_centers_container', $location)) {

                            $item['delivery'] = [
                                'id' => 'L' . $destination['dcs_logistic_center_id'],
                                'due_datetime' => $location['campagne_logistic_centers_container']['datetime'],
                                'date' => $location['campagne_logistic_centers_container']['date'],
                                'time' => $location['campagne_logistic_centers_container']['time'],
                                'delivery_note' => _string($location['campagne_logistic_centers_container']['delivery_note'])
                            ];
                        }

                        $item['boxes'] = [];

                        $locations["" . $location['external_id'] . ""] = $item;
                    }
                }
            } else if (!empty($_POST['truck_ids']) && count($_POST['truck_ids']) > 0) {

                foreach ($_POST['truck_ids'] as $truck_id) {

                    $truck = $campagne['distribution']['trucks']['' . $truck_id . ''];

                    $location_data = json_decode($truck['locations'], true);
                

                    $destination = $account['dcs']['' . $truck['dc_id'] . ''];

                    $destination['type'] = 'distribution_center';

                    foreach ($location_data as $location) {

                        if (array_key_exists("" . $location['external_id'] . "", $locations)) {
                            continue;
                        }

                        if (!array_key_exists('group', $location)) {
                            continue;
                        }

                        $item = [
                            'delivery_address' => [
                                'external_id' => $location['external_id'],
                                'name' => _string($location['group']['name']),
                                'address_1' => _string($location['group']['address_1']),
                                'postal_code' => _string($location['group']['postal_code']),
                                'city' => _string($location['group']['city']),
                                'country' => _string($location['group']['country'])
                            ],
                            'destination' => [
                                'id' => $destination['id'],
                                'type' => $destination['type'],
                                'name' => _string($destination['name']),
                                'external_id' => $destination['external_id'],
                                'color_cmyk' => 'cmyk ' . $destination['color_cmyk'],
                                'truck_id' => $truck['id']
                            ],
                            'delivery' => [
                                'id' => 'T' . $truck['id'],
                                'due_datetime' => $truck['due_datetime'],
                                'date' => $truck['date'],
                                'time' => $truck['time'],
                                'delivery_note' => _string($truck['delivery_note'])
                            ]
                        ];
                        $item['boxes'] = [];

                        $locations["" . $location['external_id'] . ""] = $item;
                    }
                }
            }


            $containers = [
                'campagne' => [
                    'id' => CAMPAGNE_ID,
                    'name' => _string($campagne['name']),
                    'erp_id' => $campagne['erp_id'],
                    'palletlist_address' => _string($campagne['palletlist_address']),
                    'palletlist_num_items' => (integer) $campagne['palletlist_num_items']
                ],
                'destination' => [
                    'id' => $destination['id'],
                    'name' => _string($destination['name']),
                    'type' => $destination['type'],
                    'external_id' => $destination['external_id'],
                    'color_cmyk' => 'cmyk ' . $destination['color_cmyk'],
                ],
                'container' => [
                    'id' => $campagne['distribution']['container']['id'],
                    'name' => _string($campagne['distribution']['container']['name'])
                ]
            ];

            if (is_array($truck)) {

                $containers['truck'] = [
                    'id' => $truck['id'],
                    'name' => _string($truck['name']),
                    'due_datetime' => $truck['due_datetime'],
                    'delivery_note' => _string($truck['delivery_note'])
                ];
            }

            $containers['containers'] = [];

            foreach ($campagne['distribution']['container']['boxes'] as $item) {

                if (count($item['campagne_boxes']) === 0) {
                    continue;
                }

                foreach ($item['campagne_boxes'] as $box) {

                    if (count($box['products']) === 0) {
                        continue;
                    }

                    foreach ($box['products'] as $product) {

                        $picklist_data = json_decode($product['picklist_data'], true);

                        foreach ($picklist_data as $picklist_location) {
                          

                            if ($picklist_location['quantity'] < 1) {
                                continue;
                            }
                            if($picklist_location['external_id'] === '1020') {
                                arr($picklist_location, false);
                                arr($locations['' . $picklist_location['external_id'] . ''], false);
                            }
                            if (array_key_exists('' . $picklist_location['external_id'] . '', $locations) && !array_key_exists('' . $picklist_location['external_id'] . '', $containers['containers'])) {
                                $containers['containers']['' . $picklist_location['external_id'] . ''] = $locations['' . $picklist_location['external_id'] . ''];
                                $containers['containers']['' . $picklist_location['external_id'] . '']['boxes'] = [];
                            }

                            if (array_key_exists('' . $picklist_location['external_id'] . '', $containers['containers']) && !array_key_exists('' . $box['id'] . '', $containers['containers']['' . $picklist_location['external_id'] . '']['boxes'])) {
                                $containers['containers']['' . $picklist_location['external_id'] . '']['boxes']['' . $box['id'] . ''] = _string($box['name']);
                            }
                        }
                    }
                }
            }

// ksort($containers['containers']);
//echo '<pre>';
//arr($containers['containers']);

            $box_id = false;
            $truck_id = false;
            $logistic_center_id = false;

            $columns = [];
            $values = [];

            $columns[] = "`campagne_id`";
            $values[] = "'" . CAMPAGNE_ID . "'";

            $columns[] = "`created`";
            $values[] = "NOW()";

            $columns[] = "`created_user_id`";
            $values[] = "'" . USER_ID . "'";

            if (count($_POST['box_ids']) > 0) {

                $box_id = $_POST['box_ids'][0];

                if ($box_id) {

                    $columns[] = '`campagne_container_box_id`';
                    $values[] = "'" . $box_id . "'";

                    echo '<h1>Box: ' . $box_id . '</h1>';
                }
            }

            if (array_key_exists('truck_ids', $_POST) && count($_POST['truck_ids']) > 0) {

                $truck_id = $_POST['truck_ids'][0];

                if ($truck_id) {
                    $columns[] = '`campagne_dc_truck_id`';
                    $values[] = "'" . $truck_id . "'";

                    echo '<h1>Truck: ' . $truck_id . '</h1>';
                }
            }

            if (array_key_exists('logistic_center_id', $_POST) && count($_POST['logistic_center_id']) > 0) {

                $logistic_center_id = $_POST['logistic_center_id'][0];

                if ($logistic_center_id) {
                    $columns[] = '`campagne_logistic_center_id`';
                    $values[] = "'" . $logistic_center_id . "'";

                    echo '<h1>Depot ' . $logistic_center_id . '</h1>';
                }
            }

            if ($box_id && ($truck_id || $logistic_center_id) && (is_array($containers) && is_array($ret))) {

// arr($containers);
// arr($ret);

                if ($truck_id) {
                    // $select = $db->query("SELECT `id` FROM `campagne_picklists` WHERE `campagne_container_box_id` = '".$box_id."' AND `campagne_dc_truck_id` = '".$truck_id."'") or die($db->error());
                }

                $container_data = json_encode($containers);
                $picklist_data = json_encode($ret);

                $columns[] = "`picklist_data`";
                $values[] = "'" . $db->real_escape_string($picklist_data) . "'";

                $columns[] = "`container_data`";
                $values[] = "'" . $db->real_escape_string($container_data) . "'";

                $insert_columns = implode(",", $columns);
                $insert_values = implode(",", $values);

                $insert = $db->query("INSERT INTO `campagne_picklists` (" . $insert_columns . ") VALUES (" . $insert_values . ")") or die($db->error());
                if ($insert) {
                    $picklist_id = $db->insert_id;
                    if ($picklist_id) {
                        header("location: " . CAMPAGNE_URL . "#picklists");
                    }
                }
            }
        }
        ?>
    </div>
</div>

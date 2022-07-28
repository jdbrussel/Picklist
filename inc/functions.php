<?php

define('WEEKBOX', true);
define('RAYON', false);
$WEEKBOX = false;

// arr((WEEKBOX ? 'Weekbox' : 'Not Weekbox'), false);

function upload_excel_file($file, $dir, $rewrite = false) {

    if (empty($file) || $file['error'] !== 0 || $file['size'] < 1 || !$dir) {
        return false;
    }
    if (!is_dir($dir)) {
        return false;
    }

    $filename = $file["name"];
    $tmp_name = $file["tmp_name"];
    $target_file = $dir . basename($filename);
    if ($rewrite || !file_exists($target_file)) {
        if (move_uploaded_file($tmp_name, $target_file)) {
            return [
                'filename' => $filename,
                'src' => $target_file
            ];
        }
    }

    return false;
}

function return_backorder($external_id) {

    global $account;
    global $db;

    if (array_key_exists($external_id, $account['stock']['backorders']['pending'])) {

        return $account['stock']['backorders']['pending']['' . $external_id . ''];
    } else {
        return false;
    }
}

function return_stock($external_id) {

    global $account;

    if (array_key_exists($external_id, $account['stock']['products'])) {

        return $account['stock']['products']['' . $external_id . '']['stock'];
    } else {

        return false;

        return [
            'cur_stock' => 0,
            'in_stock' => 0,
            'in_order' => 0,
        ];
    }
}

function campagne_product_render($product) {

    ksort($product);

    global $account;
    global $db;

    $product['external_id'] = trim($product['external_id']);

    $products = [];

    if ($product['product_type'] === 'set' && $product['set_delivery'] === 'seperate' && !empty($product['included_external_ids'])) {
        $products = json_decode($product['included_external_products'], true);
    } else if ($product['product_type'] === 'product' || ($product['product_type'] === 'set' && $product['set_delivery'] === 'set')) {
        if (!empty($product['external_id'])) {
            $products[] = [
                'external_id' => $product['external_id'],
                'name' => trim($product['name'])
            ];
        }
    }

    if (not_empty('quantity', $product) && not_empty('unit_quantity', $product)) {
        $product['calc_quantity'] = $product['quantity'] / $product['unit_quantity'];
    }

    if ($product['external_id'] === "P1918678" && USER_ID === "1") {
        // arr($product, false);
    }

    $product['status']['_complete'] = false;

    $product['status']['_state'] = 'product_pending';

    $product['status']['_num_stock_items'] = 0;

    $product['status']['_stock_warning'] = false;

    $product['status']['_num_backorders'] = 0;

    $product['status']['_backorder_warning'] = false;


    $product['status']['_included_products'] = json_decode($product['included_external_products'], true);

    $product['status']['_num_included_products'] = count($products);

    $product['status']['set_ready_for_picking'] = false;

    if ($product['status']['_num_included_products'] > 0) {

        if ($product['status']['_num_included_products'] > 1) {

            $product['status']['_state'] = 'set_pending';
        }

        $items = [];

        foreach ($products as $key => $_product) {

            $external_id = trim($_product['external_id']);

            $items['' . $external_id . ''] = $_product;

            $items['' . $external_id . '']['status']['_needed'] = $product['quantity'];

            $stock = return_stock($external_id);

            $items['' . $external_id . '']['stock'] = $stock;

            $items['' . $external_id . '']['status']['_stock_warning'] = false;

            $backorder = return_backorder($external_id);

            $items['' . $external_id . '']['backorder'] = $backorder;

            $items['' . $external_id . '']['status']['_backorder_warning'] = false;

            if (!is_array($stock) && !is_array($backorder)) {

                $items['' . $external_id . '']['status']['_stock_warning'] = false;

                $items['' . $external_id . '']['status']['_backorder_warning'] = false;

                $product['status']['_complete'] = false;
            } else if (!is_array($stock) && is_array($backorder)) {

                $items['' . $external_id . '']['status']['_stock_warning'] = true;

                $product['status']['_num_backorders']++;

                $items['' . $external_id . '']['status']['_backorder_warning'] = false;

                if ($backorder['quantity'] < $product['quantity']) {

                    $items['' . $external_id . '']['status']['_backorder_warning'] = true;
                } else {

                    $items['' . $external_id . '']['status']['_stock_warning'] = false;
                }

                $product['status']['_complete'] = false;
            } else if (is_array($stock)) {

                if ($stock['cur_stock'] > 0) {

                    $product['status']['_num_stock_items']++;
                }

                $items['' . $external_id . '']['status']['_stock_warning'] = false;

                if ($stock['cur_stock'] < $product['quantity']) {

                    $items['' . $external_id . '']['status']['_stock_warning'] = true;

                    if (is_array($backorder)) {

                        $product['status']['_num_backorders']++;

                        $items['' . $external_id . '']['status']['_backorder_warning'] = false;

                        if (($backorder['quantity'] + $stock['cur_stock']) < $product['quantity']) {

                            $items['' . $external_id . '']['status']['_backorder_warning'] = true;
                        } else {

                            $items['' . $external_id . '']['status']['_stock_warning'] = false;
                        }
                    }

                    $product['status']['_complete'] = false;
                }
            }

            if ($items['' . $external_id . '']['status']['_stock_warning']) {

                $product['status']['_stock_warning'] = true;
            }

            if ($items['' . $external_id . '']['status']['_backorder_warning']) {

                $product['status']['_backorder_warning'] = true;
            }

            ksort($items['' . $external_id . '']);
        }

        $product['status']['set_ready_for_picking'] = false;

        if ($product['product_type'] === 'set' && $product['status']['_num_stock_items'] === $product['status']['_num_included_products']) {

            $product['status']['set_ready_for_picking'] = true;
        }

        $product['products'] = $items;
    }

    $product['status']['_warning'] = false;

    $product['status']['icon'] = [
        'html' => 'more_horiz',
        'class' => '',
    ];

    $status = $product['status'];

    if ($status['_num_stock_items'] === $status['_num_included_products']) {

        if (!$status['_stock_warning']) {

            $product['status']['_complete'] = true;
        }
    }

    if ($product['product_type'] === 'set' && $product['set_delivery'] === 'set' && empty($product['external_id'])) {

        $product['status']['_complete'] = false;
    }

    if ($product['status']['set_ready_for_picking'] && $product['status']['_complete']) {


        if ($product['set_picking_status'] !== 'done') {

            $db->query("UPDATE `campagne_products` SET `set_picking_status` = 'pending' WHERE `id` = '" . $product['id'] . "'");
        }

        $product['status']['icon'] = [
            'html' => 'list_alt',
            'class' => 'pending',
        ];
    } else if ($product['status']['_complete']) {

        $product['status']['icon'] = [
            'html' => 'check_box',
            'class' => 'done',
        ];
    } else {

        if ($status['_state'] === 'set_pending') {

            $product['status']['icon']['html'] = 'more_horiz';

            if ($status['_num_backorders'] > 0) {

                if ($status['_num_backorders'] === $status['_num_included_products']) {

                    $product['status']['icon']['html'] = 'check_box_outline_blank';

                    if ($status['_backorder_warning']) {

                        $product['status']['_warning'] = true;
                    } else {
                        
                    }
                }
            }

            if ($status['_num_stock_items'] && $status['_stock_warning']) {

                $product['status']['_warning'] = true;
            }
        } else if ($status['_state'] === 'product_pending') {

            if ($status['_num_backorders'] === 1) {

                $product['status']['_state'] = 'product_backorder_pending';

                $product['status']['icon']['html'] = 'check_box_outline_blank';

                if ($status['_backorder_warning']) {

                    $product['status']['_state'] = 'product_backorder_incomplete';

                    $product['status']['_warning'] = true;
                }
            }
        }

        if ($status['_num_backorders'] > 0 || $status['_num_stock_items'] > 0) {
            $product['status']['icon']['class'] = 'pending';
        }

        if ($product['status']['_warning']) {

            $product['status']['icon']['class'] .= ' warning';
        }
    }

    // ksort($product['status']);

    return $product;
}

function insert_location($location_data) {

    global $db;
    global $account;

    $ret = [];

    if (not_empty('external_id', $location_data)) {

        $location_data['name'] = htmlentities($location_data['name'], ENT_NOQUOTES, 'UTF-8');

        $select_group = $db->query("SELECT `id` FROM `groups` WHERE `external_id` = '" . $db->real_escape_string($location_data['external_id']) . "' AND `owner_id` = '" . $account['id'] . "'");

        $group_id = false;

        if ($select_group->num_rows === 0) {

            $insert_group = $db->query("
                INSERT INTO 
                        `groups` 
                        ( 
                                `type`, 
                                `owner_id`, 
                                `external_id`, 
                                `name`, 
                                `created`,
                                `created_user_id`,
                                `blame_user_id`
                        )
                VALUES 
                        (
                                'location', 
                                '" . $account['id'] . "', 
                                '" . $db->real_escape_string($location_data['external_id']) . "', 
                                '" . $db->real_escape_string($location_data['name']) . "', 
                                NOW(), 
                                '" . USER_ID . "',
                                '" . USER_ID . "'
                        )
            ") or die($db->error);

            $group_id = $db->insert_id;
        } else {
            $group = $select_group->fetch_assoc();
            $group_id = $group['id'];
        }

        if ($group_id) {

            $ret['group_id'] = $group_id;

            $select_group_address = $db->query("SELECT `id` FROM `group_addresses` WHERE `type` = 'delivery' AND `owner_id` = '" . $group_id . "' LIMIT 0,1");

            if ($select_group_address->num_rows === 0) {

                $insert_group_address = $db->query("
                    INSERT INTO 
                            `group_addresses` 
                    ( 
                            `owner_id`, 
                            `type`, 
                            `address_1`,
                            `postal_code`, 
                            `city`, 
                            `rayon`, 
                            'formule',
                            `country`, 
                            `country_code`,
                            `created`,
                            `created_user_id`,
                            `blame_user_id`
                    )
                    VALUES 
                    (
                            '" . $ret['group_id'] . "', 
                            'delivery', 
                            '" . $db->real_escape_string($location_data['address_1']) . "', 
                            '" . $db->real_escape_string($location_data['postal_code']) . "', 
                            '" . $db->real_escape_string($location_data['city']) . "', 
                            '" . $db->real_escape_string($location_data['rayon']) . "', 
                            '" . $db->real_escape_string($location_data['formule']) . "',      
                            '" . $db->real_escape_string($location_data['country']) . "', 
                            '" . $db->real_escape_string($location_data['country_code']) . "', 
                            NOW(), 
                            '" . USER_ID . "', 
                            '" . USER_ID . "'
                    )
                ");

                if ($insert_group_address && $db->insert_id) {
                    $ret['group_address_id'] = $db->insert_id;
                }
            } else {

                $group_address = $select_group_address->fetch_assoc();
                $ret['group_address_id'] = $group_address['id'];
            }
        }
    }

    return $ret;
}

function return_location($external_id, $row, $indexes, $import = true) {

    global $account;
    global $db;
    global $data;

    $ret = [];

    foreach ($indexes as $name => $index) {
        $ret["" . $name . ""] = '';
        if(!empty($index) && array_key_exists($index, $row)) {
            $ret["" . $name . ""] = return_str($row["" . $index . ""]);
        }
    }

    

    if (1===2 && not_empty("" . $external_id . "", $account['locations'])) {
        $insertdata = $ret;
        $ret = $account['locations']["" . $external_id . ""];

//        if(not_empty('group_id', $ret)) {
//            $update = $db->query("UPDATE `groups` SET `name` = '".$insertdata['name']."' WHERE `id` = '" . $ret['group_id'] . "' LIMIT 1") or die($db->error);
//        }
       
        
        if(1===2 && not_empty('group_id', $ret)) {
            
            if(!array_key_exists('' .$ret['group_id']. '' , $_SESSION['group_updates'])) {
                
                $updates = [];
            
                if(array_key_exists('formule', $insertdata) && array_key_exists('formule', $ret)) {
                    if($ret['formule'] !== $insertdata['formule']) {
                        if(empty($insertdata['formule'])) {
                            $updates[] = "`formule` = NULL";
                        }
                        else {
                            $updates[] = "`formule` = '".$insertdata['formule']."'";
                        }
                    }
                }

                if(array_key_exists('rayon', $insertdata) && array_key_exists('rayon', $ret)) {
                    if($ret['rayon'] !== $insertdata['rayon']) {
                        if(empty($insertdata['rayon'])) {
                            $updates[] = "`rayon` = NULL";
                        }
                        else {
                            $updates[] = "`rayon` = '".$insertdata['rayon']."'";
                        }
                    }
                }

                if(count($updates) > 0) {
                   // $update = $db->query("UPDATE `groups` SET ".implode(',',$updates)." WHERE `id` = '" . $ret['group_id'] . "' LIMIT 1") or die($db->error);
                   // $update = $db->query("UPDATE `group_addresses` SET ".implode(',',$updates)." WHERE `owner_id` = '" . $ret['group_id'] . "' LIMIT 1") or die($db->error);
                }
                
                $_SESSION['group_updates']['' .$ret['group_id']. ''] = 1;
                
            }
            
        }
//     $update = $db->query("UPDATE `groups` SET `rayon` = NULL, `formule` = NULL WHERE 1") or die($db->error);
//     $update = $db->query("UPDATE `group_addresses` SET `rayon` = NULL, `formule` = NULL  WHERE 1") or die($db->error);   
//        arr($insertdata, false);
//        arr($ret);
    }
    
    $ret['group_id'] = false;
    $ret['group_address_id'] = false;
    
    if(array_key_exists("" . $external_id . "", $account['locations'])){
        $location = $account['locations']["" . $external_id . ""];
        if(array_key_exists('group_id', $location) && !empty($location['group_id'])) {
            $ret['group_id'] = $location['group_id'];
        }
        if(array_key_exists('group_address_id', $location) && !empty($location['group_address_id'])) {
            $ret['group_address_id'] = $location['group_address_id'];
        }
    }

    foreach ($indexes as $name => $index) {
        $ret["" . $name . ""] = '';
        if(!empty($index) && array_key_exists($index, $row)) {
            $ret["" . $name . ""] = return_str($row["" . $index . ""]);
        }
    }
    
    if(array_key_exists('address_number', $ret) && !empty($ret['address_number'])) {
        $ret['address_1'] .= ' ' . $ret['address_number'];
        unset($ret['address_number']);
    }

    if (!not_empty('country', $ret)) {
        $ret['country'] = 'Nederland';
    }
    if (!not_empty('country_code', $ret)) {
        $ret['country_code'] = 'NL';
    }

    if (!not_empty('group_id', $ret)) {
        
        $ret['external_id'] = $external_id;
        
        if ($import) {
            $inserted = insert_location($ret);
            if (!not_empty('group_id', $inserted)) {
                $ret['group_id'] = $inserted['group_id'];
            }
            if (!not_empty('group_address_id', $inserted)) {
                $ret['group_address_id'] = $inserted['group_address_id'];
            }
        }
        
    }
    return $ret;
}

function fetch_user($user_id = false, $username = false, $password = false) {

    global $db;

    $user = [];

    $columns = " `id`, `department`, `accounts`, CONCAT(`first_name` , ' ' , `last_name`) as `user_name` ";

    if ($username && $password) {
        $query_user = $db->query("SELECT " . $columns . " FROM `group_users` WHERE `user` = '" . $username . "' AND `pass` = '" . md5($password) . "'") or die($db->error);
    } else if ($user_id) {
        $query_user = $db->query("SELECT " . $columns . " FROM `group_users` WHERE `id` = '" . $user_id . "'") or die($db->error);
    }
    if ($query_user->num_rows === 1) {
        $user = $query_user->fetch_assoc();
        if (not_empty('accounts', $user)) {
            $user['accounts'] = json_decode($user['accounts'], true);
        }
        setcookie("username", $username, time() + 86400);
        define("USER_ID", $user['id']);
        $_SESSION['user_id'] = $user['id'];
    }

    return $user;
}

function owner_fetch($owner_id) {

    global $db;

    $owner = [];

    $query_owner = $db->query("SELECT * FROM `groups` WHERE `id` = " . OWNER_ID . " LIMIT 1") or die($db->error);
    if ($query_owner->num_rows === 1) {

        $owner = $query_owner->fetch_assoc();

        ksort($owner);
    }
    return $owner;
}

function account_fetch($owner_id, $account_id) {

    global $db;

    $owner = owner_fetch($owner_id);

    $account = [];

    $query_account = $db->query("
		SELECT 
			`groups`.* ,
			`group_modules`.`campagnes` as `campagne_module`,
			`group_modules`.`weekbox` as `weekbox_module`,
			`group_modules`.`mailings` as `mailing_module`
		FROM 
			`groups` 
		LEFT JOIN
			`group_modules` ON  `group_modules`.`owner_id`  =  `groups`.`id`
		WHERE 
			`type` = 'account' 
				AND 
			`groups`.`id`  = " . $account_id . " 
		LIMIT 1
	") or die($db->error);

    if ($query_account->num_rows === 1) {

        $account = $query_account->fetch_assoc();

        if (!empty($account['id']) && is_numeric($account['id'])) {

            $pattern_query = $db->query("
				SELECT * FROM `group_regexpatterns` WHERE `owner_id` = '" . $account['id'] . "'
			") or die($db->error);
            if ($pattern_query->num_rows === 1) {
                $account['patterns'] = $pattern_query->fetch_assoc();
            } else {
                $account['patterns']['pattern_location_id'] = '([0-9]{2,10})';
                $account['patterns']['pattern_external_id_single'] = '(.*)$';
                $account['patterns']['pattern_external_ids_multi'] = '(.*)\s{0,3}(\S|\s)\s{0,3}(.*(\n|$))';
                $account['patterns']['pattern_weekbox_fixed_item'] = '(.*)';
            }


            $query = $db->query("
				SELECT * FROM `group_location_types` WHERE `owner_id` = '" . $account['id'] . "' ORDER BY `name` ASC
			") or die($db->error);

            $account['location_types'] = [];
            while ($item = $query->fetch_assoc()) {

                $weekbox_group_type_fixed_items_query = $db->query("SELECT `weekbox_fixed_items` FROM `weekbox_group_type_fixed_items` WHERE `group_type_id` = " . $item['id'] . " ") or die($db->error);

                if ($weekbox_group_type_fixed_items_query->num_rows === 1) {
                    $item['weekbox'] = [];
                    while ($subitem = $weekbox_group_type_fixed_items_query->fetch_assoc()) {

                        foreach (json_decode($subitem['weekbox_fixed_items'], true) as $fixed_item_quantity) {
                            $item['weekbox']['fixed_item_quantities']["" . $fixed_item_quantity['external_id'] . ""] = $fixed_item_quantity['quantity'];
                        }
                        //$item['weekbox']['fixed_item_quantities'] = json_decode($subitem['weekbox_fixed_items'], true);
                    }
                }

                $account['location_types'][] = $item;
                $account['location_types_by_name']["" . strtoupper($item['name']) . ""] = $item;
            }

            $query = $db->query("
				SELECT
					`groups`.`id` as `group_id`,
					`groups`.`external_id`,
					`groups`.`name`,
					`groups`.`location_type_id`,
					`group_location_types`.`name` as `location_type`,
					`group_addresses`.`id` as `group_address_id`,
					`group_addresses`.`address_1`,
					`group_addresses`.`address_2`,
					`group_addresses`.`postal_code`,
					`group_addresses`.`city`,
                                        `group_addresses`.`rayon`,
                                        `group_addresses`.`formule`,
					`group_addresses`.`country`
				FROM
					`groups`
				LEFT JOIN
					`group_addresses` ON  `groups`.`id` =  `group_addresses`.`owner_id`  AND `group_addresses`.`type` = 'delivery'
				LEFT JOIN
					`group_location_types` ON  `groups`.`location_type_id` =  `group_location_types`.`id` 
				WHERE
					`groups`.`type` = 'location'  AND `groups`.`owner_id` = '" . $account['id'] . "'
				GROUP BY `groups`.`id`
				ORDER BY `external_id` ASC
			") or die($db->error);

            $account['locations'] = [];
            while ($item = $query->fetch_assoc()) {
                $item['external_id'] = trim(strtoupper($item['external_id']));
                $item['name'] = htmlentities($item['name'], ENT_QUOTES);
                if (empty($item['external_id'])) {
                    continue;
                }
                $account['locations']["" . $item['external_id'] . ""] = $item;
            }

            $query = $db->query("SELECT * FROM `campagnes` WHERE `owner_id` = '" . $account['id'] . "' ORDER BY `name` ASC");

            $account['campagnes'] = [];
            while ($item = $query->fetch_assoc()) {

                /*
                  `id`,
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
                  `locations_start_row_index`,
                  `import_location_data`,
                  `products_id_row_index`,
                  `products_name_row_index`,
                  `products_value_column_indexes`,
                  `products_articlenumber_column_indexes`,
                  `products_unit_quantity_row_index`,
                  `created`,
                  `created_user_id`,
                  `last_update`,
                  `blame_user_id`
                 */

                $subquery = $db->query("
                        SELECT
                                `campagne_product_files`.`id`,
                                `campagne_product_files`.`name`,
                                `campagne_product_files`.`type`,
                                `campagne_product_files`.`products_value_column_indexes`,
                                `campagne_product_files`.`products_articlenumber_column_indexes`,
                                DATE_FORMAT(`campagne_product_files`.`created`, '%d/%m/%Y') as `created_date`,
                                COUNT(`campagne_products`.`id`) as `num_products`,
                                `campagne_products`.`value_product`
                        FROM
                                `campagne_product_files`
                        LEFT JOIN
                                `campagne_products`
                                ON
                                `campagne_products`.`campagne_product_file_id` = `campagne_product_files`.`id`
                        WHERE
                                `campagne_product_files`.`campagne_id` = " . $item['id'] . "
                        GROUP BY
                                `campagne_product_files`.`id`
                        ORDER BY
                                `campagne_product_files`.`name`
                ");
                $item['product_files'] = [];
                while ($subitem = $subquery->fetch_assoc()) {
                    $item['product_files']['' . $subitem['id'] . ''] = $subitem;
                }
                $account['campagnes']['' . $item['id'] . ''] = $item;
            }


            $account['weekbox'] = [];


            $query_seals = $db->query("SELECT `id`,`name`,`quantity_switch` FROM `weekbox_seal` WHERE `owner_id` = '" . ACCOUNT_ID . "' ") or die($db->error);

            if ($query_seals->num_rows > 0) {

                $account['weekbox']['seals'] = [];

                while ($seal = $query_seals->fetch_assoc()) {

                    $seal['products'] = [];
                    $seal['fixed_items_external_ids'] = [];

                    $account['weekbox']['seals'][$seal['id']] = $seal;
                }
            }


            $query_fixed_items = $db->query("SELECT * FROM `weekbox_fixed_items` WHERE `owner_id` = '" . ACCOUNT_ID . "' ") or die($db->error);

            if ($query_fixed_items->num_rows > 0) {

                $account['weekbox']['fixed_items'] = [];

                while ($fixed_item = $query_fixed_items->fetch_assoc()) {

                    if (not_empty('weekbox_seal_id', $fixed_item) && $fixed_item['weekbox_seal_id'] > 0) {

                        $account['weekbox']['seals'][$fixed_item['weekbox_seal_id']]['fixed_items_external_ids'][] = $fixed_item['external_id'];
                    }

                    $account['weekbox']['fixed_items']["" . $fixed_item['external_id'] . ""] = $fixed_item;
                }
            }

            // arr($account['weekbox'], false);


            $account['formdata'] = [];

            $query = $db->query("SELECT * FROM `suppliers` WHERE `owner_id` = '" . $account['id'] . "' ORDER BY `external` ASC, `name` ASC");

            $account['formdata']['suppliers'] = '<select name="supplier_id" data-value="supplier_id"><option value="">Selecteer een leverancier</option>';
            $account['suppliers'] = [];
            while ($item = $query->fetch_assoc()) {
                $account['suppliers'][$item['id']] = $item;
                $account['formdata']['suppliers'] .= '<option value="' . $item['id'] . '">' . $item['name'] . '</option>';
            }
            $account['formdata']['suppliers'] .= '</select>';

            $query = $db->query("SELECT * FROM `dcs` WHERE `dcs`.`owner_id` =  " . $account['id'] . " ORDER BY `dcs`.`name` DESC");

            $account['dcs'] = [];
            while ($item = $query->fetch_assoc()) {
                $account['dcs'][$item['id']] = $item;
            }

            $query = $db->query("SELECT * FROM `dcs_logistic_centers` WHERE `dcs_logistic_centers`.`owner_id` =  " . $account['id'] . " ORDER BY `dcs_logistic_centers`.`default` DESC");

            $account['formdata']['dcs_logistic_centers'] = '<select name="dcs_logistic_center_id" data-value="dcs_logistic_center_id"><option value="">Afleverlocatie</option>';
            $account['dcs_logistic_centers'] = [];
            while ($item = $query->fetch_assoc()) {
                $account['dcs_logistic_centers'][$item['id']] = $item;
                if ($item['default'] === '1') {
                    $account['default_dcs_logistic_center_id'] = $item['id'];
                }
                $account['formdata']['dcs_logistic_centers'] .= '<option value="' . $item['id'] . '" ' . ($item['default'] === '1' ? 'selected' : '') . '>' . $item['name'] . '</option>';
            }
            $account['formdata']['dcs_logistic_centers'] .= '</select>';

            $query = $db->query("SELECT * FROM `dcs_containers` WHERE `dcs_containers`.`owner_id` =  " . $account['id'] . " ORDER BY `dcs_containers`.`name` ASC");

            $account['containers'] = [];
            while ($item = $query->fetch_assoc()) {
                $subquery = $db->query("SELECT * FROM `dcs_containers_boxes` WHERE `dcs_containers_boxes`.`container_id` =  " . $item['id'] . " ORDER BY `dcs_containers_boxes`.`col` ASC");
                $item['boxes'] = [];
                while ($subitem = $subquery->fetch_assoc()) {
                    $item['boxes']['' . $subitem['id'] . ''] = $subitem;
                }
                $account['containers']['' . $item['id'] . ''] = $item;
            }

            $account['stock'] = [];

            $query = $db->query("SELECT * FROM `stock_locations` WHERE  `owner_id` = '" . $owner['id'] . "' ");

            $account['stock']['locations'] = [];
            while ($item = $query->fetch_assoc()) {
                $account['stock']['locations']['' . $item['id'] . ''] = $item;
            }

            $query = $db->query("SELECT * FROM `stock_backorders` WHERE `stock_backorders`.`owner_id` =  " . $account['id'] . " ORDER BY `stock_backorders`.`external_id` DESC, `stock_backorders`.`id` DESC ");

            $account['stock']['backorders'] = [
                'pending' => [],
                'history' => [],
            ];
            $item['external_id'] = trim($item['external_id']);
            while ($item = $query->fetch_assoc()) {

                if (array_key_exists($item['supplier_id'], $account['suppliers'])) {
                    $item['supplier'] = $account['suppliers'][$item['supplier_id']];
                }

                if ($item['status'] === 'pending') {
                    $account['stock']['backorders']['pending']['' . $item['external_id'] . ''] = $item;
                } else {
                    $account['stock']['backorders']['history']['' . $item['external_id'] . ''] = $item;
                }
            }

            $query = $db->query("
				SELECT
					`stock`.*,
					`stock`.`in_stock` - `stock`.`in_order` as `cur_stock`
				FROM
					`stock`
				WHERE
					`stock`.`owner_id` =  " . $account['id'] . "
				ORDER BY `stock`.`external_id` DESC
			");

            $account['stock']['products'] = [];

            while ($item = $query->fetch_assoc()) {

                $item['external_id'] = trim($item['external_id']);

                $item['stock'] = [
                    'cur_stock' => $item['cur_stock'],
                    'in_order' => $item['in_order'],
                    'in_stock' => $item['in_stock'],
                    'backorders' => [],
                    'location' => [],
                ];

                unset($item['in_order']);
                unset($item['cur_stock']);
                unset($item['in_stock']);

                if (array_key_exists($item['stock_location_id'], $account['stock']['locations'])) {
                    $item['stock']['location'] = $account['stock']['locations']['' . $item['stock_location_id'] . ''];
                }

                if (array_key_exists($item['external_id'], $account['stock']['backorders']['pending'])) {
                    $item['stock']['backorders'] = $account['stock']['backorders']['pending']['' . $item['external_id'] . ''];
                }

                $account['stock']['products']['' . $item['external_id'] . ''] = $item;
            }
        }

        ksort($account);
    }

    return $account;
}

function return_actual_containers($campagne_id, $truck_id) {

    global $db;

    $location_ids = [];
    $truck_containers = $db->query("SELECT 
		`external_id`,
		`dc_id`
		 FROM 
		 	`campagne_dc_trucks_containers` 
		 WHERE 
		 	`campagne_id` = " . $campagne_id . " && 
		 	`dc_truck_id` = " . $truck_id . " 
		 ORDER BY 
		 	`external_id` ASC
	") or die($db->error);

    $dc_id = false;
    while ($location = $truck_containers->fetch_assoc()) {
        if (!in_array("" . $location['external_id'] . "", $location_ids)) {
            $location_ids[] = $location['external_id'];
            $dc_id = $location['dc_id'];
        }
    }

    $campagne_products = $db->query("SELECT `picklist_data` FROM `campagne_products` WHERE `campagne_id` = " . $campagne_id . " ") or die($db->error);

    $filled = [];

    while ($cp = $campagne_products->fetch_assoc()) {

        $pickdata = [];
        $picklist = json_decode($cp['picklist_data'], true);
        if (is_array($picklist)) {
            $pickdata = $picklist;
        }

        foreach ($pickdata as $location_pickdata) {

            if (in_array($location_pickdata['external_id'], $location_ids)) {

                if (!array_key_exists("" . $location_pickdata['external_id'] . "", $filled) && $location_pickdata['quantity'] > 0) {

                    $filled["" . $location_pickdata['external_id'] . ""] = $location_pickdata['quantity'];
                }
            }
        }
    }

    ksort($filled);

    $filled = array_keys($filled);

    $empty = array_values(array_diff($location_ids, $filled));

    // arr($arr2);

    $ret = [
        'error' => [],
        'data' => [
            'campagne' => $campagne_id,
            'dc' => $dc_id,
            'truck' => $truck_id,
            'containers' => [
                'scheduled' => json_encode($location_ids),
                'filled' => json_encode($filled),
                'empty' => json_encode($empty)
            ]
        ]
    ];


    /*
      foreach($empty as $external_id) {
      // todo : do something with empty containers...
      }
     */

    return $ret;
}

function truck_containers_fetch($truck) {

    if (!$truck['id']) {
        return false;
    }

    global $db;
    global $account;

    $containers = $db->query("
		SELECT
			*,
			DATE_FORMAT(`created`, '%d/%m/%Y') as `added`
		FROM
			`campagne_dc_trucks_containers`
		WHERE
			`dc_truck_id` = '" . $truck['id'] . "'
	");

    $ret = [
        'location' => [],
        'location_ids' => [],
        'location_dcs' => []
    ];

    while ($container = $containers->fetch_assoc()) {

        if (!in_array("" . $container['external_id'] . "", $ret['location_dcs'])) {

            $ret['location_dcs']["" . $container['external_id'] . ""] = [
                'dc_id' => $truck['dc_id'],
                'truck_id' => $truck['id'],
            ];
        }

        if (!in_array("" . $container['external_id'] . "", $ret['location_ids'])) {
            $ret['location_ids'][] = $container['external_id'];
        }

        if (array_key_exists("" . $container['external_id'] . "", $account['locations'])) {
            $container['group'] = $account['locations']["" . $container['external_id'] . ""];
        }

        $ret['locations']["" . $container['external_id'] . ""] = $container;
    }

    if (array_key_exists('locations', $ret) && count($ret['locations']) > 0) {
        ksort($ret['locations']);
    }

    return $ret;
}

function sortArray($items, $order_object) {
    $sorted = [];
    foreach ($items as $item) {
        $keys = explode('.', $order_object['key']);
        if (count($keys) === 1) {
            $key = (array_key_exists($keys[0], $item) ? $item[$keys[0]] : 'Null');
        } else if (count($keys) === 2) {
            $key = (array_key_exists($keys[0], $item) && array_key_exists($keys[1], $item[$keys[0]]) ? $item[$keys[0]][$keys[1]] : 'Null');
        }
        if (!array_key_exists($key, $sorted)) {
            $sorted[$key] = [];
        }
        $sorted[$key][] = $item;
    }
    ksort($sorted);
    if (strtoupper($order_object['direction']) === 'DESC') {
        krsort($sorted);
    }
    $sorteditems = [];
    foreach ($sorted as $key => $items) {
        foreach ($items as $item) {
            $sorteditems[] = $item;
        }
    }
    return $sorteditems;
}

function orderArray($items = [], $order_objects = []) {
    for ($level = count($order_objects) - 1; $level >= 0; $level--) {
        $items = sortArray($items, $order_objects[$level]);
    }
    return $items;
}

function campagne_fetch($owner_id, $account_id, $campagne_id) {

    global $db;

    $owner_id = intval($owner_id);
    $account_id = intval($account_id);
    $campagne_id = intval($campagne_id);

    $owner = owner_fetch($owner_id);

    $account = account_fetch($owner_id, $account_id);

    // arr($account);

    $q_campagne = $db->query("SELECT 
		`campagnes`.*,
		DATE_FORMAT(`pick_datetime`, '%d/%m/%Y %H:%i') as `pick_datetime`,
		DATE_FORMAT(`pick_datetime`, '%d/%m/%Y') as `pick_date`,
		DATE_FORMAT(`pick_datetime`, '%H:%i') as `pick_time`  
		FROM 
		`campagnes` 
		WHERE 
		`id` = " . intval($campagne_id) . " 
		LIMIT 1");

    if ($q_campagne->num_rows !== 1) {
        return [];
    }

    $campagne = $q_campagne->fetch_assoc();

    $campagne['distribution'] = [];

    $excluded_locations = [];
    if (not_empty('excluded_locations', $campagne)) {
        $excluded_locations = json_decode($campagne['excluded_locations'], true);
    }
    if (not_empty('container_id', $campagne)) {
        $campagne['distribution']['container'] = fetch_campagne_container($campagne['container_id']);
    }
    // arr($campagne);

    $query = $db->query("
		SELECT
			`campagne_product_files`.`id`,
			`campagne_product_files`.`name`,
			`campagne_product_files`.`type`,
			`campagne_product_files`.`products_value_column_indexes`,
			`campagne_product_files`.`products_articlenumber_column_indexes`,
			`campagne_product_files`.`location_data_sheet_index`, 
			`campagne_product_files`.`variation_data_sheet_index`, 
			`campagne_product_files`.`variations_name_column_index`, 
			`campagne_product_files`.`variantions_location_match_column`, 
			`campagne_product_files`.`variations_start_row_index`, 
			`campagne_product_files`.`locations_name_row_index`, 
			`campagne_product_files`.`locations_id_column_index`, 
			`campagne_product_files`.`locations_name_column_index`, 
			`campagne_product_files`.`locations_address_column_index`, 
                        `campagne_product_files`.`locations_address_number_column_index`, 
			`campagne_product_files`.`locations_postal_code_column_index`, 
			`campagne_product_files`.`locations_city_column_index`, 
                        `campagne_product_files`.`locations_rayon_column_index`, 
                        `campagne_product_files`.`locations_formule_column_index`, 
			`campagne_product_files`.`locations_start_row_index`, 
			`campagne_product_files`.`products_id_row_index`, 
			`campagne_product_files`.`products_name_row_index`, 
			`campagne_product_files`.`products_unit_quantity_row_index`,
                        `campagne_product_files`.`products_version_row_index`,
                        `campagne_product_files`.`products_version_multiplier_row_index`,
			`campagnes`.`name` as `campagne_name`,
			`campagne_products`.`set_picking_status`,
				DATE_FORMAT(`campagne_product_files`.`created`, '%d/%m/%Y') as `created_date`,
			COUNT(`campagne_products`.`id`) as `num_products`,
			`campagne_products`.`value_product`
		FROM
			`campagne_product_files`
		LEFT JOIN
			`campagne_products`
			ON `campagne_products`.`campagne_product_file_id` = `campagne_product_files`.`id`
		LEFT JOIN
			`campagnes`
			ON `campagnes`.`id` = `campagne_product_files`.`campagne_id`
		WHERE
			`campagne_product_files`.`campagne_id` = " . $campagne['id'] . "
			GROUP BY `campagne_product_files`.`id`
			ORDER BY `campagne_product_files`.`name`
		") or die($db->error);

    $campagne['product_files'] = [];

    $campagne['products'] = [];

    $campagne['distribution']['locations_in_campagne'] = [];

    $campagne['campagne_products'] = [];
    
    $campagne['campagne_products']['bad_products'] = [];

    $campagne['campagne_products']['ready_for_picking'] = [];

    while ($product_file = $query->fetch_assoc()) {

        if (strlen($product_file['products_value_column_indexes']) > 0) {
            $product_file['product_columns'] = explode(',', $product_file['products_value_column_indexes']);
        } else {
            $product_file['product_columns'] = [];
        }

        if (strlen($product_file['products_articlenumber_column_indexes']) > 0) {
            $product_file['product_variant_articlenumber_columns'] = json_decode($product_file['products_articlenumber_column_indexes'], true);
        } else {
            $product_file['product_variant_articlenumber_columns'] = [];
        }
        
        
        
        /*
         * 
         * 
         */
        $bad_products = $db->query("SELECT 
                        `id`,
                        `external_id`,
                        `name`,
                        `picklist_data`,
                        `quantity`,
                        `unit_quantity`,
                        `product_type`
		FROM 
			`campagne_products` 
		WHERE 
                    (
                    `external_id` IS NULL || `external_id` = ''
                    )
                    AND 
                    (
                    `campagne_id` = '" . $campagne['id'] . "' AND 
                    `campagne_products`.`campagne_product_file_id` = '" . $product_file['id'] . "'
                    )
        ") or die($db->error);
        $items = [];
	if($bad_products->num_rows > 0) {
            while($item = $bad_products->fetch_assoc()) {
                if($item['quantity'] < 1) {
                    continue;
                }
                $allocation = json_decode($item['picklist_data'], true);
                unset($item['picklist_data']);
                $num_locations = 0;
                $num_items = 0;
                foreach($allocation as $location_id => $item_data) {
                    if((integer) $item_data['quantity'] > 0) {
                        $num_locations++;
                        $num_items += $item_data['quantity'];
                    }
                }
                $item['locations_allocated'] = $num_locations;
                $item['quantity_allocated'] = (integer) $num_items / (integer) $item['unit_quantity'];
                if($num_items > 0) {
                    $campagne['campagne_products']['bad_products'][] = $item;
                }
            }
	}
        /*
         * 
         * 
         */
        
        
        
        
        
    $_the_query = "
			SELECT
				`campagne_products`.*
			FROM
				`campagne_products`
                        WHERE
				`campagne_products`.`campagne_id` = '" . $campagne['id'] . "' AND `campagne_products`.`campagne_product_file_id` = '" . $product_file['id'] . "'
			GROUP BY `campagne_products`.`id`
		";
        $q_campagne_product_files_products = $db->query($_the_query) or die( $_the_query );

        $i = 0;

        while ($product = $q_campagne_product_files_products->fetch_assoc()) {

            $product = campagne_product_render($product);

            if (!not_empty('unit_quantity', $product)) {
                $product['unit_quantity'] = 1;
            }

            $product['quantity'] = intval($product['quantity']) / intval($product['unit_quantity']);

            $picklist = json_decode($product['picklist_data'], true);

            if (is_array($picklist)) {

                foreach ($picklist as $loc) {
             
                    if (!array_key_exists('external_id', $loc)) {
                        $loc['external_id'] = $loc['id'];
                    }

                    if (!array_key_exists("" . $loc['external_id'] . "", $campagne['distribution']['locations_in_campagne'])) {

                        if (!is_array($excluded_locations)) {
                            $excluded_locations = [];
                        }

                        if (!in_array("" . $loc['external_id'] . "", $excluded_locations)) {
                            $campagne['distribution']['locations_in_campagne']["" . $loc['external_id'] . ""] = $loc;
                            unset($campagne['distribution']['locations_in_campagne']["" . $loc['external_id'] . ""]['quantity']);
                        }   
                    }
                    else {
                        if(!empty($loc['rayon']) && empty($campagne['distribution']['locations_in_campagne']["" . $loc['external_id'] . ""]['rayon'])) {
                            $campagne['distribution']['locations_in_campagne']["" . $loc['external_id'] . ""]['rayon'] = $loc['rayon'];
                        }
                        if(!empty($loc['formule']) && empty($campagne['distribution']['locations_in_campagne']["" . $loc['external_id'] . ""]['formule'])) {
                            $campagne['distribution']['locations_in_campagne']["" . $loc['external_id'] . ""]['formule'] = $loc['formule'];
                        }
                    }
                }
                if(1 === 2) {
                            //arr($campagne['distribution']['locations_in_campagne'], false);
                            if($product['external_id'] === 'HHJ000001') {
                               arr($campagne['distribution']['locations_in_campagne']);
                            }
                }
                //arr($campagne['distribution']['locations_in_campagne']);
            }

            unset($product['external_ids']);

            $i++;
            $index = $i;
            if (!empty($product['campagne_product_file_column_index'])) {
                $index = trim($product['campagne_product_file_column_index']);
            }
            $product_file['products']['' . $index . ''] = $product;
            $campagne['products'][] = $product;

            if ($product['set_picking_status'] == 'pending') {
                $campagne['campagne_products']['ready_for_picking']['' . $product['id'] . ''] = $product;
            }
        }

        $campagne['product_files'][$product_file['id']] = $product_file;
    }

    //arr($campagne['distribution']);

    $campagne['distribution']['location_dcs'] = [];

    $campagne['distribution']['dcs'] = [];

    $campagne['distribution']['trucks'] = [];

    $query = $db->query("SELECT *, DATE_FORMAT(`created`, '%d/%m/%Y') as `added` FROM `campagne_dc` WHERE `campagne_id` = '" . $campagne['id'] . "'");

    while ($item = $query->fetch_assoc()) {

        $item['dc'] = [];

        if (array_key_exists($item['dc_id'], $account['dcs'])) {
            $item['dc'] = $account['dcs']['' . $item['dc_id'] . ''];
        }

        $item['locations'] = [];

        $trucks = $db->query("
			SELECT
				`campagne_dc_trucks`.`id`,
				`campagne_dc_trucks`.`name`,
				`campagne_dc_trucks`.`loading_order`,
				`campagne_dc_trucks`.`due_date`,
				`campagne_dc_trucks`.`due_time`,
				`campagne_dc_trucks`.`delivery_note`,
				
				`dc`.`id` as `dc_id`,
				`dc`.`name` as `dc_name`,

				DATE_FORMAT(`campagne_dc_trucks`.`due_datetime`, '%d/%m/%Y') as `date`,
				DATE_FORMAT(`campagne_dc_trucks`.`due_datetime`, '%H:%i') as `time` ,
				DATE_FORMAT(`campagne_dc_trucks`.`due_datetime`, '%d/%m/%Y %H:%i') as `due_datetime`,

				DATE_FORMAT(`campagne_dc_trucks`.`loading_datetime`, '%d/%m/%Y') as `loading_date`,
				DATE_FORMAT(`campagne_dc_trucks`.`loading_datetime`, '%H:%i') as `loading_time` ,
				DATE_FORMAT(`campagne_dc_trucks`.`loading_datetime`, '%d/%m/%Y %H:%i') as `loading_datetime`,

				DATE_FORMAT(`campagne_dc_trucks`.`created`, '%d/%m/%Y') as `added`,
				
				DATE_FORMAT(`campagne_dc_trucks`.`loading_datetime`, '%d/%m/%Y %H:%i') as `loading_datetime`,

				DATE_FORMAT(`campagne_dc_trucks`.`printed_datetime`, '%d/%m/%Y %H:%i') as `printed_datetime`,
				concat_ws(' ', `printed_user_table`.`first_name`, `printed_user_table`.`last_name`) as `printed_by_user`,

				DATE_FORMAT(`campagne_dc_trucks`.`picking_datetime`, '%d/%m/%Y %H:%i') as `picking_datetime`,
				concat_ws(' ', `picking_user_table`.`first_name`, `picking_user_table`.`last_name`) as `picking_by_user`,

				DATE_FORMAT(`campagne_dc_trucks`.`picked_datetime`, '%d/%m/%Y %H:%i') as `picked_datetime`,
				concat_ws(' ', `picked_user_table`.`first_name`, `picked_user_table`.`last_name`) as `picked_by_user`,

				TIMEDIFF(`campagne_dc_trucks`.`loading_datetime`, CURRENT_TIMESTAMP() ) as `send`
			FROM
				`campagne_dc_trucks`

			LEFT JOIN
				`dcs` as `dc` ON `dc`.`id` = `campagne_dc_trucks`.`dc_id`

			LEFT JOIN
				`group_users` as `printed_user_table` ON `printed_user_table`.`id` = `campagne_dc_trucks`.`printed_user_id`

			LEFT JOIN
				`group_users` as `picking_user_table` ON `picking_user_table`.`id` = `campagne_dc_trucks`.`picking_user_id`

			LEFT JOIN
				`group_users` as `picked_user_table` ON `picked_user_table`.`id` = `campagne_dc_trucks`.`picked_user_id`
			
			WHERE
				
				`campagne_dc_trucks`.`campagne_id` = '" . $campagne['id'] . "'
			AND
				`campagne_dc_trucks`.`dc_id` = '" . $item['dc_id'] . "'
			
			ORDER BY
				`campagne_dc_trucks`.`id` ASC

		") or die($db->error);

        $item['trucks'] = [];

        while ($truck = $trucks->fetch_assoc()) {

            $containers = $db->query("
				SELECT
					*,
					DATE_FORMAT(`created`, '%d/%m/%Y') as `added`
				FROM
					`campagne_dc_trucks_containers`
				WHERE
				 	`campagne_id` = '" . $campagne['id'] . "'
				 AND
					`dc_truck_id` = '" . $truck['id'] . "'
			");

            $truck['location_ids'] = [];

            $truck['locations'] = [];

            while ($container = $containers->fetch_assoc()) {

                if (!in_array("" . $container['external_id'] . "", $campagne['distribution']['location_dcs'])) {

                    $campagne['distribution']['location_dcs']['' . $container['external_id'] . ''] = [
                        'dc_id' => $item['dc_id'],
                        'truck_id' => $truck['id'],
                    ];
                }

                if (!in_array($container['external_id'], $truck['location_ids'])) {
                    $truck['location_ids'][] = $container['external_id'];
                }

                if (!array_key_exists("" . $container['external_id'] . "", $item['locations'])) {
                    $item['locations']["" . $container['external_id'] . ""] = [
                        'truck_id' => $container['dc_truck_id'],
                    ];
                }

                if (array_key_exists("" . $container['external_id'] . "", $account['locations'])) {
                    $container['group'] = $account['locations']["" . $container['external_id'] . ""];
                }


                $truck['locations']["" . $container['external_id'] . ""] = $container;
            }

            ksort($truck['locations']);

            $containers = return_actual_containers($campagne['id'], $truck['id']);

            $truck['containers'] = $containers['data']['containers'];

            $truck['num_containers'] = count($truck['containers']['filled']);

            $truck['locations'] = json_encode($truck['locations']);

            $truck['location_ids'] = array_sort(json_decode($truck['containers']['scheduled'], true), true);

            ksort($truck);

            $item['trucks']["" . $truck['id'] . ""] = $truck;
        }
        ksort($campagne['distribution']['location_dcs']);
        $campagne['distribution']['dcs']["" . $item['dc_id'] . ""] = $item;
    }

    $trucks = $db->query("
			SELECT
				`campagne_dc_trucks`.`id`,
				`campagne_dc_trucks`.`name`,
				`campagne_dc_trucks`.`loading_order`,
				`campagne_dc_trucks`.`due_date`,
				`campagne_dc_trucks`.`due_time`,
				`campagne_dc_trucks`.`delivery_note`,

				`dc`.`name` as `dc_name`,
				`dc`.`id` as `dc_id`,
				`dc`.`color` as `dc_color_hex`,
				`dc`.`color_cmyk` as `dc_color_cmyk`,

				DATE_FORMAT(`campagne_dc_trucks`.`due_datetime`, '%d/%m/%Y') as `date`,
				DATE_FORMAT(`campagne_dc_trucks`.`due_datetime`, '%H:%i') as `time` ,
				DATE_FORMAT(`campagne_dc_trucks`.`due_datetime`, '%d/%m/%Y %H:%i') as `due_datetime`,

				DATE_FORMAT(`campagne_dc_trucks`.`loading_datetime`, '%d/%m/%Y') as `loading_date`,
				DATE_FORMAT(`campagne_dc_trucks`.`loading_datetime`, '%H:%i') as `loading_time` ,
				
				DATE_FORMAT(`campagne_dc_trucks`.`loading_datetime`, '%d/%m/%Y %H:%i') as `loading_datetime`,
				concat_ws(' ', `loading_user_table`.`first_name`, `loading_user_table`.`last_name`) as `loading_by_user`,

				DATE_FORMAT(`campagne_dc_trucks`.`created`, '%d/%m/%Y') as `added`,
				
				DATE_FORMAT(`campagne_dc_trucks`.`loading_datetime`, '%d/%m/%Y %H:%i') as `loading_datetime`,

				DATE_FORMAT(`campagne_dc_trucks`.`printed_datetime`, '%d/%m/%Y %H:%i') as `printed_datetime`,
				concat_ws(' ', `printed_user_table`.`first_name`, `printed_user_table`.`last_name`) as `printed_by_user`,

				DATE_FORMAT(`campagne_dc_trucks`.`picking_datetime`, '%d/%m/%Y %H:%i') as `picking_datetime`,
				concat_ws(' ', `picking_user_table`.`first_name`, `picking_user_table`.`last_name`) as `picking_by_user`,

				DATE_FORMAT(`campagne_dc_trucks`.`picked_datetime`, '%d/%m/%Y %H:%i') as `picked_datetime`,
				concat_ws(' ', `picked_user_table`.`first_name`, `picked_user_table`.`last_name`) as `picked_by_user`,

				TIMEDIFF(`campagne_dc_trucks`.`loading_datetime`, CURRENT_TIMESTAMP() ) as `send`
			FROM
				`campagne_dc_trucks`

			LEFT JOIN
				`dcs` as `dc` ON `dc`.`id` = `campagne_dc_trucks`.`dc_id`

			LEFT JOIN
				`group_users` as `loading_user_table` ON `loading_user_table`.`id` = `campagne_dc_trucks`.`loading_user_id`

			LEFT JOIN
				`group_users` as `printed_user_table` ON `printed_user_table`.`id` = `campagne_dc_trucks`.`printed_user_id`

			LEFT JOIN
				`group_users` as `picking_user_table` ON `picking_user_table`.`id` = `campagne_dc_trucks`.`picking_user_id`

			LEFT JOIN
				`group_users` as `picked_user_table` ON `picked_user_table`.`id` = `campagne_dc_trucks`.`picked_user_id`
			
			WHERE
				
				`campagne_dc_trucks`.`campagne_id` = '" . $campagne['id'] . "'			
			ORDER BY
				`campagne_dc_trucks`.`loading_order` ASC,
				`campagne_dc_trucks`.`loading_datetime` DESC,
				
				`campagne_dc_trucks`.`dc_id` ASC,
				`campagne_dc_trucks`.`due_datetime` DESC,
				`campagne_dc_trucks`.`id` DESC

		") or die($db->error);

    while ($truck = $trucks->fetch_assoc()) {

        $containers = truck_containers_fetch($truck);

        $truck['containers'] = $containers;
        $truck['locations'] = [];
        if (!empty($containers['locations'])) {
            $truck['locations'] = json_encode($containers['locations']);
        }
        $campagne['distribution']['trucks']["" . $truck['id'] . ""] = $truck;
    }


    ksort($account['dcs']);
    ksort($campagne['distribution']['dcs']);

    $campagne['distribution']['logistic_centers'] = [];

    foreach ($account['dcs_logistic_centers'] as $logistic_center) {

        $dcs_logistic_centers_containers = $db->query("
			SELECT
				`campagne_logistic_centers`.`id`,
				`campagne_logistic_centers`.`location_data`
			FROM
				`campagne_logistic_centers`
			WHERE
				`campagne_id` = '" . $campagne['id'] . "'
			AND  
				`campagne_logistic_centers`.`dcs_logistic_center_id` = '" . $logistic_center['id'] . "'
			ORDER BY
				`campagne_logistic_centers`.`id` ASC
		");

        if ($dcs_logistic_centers_containers->num_rows > 0) {

            while ($item = $dcs_logistic_centers_containers->fetch_assoc()) {
                // echo $item['location_data'];
            }

            // die();
            // if(!array_key_exists($logistic_center['id'],$campagne['distribution']['logistic_centers'])) {
            // 	$campagne['distribution']['logistic_centers'][$logistic_center['id']] = [
            // 			'data' => $logistic_center,
            // 			'containers' => []
            // 	];
            // }
            // while($dcs_logistic_centers_container = $dcs_logistic_centers_containers->fetch_assoc()) {
            // 	$campagne['distribution']['logistic_centers'][$logistic_center['id']]['containers'][] = $dcs_logistic_centers_container;
            // }
        }
    }

    $campagne['distribution']['dc_containers'] = [];
    $campagne['distribution']['no_dc'] = [];

    //arr($campagne['distribution']['locations_in_campagne']);

    foreach ($campagne['distribution']['locations_in_campagne'] as $external_id => $location_in_campagne) {

        if (array_key_exists($external_id, $campagne['distribution']['location_dcs'])) {

            $distribution = $campagne['distribution']['location_dcs']['' . $external_id . ''];

            if (!array_key_exists($distribution['dc_id'], $campagne['distribution']['dc_containers'])) {
                $campagne['distribution']['dc_containers'][$distribution['dc_id']] = [];
            } else if (!array_key_exists($distribution['truck_id'], $campagne['distribution']['dc_containers'][$distribution['dc_id']])) {
                $campagne['distribution']['dc_containers'][$distribution['dc_id']][$distribution['truck_id']] = [];
            }

            $campagne['distribution']['dc_containers'][$distribution['dc_id']][$distribution['truck_id']]['' . $external_id . ''] = $location_in_campagne;
            $campagne['distribution']['locations_in_campagne']["" . $external_id . ""]['distribution'] = $distribution;

            ksort($campagne['distribution']['dc_containers'][$distribution['dc_id']]);
            ksort($campagne['distribution']['dc_containers'][$distribution['dc_id']][$distribution['truck_id']]);
        } else if (!array_key_exists($external_id, $campagne['distribution']['no_dc'])) {

            if (array_key_exists('default_dcs_logistic_center_id', $account) && is_numeric($account['default_dcs_logistic_center_id'])) {

                // $location_group_id = 'NULL';
                // if(array_key_exists($external_id, $account['locations'])) {
                // 	$location_group_id = $account['locations'][''. $external_id .'']['group_id'];
                // }
            }
            if(USER_ID === '1') {
                
                $rayon = false;
                if(strpos($external_id, 'NB') > -1) {
                    if(!empty($location_in_campagne['rayon'])) {
                        $rayon = $location_in_campagne['rayon'];
                    }
                    $location_in_campagne['rayon'] = 'NB';
                    $location_in_campagne['rebuild'] = true;
                }
                if(strpos($external_id, 'RB') > -1) {
                    if(!empty($location_in_campagne['rayon'])) {
                        $rayon = $location_in_campagne['rayon'];
                    }
                    $location_in_campagne['rayon'] = 'RB';
                    $location_in_campagne['rebuild'] = true;
                }
                if($rayon) {
                    $location_in_campagne['rayon'] .= ' - '. $rayon;
                }
                
                
              // $location_in_campagne['external_id'] = str_replace(["NB", "- NB", " - NB", "RB", "- RB", " - RB"] , "", $external_id);
            }
            
            $campagne['distribution']['no_dc']['' . $external_id . ''] = $location_in_campagne;
        }

        ksort($campagne['distribution']['dc_containers']);
    }
// ksort($campagne['distribution']['no_dc']);
//////////////////////////////////////
//////////////////////////////////////
//
    $order = [
        [
                'key' => 'rayon',
                'direction' => 'ASC'
        ],
        [
                'key' => 'formule',
                'direction' => 'ASC'
        ],
        [
            'key' => 'external_id',
            'direction' => 'ASC'
        ]
    ];

    if (WEEKBOX) {
        $order = [];
//        $order = [
//            [
//                'key' => 'formule',
//                'direction' => 'ASC'
//            ],
//            [
//                'key' => 'external_id',
//                'direction' => 'ASC'
//            ]
//        ];
    }
    if (RAYON) {
        $order = [
            [
                'key' => 'rayon',
                'direction' => 'ASC'
            ],
            [
                'key' => 'external_id',
                'direction' => 'ASC'
            ]
        ];
    }
    
    $order = [
        [
                'key' => 'rayon',
                'direction' => 'ASC'
        ],
        [
                'key' => 'formule',
                'direction' => 'ASC'
        ],
        [
            'key' => 'external_id',
            'direction' => 'ASC'
        ]
    ];
   
   
    if(!empty($order)) {
        $campagne['distribution']['no_dc'] = orderArray($campagne['distribution']['no_dc'], $order);
    }


//    if (!WEEKBOX) {
//        ksort($campagne['distribution']['no_dc']);
//    }


//    [0] => Array
//        (
//            [group_id] => 4792
//            [external_id] => 3025
//            [name] => Jumbo Huizen
//            [location_type_id] => 2
//            [location_type] => Nieuw
//            [group_address_id] => 329
//            [address_1] => De Kostmand 2
//            [address_2] => 
//            [postal_code] => 1276 CJ
//            [city] => Huizen
//            [rayon] => 
//            [formule] => Nieuw
//            [country] => Nederland
//            [country_code] => NL
        
foreach($campagne['distribution']['no_dc'] as $key => $location) {
    $addon = false;
    
    if(RAYON && !empty($location['rayon'])) {
        $addon = '(Rayon '. $location['rayon'] . ')';
    }
    elseif(!empty($location['formule'])) {
        $addon = '('. $location['formule'] . ')';
    }
    if($addon) {
       $campagne['distribution']['no_dc'][$key]['name'] = trim(str_replace($addon, '', $campagne['distribution']['no_dc'][$key]['name']));
       $campagne['distribution']['no_dc'][$key]['name'] .= ' '. $addon;
    }
}
   // arr($campagne['distribution']['no_dc'], false);

///////////////////////////////////////
/////////////////////////////////////////
// arr($campagne['distribution']['no_dc']);
    if (is_array($campagne['distribution']['no_dc']) && not_empty('default_dcs_logistic_center_id', $account)) {

        $location_data = json_encode($campagne['distribution']['no_dc']);

        $default_dcs_logistic_center_id = false;

        $dcs_logistic_centers_containers = $db->query("SELECT `id` FROM `campagne_logistic_centers` WHERE  `campagne_id` = '" . CAMPAGNE_ID . "' && `dcs_logistic_center_id` = '" . $account['default_dcs_logistic_center_id'] . "' LIMIT 1") or die($db->error);

        if ($dcs_logistic_centers_containers->num_rows === 1) {

            $default_dcs_logistic_center_id = $dcs_logistic_centers_containers->fetch_assoc()['id'];
        }

        if ($default_dcs_logistic_center_id) {
            $db->query("
				UPDATE 
					`campagne_logistic_centers` 
				SET 
					`location_data` = '" . $location_data . "'
				WHERE 
					`id` = '" . $default_dcs_logistic_center_id . "'
			") or die($db->error);
        } else {
            $db->query("
				INSERT INTO 
					`campagne_logistic_centers` 
					(
						`campagne_id`, 
						`dcs_logistic_center_id`, 
						`location_data`, 
						`created`
					) 
				VALUES
					(
						'" . CAMPAGNE_ID . "', 
						'" . $account['default_dcs_logistic_center_id'] . "', 
						'" . $location_data . "', 
						NOW()
					)
			") or die($db->error);
        }
    }


    $campagne_logistic_centers_containers_query = $db->query("
		SELECT 
			*,
			DATE_FORMAT(`campagne_logistic_centers_containers`.`delivery_datetime`, '%d/%m/%Y') as `delivery_date`,
			DATE_FORMAT(`campagne_logistic_centers_containers`.`delivery_datetime`, '%H:%i') as `delivery_time`,
			DATE_FORMAT(`campagne_logistic_centers_containers`.`delivery_datetime`, '%d/%m/%Y %H:%i') as `delivery_datetime`
		FROM 
			`campagne_logistic_centers_containers` 
		WHERE  
			`campagne_id` = '" . CAMPAGNE_ID . "' 
		GROUP BY `external_id`
	") or die($db->error);

    $campagne_logistic_centers_containers = [];

    while ($campagne_logistic_centers_container = $campagne_logistic_centers_containers_query->fetch_assoc()) {

        if (!empty($campagne_logistic_centers_container['external_id']) && !array_key_exists('' . $campagne_logistic_centers_container['external_id'] . '', $campagne_logistic_centers_containers)) {

            $campagne_logistic_centers_containers['' . $campagne_logistic_centers_container['external_id'] . ''] = $campagne_logistic_centers_container;
        }
    }

    $campagne_logistic_centers = $db->query("SELECT `id`,`location_data` FROM `campagne_logistic_centers` WHERE  `campagne_id` = '" . CAMPAGNE_ID . "'") or die($db->error);

    while ($campagne_logistic_center = $campagne_logistic_centers->fetch_assoc()) {

        $locations = json_decode($campagne_logistic_center['location_data'], true);

        $location_data_items = [];

        foreach ($locations as $location) {

            if (array_key_exists('' . $location['external_id'] . '', $campagne_logistic_centers_containers)) {

                $location['campagne_logistic_centers_container'] = [
                    'id' => $campagne_logistic_centers_containers['' . $location['external_id'] . '']['id'],
                    'datetime' => $campagne_logistic_centers_containers['' . $location['external_id'] . '']['delivery_datetime'],
                    'time' => $campagne_logistic_centers_containers['' . $location['external_id'] . '']['delivery_time'],
                    'date' => $campagne_logistic_centers_containers['' . $location['external_id'] . '']['delivery_date'],
                    'delivery_note' => $campagne_logistic_centers_containers['' . $location['external_id'] . '']['delivery_note']
                ];
            }

            $location_data_items['' . $location['external_id'] . ''] = $location;
        }

        $location_data = json_encode($location_data_items);

        $db->query("UPDATE `campagne_logistic_centers` SET `location_data` = '" . $db->real_escape_string($location_data) . "' WHERE  `id` = '" . $campagne_logistic_center['id'] . "' LIMIT 1") or die($db->error);
    }


    $dcs_logistic_centers = $db->query("
		SELECT 
			`campagne_logistic_centers`.*,
			`dcs_logistic_centers`.*
		FROM 
			`campagne_logistic_centers`
		LEFT JOIN 
				`dcs_logistic_centers`  ON `campagne_logistic_centers`.`dcs_logistic_center_id` = `dcs_logistic_centers`.`id`
		
		WHERE
			`campagne_logistic_centers`.`campagne_id` = '" . CAMPAGNE_ID . "'
	") or die($db->error);


    while ($lc = $dcs_logistic_centers->fetch_assoc()) {

        $lc['lc_id'] = $lc['id'];

        $campagne['distribution']['logistic_centers'][$lc['id']] = [];
        $campagne['distribution']['logistic_centers'][$lc['id']]['data'] = $lc;
        $campagne['distribution']['logistic_centers'][$lc['id']]['locations'] = [];
        $campagne['distribution']['logistic_centers'][$lc['id']]['location_ids'] = [];

        if (!empty($lc['location_data'])) {
            $lc_locations = json_decode($lc['location_data'], true);
            unset($campagne['distribution']['logistic_centers'][$lc['id']]['data']['location_data']);
        }

        $location_ids = [];
        $locations = [];

        foreach ($lc_locations as $external_id => $data) {
            $locations["" . $external_id . ""] = $data;
            $location_ids[] = $external_id;
        }

        $campagne['distribution']['logistic_centers'][$lc['id']]['num_locations'] = count($location_ids);
        $campagne['distribution']['logistic_centers'][$lc['id']]['locations'] = json_encode($locations);
        $campagne['distribution']['logistic_centers'][$lc['id']]['location_ids'] = json_encode($location_ids);
    }

    $campagne['picklists'] = get_campagne_picklists(CAMPAGNE_ID);
    $campagne['picklist_station_cards'] = get_campagne_station_cards(CAMPAGNE_ID);

    $campagne['edit-lock'] = false;
    if (!empty($campagne['picklists'])) {
        $campagne['edit-lock'] = true;
    }

    return $campagne;
}

function clear_array($arr) {
    foreach ($arr as $key => $data) {
        if (gettype($data) === 'array') {
            if (empty($data)) {
                unset($arr["" . $key . ""]);
            } else {
                $arr["" . $key . ""] = clear_array($data);
            }
        }
        if (gettype($data) === 'string') {
            unset($arr["" . $key . ""]);
        }
    }
    return $arr;
}
function get_campagne_station_cards($campagne_id = false) {
    global $db;
    $ret = [];
    if($campagne_id) {
        $picklists = $db->query("SELECT `id`,`campagne_container_box_id`,`created` FROM `campagne_station_cards` WHERE `campagne_id` = ".$campagne_id."");
        while($result = $picklists->fetch_assoc()) {
            $ret[$result['campagne_container_box_id']] = $result;
        }
    }
    return $ret;
}
function get_campagne_picklists() {

    global $db;
// `campagne_picklists`.`picklist_data` as `picklist_data`,
// `campagne_picklists`.`container_data` as `container_data`,
    $picklists = $db->query("SELECT 
			
			`campagne_picklists`.`id` as `picklist_id`,
			`campagne_picklists`.`campagne_logistic_center_id`,
			DATE_FORMAT(`campagne_picklists`.`created`, '%d-%m-%Y %H:%i uur') as `created_datetime`,
			`campagne_container_boxes`.`id` as `campagne_box_id`,
			`dcs_containers_boxes`.`name` as `campagne_box_name`,
			`dcs`.`id` as `dc_id`,
			`dcs`.`name` as `dc_name`,
			`campagne_dc_trucks`.`id` as `dc_truck_id`,
			
			`dcs_logistic_centers`.`name` as `logistic_center_name`,
			`created_user_table`.`id` as `created_user_id`,
			DATE_FORMAT(`campagne_picklists`.`created`, '%d-%m-%Y %H:%i uur') as `created_datetime`,
			concat_ws(' ', `created_user_table`.`first_name`, `created_user_table`.`last_name`) as `created_user`

		FROM 
			`campagne_picklists`

		LEFT JOIN 
			`campagnes` ON `campagnes`.`id` = `campagne_picklists`.`campagne_id`
		
		LEFT JOIN 
			`campagne_container_boxes` ON `campagne_container_boxes`.`id` = `campagne_picklists`.`campagne_container_box_id`
			LEFT JOIN 
				`dcs_containers_boxes` ON `dcs_containers_boxes`.`id` = `campagne_container_boxes`.`dc_container_box_id`
 		
 		LEFT JOIN 
			`dcs_logistic_centers` ON `dcs_logistic_centers`.`id` = `campagne_picklists`.`campagne_logistic_center_id`

		LEFT JOIN 
			`campagne_dc_trucks` ON `campagne_dc_trucks`.`id` = `campagne_picklists`.`campagne_dc_truck_id`
			LEFT JOIN 
			`dcs` ON `dcs`.`id` = `campagne_dc_trucks`.`dc_id`
		
		LEFT JOIN
			`group_users` as `created_user_table` ON `created_user_table`.`id` = `campagne_picklists`.`created_user_id`

		WHERE 
			`campagne_picklists`.`campagne_id` = " . CAMPAGNE_ID . "

		ORDER BY `campagne_picklists`.`created` DESC

	")or die($db->error);


    $ret = [];

    while ($picklist = $picklists->fetch_assoc()) {

        $truck_id = false;
        $logistic_center_id = false;
        $dc_id = false;

        if (!array_key_exists('campagne_box_id', $picklist) || empty($picklist['campagne_box_id'])) {
            continue;
        }
        if (array_key_exists('campagne_box_id', $picklist) && !empty($picklist['campagne_box_id'])) {
            $box_id = $picklist['campagne_box_id'];
        }

        if ($box_id && !array_key_exists("" . $box_id . "", $ret)) {
            $ret["" . $box_id . ""] = [
                'name' => return_str($picklist['campagne_box_name']),
                'dcs' => [],
                'lcs' => []
            ];
        }

        if (array_key_exists('dc_id', $picklist) && !empty($picklist['dc_id'])) {
            $dc_id = $picklist['dc_id'];
        }

        if ($dc_id && !array_key_exists("" . $dc_id . "", $ret["" . $box_id . ""]['dcs'])) {
            $ret["" . $box_id . ""]['dcs']["" . $dc_id . ""] = [
                'name' => return_str($picklist['dc_name']),
                'trucks' => []
            ];
        }

        if (array_key_exists('campagne_logistic_center_id', $picklist) && !empty($picklist['campagne_logistic_center_id'])) {
            $logistic_center_id = $picklist['campagne_logistic_center_id'];
        }

        if ($logistic_center_id && !array_key_exists("" . $logistic_center_id . "", $ret["" . $box_id . ""]['lcs'])) {
            $ret["" . $box_id . ""]['lcs']["" . $logistic_center_id . ""] = [
                'name' => return_str($picklist['logistic_center_name']),
                'trucks' => []
            ];
        }

        if (array_key_exists('dc_truck_id', $picklist) && !empty($picklist['dc_truck_id'])) {
            $truck_id = $picklist['dc_truck_id'];
        }

        if (!$truck_id && !$logistic_center_id) {
            continue;
        }

        if ($dc_id && $truck_id && !array_key_exists("" . $truck_id . "", $ret["" . $box_id . ""]["dcs"]["" . $dc_id . ""]["trucks"])) {
            $ret["" . $box_id . ""]["dcs"]["" . $dc_id . ""]["trucks"]["" . $truck_id . ""] = $picklist['picklist_id'];
            $array = [
                'id' => $picklist['picklist_id'],
                'created' => $picklist['created_datetime'],
                'blame_user' => $picklist['created_user']
            ];
        } else if ($logistic_center_id) {
            $ret["" . $box_id . ""]["lcs"]["" . $logistic_center_id . ""]["trucks"][] = $picklist['picklist_id'];
            $array = [
                'id' => $picklist['picklist_id'],
                'created' => $picklist['created_datetime'],
                'blame_user' => $picklist['created_user']
            ];
        }

        //echo 'T' . $truck_id . '<br/>';
        //echo 'LC' . $logistic_center_id . '<br/>';
    }

    return $ret;
}

function product_content_description($product_type, $numproducts) {

    intval($numproducts);
    $ret = '';

    if ($product_type === 'set' && $numproducts > 0) {
        $ret = 'Set van ' . $numproducts . ' artikelen';
    } else if ($product_type === 'set' && $numproducts === 0) {
        $ret = 'Complete set';
    }

    return $ret;
}

function station_name($x, $num_chars = 2) {

    $x = intval($x);

    $alfabet = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];

    $number = $x + 1;

    if ($number > count($alfabet)) {

        $char1 = floor($number / count($alfabet));

        $char2 = (($number - 1) > 0 ? ($number - 1) : 0) - ($char1 * count($alfabet));

        $return = $alfabet[$char1] . $alfabet[$char2];
        
    } else {

        $char1 = 0;

        $char2 = $x;

        $return = $alfabet[$char1] . $alfabet[$char2];
    }

    return strtoupper($return);
}

function fetch_campagne_container($container_id) {

    if (!$container_id) {
        return [];
    }

    global $db;
    global $account;

    $q_container = $db->query("
			SELECT 	
				* 
			FROM 
				`dcs_containers` 
			WHERE 
				`id` = " . $container_id . " 
			LIMIT 1");

    if ($q_container->num_rows === 1) {

        $container_array = $q_container->fetch_assoc();

        $q_container_box = $db->query("
			SELECT
				*
			FROM
				`dcs_containers_boxes`
			WHERE
				`container_id` = " . $container_id . "
			ORDER BY
				`name` ASC
		");

        $container_array['boxes'] = [];

        while ($item = $q_container_box->fetch_assoc()) {

            $q_campagne_container_box = $db->query("
					SELECT
						`campagne_container_boxes`.*,
                                                `campagne_station_cards`.`id` as `campagne_station_card_id`,
                                                DATE_FORMAT(`campagne_station_cards`.`created`, '%d-%m-%Y %H:%i uur') as `campagne_station_card_created`,
                                                concat_ws(' ', `created_campagne_station_card_user_table`.`first_name`, `created_campagne_station_card_user_table`.`last_name`) as `campagne_station_card_created_user`,
						CASE WHEN `campagne_station_cards`.`created` IS NOT NULL AND `campagne_station_cards`.`created` < `campagne_container_boxes`.`last_update` THEN '1' ELSE '0' END AS `update_warning`,
                                                DATE_FORMAT(`campagne_container_boxes`.`created`, '%d-%m-%Y %H:%i uur') as `created_date`,
                                                DATE_FORMAT(`campagne_container_boxes`.`last_update`, '%d-%m-%Y %H:%i uur') as `last_box_update`,
						DATE_FORMAT(`campagne_container_boxes`.`last_update`, '%d-%m-%Y %H:%i:%s') as `last_update_date`,
						concat_ws(' ', `created_user_table`.`first_name`, `created_user_table`.`last_name`) as `created_user`,
						concat_ws(' ', `blame_user_table`.`first_name`, `blame_user_table`.`last_name`) as `blame_user`
					FROM
						`campagne_container_boxes`
					LEFT JOIN
						`group_users` as `blame_user_table` ON `blame_user_table`.`id` = `campagne_container_boxes`.`blame_user_id`
					LEFT JOIN
						`group_users` as `created_user_table` ON `created_user_table`.`id` = `campagne_container_boxes`.`created_user_id`
					LEFT JOIN
						`campagne_station_cards` ON `campagne_station_cards`.`campagne_container_box_id` = `campagne_container_boxes`.`id`
                                        LEFT JOIN
						`group_users` as `created_campagne_station_card_user_table` ON `created_campagne_station_card_user_table`.`id` = `campagne_station_cards`.`created_user_id`
                                        WHERE
						`campagne_container_boxes`.`campagne_id` = " . CAMPAGNE_ID . "
					AND
						`campagne_container_boxes`.`dc_container_box_id` = " . $item['id'] . "
					ORDER BY
						`campagne_container_boxes`.`id` ASC
				") or die($db->error);

            $boxnr = 1;
            $max_boxes = $item['max_boxes'];

            $item['campagne_boxes'] = [];
            $item['max'] = 'false';

            $container_array['boxes']['' . $item['id'] . ''] = $item;

            while ($subitem = $q_campagne_container_box->fetch_assoc()) {

                $box = [
                    'id' => $subitem['id'],
                    'name' => return_str($subitem['name']),
                    'erp_id' => $item['erp_id'],
                    'order' => $boxnr,
                    'campagne_products' => $subitem['campagne_products'],
                    'created' => $subitem['created_date'],
                    'created_user' => $subitem['created_user'],
                    'last_update' => $subitem['last_box_update'],
                    'blame_user' => $subitem['blame_user'],
                    'station_card_id' => $subitem['campagne_station_card_id'],
                    'station_card_created' => $subitem['campagne_station_card_created'],
                    'station_card_blame_user' => $subitem['campagne_station_card_created_user'],
                    'update_warning' => $subitem['update_warning']
                ];
               // arr($box);

                $box['products'] = [];

                if (!empty($box['campagne_products'])) {

                    $ids = json_decode($box['campagne_products']);

                    $temp = [];

                    if (count($ids) > 0) {

                        $in_ids = implode(",", $ids);

                        $cp_query = $db->query("
                                SELECT
                                        `campagne_products`.`name`,
                                        `campagne_products`.`id` as `campagne_product_id`,
                                        `campagne_products`.`product_type`,
                                        `campagne_products`.`quantity`,
                                        `campagne_products`.`unit_quantity`,
                                        CEIL(`quantity` / `unit_quantity`) as `quantity_units`,
                                        `campagne_products`.`included_external_ids`,
                                        `campagne_products`.`included_external_products`,
                                        `campagne_products`.`external_id`,
                                        `campagne_products`.`picklist_note`,
                                        `campagne_products`.`picklist_data`,
                                        `campagne_products`.`variations_data`,
                                        `campagne_products`.`value_product`,
                                        `campagne_products`.`set_delivery`,
                                        `campagne_products`.`stations`
                                FROM 
                                        `campagne_products`
                                WHERE 
                                        `campagne_products`.`id` IN (" . $in_ids . ")
                                ");
                       
                        while ($campagne_product = $cp_query->fetch_assoc()) {
                            if (array_key_exists($campagne_product['external_id'], $account['stock']['products'])) {
                                $campagne_product['stock_data'] = json_encode($account['stock']['products']['' . $campagne_product['external_id'] . '']);
                            }

                            $campagne_product['variations'] = 1;
                            if (not_empty('variations_data', $campagne_product)) {
                                $campagne_product['variations'] = count(json_decode($campagne_product['variations_data'], true));
                            }
                            $campagne_product['unit_quantity'] = intval($campagne_product['unit_quantity']);

                            if ($campagne_product['set_delivery'] === 'set') {
                                $campagne_product['stations'] = 'combined';
                            }
                            if ($campagne_product['product_type'] === 'product') {
                                $campagne_product['stations'] = false;
                            }

// todo: Check if needed
                            if (!empty($campagne_product['variations_data'])) {
                                $campagne_product['set_delivery'] = 'variations';
                                $campagne_product['stations'] = 'combined';
                            }

                            ksort($campagne_product);

                            $temp['' . $campagne_product['campagne_product_id'] . ''] = $campagne_product;
                        }

                        $box['products_ids'] = $ids;
                        $station_id = 0;
                        foreach ($ids as $campagne_product_id) {
                            if (array_key_exists($campagne_product_id, $temp)) {
                                $temp['' . $campagne_product_id . '']['station_id'] = station_name($station_id);
                                $box['products'][] = $temp['' . $campagne_product_id . ''];
                                $station_id++;
                            }
                        }
                    }
                }

                $container_array['boxes']['' . $item['id'] . '']['campagne_boxes'][] = $box;
                $boxnr++;
            }
            $container_array['boxes']['' . $item['id'] . '']['num_boxes'] = count($container_array['boxes'][$item['id']]['campagne_boxes']);
            if ($container_array['boxes']['' . $item['id'] . '']['num_boxes'] >= $max_boxes) {
                $container_array['' . $item['id'] . '']['max'] = 'true';
            }
        }
    }
    
   // $container_array['boxes'] = array_values($container_array['boxes']);

    return $container_array;
}

function dclist_delete($dc_id, $campagne_dc_id = false) {

    if (empty($dc_id)) {
        return false;
    }

    $errors = [];

    global $db;

    $campagne_dcs = $db->query("SELECT `id`,`dc_id`,`filename` FROM `campagne_dc` WHERE `campagne_id` = '" . CAMPAGNE_ID . "' && `dc_id` = '" . $dc_id . "'") or die($db->error);

    while ($campagne_dc = $campagne_dcs->fetch_assoc()) {

        $campagne_dc['file'] = DIR_DISTRIBUTION_EXCEL_FILES . $campagne_dc['filename'];

        if (!empty($campagne_dc['file'])) {

            if (!not_empty('id', $campagne_dc)) {
                continue;
            }

            $delete_campagne_dc = $db->query("DELETE FROM `campagne_dc` WHERE `id` = '" . $campagne_dc['id'] . "'");

            if ($delete_campagne_dc) {

                $campagne_dc_trucks = $db->query("SELECT `id` FROM `campagne_dc_trucks` WHERE `campagne_id` = '" . CAMPAGNE_ID . "' && `dc_id` = '" . $dc_id . "'") or die($db->error);

                while ($truck = $campagne_dc_trucks->fetch_assoc()) {

                    if (!not_empty('id', $truck)) {
                        continue;
                    }

                    $delete_campagne_truck = $db->query("DELETE FROM `campagne_dc_trucks` WHERE `id` = '" . $truck['id'] . "' ");

                    if ($delete_campagne_truck) {

                        $delete_campagne_dc_trucks_containers = $db->query("DELETE FROM `campagne_dc_trucks_containers` WHERE `campagne_id` = '" . CAMPAGNE_ID . "' &&  `dc_truck_id` = '" . $truck['id'] . "'");
                    }
                }
            }

            unlink($campagne_dc['file']);
        }
    }

    if (count($errors) === 0) {
        return true;
    } else {
        return $errors;
    }
}


    /*
     * num2alpha
     */
    
    
    function num2alpha($n) {
         $r = '';
        for ($i = 1; $n >= 0 && $i < 10; $i++) {
            $r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))) . $r;
            $n -= pow(26, $i);
        }
        return $r;
       
    }
    
    /*
     * alpha2num
     */
    
    function alpha2num($a) {
       $r = 0;
        $l = strlen($a);
        for ($i = 0; $i < $l; $i++) {
            $r += pow(26, $i) * (ord($a[$l - $i - 1]) - 0x40);
        }
        return $r - 1;
    }

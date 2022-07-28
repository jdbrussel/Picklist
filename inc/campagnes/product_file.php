<?php
if (array_key_exists(PRODUCT_FILE_ID, $campagne['product_files'])) {
    $product_file = $campagne['product_files'][PRODUCT_FILE_ID];
    $file_sql = $db->query("SELECT `excel_data` FROM `campagne_product_files` WHERE `id` = '" . PRODUCT_FILE_ID . "'")or die($db->error);
    if ($file_sql->num_rows === 1) {
        $file = $file_sql->fetch_assoc();
        $excel_data = json_decode($file['excel_data'], true);
    }
}

$filename = DIR_PICKLIST_EXCEL_FILES . $product_file['name'];

if (!empty($excel_data)) {

    $product_columns = $product_file['product_columns'];

    $check_query = "SELECT `id` FROM `campagne_products` ";
    $delete_query = "DELETE FROM `campagne_products` ";

    $where_query = "WHERE `campagne_product_file_id` = " . PRODUCT_FILE_ID . "";

    if (count($product_columns) > 0) {
        $product_columns_string = "'" . implode("','", $product_columns) . "'";
        $where_query .= " AND `campagne_product_file_column_index` NOT IN (" . $product_columns_string . ")";
    }

    $check_query .= $where_query;
    $delete_query .= $where_query;

    $results = $db->query($check_query)or die($db->error);

    if ($results->num_rows > 0) {

        while ($result = $results->fetch_assoc()) {

            $id = $result['id'];

            $container_boxes_query = $db->query("SELECT `id`,`campagne_products` FROM `campagne_container_boxes` WHERE `campagne_products` LIKE '%\"" . $result['id'] . "\",%'") or die($db->error);

            if ($container_boxes_query->num_rows > 0) {

                while ($box = $container_boxes_query->fetch_assoc()) {
                    $campagne_products = str_replace('"' . $result['id'] . '",', '', $box['campagne_products']);
                    $db->query("UPDATE `campagne_container_boxes` SET `campagne_products` = '" . $campagne_products . "' WHERE `id` = '" . $box['id'] . "' ") or die($db->error);
                }
            }
        }

        $db->query($delete_query) or die($db->error());
    }

    $location_rows = [];
    if (array_key_exists('sheet', $excel_data) && array_key_exists('location_data', $excel_data['sheet']) && array_key_exists('rows', $excel_data['sheet']['location_data']) && count($excel_data['sheet']['location_data']['rows']) > 0) {
        $location_rows = $excel_data['sheet']['location_data']['rows'];
        foreach ($location_rows as $key => $location_row) {
            foreach ($location_row as $column => $value) {
                $from_encoding =  mb_detect_encoding($value);
              
                if($from_encoding === 'UTF-8') {
                    $value = mb_convert_encoding($value, "ASCII", $from_encoding);
                    $value = str_replace("?","",$value);
                    $value = trim($value);
                    $value = mb_convert_encoding($value, "UTF-8", "ASCII");
                }
                $location_rows[$key]["" . $column . ""] = str_replace(', ','',$value);
            }
        }
    }
} else {
    die('No-Excel-Data');
}

function check_external_id_for_use($external_id) {

    global $db;

    $query = $db->query("SELECT 
        `id`, `external_id` ,`name` 
        FROM `campagne_products` 
        WHERE 
        `campagne_product_file_id` = " . PRODUCT_FILE_ID . " 
        AND 
        `external_id` = '" . $external_id . "'
    ") or die($db->error);
    if ($query->num_rows === 0) {
        return true;
    } else {
        return false;
//        $result = $query->fetch_assoc();
//        die($external_id . ' bestaat al ('. $result['external_id'] . ' - ' .$result['name'] .')');
    }
}

/////////////////////////////////////
/////////////////////////////////////


if ($product_file['type'] === "products") {

    $campagne_file_products = [];

    $inserted = 0;

    $used_external_ids = [];

    $_SESSION['group_updates'] = [];

    foreach ($product_columns as $column) {

        if (empty($column)) {
            continue;
        }

        $campagne_file_product = $db->query("
            SELECT 
                `id`,
                `external_id`, 
                `name` 
            FROM 
                `campagne_products` 
            WHERE 
                `campagne_product_file_id` = " . PRODUCT_FILE_ID . " 
                    AND 
               `campagne_product_file_column_index` = '" . $column . "'
        ") or die($db->error);

// if($column === "AB") {
// 	$external_ids_str = htmlentities($excel_data['sheet']['products_data']['external_ids'][''.$column.''], ENT_QUOTES);
// 	arr($external_ids_str, false);
// 	preg_match_all(PRODUCT_EXTERNAL_ID_PATTERN, $external_ids_str, $single_matches, PREG_SET_ORDER, 0);
// 	arr($single_matches, false);
// 	if(count($single_matches) > 0) {
// 		arr(PRODUCTS_EXTERNAL_IDS_PATTERN, false);
// 		preg_match_all(PRODUCTS_EXTERNAL_IDS_PATTERN, $external_ids_str, $multi_matches, PREG_SET_ORDER, 0);
// 		arr($multi_matches, false);
// 	}
// }

        if ($campagne_file_product->num_rows === 1) {
            continue;
        } else if (!empty($column) && !array_key_exists("" . $column . "", $campagne_file_products)) {

            if (!array_key_exists('' . $column . '', $excel_data['sheet']['products_data']['names'])) {
                continue;
            }

            $product_name = return_str($excel_data['sheet']['products_data']['names']['' . $column . '']);

            $product_name = nl2br($product_name);

            $product_name = substr(UCFirst(trim($product_name)), 0, 50);

            $product_type = "product";

            $picklist_note = "";

            $external_id = false;

            $product_set_delivery = "";

            $included_external_ids = [];

            $included_external_products = [];

            $external_ids_str = htmlentities($excel_data['sheet']['products_data']['external_ids']['' . $column . ''], ENT_QUOTES);
            
            preg_match_all(PRODUCT_EXTERNAL_ID_PATTERN, $external_ids_str, $matches, PREG_SET_ORDER, 0);
            
            if(empty($matches)) {
                $pattern = "/((HHJ|P)\d{5,7}(\S\d{3}|))(\S\d{3}|\s|\S|)(.*)/";
                preg_match_all('/((HHJ|P)\d{5,7}(\S\d{3}|))(\S\d{3}|\s|\S|)(.*)/', $external_ids_str, $matches, PREG_SET_ORDER, 0);
                // preg_match( $pattern , $external_ids_str, $matches, PREG_SET_ORDER, 0);
            }
            
            if (count($matches) === 1) {
                
                if(strpos($matches[0][0], '/')) {
                    $external_id = false;
                    foreach($matches[0] as $key => $match) {
                        $match = trim($match);
                        if($key === 0) {
                            continue;
                        }
                        if(strpos($match, 'P') === 0) {
                            $external_id = $match;
                            break;
                        }
                    }
    
                }
                
                if(!$external_id) {
                    $external_id = $matches[0][0];
                }
                
            } else if (count($matches) > 1) {

                $external_id = return_article_number("BS");

                $picklist_note = $external_ids_str;

                $preg_match_external_id_index = 1;

                $preg_match_name_index = 5;

                foreach ($matches as $match) {

                    if (not_empty('' . $preg_match_external_id_index . '', $match) && not_empty('' . $preg_match_name_index . '', $match)) {

                        $included_external_ids[] = return_article_number($match[$preg_match_external_id_index]);

                        $included_external_products[] = [
                            'external_id' => return_article_number($match[$preg_match_external_id_index]),
                            'name' => trim($match[$preg_match_name_index])
                        ];
                    }
                }
            }

            if (count($included_external_ids) > 0) {
                $product_type = "set";
                $product_set_delivery = "seperate";
            }

            unset($matches);

            $unit_quantity = 1;

            if (strlen($excel_data['sheet']['products_data']['unit_quantities']['' . $column . '']) > 0) {

                $matches = explode(" ", $excel_data['sheet']['products_data']['unit_quantities']['' . $column . '']);

                foreach ($matches as $match) {

                    if (is_numeric($match)) {
                        $unit_quantity = $match;
                        break;
                    }
                }
            }

            $excelproddata = $excel_data['sheet']['products_data'];
            


            /*
             * 
             * 
              "name" => "products_data",
              "names" => $product_names,
              "version_details" => $product_version_details,
              "external_ids" => $product_external_ids,
              "unit_quantities" => $product_unit_quantities,
              "num_versions" => $product_num_versions,
              "version_multiplier" => $product_version_multiplier
             * 
             *
             *             
             * Update 21/06/2021
             * All products with more than 1 units become a set
             */
            
            if ((integer) $excelproddata['num_versions']['' . $column . ''] > 1) {
                $product_type = 'set';
                $product_set_delivery = 'set';
            }
            if ((integer) $excelproddata['num_versions']['' . $column . ''] === 1) {
                if ((integer) $excelproddata['version_multiplier']['' . $column . ''] > 1) {
                    $product_type = 'products';
                    $product_set_delivery = 'seperate';
                }
                if ((integer) $excelproddata['version_multiplier']['' . $column . ''] > 1) {
                    $product_type = 'set';
                    $product_set_delivery = 'set';
                }
                if ((integer) $excelproddata['version_multiplier']['' . $column . ''] >= 25) {
                    $product_type = 'set';
                    // $product_type = 'set';
                    $product_set_delivery = 'set';
                }
            }

            /*
             * Update 21/06/2021
             * Picklist note adds num versions and version multiplier
             */
            if (!$picklist_note || empty($picklist_note)) {
                $picklist_note = '';
            }
            if (!empty($picklist_note)) {
                $picklist_note = '

';
            }
            if ($product_type === 'set' && (integer) $excelproddata['num_versions']['' . $column . ''] >= 1) {
                $picklist_note .= '
Aantal versies: ' . $excelproddata['num_versions']['' . $column . ''];
            }
            if ((integer) $excelproddata['version_multiplier']['' . $column . ''] >= 1) {
                $picklist_note .= '
Aantal per versie: ' . $excelproddata['version_multiplier']['' . $column . ''] . ($excelproddata['version_multiplier']['' . $column . ''] === 1 ? ' stuk' : ' stuks');
            }


            if (not_empty('locations_id_column_index', $product_file)) {
                
                $column_index = [
                    'external_id' => $product_file['locations_id_column_index'],
                    'name' => $product_file['locations_name_column_index'],
                    'address_1' => $product_file['locations_address_column_index'],
                    'address_number' => $product_file['locations_address_number_column_index'],
                    'postal_code' => $product_file['locations_postal_code_column_index'],
                    'city' => $product_file['locations_city_column_index'],
                    'rayon' => $product_file['locations_rayon_column_index'],
                    'formule' => $product_file['locations_formule_column_index'],
                ];
                
                $campagne_product_locations = [];

                $campagne_product_picklist = [];

                $campagne_product_quantity = 0;

                $unknow_index = 0;

                foreach ($location_rows as $location_row) {
                    
                    $location_quantity = 0;

                    if (not_empty("" . $column_index['external_id'] . "", $location_row)) {

                        $location_external_id = return_location_external_id($location_row["" . $column_index['external_id'] . ""]);

                        if (!$location_external_id) {
                            if (strpos(strtolower($location_row["" . $column_index['name'] . ""]), "hoofdkantoor") === false) {
                                continue;
                            } else {
                                $unknow_index++;
                                $location_external_id = 'HK-' . $unknow_index;
                            }
                        }

//                        $location_data = [];
//                        foreach($column_index as $index_name => $index_index) {
//                            if(array_key_exists(''.$index_index.'', $location_row)) {
//                                $location_data[''.$index_name.''] = (string) trim($location_row[''.$index_index.'']);
//                            }
//                        }
                       

                        $location_data = return_location($location_external_id, $location_row, $column_index);
                  
                        foreach ($location_data as $key => $value) {
                            $location_data["" . $key . ""] = str_replace("'", "", stripslashes($value));
                        }

                            if(!array_key_exists(''.$location_external_id.'', $account['locations'])) {

                                $check_query = "SELECT `id` FROM `groups` WHERE `owner_id` = '".ACCOUNT_ID."' &&  `external_id` = '".$db->real_escape_string($location_external_id)."'";
                               
                                $check = $db->query($check_query)or die();

                                if($check->num_rows === 0) {

                                    $group_query = "INSERT INTO `groups` 
                                        (
                                            `type`,
                                            `owner_id`, 
                                            `external_id`, 
                                            `name`,
                                            `created`, 
                                            `created_user_id`
                                        )
                                        VALUES 
                                        (
                                            'location',
                                            ".ACCOUNT_ID.", 
                                            '".$db->real_escape_string($location_external_id)."', 
                                            '".$db->real_escape_string($location_data['name'])."',
                                            NOW(),
                                            ".USER_ID."
                                        )
                                    ";

                                    $insert_group = $db->query($group_query)or die();
                                    if($insert_group && $db->insert_id) {
                                        $location_data['group_id'] = $db->insert_id;
                                    }

                                }
                                else {
                                    $group = $check->fetch_assoc();
                                    $location_data['group_id'] = $group['id'];
                                }
                            }
                            
                            if(!empty($location_data['group_id']) && (!array_key_exists('group_address_id', $location_data) || empty($location_data['group_address_id']))) {

                                $check_query = "SELECT `id` FROM `group_addresses` WHERE `owner_id` = '".$location_data['group_id']."'";
                                $check = $db->query($check_query)or die();

                                if($check->num_rows === 0) {
                                    $group_address_query = "INSERT INTO `group_addresses` 
                                        (
                                            `owner_id`, 
                                            `type`, 
                                            `external_id`, 
                                            `address_1`, 
                                            `postal_code`, 
                                            `city`, 
                                            `formule`, 
                                            `rayon`, 
                                            `country`, 
                                            `country_code`,
                                            `created`, 
                                            `created_user_id`
                                        )
                                        VALUES 
                                        (
                                            ". $location_data['group_id'].", 
                                            'delivery',
                                            '".$db->real_escape_string($location_data['external_id'])."', 
                                            '".$db->real_escape_string($location_data['address_1'])."',
                                            '".$db->real_escape_string($location_data['postal_code'])."',
                                            '".$db->real_escape_string($location_data['city'])."',
                                            '".$db->real_escape_string($location_data['formule'])."',
                                            '".$db->real_escape_string($location_data['rayon'])."',
                                            '".$db->real_escape_string($location_data['country'])."',
                                            '".$db->real_escape_string($location_data['country_code'])."',
                                            NOW(),
                                            ".USER_ID."
                                        )
                                    ";

                                    $insert_group_address = $db->query($group_address_query)or die();
                                    if($insert_group_address && $db->insert_id) {
                                        $location_data['group_address_id'] = $db->insert_id;
                                    }
                                }
                                else {
                                    $group_address = $check->fetch_assoc();
                                    $location_data['group_address_id'] = $group_address['id'];
                                }


                            }


                        if (not_empty("" . $column . "", $location_row) && is_numeric($location_row["" . $column . ""])) {

                            $location_quantity = intval($location_row['' . $column . '']);
                            $campagne_product_quantity += $location_quantity;

                            if (!array_key_exists("" . $location_external_id . "", $campagne_product_locations)) {
                                $campagne_product_locations["" . $location_external_id . ""] = $location_data;
                            }
                        }

                        // if($location_quantity < 1) {
                        //	 continue;
                        // }

                        if (!array_key_exists("" . $location_external_id . "", $campagne_product_picklist)) {
                            $location_data['quantity'] = $location_quantity;
                            $campagne_product_picklist["" . $location_external_id . ""] = $location_data;
                        }

                    }
                }

            }

            $campagne_file_products["" . $column . ""] = [
                'campagne_product_file_column_index' => $column,
                'external_id' => $external_id,
                'name' => $product_name,
                'product_type' => $product_type,
                'included_external_ids' => json_encode($included_external_ids),
                'included_external_products' => json_encode($included_external_products),
                'set_delivery' => $product_set_delivery,
                'picklist_note' => $picklist_note,
                'unit_quantity' => $unit_quantity,
                'picklist_data' => json_encode($campagne_product_picklist),
                'locations' => json_encode($campagne_product_locations),
                'num_locations' => count($campagne_product_locations),
                'quantity' => $campagne_product_quantity
            ];

            if (!check_external_id_for_use($external_id)) {
                $external_id = '----------';
            }

            $query = "
				INSERT INTO
					`campagne_products`
				(
					`campagne_id`,
					`campagne_product_file_id`,
					`campagne_product_file_column_index`,
					`external_id`,
					`name`,
					`product_type`,
					`included_external_ids`,
					`included_external_products`,
					`set_delivery`,
					`unit_quantity`,
					`quantity`,
					`locations`,
					`picklist_note`,
					`picklist_data`,
					`created`,
					`last_update`,
					`blame_user_id`
					)
				VALUES
				(
					'" . CAMPAGNE_ID . "',
					'" . PRODUCT_FILE_ID . "',
					'" . $column . "',
					'" . $db->real_escape_string($external_id) . "',
					'" . $db->real_escape_string($product_name) . "',
					'" . $product_type . "',
					'" . json_encode($included_external_ids) . "',
					'" . json_encode($included_external_products) . "',
					'" . $product_set_delivery . "',
					'" . $unit_quantity . "',
					'" . $campagne_product_quantity . "',
					'" . count($campagne_product_locations) . "',
					'" . $db->real_escape_string($picklist_note) . "',
					'" . json_encode($campagne_product_picklist) . "',
					NOW(),
					NOW(),
					'" . USER_ID . "'
				)";


            $insert = $db->query($query) or die($db->error);
            if ($insert && $db->insert_id) {
                if (empty($external_id)) {
                    update_external_id($db->insert_id, false, 'B');
                }
                $inserted++;
            }
        }
    }
    if ($inserted > 0) {
        header("Location: " . CAMPAGNE_URL . "&product_file_id=" . PRODUCT_FILE_ID . "&dev");
    }
    ?>
    <div class="tabs" data-back="<?php echo CAMPAGNE_URL; ?>">

        <?
        ////
        //// TAB INCLUDED PRODUCTS
        ////
        ?>

        <div class="tab" data-tab-id="included-products" data-name="Productlijst" data-icon="pie_chart">

            <div class="row">

                <div class="col-8">

                    <h4>Producten</h4>
                    <hr>
    <?php
    // arr($product_file['products']);
    if (!empty($product_file['products'])) {
        ksort($product_file['products']);
        foreach ($product_file['products'] as $column => $product) {
            // arr($product,false);
            echo $product['external_id'] . '  | ' . $product['name'] . ' (<span class="quantity">' . $product['quantity'] . '</span>)<hr>';
        }
    }
    ?>
                </div>
                <div class="col-4">

                    <h4>Mogelijke producten</h4>
                    <hr>

                    <form method="POST" action="#picklist-products" name="product_name_columns" style="display:block">

                        <div class="row">
                            <div class="col-6">
                                <input type="checkbox" name="select-all" id="select-all" data-item-name="products_value_column_indexes" style="margin: 5px 0  0 ">
                                <label for="select-all">Alles Selecteren</label>
                            </div>

                            <div class="col-6">
                                <input type="checkbox" name="de-select-all" id="de-select-all" data-item-name="product_column_indexes" style="margin: 5px 0  0 ">
                                <label for="de-select-all">Alles De-selecteren</label>
                            </div>
                        </div>

                        <script>
                            $(function () {
                                $('#select-all').unbind('click').bind('click', function () {
                                    if ($(this).is(':checked')) {
                                        $('#de-select-all').prop('checked', false);
                                        $("input[type=checkbox].product_column_checkbox:not(:checked)").trigger('click');
                                    }
                                });
                                $("input[type=checkbox].product_column_checkbox").unbind('click').bind('click', function () {
                                    if (!$(this).is(':checked') && $('#select-all').is(':checked')) {
                                        $('#select-all').prop('checked', false);
                                    }
                                    if ($(this).is(':checked')) {
                                        $('#de-select-all').prop('checked', false);
                                    }
                                });
                                if ($("input[type=checkbox].product_column_checkbox:not(:checked)").length === 0) {
                                    $('#select-all').prop('checked', true);
                                }
                                $('#de-select-all').unbind('click').bind('click', function () {
                                    if ($(this).is(':checked')) {
                                        $('#select-all').prop('checked', false);
                                        $("input[type=checkbox].product_column_checkbox:checked").trigger('click');
                                    }
                                });
                            });
                        </script>

                        <ul class="chips">
    <?php
    foreach ($excel_data['sheet']['products_data']['names'] as $columnname => $value) {

        if (empty($value) || $value === 'Item - Versie Details') {
            continue;
        }

        $checked = "";
        if (in_array("" . $columnname . "", $product_columns)) {
            $checked = "checked";
        }
        ?>

                                <li class="ui-state-default selectable-item checked <?= (!empty($checked) ? "active-item" : ""); ?> stretch"><label for="column_<?= $columnname; ?>" class="checkbox stretch text-overflow"><input type="checkbox" name="products_value_column_indexes[]" value="<?= $columnname; ?>" id="column_<?= $columnname; ?>" onchange="$(this).parent('li').toggleClass('active-item');" class="product_column_checkbox checked"  <?= $checked; ?>><span class="column_id"><?= $columnname; ?></span><?= return_str($value); ?></label>
                                </li>

        <?php
    }
    ?>
                        </ul>

                        <br/>
                        <input type="submit" name="save_product_columns" value="Wijzigingen opslaan">

                    </form>
                </div>
            </div>

        </div>

        <?
        ////
        //// TAB VEDEELLIJSTEN
        ////

        ?>

        <div class="tab" data-tab-id="picklists" data-name="Verdeellijst" data-icon="pie_chart">

            <div class="row">

                <div class="col-xs-12 col-md-7">

                    <div class="sheet-container">

    <?php
    if (!empty($product_file['products'])) {

        //        $test = json_decode($product_file['products']['L']['picklist_data'], true);
        //        $output = '';
        //        foreach($test as $location_id => $data) {
        //            $output .= $location_id . ";" . $data['quantity']. "<br/>";
        //        }
        //        arr($output);

        foreach ($product_file['products'] as $item) {

            //arr($item['picklist_data'], true);
            ?>

                                <div class="picklist-container" data-picklist-campagne-product-id="<?= $item['id']; ?>">
                                    <div class="sheet" data-id="sheet-<?= $item['id']; ?>">
                                        <h4><strong><?= $item['external_id']; ?></strong><br/><?= html_entity_decode($item['name']); ?></h4>
                                        <hr>
                                        <div class="scroll_browser" style="display:block;overflow:auto;height:625px;padding: 0px 5px 0px 0px;">
                                            <table border="0" cellpadding="0" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th colspan="3">Filiaal</th>
                                                        <th colspan="2" style="text-align:left;">
                                                            &nbsp;
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
            <?php
            $item['picklist'] = [];
            $picklist = json_decode($item['picklist_data'], true);
            if (is_array($picklist)) {
                $item['picklist'] = json_decode($item['picklist_data'], true);
            }

            $locations = 0;
            $quantity = 0;

            foreach ($item['picklist'] as $listitem) {

                if (not_empty('external_id', $listitem)) {

                    if (not_empty("" . $listitem['external_id'] . "", $account['locations'])) {
                        $listitem['group_id'] = $account['locations']['' . $listitem['external_id'] . '']['group_id'];
                    }

                    $unit_quantity = 1;
                    if (not_empty('unit_quantity', $item)) {
                        $unit_quantity = intval($item['unit_quantity']);
                    }

                    if ($unit_quantity > 1) {
                        $listitem['quantity'] = $listitem['quantity'] / $unit_quantity;
                    }

                    if ($listitem['quantity'] > 0) {
                        $quantity = $quantity + $listitem['quantity'];
                        $locations++;
                    }
                }
                ?>
                                                        <tr data-search-id="<?= $listitem['external_id']; ?>">
                                                            <td class="id small"><?= $listitem['external_id']; ?></td>
                                                            <td class="id" style="padding:0px;width:15px;min-width:15px;max-width:15px;"><span onclick="$('[data-id=picklist-locations]').trigger('click');"><?= (!empty($listitem['group_id']) ? '<i class="material-icons active">link</i>' : '<i class="material-icons no-good">link_off</i>'); ?></span></td>
                                                            <td class="stretch text-overflow"><?= html_entity_decode($listitem['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                            <td>
                                                                <div class="picklist-item">
                                                                    <input type="hidden" class="campagne_product_id" value="<?= $item['external_id']; ?>">
                                                                    <input type="hidden" class="external_id" value="<?= $listitem['external_id']; ?>">
                                                                    <input type="hidden" class="location_name" value="<?= $listitem['name']; ?>">
                                                                    <input type="number" name="quantity" value="<?= $listitem['quantity']; ?>" min="0" data-unit-quantity="<?= $unit_quantity; ?>" class="in_row item-quantity <?= ($listitem['quantity'] === 0 || !$listitem['quantity'] ? 'zero' : ''); ?>">
                                                                </div>
                                                            </td>
                                                            <td class="id small" style="min-width:60px;"><?= ($item['product_type'] === 'set' ? 'sets' : 'stuks'); ?></td>
                                                        </tr>

                <?php
            }
            ?>
                                                </tbody>
                                                <tfooter>
                                                    <tr>
                                                        <td colspan=3>Aantal filialen:</td>
                                                        <td class="quantity num_locations" data-campagne-product-id="<?= $item['id']; ?>"><?= $locations; ?></td>
                                                        <td class="id small" style="min-width:60px;"><?= ($item['product_type'] === 'set' ? 'sets' : 'stuks'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan=3>Aantal producten totaal:</td>
                                                        <td class="quantity num_products" data-campagne-product-id="<?= $item['id']; ?>"><?= $quantity; ?></td>
                                                        <td class="id small" style="min-width:60px;"><?= ($item['product_type'] === 'set' ? 'sets' : 'stuks'); ?></td>
                                                    </tr>
                                                </tfooter>
                                            </table>
                                        </div>
                                    </div>
                                </div>
            <?php
        }
    }
    ?>
                    </div>
                </div>
                <div class="col-xs-12 col-5 right-menu">
                    <h4>Producten</h4>
                    <hr>
                    <div class="scroll_browser" style="display:block;overflow:auto;height:625px;padding: 0px 5px 0px 0px;">
                        <ul class="chips sheet-selector">
    <?php
    if (!empty($product_file['products'])) {
        // arr($product_file['products']);
        foreach ($product_file['products'] as $item) {
            echo '<li class="ui-state-default sortable-item" data-campagne-product-id="' . $item['id'] . '" data-search-id="' . $item['external_id'] . '"><a class="sheet-selector text-overflow stretch" data-id="' . $item['id'] . '" ><span class="external_id" data-value="external_id">' . (!empty($item['external_id']) ? $item['external_id'] : '---') . '</span>' . html_entity_decode($item['name'], ENT_QUOTES, 'UTF-8') . '</a></li>';
        }
    }
    ?>
                        </ul>
                    </div>

                </div>
            </div>

        </div>

        <?
        ////
        //// EINDE TABS
        ////
        ?>

    </div>

    <?php
} else if ($product_file['type'] === "variation_products") {

    $variantions_column = $product_file['variations_name_column_index'];

    $variantions_location_match_column = $product_file['variantions_location_match_column'];

    $campagne_file_product_variations = [];

    foreach ($excel_data['sheet']['variation_data']['rows'] as $row) {

        if (array_key_exists("" . $variantions_column . "", $row)) {
            $variant_name = return_product_variant_name($row["" . $variantions_column . ""]);
        }
        if (!empty($variant_name) && !array_key_exists("" . $variant_name . "", $campagne_file_product_variations)) {
            /*
              $campagne_file_product_variations["" . $variant_name . ""] = [

              'name' => $variant_name,
              'data' => json_encode($row)
              ];
             */

            $campagne_file_product_variations[] = [
                'name' => $variant_name,
                'data' => json_encode($row)
            ];
        }
    }

    // ksort($campagne_file_product_variations);

    $variations = [];

    foreach ($campagne_file_product_variations as $variant) {
        $variations[] = $variant;
    }

    $campagne_file_products = [];

    $p = 0;

    foreach ($product_columns as $column) {

        if (empty($column)) {
            continue;
        }

        $product_name = return_variation_product_name($excel_data['sheet']['variation_data']['headers']['' . $column . '']);

        $campagne_file_product = $db->query("SELECT * FROM `campagne_products` WHERE `campagne_product_file_id` = " . PRODUCT_FILE_ID . " AND `campagne_product_file_column_index` = '" . $column . "'") or die($db->error);

        if ($campagne_file_product->num_rows === 1) {
            $data = $campagne_file_product->fetch_assoc();
            $campagne_file_products["" . $column . ""] = [
                'external_id' => $data['external_id'],
                'campagne_product_file_column_index' => $data['campagne_product_file_column_index'],
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'num_locations' => $data['locations'],
                'num_variations' => count(json_decode($data['variations_data'], true)),
                'variations' => json_decode($data['variations_data'], true),
                'picklist_data' => json_decode($data['picklist_data'], true)
            ];
            continue;
        }


        if (!empty($column) && !array_key_exists("" . $column . "", $campagne_file_products)) {

            // arr($column, false);
            $campagne_file_products["" . $column . ""] = [
                'external_id' => false,
                'campagne_product_file_column_index' => $column,
                'name' => $product_name,
                'quantity' => 0,
                'num_locations' => 0,
                'num_variations' => 0,
                'variations' => [],
                'picklist_data' => []
            ];


            $i = 0;
            foreach ($variations as $variant) {

                $variation_name = $variant['name'];

                $i++;

                $product_variant_quantity = 1;

                if (!empty($variation_name) && !array_key_exists("" . $variation_name . "", $campagne_file_products["" . $column . ""]['variations'])) {

                    $variation_id = 'V' . return_index($i);
                    $variant_data = json_decode($variant['data'], true);

                    $variant_external_id_column = false;

                    if (not_empty('' . $column . '', $product_file['product_variant_articlenumber_columns'])) {
                        $variant_external_id_column = $product_file['product_variant_articlenumber_columns']['' . $column . ''];
                    }

                    if (array_key_exists("" . $variant_external_id_column . "", $variant_data)) {
                        $variant_external_id = return_article_number($variant_data['' . $variant_external_id_column . '']);
                    } else {
                        $variant_external_id = false;
                    }

                    $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""] = [
                        'external_id' => $variant_external_id,
                        'id' => $variation_id,
                        'name' => $variation_name,
                        'quantity' => 0,
                        'num_locations' => 0,
                        'locations' => []
                    ];
                }

                $locations = [];

                $headers = array_flip($excel_data['sheet']['location_data']['headers']);

                if (array_key_exists("" . $product_name . "", $headers)) {
                    $variant_quantity_column = $headers["" . $product_name . ""];
                    settype($variant_quantity_column, 'string');
                }

                foreach ($location_rows as $location) {

                    $variant_quantity = 1;

                    if (!empty($variant_quantity_column) && array_key_exists("" . $variant_quantity_column . "", $location)) {
                        $variant_quantity = $location["" . $variant_quantity_column . ""];
                    }

                    $variation_match_name = return_product_variant_name($location["" . $variantions_location_match_column . ""]);
                    if ($variation_match_name !== $variation_name) {
                        continue;
                    }

                    $external_id = $location['' . $product_file['locations_id_column_index'] . ''];
                    $location_name = $location['' . $product_file['locations_name_column_index'] . ''];

                    if (!$external_id || array_key_exists("" . $external_id . "", $locations)) {
                        continue;
                    }

                    $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['num_locations']++;

                    $this_location = [];

                    $this_location['external_id'] = $external_id;
                    $this_location['name'] = $location_name;

                    $this_location["variant"] = [
                        "id" => $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['id'],
                        "external_id" => $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['external_id'],
                        "name" => $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['name']
                    ];

                    $quantity = $variant_quantity * $product_variant_quantity;
                    if ($quantity > 0) {
                        $campagne_file_products["" . $column . ""]["variations"]["" . $variation_name . ""]["quantity"] += $quantity;
                        $this_location['quantity'] = $quantity;
                    }

                    if (array_key_exists('locations', $account) && array_key_exists("" . $external_id . "", $account['locations'])) {
                        $this_location['group'] = $account['locations']["" . $external_id . ""];
                        foreach ($this_location['group'] as $key => $value) {
                            $this_location['group'][$key] = htmlentities($value, ENT_QUOTES);
                        }
                        $this_location['name'] = $this_location['group']['name'];
                    }

                    $locations["" . $external_id . ""] = $this_location;

                    if (!empty($external_id) && !array_key_exists("" . $external_id . "", $campagne_file_products["" . $column . ""]['picklist_data'])) {

                        $campagne_file_products["" . $column . ""]['picklist_data']["" . $external_id . ""] = [
                            "external_id" => $external_id,
                            "name" => $this_location['name'],
                            "variant" => [
                                "id" => $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['id'],
                                "external_id" => $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['external_id'],
                                "name" => $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['name']
                            ],
                            "quantity" => $this_location['quantity']
                        ];
                    }
                }

                if ($campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['quantity'] > 0) {
                    $campagne_file_products["" . $column . ""]['num_variations']++;
                }

                $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['locations'] = json_encode($locations);
                $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['locations'] = $locations;


                $campagne_file_products["" . $column . ""]['quantity'] += $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['quantity'];
                $campagne_file_products["" . $column . ""]['num_locations'] += $campagne_file_products["" . $column . ""]['variations']["" . $variation_name . ""]['num_locations'];
            }

            if ($campagne_file_product->num_rows === 0) {

                $variation_data = json_encode($campagne_file_products["" . $column . ""]['variations']);
                $picklist_data = json_encode($campagne_file_products["" . $column . ""]['picklist_data']);

                $query = "INSERT INTO `campagne_products` (
					`campagne_id`,
					`campagne_product_file_id`,
					`campagne_product_file_column_index`,
					`product_type`,
					`external_id`,
					`variations_data`,
					`picklist_data`,
					`name`,
					`locations`,
					`quantity`,
					`created`,
					`last_update`,
					`blame_user_id`
				) VALUES (
					'" . CAMPAGNE_ID . "',
					'" . PRODUCT_FILE_ID . "',
					'" . $column . "',
					'product',
					'" . $campagne_file_products["" . $column . ""]['external_id'] . "',
					'" . $variation_data . "',
					'" . $picklist_data . "',
					'" . $campagne_file_products["" . $column . ""]['name'] . "',
					'" . $campagne_file_products["" . $column . ""]['num_locations'] . "',
					'" . $campagne_file_products["" . $column . ""]['quantity'] . "',
					NOW(),
					NOW(),
					'" . USER_ID . "'
				)";

                $insert = $db->query($query) or die($query);

                if ($insert && $db->insert_id) {
                    if (empty($campagne_file_products["" . $column . ""]['external_id'])) {
                        update_external_id($db->insert_id, false, 'BV');
                    }
                }
            }
        }
        $p++;
    }

    $product_file['variation_data'] = json_encode($campagne_file_products);

    $variation_products = json_decode($product_file['variation_data'], true);
    ?>
    <div class="tabs" style="margin: 0 auto 15px auto" data-back="<?= CAMPAGNE_URL; ?>#product-files">

        <div class="tab" data-tab-id="picklist-products" data-name="Producten in dit bestand (<?= count($variation_products); ?>)" data-icon="style">

            <div class="row">
                <div class="col-8">

                    <style>
                        .station_id {
                            background:#333;
                            color:#FFF;
                            text-align:center;
                            margin: 0 15px 0 0px;
                            padding: 2px 10px;
                        }
                    </style>

    <?php
    $i = 0;
    foreach ($variation_products as $column => $product) {
        $i++;
        $product_id = $i;
        ?>
                        <div class="sheet <?= ($i === 1 ? 'current' : '' ); ?>" data-id="sheet-<?= $product_id; ?>">

                            <h4><?= $product['name']; ?> (<?= $product['num_variations']; ?> varianten)</h4>
                            <hr>

                            <table border="0" cellpadding="0" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th class="index small">#</th>
                                        <th class="stretch">Variant</th>
                                        <th class="small">Locatie's</th>
                                        <th class="small">Artikelnummer</th>
                                        <th class="small">Aantal</th>
                                        <th class="" colspan=2></th>
                                    </tr>
                                </thead>
                                <tbody>
        <?php
        foreach ($product['variations'] as $variant_name => $variant) {
            echo '<tr>';
            echo '<td class="small">' . $variant['id'] . '</td>';

            echo '<td>' . $variant_name . '</td>';
            echo '<td class="quantity small">' . count($variant['locations']) . '</td>';
            echo '<td><input type="text" data-campagne-product-variant-id="' . $variant['id'] . '" value="' . $variant['external_id'] . '" data-original-value="' . $variant['external_id'] . '" class="campagne_product_variant_external_id in_row id capitalize stocksearch  ui-autocomplete-input" data-value="campagne_product_variant_external_id" autocomplete="off"></td>';

            echo '<td class="quantity">' . $variant['quantity'] . '</td>';
            echo '<td class="small">stuks</td>';
            echo '<td class="small"><a class="in_row Verdeellijst" data-action="edit-variant-picklist" data-item="<?=json_encode($location_data);?>""><i class="material-icons">list</i></a></td>';
            echo '</tr>';

            // echo '<tr>';
            // 	echo '<td colspan=10>';
            // 		arr( count($variant['locations']), false);
            // 	echo '</td>';
            // echo '</tr>';
        }
        ?>
                                </tbody>
                            </table>
                        </div>
        <?php
    }
    ?>

                </div>
                <div class="col-4">

    <?php
    if (!empty($variation_products)) {
        ?>
                        <h4>Geselecteerde Producten</h4>
                        <hr>

                        <form method="POST" action="#picklist-products">

                            <ul class="chips sheet-selector">
        <?php
        $i = 0;
        foreach ($variation_products as $column => $product) {

            $product_id = $i + 1;
            ?>

                                    <li class="ui-state-default sortable-item" data-campagne-variant-product-id="<?= $column; ?>" data-search-id=""><a class="sheet-selector current" data-id="<?= $product_id; ?>"><span class="external_id" data-value="external_id">
                                    <?= $product['campagne_product_file_column_index']; ?></span><?= $product['name']; ?>
                                            <span style="margin: 5px;float: right;width:33%">
                                                <input type="hidden" name="product_columns" value="<?= $product_file['products_value_column_indexes']; ?>">
                                                <select name="save_variant_article_number[<?= $column; ?>]" class="in_row" onchange="this.form.submit()">
                                                    <option value="">Kies een kolom</option>
            <?php
            $selected_column = $product_file['product_variant_articlenumber_columns']["" . $column . ""];

            foreach ($excel_data['sheet']['variation_data']['headers'] as $column => $name) {
                $select = false;
                if ($selected_column === $column) {
                    $select = true;
                }
                echo "<option value='" . $column . "' " . ($select ? 'selected' : '') . ">" . $column . " - " . $name . " - " . $selected_column . "</option>";
            }
            ?>
                                                </select>
                                            </span>
                                        </a></li>

            <?php
            $i++;
        }
        ?>
                            </ul>

                        </form>
        <?php
    }
    ?>
                    <h4>Mogelijke producten</h4>
                    <hr>

                    <form method="POST" action="#picklist-products" name="product_name_columns" style="display:block">

                        <ul class="chips">
    <?php
    foreach ($excel_data['sheet']['variation_data']['headers'] as $columnname => $value) {

        $checked = "";
        if (in_array("" . $columnname . "", $product_columns)) {
            $checked = "checked";
        }
        ?>

                                <li class="ui-state-default selectable-item checked <?= (!empty($checked) ? "active-item" : ""); ?>"><input type="checkbox" name="products_value_column_indexes[]" value="<?= $columnname; ?>" id="column_<?= $columnname; ?>" onchange="$(this).parent('li').toggleClass('active-item');" class="checked"  <?= $checked; ?>><label for="column_<?= $columnname; ?>" class="checkbox"><?= $columnname; ?> - <?= $value; ?></label>
                                </li>

        <?php
    }
    ?>
                        </ul>

                        <br/>
                        <input type="submit" name="save_product_columns" value="Wijzigingen opslaan">

                    </form>


                </div>

            </div>

        </div>

        <div class="tab" data-tab-id="settings" data-name="Instellingen excel" data-icon="settings">
            <h4>Settings</h4>
            <hr>
        </div>
    <?php
    $variation_data = [];

    // arr($variation_data);

    if (count($variation_data) > 0) {
        ?>
            <div class="tab" data-tab-id="picklist-products" data-name="Producten in dit bestand (<?= count($variation_data); ?>)" data-icon="style">

                <div class="row">

                    <div class="col-8">
                        <h4><?= $product_file['name']; ?></h4>
                        <hr>

                        <table>
                            <thead>
                                <tr>
                                    <th class="small">id</th>
                                    <th class="small">Artikelnummer</th>
                                    <th class="small">Productnaam</th>
                                    <th class="small">Variations</th>
                                    <th class="small" colspan="2">Nodig</th>
                                </tr>
                            </thead>
                            <tbody>

        <?php
        $p_id = 0;

        foreach ($variation_data as $product_name => $variant) {

            $p_id++;

            echo '<tr>';

            echo '<td class="index small">' . $p_id . '</td>';
            echo '<td class="small">' . (!empty($variant['external_id']) ? $variant['external_id'] : '---') . '</td>';
            echo '<td class="stretch text-overflow">' . $product_name . '</td>';
            echo '<td class="quantity small">' . count($variant['variants']) . '</td>';
            echo '<td class="quantity">' . $variant['quantity'] . '</td>';
            echo '<td class="small">stuks</td>';

            echo '</tr>';
            //arr( $variant, false);
        }
        ?>

                            </tbody>

                        </table>

                    </div>
                    <div class="col-4">

        <?php
        $p_id = 0;

        foreach ($variation_data as $product_name => $variation_product) {

            $p_id++;

            echo "<h4>" . $product_name . "</h4>";
            echo "<hr>";

            foreach ($variation_product['variants'] as $variant) {
                echo '<span style="font-size:11px;">' . $variant['id'] . ' - ' . $variant['variant'] . ' - ' . $variant['quantity'] . '<br/>';
                // arr($variant, false);
            }
        }
        ?>

                    </div>

                </div>
            </div>
        <?php
    }
    ?>
    </div>
        <?php
    }


    if (1 === 1) {
        
    } else if ($product_file['type'] === "products" && !empty($product_file['excel_data'])) {
        $excel_data = json_decode($product_file['excel_data'], true);
    } else if ($product_file['type'] === "products" && file_exists($filename)) {

        try {
            $inputFileType = PHPExcel_IOFactory::identify($filename);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($filename);
            $excel_data = array(1, $objPHPExcel->getActiveSheet()->toArray(null, false, true, true));

            $db->query("UPDATE `campagne_product_files` SET `excel_data` = '" . $db->real_escape_string(json_encode($excel_data)) . "' WHERE `id` = '" . PRODUCT_FILE_ID . "'") or die($db->error);
        } catch (\Exception $exc) {
            var_dump($exc);
        }
    }


    if (1 == 2 && $product_file['type'] === "products" && !empty($excel_data)) {


        if ($excel_data[0] === 1) {


            $rows = $excel_data[1];

            if ($product_file['products_id_row_index'] > 0 && $product_file['products_name_row_index'] > 0) {

                $productids = $rows[$product_file['products_id_row_index']];
                $productnames = $rows[$product_file['products_name_row_index']];

                foreach ($productnames as $key => $productname) {

                    if (empty($productnames[$key])) {
                        unset($productnames[$key]);
                        unset($productids[$key]);
                        continue;
                    } else if (empty($productids[$key]) || strlen($productids[$key]) < 3) {
                        unset($productnames[$key]);
                        unset($productids[$key]);
                        continue;
                    } else if (!empty($productids[$key]) && strlen($productids[$key]) > 3) {
                        preg_match_all(PRODUCT_EXTERNAL_ID_PATTERN, $productids[$key], $matches, PREG_SET_ORDER, 0);
                        if (count($matches) < 1) {
                            unset($productnames[$key]);
                            unset($productids[$key]);
                            continue;
                        }
                    } else if (in_array($productname, SKIP_PRODUCTNAMES)) {
                        unset($productnames[$key]);
                        unset($productids[$key]);
                        continue;
                    }

                    $productnames[$key] = return_name($productnames[$key]);

                    // echo $productnames[$key] . ' = ' . $productids[$key]. '<br/>';
                }

                // echo '<pre>';
                // print_r($productnames);
                // print_r($productids);
                // print_r($products_unit_quantity);
                // die();
                // if (strlen($product_file['products_value_column_indexes']) > 0) {
                // 	$productcolumns = explode(',', $product_file['products_value_column_indexes']);
                // } else {
                // 	$productcolumns = [];
                // }
                // if (strlen($product_file['products_articlenumber_column_indexes']) > 0) {
                // 	$product_variant_articlenumber_columns = explode(',', $product_file['products_articlenumber_column_indexes']);
                // } else {
                // 	$productcolumns = [];
                // }

                $locationsheadernames = $rows[$product_file['locations_name_row_index']];
            }
            ?>

        <?php
        $run = true;

        if (!isset($productcolumns) || count($productcolumns) === 0 || !isset($product_file['locations_name_column_index'])) {

            $run = false;

            $query_campagne_products = $db->query("SELECT `id` FROM `campagne_products` WHERE `campagne_id` = '" . $campagne['id'] . "' && `campagne_product_file_id` = '" . PRODUCT_FILE_ID . "'") or die($db->error);

            while ($campagne_product = $query_campagne_products->fetch_assoc()) {

                $query_campagne_container_boxes = $db->query("SELECT `id`,`campagne_products` FROM `campagne_container_boxes` WHERE `campagne_id` = '" . $campagne['id'] . "' AND `campagne_products` LIKE '%\"" . $campagne_product['id'] . "\",%'") or die($db->error);

                while ($box = $query_campagne_container_boxes->fetch_assoc()) {

                    $campagne_products = str_replace('"' . $campagne_product['id'] . '",', '', $box['campagne_products']);

                    $db->query("UPDATE `campagne_container_boxes` SET `campagne_products` = '" . $campagne_products . "' WHERE `id` = '" . $box['id'] . "' ") or die($db->error);
                    $db->query("DELETE FROM `campagne_products` WHERE `id` = '" . $campagne_product['id'] . "'") or die($db->error);

                    echo 'ID: ' . $campagne_product['id'] . ' in ' . $box['campagne_products'] . '<br/>';
                    echo $campagne_products . '<br/>';
                }
            }

            $run = true;
        }
        ?>

        <?php
        if ($run) {
            ?>
            <div class="tabs" style="margin: 0 auto 15px auto" data-back="<?= CAMPAGNE_URL; ?>#product-files">

            <?php
            $_locations = [];

            $products = [];

            $keep = [];

            $knownproductnames = [];
            
            if (array_key_exists("" . $product_file['products_packaging_type_row_index'] . "", $rows)) {
                $products_packaging_type_row = $rows["" . $product_file['products_packaging_type_row_index'] . ""];
            }
            
            if (array_key_exists("" . $product_file['products_unit_quantity_row_index'] . "", $rows)) {
                $products_unit_quantity_row = $rows["" . $product_file['products_unit_quantity_row_index'] . ""];
            }

            foreach ($productcolumns as $product_index => $productcolumn) {

                if (!array_key_exists($productcolumn, $productnames)) {
                    // echo $productcolumn . '<hr>';
                    // echo '<pre>';
                    // print_r($productnames);
                    // die();
                    continue;
                }

                $productname = return_name($productnames['' . $productcolumn . '']);

                $unit_quantity = 1;
                if (is_array($products_unit_quantity_row) && array_key_exists("" . $productcolumn . "", $products_unit_quantity_row)) {
                    $words = explode(' ', trim($products_unit_quantity_row["" . $productcolumn . ""]));
                    foreach ($words as $word) {
                        if (is_numeric($word)) {
                            $unit_quantity = $word;
                            break;
                        }
                    }
                    if (!is_numeric($unit_quantity)) {
                        $unit_quantity = 1;
                    }
                }

                $x = 1;
                do {
                    if (in_array($productname, $knownproductnames)) {
                        $x++;
                        $productname .= ' - ' . $x;
                    }
                } while (in_array($productname, $knownproductnames));

                $knownproductnames[] = $productname;

                $external_id = false;

                if (strlen($productids[$productcolumn]) > 1) {

                    preg_match_all(PRODUCT_EXTERNAL_ID_PATTERN, $productids[$productcolumn], $matches, PREG_SET_ORDER, 0);

                    $external_id = [];

                    foreach ($matches as $match) {

                        $external_id[] = $match[0];
                    }
                }

                if ([] === $external_id || empty($external_id)) {
                    //continue;
                }
                $keep[] = md5($productname);

                $product['type'] = (count($external_id) > 1 ? 'set' : 'product');

                $picklist_note = '';

                if ($product['type'] === 'set') {
                    $picklist_note = $productids[$productcolumn];
                }


                $products['' + $product_index + ''] = [
                    'name' => $productname,
                    'external_id' => $external_id,
                    'type' => $product['type'],
                    'locations' => 0,
                    'quantity' => 0,
                    'unit_quantity' => $unit_quantity,
                    'picklist' => [],
                    'picklist_note' => $picklist_note,
                ];

                $loc_index = 0;
                $unknow_index = 1;


                for ($i = $product_file['locations_start_row_index']; $i <= count($rows); $i++) {

                    $row = $rows[$i];

                    $location_external_id = $row[$product_file['locations_id_column_index']];
                    $location_name = return_name($row[$product_file['locations_name_column_index']]);

                    $location_address_1 = '';
                    $location_postal_code = '';
                    $location_city = '';
                    $location_country = '';

                    if (!empty($product_file['locations_address_column_index'])) {
                        $location_address_1 = return_str($row[$product_file['locations_address_column_index']]);
                    }
                    if (!empty($product_file['locations_address_number_column_index'])) {
                        $location_address_1 .= ' '. return_str($row[$product_file['locations_address_number_column_index']]);
                    }
                    if (!empty($product_file['locations_postal_code_column_index'])) {
                        $location_postal_code = return_str($row[$product_file['locations_postal_code_column_index']]);
                    }

                    if (!empty($product_file['locations_city_column_index'])) {
                        $location_city = return_str($row[$product_file['locations_city_column_index']]);
                    }

                    $quantity = false;
                    if ($row[$productcolumn] >= 0) {
                        $quantity = intval(str_replace([',', '.'], ['', ''], $row[$productcolumn]));
                    }


                    if (empty($location_external_id)) {
                        if (strpos(strtolower($location_name), "hoofdkantoor") === false) {
                            continue;
                        } else {
                            $location_external_id = 'HK-' . $unknow_index;
                            $unknow_index++;
                        }
                    }

                    if ($quantity >= 0) {
                        if (!in_array($location_external_id, $_locations)) {
                            $_locations[] = $location_external_id;
                        }
                    }

                    $location_country = 'NL';

                    $products[$product_index]['picklist'][$loc_index] = [
                        'external_id' => $location_external_id,
                        'name' => $location_name,
                        'address_1' => $location_address_1,
                        'postal_code' => $location_postal_code,
                        'city' => $location_city,
                        'country' => $location_country,
                        'quantity' => $quantity,
                    ];

                    arr($products[$product_index]['picklist'][$loc_index], false);

                    $products[$product_index]['locations']++;
                    $products[$product_index]['quantity'] = $products[$product_index]['quantity'] + $quantity;
                    $loc_index++;
                }
            }

            // echo '<pre>';
            // print_r($products);
            // echo '</pre>';
            // die();

            $select = $db->query("SELECT `id`,`name`,`external_id` FROM `campagne_products` WHERE `campagne_id` = '" . CAMPAGNE_ID . "' && `campagne_product_file_id` = '" . PRODUCT_FILE_ID . "'");

            $delete = [];
            while ($item = $select->fetch_assoc()) {
                if (!in_array(md5($item['name']), $keep)) {
                    $delete[] = md5($item['name']);
                    $db->query("DELETE FROM `campagne_products` WHERE `id` = " . $item['id'] . "");
                }
            }

            // echo '<pre>';
            // print_r($delete);
            // echo '</pre>';
            ?>

                <div class="tab" data-tab-id="picklist-products" data-name="Producten in dit bestand (<?= count($productcolumns); ?>)" data-icon="style">
                    <div class="row">
                        <div class="col-xs-12 col-8">

                            <h4>
            <?= html_entity_decode($product_file['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </h4>
                            <hr>

                            <div class="scroll_browser">

            <?php
            if (count($productcolumns) === 0) {
                ?>
                                    <p>Er zijn nog geen producten geselecteerd uit dit bestand</p>
                                    <?php
                                } else {
                                    ?>

                                    <table border="0" cellpadding="0" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th  class="id">#</th>
                                                <th class="small">Artikelnummer</th>
                                                <th class="stretch small">Productnaam</th>
                                                <th></th>
                                                <th class="small quantity">Opmerking</th>
                                                <th class="small quantity">Nodig</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                <?php
                if (count($products) > 0) {

                    $i = 1;
// echo '<pre>';
                    arr($products);
// die();
                    foreach ($products as $product) {

                        $product_name = $db->real_escape_string($product['name']);
                        $picklist_data = json_encode($product['picklist']);

                        $external_id = '';
                        $external_ids = '';

                        if ($product['type'] === 'product' && is_array($product['external_id']) && !empty($product['external_id'][0])) {
                            $external_id = $db->real_escape_string($product['external_id'][0]);
                        } else if ($product['type'] === 'set' && is_array($product['external_id']) && count($product['external_id']) > 0) {
                            $external_ids = $db->real_escape_string(json_encode($product['external_id']));
                        }



                        $picklist_note = $db->real_escape_string($product['picklist_note']);

                        $check = $db->query("
                            SELECT
                                    *,
                                    `campagne_products`.`quantity` / `campagne_products`.`unit_quantity` as `calc_quantity`
                            FROM
                                    `campagne_products`
                            WHERE
                                    `campagne_id` = " . CAMPAGNE_ID . "
                                            AND
                                    `campagne_product_file_id` = " . PRODUCT_FILE_ID . "
                                            AND
                                    `name` = '" . $product_name . "'
                            LIMIT 0,1
                        ");

                        $campagne_product_id = false;

                        if ($check->num_rows === 0) {

                            $action = $db->query("
                                INSERT INTO
                                        `campagne_products`
                                        (
                                                `campagne_id`,
                                                `campagne_product_file_id`,
                                                `product_type`,
                                                `set_delivery`,
                                                `external_id`,
                                                `included_external_ids`,
                                                `name`,
                                                `locations`,
                                                `quantity`,
                                                `unit_quantity`,
                                                `picklist_data`,
                                                `picklist_note`,
                                                `created`
                                                )
                                VALUES
                                        (
                                                '" . $campagne['id'] . "',
                                                '" . PRODUCT_FILE_ID . "',
                                                '" . $product['type'] . "',
                                                '" . ($product['type'] === 'set' ? 'set' : 'seperate') . "',
                                                '" . $external_id . "',
                                                '" . $external_ids . "',
                                                '" . $product_name . "',
                                                '" . $product['locations'] . "',
                                                '" . $product['quantity'] . "',
                                                '" . $product['unit_quantity'] . "',
                                                '" . $picklist_data . "',
                                                '" . $picklist_note . "',
                                                NOW()
                                        )
                        ") or die($db->error);

                            $campagne_product_id = $db->insert_id;
                        } else {

                            $dbproduct = $check->fetch_assoc();

                            $campagne_product_id = $dbproduct['id'];

                            $product['quantity'] = $dbproduct['quantity'];
                            $product['unit_quantity'] = $dbproduct['unit_quantity'];
                            $product['calc_quantity'] = intval($dbproduct['calc_quantity']);

                            $action = $db->query("
                                    UPDATE
                                            `campagne_products`
                                            SET
                                            `blame_user_id` =  " . USER_ID . "
                                    WHERE
                                             `id` = " . $campagne_product_id . "
                            ");
                            //	`external_id` = '".$external_id."'
                            // `included_external_ids` = '".$external_ids."'
                            // `picklist_data` = '".$picklist_data."'
                        }

                        if ($action) {

                            $external_id = '';

                            if ($product['type'] === 'product') {

                                if (!empty($dbproduct['external_id'])) {
                                    $external_id = $dbproduct['external_id'];
                                } else {
                                    $external_id = '---';
                                }
                            } else if ($product['type'] === 'set' && !empty($product['external_id'])) {
                                if (!empty($dbproduct['external_id'])) {
                                    $external_id = $dbproduct['external_id'];
                                } else {
                                    $external_id = '---';
                                }
                            }

                            echo '<tr data-campagne-product-id="' . $campagne_product_id . '">';
                            echo '<td class="id small">' . str_pad($i, 2, "0", STR_PAD_LEFT) . '</td>';
                            echo '<td data-value="external_id" class="small">' . $external_id . '</td>';
                            echo '<td class="text-overflow" data-value="name">' . html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8') . '</td>';


                            echo '<td data-value="campagne_product_content" class="small text-overflow md">' . ($product['type'] === 'set' ? 'Set van ' . count($product['external_id']) . ' artikelen' : '');

                            if ($product['unit_quantity'] > 1) {
                                echo "Verpakt per <span class=quantity>" . $product['unit_quantity'] . "</span>";
                            }

                            echo'</td>';
                            echo '<td>';
                            if ($product['type'] === 'set') {
                                $icon = "chat";
                                if (empty($product['picklist_note'])) {
                                    $icon = "add_comment";
                                }
                                ?>
                                                        <a class="in_row add_comment" data-action="picklist_note" data-campagne-product-id="<?= $campagne_product_id; ?>"><i class="material-icons"><?= $icon; ?></i><span></span></a>
                                                            <?php
                                                        }

                                                        echo '</td>';
                                                        echo '<td class="quantity num_products" data-campagne-product-id="' . $campagne_product_id . '">' . $product['calc_quantity'] . '</td>';
                                                        echo '<td class="small">' . ($product['type'] === 'product' ? 'stuks' : 'sets') . '</td>';

                                                        echo '</tr>';
                                                    }
                                                    $i++;
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                        <?php
                                    }
                                    ?>
                            </div>
                        </div>

                        <div class="col-xs-12 col-4 right-menu">


                            <div class="row mini-form">
                                <div class="col-6" >
                                    <h4>Producten</h4>
                                </div>
                                <div class="col-6" style="padding-top: 31px;">
                                    <div style="display: inline-block;float:right;">
                                        <div style="float:left;margin-right:4px;padding-top:2px;">
                                            <input type="checkbox" id="check_all" class="check_all" data-items="selectable-item">
                                        </div>
                                        <div style="float:left;">
                                            <label for="check_all">Alles selecteren</label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <script>
                                $(function () {
                                    $('.check_all').each(function () {

                                        $(this).unbind('change').bind('change', function () {

                                            var itemClass = $(this).attr('data-items');


                                            if ($(this).is(':checked')) {
                                                $('.' + itemClass).addClass('active-item').find('input[type=checkbox]').prop('checked', true);
                                            } else {
                                                $('.' + itemClass + ':not(.checked)').removeClass('active-item').find('input[type=checkbox]:not(.checked)').prop('checked', false);
                                            }

                                        }).unbind('dblclick').bind('dblclick', function () {

                                            var itemClass = $(this).attr('data-items');

                                            $('.' + itemClass + '').removeClass('active-item').find('input[type=checkbox]').prop('checked', false);

                                            $(this).prop('checked', false);

                                        });
                                    });
                                });
                            </script>

                            <hr>




                            <form method="POST" action="#products" name="product_name_columns" style="display:block">

                                <ul class="chips">
            <?php
            foreach ($productnames as $column => $columnvalue) {
                if (empty($columnvalue) || strlen($columnvalue) < 5) {
                    continue;
                }

                $columnvalue = strtoupper($columnvalue) . '';

                $checked = 0;

                if (in_array($column, $productcolumns)) {
                    $checked = 1;
                }
                ?>
                                        <li class="ui-state-default selectable-item <?= ($checked > 0 ? 'active-item' : ''); ?> <?= ($checked > 0 ? 'checked' : ''); ?>">
                                            <input type="checkbox" name="products_value_column_indexes[]" value="<?= $column; ?>" id="column_<?= $column; ?>" <?= ($checked > 0 ? 'checked' : ''); ?> onchange="$(this).parent('li').toggleClass('active-item');" class="<?= ($checked > 0 ? 'checked' : ''); ?>"><label for="column_<?= $column; ?>" class="checkbox"><?= $column; ?> - <?= $columnvalue; ?></label>
                                        </li>
                <?php
            }
            ?>
                                </ul>

                                <div class="row mini-form" style="clear:both;">
                                    <div class="col-8">
                                        <input type="submit" name="save_product_columns" value="Wijzigingen Opslaan" class="form">
                                    </div>
                                    <div class="col-4" style="padding-top: 15px;">
                                    </div>
                                </div>

                            </form>

                        </div>

                    </div>
                </div>



            <?php
            if (count($productcolumns) > 0) {

                $select = $db->query("SELECT
                        *,
                        `campagne_products`.`quantity` / `campagne_products`.`unit_quantity` as `calc_quantity`
                        FROM
                        `campagne_products`
                        WHERE
                        `campagne_id` = '" . $campagne['id'] . "' && `campagne_product_file_id` = '" . PRODUCT_FILE_ID . "'
                ");

                $campagne_products = [];

                while ($item = $select->fetch_assoc()) {

                    $item['picklist'] = json_decode($item['picklist_data'], true);
                    unset($item['picklist_data']);
                    $item['set'] = '';

                    if ($item['product_type'] === 'set' && !empty($item['included_external_ids'])) {
                        $item['products'] = json_decode($item['included_external_ids'], true);
                        $item['set'] = '(set van <span data-value="num_products_included">' . count($item['products']) . '</span> producten)';
                    }

                    $campagne_products[] = $item;
                }

                $campagne_locations = [
                    'known' => [],
                    'unknown' => [],
                ];

                foreach ($campagne_products as $item) {

                    if ($item['locations'] > 0) {

                        foreach ($item['picklist'] as $listitem) {

                            if (!$unit_quantity) {
                                $unit_quantity = 1;
                            }

                            if ($listitem['quantity'] > 0) {

                                $listitem['quantity'] = $listitem['quantity'] / $unit_quantity;

                                if (!array_key_exists("" . $listitem['external_id'] . "", $account['locations']) && !array_key_exists("" . $listitem['external_id'] . "", $campagne_locations['unknown'])) {

                                    $campagne_locations['unknown']["" . $listitem['external_id'] . ""] = $listitem;
                                    // todo: check if import new groups from settings
                                    $insert = $db->query("INSERT INTO `groups`
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
												'" . ACCOUNT_ID . "',
												'" . $db->real_escape_string($listitem['external_id']) . "',
												'" . $db->real_escape_string($listitem['name']) . "',
												NOW(),
												'" . USER_ID . "',
												'" . USER_ID . "'
											)
										");
                                }

                                if (array_key_exists("" . $listitem['external_id'] . "", $account['locations']) && !array_key_exists("" . $listitem['external_id'] . "", $campagne_locations['known'])) {

                                    $listitem['data'] = $account['locations']["" . $listitem['external_id'] . ""];
                                    $campagne_locations['known']["" . $listitem['external_id'] . ""] = $listitem;

                                    if (!$listitem['data']['group_address_id'] && $listitem['data']['group_id']) {

                                        if (!empty($listitem['address_1']) && !empty($listitem['postal_code']) && !empty($listitem['city'])) {
                                            // todo: check if import new groups from settings

                                            $insert = $db->query("INSERT INTO `group_addresses`
												(
													`owner_id`,
													`type`,
													`address_1`,
													`postal_code`,
													`city`,
													`country`,
													`country_code`,
													`created`,
													`created_user_id`,
													`blame_user_id`
												)
												VALUES
												(
													'" . $listitem['data']['group_id'] . "',
													'delivery',
													'" . $db->real_escape_string($listitem['address_1']) . "',
													'" . $db->real_escape_string($listitem['postal_code']) . "',
													'" . $db->real_escape_string($listitem['city']) . "',
													'Nederland',
													'NL',
													NOW(),
													'" . USER_ID . "',
													'" . USER_ID . "'
												)
											");
                                        }
                                    }
                                }
                            }
                        }
                        // arr($campagne_locations['unknown']);
                        // echo '</pre>';
                        // die('------------------');
                    }
                }
            }
            ?>

                <?php
                if (isset($campagne_products) && count($campagne_products) > 0) {
                    ?>
                    <div class="tab" data-tab-id="picklist-locations" data-name="Filialen in dit bestand (<?= (count($campagne_locations['known']) + count($campagne_locations['unknown'])); ?>)" data-icon="business">

                        <div class="row">
                            <div class="col-xs-12 col-7" >
                                <h4>Onbekende filialen</h4>
                                <hr>

                                <div style="display:block;overflow:auto;height:625px;padding: 0px 5px 0px 0px;">
                                    <table border="0" cellpadding="0" cellspacing="0" class="location-list" id="unknown">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th class="stretch" colspan="2">Filiaal naam</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                <?php
                ksort($campagne_locations['unknown']);
                foreach ($campagne_locations['unknown'] as $item) {
                    $item_json = json_encode($item);
                    ?>
                                                <tr data-external_id="<?= $item['external_id']; ?>">
                                                    <td class="id small"><?= $item['external_id']; ?></td>
                                                    <td class="stretch text-overflow"><?= html_entity_decode($item['name']); ?></td>
                                                    <td>
                                                        <a class="in_row add text" data-action="add-new-location-group" data-item='<?= $item_json; ?>'><i class="material-icons">add_box</i><span>Toevoegen</span></a>
                                                    </td>
                                                </tr>
                    <?php
                }
                ?>
                                        </tbody>
                                    </table>
                                </div>
                                <script>
                                    $(function () {
                                        $('table tbody tr td select').addClass('in_row');
                                    });
                                </script>
                            </div>
                            <div class="col-xs-12 col-5 right-menu">

                                <h4>Bekende filialen</h4>
                                <hr>

                                <div style="display:block;overflow:auto;height:625px;padding: 0px 5px 0px 0px;">
                                    <table border="0" cellpadding="0" cellspacing="0" class="location-list" id="known">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th class="stretch" colspan="2">Filiaal naam</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                <?php
                $i = 0;
                foreach ($campagne_locations['known'] as $item) {

                    $location = [
                        'external_id' => $item['external_id'],
                        'data' => [
                            'group_id' => $item['data']['group_id'],
                            'group_address_id' => $item['data']['group_address_id']
                        ]
                    ];

                    $item_json = json_encode($location);
                    $i++;
                    ?>
                                                <tr data-external_id="<?= $item['external_id']; ?>">
                                                    <td class="id small"><?= $item['external_id']; ?></td>
                                                    <td class="stretch text-overflow"><?= html_entity_decode($item['name']); ?></td>
                                                    <td>
                                                        <a class="in_row edit" data-action="edit-location-group" data-item='<?= $item_json; ?>'><i class="material-icons">edit</i></a>
                                                    </td>
                                                </tr>
                    <?php
                }
                ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>


                <?php
            }
            ?>

                <div class="tab" data-tab-id="settings" data-name="Instellingen Excel"  data-icon="settings">
                    <form method="POST" name="settings" action="#settings">
                        <div class="row">
                            <div class="col-xs-12 col-4 right-menu">


                                <h4>Rijen</h4>
                                <hr>

                                <label class="select">Rij voor productnamen</label>
                                <select name="products_name_row_index">
                                    <option value="">Selecteer een rij</option>
            <?php
            for ($i = 1; $i < count($excel_data[1]); $i++) {
                echo '<option value="' . $i . '" ' . ($i == $product_file['products_name_row_index'] ? 'selected' : '') . '>' . $i . '</option>';
            }
            ?>
                                </select>

                                <label class="select">Rij voor artikelnummers</label>
                                <select name="products_id_row_index">
                                    <option value="">Selecteer een rij</option>
            <?php
            for ($i = 1; $i < count($excel_data[1]); $i++) {
                echo '<option value="' . $i . '" ' . ($i == $product_file['products_id_row_index'] ? 'selected' : '') . '>' . $i . '</option>';
            }
            ?>
                                </select>
                                
                                <label class="select">Rij voor verpakkingstype</label>
                                <select name="products_unit_quantity_row_index">
                                    <option value="">Selecteer een rij</option>
            <?php
            for ($i = 1; $i < count($excel_data[1]); $i++) {
                echo '<option value="' . $i . '" ' . ($i == $product_file['products_packaging_type_row_index'] ? 'selected' : '') . '>' . $i . '</option>';
            }
            ?>

                                <label class="select">Rij voor verpakkingseenheid</label>
                                <select name="products_unit_quantity_row_index">
                                    <option value="">Selecteer een rij</option>
            <?php
            for ($i = 1; $i < count($excel_data[1]); $i++) {
                echo '<option value="' . $i . '" ' . ($i == $product_file['products_unit_quantity_row_index'] ? 'selected' : '') . '>' . $i . '</option>';
            }
            ?>
                                </select>

                                <label class="select">Rij voor Filiaaldata</label>
                                <select name="locations_name_row_index">
                                    <option value="">Selecteer een rij</option>
            <?php
            for ($i = 1; $i < count($excel_data[1]); $i++) {
                echo '<option value="' . $i . '" ' . ($i == $product_file['locations_name_row_index'] ? 'selected' : '') . '>' . $i . '</option>';
            }
            ?>
                                </select>

                                <label class="select">Startrij filialen</label>
                                <select name="locations_start_row_index">
                                    <option value="">Selecteer een rij</option>
            <?php
            for ($i = 1; $i < count($excel_data[1]); $i++) {
                echo '<option value="' . $i . '" ' . ($i == $product_file['locations_start_row_index'] ? 'selected' : '') . '>' . $i . '</option>';
            }
            ?>
                                </select>
                                <input type="submit" name="save_product_file_settings" value="Opslaan" class="form">
                            </div>
                            <div class="col-xs-12 col-4">

                                <h4>Filiaal data</h4>
                                <hr>

                                <label class="select">Filiaal nummer</label>
                                <select name="locations_id_column_index">
                                    <option value="">Selecteer een kolom</option>
            <?php
            foreach ($locationsheadernames as $column => $columnvalue) {
                if (empty($columnvalue)) {
                    continue;
                }
                echo '<option value="' . $column . '" ' . ($column === $product_file['locations_id_column_index'] ? 'selected' : '') . '>' . $columnvalue . '</option>';
            }
            ?>
                                </select>

                                <label class="select">Filiaal naam</label>
                                <select name="locations_name_column_index">
                                    <option value="">Selecteer een kolom</option>
            <?php
            foreach ($locationsheadernames as $column => $columnvalue) {
                if (empty($columnvalue)) {
                    continue;
                }
                echo '<option value="' . $column . '" ' . ($column === $product_file['locations_name_column_index'] ? 'selected' : '') . '>' . $columnvalue . '</option>';
            }
            ?>
                                </select>

                                <label class="select">Filiaaldata importeren?</label>
                                <select name="import_location_data">
                                    <option value="1" <?= ( $product_file['import_location_data'] == 1 ? 'selected' : ''); ?>>Ja, importerten uit excel</option>
                                    <option value="0" <?= ( $product_file['import_location_data'] == 0 ? 'selected' : ''); ?>>Nee, onbekende filialen overslaan</option>
                                </select>


                            </div>
                            <div class="col-xs-12 col-4">
                                &nbsp;
                            </div>

                        </div>
                    </form>
                </div>
            </div>
            <?php
        }
    }
}
?>
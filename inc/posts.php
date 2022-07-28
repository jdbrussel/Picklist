<?php

if(isset($_GET['refresh']) && !empty($_GET['refresh'])) {
	echo"<script> window.location.hash = '#".$_GET['refresh']."'; </script>";
}
	$posterrors = [];

	if(isset($_POST['update_campagne']) && $_POST['update_campagne'] === 'Opslaan' && is_numeric(CAMPAGNE_ID)) {

		$campagne_q = $db->query("SELECT * FROM `campagnes` WHERE `id` = ".CAMPAGNE_ID."") or die($db->error);
		
		if($campagne_q->num_rows === 1) {
			
			$campagne =  $campagne_q->fetch_assoc();

				if(isset($_POST['container_id']) && is_numeric($_POST['container_id'])) {
					
                                    $new_container_id = $_POST['container_id'];
                                        
					if($_POST['container_id'] !== $campagne['container_id']) {

						$container_boxes_q = $db->query("SELECT * FROM `dcs_containers_boxes` WHERE `container_id` = ".$_POST['container_id'] ."") or die($db->error);
						
						if($container_boxes_q->num_rows > 0) {
	 						
	 						$db->query("DELETE  FROM `campagne_container_boxes` WHERE `campagne_id` = ".CAMPAGNE_ID."") or die($db->error);

							$posterrors[] = $container_boxes_q->num_rows .' boxes gevonden!';

							$boxnr = 1;

	 						while($item = $container_boxes_q->fetch_assoc()) {
	 							//$boxname = $item['name']." (1)";
	 							$boxname = $item['name'];
	 							$db->query("INSERT INTO 
	 									`campagne_container_boxes` 
	 										(`name` , `initial`,`campagne_id`, `dc_container_box_id`, `created`, `created_user_id`, `blame_user_id`) 
	 									VALUES 
	 										('". $db->real_escape_string($boxname) ."',1,".CAMPAGNE_ID.", ".$item['id'].", NOW(), ".USER_ID.",".USER_ID.") 
	 									") or die($db->error);

	 							$boxnr++;
	 						}

	 						

						}
						else {
							$posterrors[] = 'Container \''.$_POST['container_id'] .'\' niet gevonden!';
						}
					}

				}
				$container_query = '';
				if($new_container_id > 0) {
					$container_query = " `container_id` = '".$_POST['container_id']."', ";
				}
     
        
                                $update_query = "
					UPDATE 
						`campagnes` 
					SET 
						`name` = '".$db->real_escape_string($_POST['campagne_name'])."',
						`erp_id` = '".$db->real_escape_string($_POST['erp_id'])."',
                                                `palletlist_address` = '".$db->real_escape_string($_POST['palletlist_address'])."',
                                                `palletlist_num_items` = ".(integer) $_POST['palletlist_num_items'].",
                                                `archive` = '".$_POST['archive']."',
                                                `type` = '".$_POST['type']."',
						".$container_query."
						`last_update` = NOW(),
						`blame_user_id` = ".USER_ID."
					WHERE 
						`id` = ". $campagne['id'] ."
						LIMIT 1
					";
    
				$action = $db->query($update_query) or die($db->error);

				if($action) {
					header('Location: ' . CAMPAGNE_URL . '&updated#settings');
				}

			

		}
		
		
	}

        
if(isset($_GET['delete_picklist_id']) && is_numeric($_GET['delete_picklist_id'])) {
	$action = $db->query("DELETE FROM `campagne_picklists` WHERE `campagne_id` = ".CAMPAGNE_ID."  AND `id` = ".$_GET['delete_picklist_id']."") or die($db->error);
	if($action) {
		header('Location: ' . CAMPAGNE_URL . '&deleted_picklist#picklists');
	}
}
if(isset($_GET['delete_picklist_truck']) && is_numeric($_GET['delete_picklist_truck'])) {
	$action = $db->query("DELETE FROM `campagne_picklists` WHERE `campagne_id` = ".CAMPAGNE_ID."  AND `campagne_dc_truck_id` = ".$_GET['delete_picklist_truck']."") or die($db->error);
	if($action) {
		header('Location: ' . CAMPAGNE_URL . '&deleted_picklist#picklists');
	}
}
if(isset($_GET['delete_picklist_lc']) && is_numeric($_GET['delete_picklist_lc'])) {
	$action = $db->query("DELETE FROM `campagne_picklists` WHERE `campagne_id` = ".CAMPAGNE_ID."  AND `campagne_logistic_center_id` = ".$_GET['delete_picklist_lc']."") or die($db->error);
	if($action) {
		header('Location: ' . CAMPAGNE_URL . '&deleted_picklist#picklists');
	}
}
if(!empty($_GET['delete_campagne_box']) && is_numeric($_GET['delete_campagne_box'])) {
    
		$_POST['campagne_box_id'] = $_GET['delete_campagne_box'];
		$_POST['delete_campagne_box'] = 'true';

		if(!empty($_POST['delete_campagne_box'])) {

			$action = $db->query("DELETE FROM `campagne_container_boxes` WHERE `id` = ".$_POST['campagne_box_id']." LIMIT 1") or die($db->error);

			if($action) {
				header('Location: ' . CAMPAGNE_URL . '&deletedbox#boxes');
			}
		}
}

	if(!empty($_POST['container_box_id']) && is_numeric($_POST['container_box_id'])) {
		
		$container_box_id = $_POST['container_box_id'];

		$container_box_q = $db->query("SELECT * FROM `dcs_containers_boxes` WHERE `id` = '".$container_box_id."'")or die($db->error);
		
		if($container_box_q->num_rows === 1) {

			$box = $container_box_q->fetch_assoc();

			$campagne_box_q = $db->query("SELECT `id`,`name` FROM `campagne_container_boxes` WHERE `dc_container_box_id` = '".$container_box_id."' AND `campagne_id` = '".CAMPAGNE_ID."'")or die($db->error);

			$boxname = $box['name'];

			if($campagne_box_q->num_rows > 0) {

				$boxnames = [];
				while($_box = $campagne_box_q->fetch_assoc()) {
		 			$boxnames[] = $_box['name'];
		 		}
		 		
		 		$postfixes = ['aap','noot','mies','wim','zus','jet'];

		 		$box_name = false;
				for($postfix_id = 0; $box_name === false; $postfix_id++) {
					$n =  $box['name'] . " (".$postfixes[$postfix_id].")";
					if(!in_array($n, $boxnames)) {
						$box_name = true;
						$boxname .= " (".$postfixes[$postfix_id].")"; 
					}
				}
			 	
			}

			if(!empty($_POST['add_campagne_box'])) {

					$action = $db->query("
						INSERT INTO
							`campagne_container_boxes` 
								(`campagne_id`, `dc_container_box_id`, `name`, `initial`, `created`, `created_user_id`, `last_update`, `blame_user_id`) 
							VALUES 
								('".CAMPAGNE_ID."','".$container_box_id."','".$boxname . "', 0, NOW(),'".USER_ID."', NOW() ,'".USER_ID."')
					") or die($db->error);

					if($action) {
						header('Location: ' . CAMPAGNE_URL . '&newbox#boxes');
					}
			}
		}

	}

	if(!empty($_POST['save_product_columns']) && is_numeric(PRODUCT_FILE_ID)) {

		$products_value_column_indexes  = $db->real_escape_string( implode(",",$_POST['products_value_column_indexes']) );
		
		$action = $db->query("
			UPDATE 
				`campagne_product_files` 
			SET 
				`products_value_column_indexes` = '".$products_value_column_indexes."',
				`last_update` = NOW(),
				`blame_user_id` = ".USER_ID."
			WHERE 
				`campagne_id` = ". CAMPAGNE_ID ." && 
				`id` = ". PRODUCT_FILE_ID ." LIMIT 1
			");

		if($action) {
			header('Location: ' . PRODUCT_FILE_URL . '&updated');
		}
		
	}


	if(array_key_exists('save_variant_article_number', $_POST) && count($_POST['save_variant_article_number']) > 0 && !empty($_POST['product_columns'])) {
			
		$products_articlenumber_column_indexes = $_POST['save_variant_article_number'];
		
		foreach($_POST['save_variant_article_number'] as $product_column => $external_id_column) {
			$query = "DELETE FROM `campagne_products` WHERE `campagne_product_file_column_index` = '". $product_column."' && `campagne_id` = ". CAMPAGNE_ID ." && `campagne_product_file_id` = ". PRODUCT_FILE_ID ." LIMIT 1";
			$db->query($query) or die($db->error);
		}

		$product_columns = explode(',', $_POST['product_columns']);
		$ret = [];

		foreach($products_articlenumber_column_indexes as $product_column => $article_number_column) {
			
			if(in_array("". $product_column . "", $product_columns)) {
				if(!array_key_exists("".$product_column."", $ret)) {
					$ret["".$product_column.""] = $article_number_column;
				}
			}
		}
		
		$columns = json_encode($ret);

		$action = $db->query("
			UPDATE 
				`campagne_product_files` 
			SET 
				`products_articlenumber_column_indexes` = '". $db->real_escape_string($columns) ."',
				`last_update` = NOW(),
				`blame_user_id` = ".USER_ID."
			WHERE 
				`campagne_id` = ". CAMPAGNE_ID ." && 
				`id` = ". PRODUCT_FILE_ID ." LIMIT 1
			");

		if($action) {
			header('Location: ' . PRODUCT_FILE_URL . '&updated');
		}

	}
		// arr($_POST, false);

	if(isset($_POST['save_product_file_settings']) && is_numeric(PRODUCT_FILE_ID)) {

		// echo '<pre>';
		// print_r($_POST);
		// die();

		$action = $db->query("
			UPDATE 
				`campagne_product_files` 
			SET 
				`products_id_row_index` 			= '".$_POST['products_id_row_index']."',
				`products_name_row_index` 			= '".$_POST['products_name_row_index']."',
                                `products_version_row_index` 			= '".$_POST['products_version_row_index']."',
                                `products_version_multiplier_row_index` 	= '".$_POST['products_version_multiplier_row_index']."',
				`products_unit_quantity_row_index`              = '".$_POST['products_unit_quantity_row_index']."',
				`locations_name_row_index` 			= '".$_POST['locations_name_row_index']."',
				`locations_start_row_index`                     = '".$_POST['locations_start_row_index']."',
				`locations_id_column_index`                     = '".$_POST['locations_id_column_index']."',
				`locations_name_column_index`                   = '".$_POST['locations_name_column_index']."',
				`import_location_data` 				= '".$_POST['import_location_data']."',
				`blame_user_id` 					= '".USER_ID."', 
				`last_update` = NOW()
			WHERE 
				`campagne_id` = ". CAMPAGNE_ID ." 
				&& 
				`id` = ". PRODUCT_FILE_ID ." 
			LIMIT 1
			");
		if($action) {
			header('Location: ' . PRODUCT_FILE_URL);
		}
	}

	if(count($posterrors) > 0) {
		echo '<pre>';
		print_r($posterrors);
		die();
	}

?>
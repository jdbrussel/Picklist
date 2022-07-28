<?php
require_once('../inc/globals.php');

$ret = [];

$ret['error'] = [];

if(!empty($_POST['table']) && count($_POST['columns']) > 0 && !empty($_POST['id'])) {
	
	$table = $_POST['table'];
	$columns = $_POST['columns'];
	$postdata = $_POST['data'];
	$delivery_address = [];
	$query = '';


if($table == 'weekbox_file_products' && in_array('included_products_string', $columns)) {
	
	$included_products_str_index = array_search('included_products_string', $columns);

	$included_products_str = $postdata[$included_products_str_index];
	$included_products = return_included_products($included_products_str);

	$versions_index = array_search('versions', $columns);

	if(count($included_products) > 0) {
		
		$columns[] = 'included_products';
		$postdata[] = json_encode($included_products);

		$postdata[$versions_index] = count($included_products);

	}
	else if(!empty($included_products_str)) {
		$postdata[$versions_index] = 1;
	}

	// arr($columns, false);
	// arr($postdata);
}

	if(!array_key_exists('owner_id', $_POST) || (array_key_exists('owner_id', $_POST) && !is_numeric($_POST['owner_id'])) )  {
		$ret['error'][] = 'No correct owner_id given';
	} 
	else if(!array_key_exists('user_id', $_POST) || (array_key_exists('user_id', $_POST) && !is_numeric($_POST['user_id'])) )  {
		$ret['error'][] = 'No correct user_id given';
	}
	else if(!array_key_exists('id', $_POST) || (array_key_exists('id', $_POST) && !is_numeric($_POST['id'])) )  {
		
		$cols = [];
		$vals = [];

		foreach($columns as $column_id => $column) {

			if($table === 'groups' && $column === 'delivery_address' && is_array($postdata['' . $column_id . ''])) {
				$delivery_address = $postdata['' . $column_id . ''];
				unset($postdata['' . $column_id . '']);
			}
			elseif(!empty($column) && !empty($postdata['' . $column_id . ''])) {
				$cols[] = $column;
				$vals[] = $db->real_escape_string($postdata['' . $column_id . '']);
			}
		}
		
		$columns = $cols;
		$postdata = $vals;



		$keys = '`' . implode('`,`' , $columns) . '`,`owner_id`,`created`,`created_user_id`';
		$values =  '\'' . implode('\',\'' , $postdata) . '\', \''.$_POST['owner_id'].'\', NOW(), \''.$_POST['user_id'].'\'';

		$query = "INSERT INTO `".$table."` (".$keys.") VALUES (".$values.")";
		
	}
	else {

		$id = $_POST['id'];

		$check = $db->query("
			SELECT 
				".implode(',', $columns)."		
			FROM 
				`". $table . "` 
			WHERE 
				`id` = '". $id ."'
			LIMIT 0,1
		");

		if($db->error) {
			$ret['error'][] = $db->error;
		} else if($check->num_rows !== 1) {
			$ret['error'][] = 'No record found in table '.$table.' for id ' . $id;
		} else if(count($columns) === count($postdata)) {
				
			$update = '';

			foreach($columns as $column_index => $column) {
				
				if(array_key_exists($column_index, $postdata)) {
					$columndata = $postdata[''. $column_index . ''];
				}

				$data = $columndata;

				if($table === "groups" && $column === 'delivery_address') {

				}
				else {
					if(gettype($data) === 'array') {
						$data = json_encode($columndata);
					}
					if($column === 'external_id') {
						// $data = return_external_id($data);
					}
					if($data === '' || empty($data)) {
						$data = 'NULL';
					}
					$update .= "`".$column."` = ".($data !== "NULL" ? "'".$db->real_escape_string($data)."'" : "NULL").",";
				}
				

			}

			$query = "UPDATE 
					`". $table . "` 
				SET 
					".$update."
					`last_update` = NOW(),
					`blame_user_id` = '".$_POST['user_id']."' 
				WHERE 
					`id` = '". $id ."'
				LIMIT 1";
			$ret['query'] = $query;
		}
	}

	
	if(!empty($query) && count($ret['error']) === 0) {
		
		$save = $db->query($query);
		
		if($db->insert_id > 0) { 
				
				$_POST['insert_id'] = $db->insert_id;

				if(count($delivery_address) > 0) {
					
					$query_address = "INSERT INTO `group_addresses` (
						`owner_id`,
						`type`,
						`address_1`,
						`address_2`,
						`postal_code`,
						`city`,
						`country`,
						`country_code`
					) VALUES (
						'".$_POST['insert_id']."',
						'delivery', 
						'".$db->real_escape_string($delivery_address['address_1'])."',
						'".$db->real_escape_string($delivery_address['address_2'])."',
						'".$db->real_escape_string($delivery_address['postal_code'])."',
						'".$db->real_escape_string($delivery_address['city'])."',
						'".$db->real_escape_string($delivery_address['country'])."',
						'".$db->real_escape_string($delivery_address['country_code'])."'
					)";

					$_POST['group_address_id'] = false;

					if($db->query($query_address)) {
						$_POST['group_address_id'] = $db->insert_id;
					}
					
				} 

				$_POST['inserted_item'] = $db->query("
					SELECT 
						*		
					FROM 
						`". $table . "` 
					WHERE 
						`id` = '". $_POST['insert_id'] ."'
					LIMIT 0,1
				")->fetch_assoc();

		}
		if($db->error) {
			$ret['error'][] = $db->error;
		}
	}
	else {
		$ret['error'][] = 'No query';
		$ret['error'][] = $query;
	}
	

	if(count($ret['error']) === 0) {
		$select = $db->query("
			SELECT 
				*	
			FROM 
				`". $table . "` 
			WHERE 
				`id` = '". $id ."'
			LIMIT 0,1
		");
		if($select->num_rows === 1) {
			$ret['data'] = $select->fetch_assoc();
		}
		echo json_encode($ret);		
	}
	else {

		echo json_encode($ret);	
	}
}

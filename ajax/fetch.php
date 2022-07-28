<?php
$_GET['account_id'] = $_POST['account_id'];
require_once('../inc/globals.php');

$ret = [];
$ret['error'] = [];

if(empty($_POST['client_id'])) {
	$ret['error'][] = 'No correct client_id given';
}
else if(empty($_POST['account_id'])) {
	$ret['error'][] = 'No correct account_id given';
}
else if(empty($_POST['user_id'])) {
	$ret['error'][] = 'No correct user_id given';
}
else if(!is_numeric($_POST['id']) || empty($_POST['table'])) {
		$ret['error'][] = 'No correct id/table given';
}
else if(!empty($_POST['table']) && !empty($_POST['id'])) {

	$owner_id = $_POST['client_id'];
	$account_id = $_POST['account_id'];
	$user_id = $_POST['user_id'];

	$table = $_POST['table'];
	$id = $_POST['id'];

	$WHERE = "WHERE `". $table . "`.`id` = '". $id ."'";

	if(!empty($_POST['where'])) {
		foreach($_POST['where'] as $column => $value) {
			$WHERE .= " &&  `". $table . "`.`".$column."` = '".$value."'";
		}
	}

	$select_crud_dates = "DATE_FORMAT(`". $table . "`.`created`, '%d-%m-%Y %H:%i uur') as `created_datetime`, DATE_FORMAT(`". $table . "`.`last_update`, '%d-%m-%Y %H:%i uur') as `last_update_datetime`,";
	
	$blame_join = "LEFT JOIN `group_users` as `blame_user_table` ON `blame_user_table`.`id` = `". $table . "`.`blame_user_id`";
	$select_blame_user = "concat_ws(' ',`blame_user_table`.`first_name`, `blame_user_table`.`last_name`) as `blame_user`, ";

	$created_join = "LEFT JOIN `group_users` as `created_user_table` ON `created_user_table`.`id` = `". $table . "`.`created_user_id`";
	$select_created_user = "concat_ws(' ',`created_user_table`.`first_name`, `created_user_table`.`last_name`) as `created_user`, ";

	$group_delivery_address_join = '';
	$select_group_delivery_address = '';

	if($table === "groups") {
		$group_delivery_address_join = "LEFT JOIN `group_addresses` as `delivery_address` ON `delivery_address`.`owner_id` = `". $table . "`.`id` AND `delivery_address`.`type` = 'delivery'";
		$select_group_delivery_address = " `delivery_address`.`id` as  `group_address_id`, `delivery_address`.`address_1` as `delivery_address_1`, `delivery_address`.`address_2` as `delivery_address_2`, `delivery_address`.`postal_code` as `delivery_postal_code`, `delivery_address`.`city`  as `delivery_city`, `delivery_address`.`country`  as `delivery_country`, ";
	}

	$query = "SELECT " . $select_crud_dates . $select_created_user . $select_blame_user . $select_group_delivery_address . " `".$table."`.*   FROM `". $table . "` " . $group_delivery_address_join . " " . $blame_join . " " . $created_join . " ". $WHERE ."";

	$check = $db->query($query);
	
	if($db->error) {
		$ret['error'][] = $db->error;
	} else if($check->num_rows !== 1) {
		$ret['error'][] = 'No record found in table '.$table.' for id ' . $id;
	} else {
		$ret['query'] = $query;
		$item =  $check->fetch_assoc();
		$ret['item'] = $item;
	}

}


if(count($ret['error']) > 0) {
	echo json_encode($ret['error']);		
}
else {
	echo json_encode($ret);	
}


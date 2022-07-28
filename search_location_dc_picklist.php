<?php
$_GET['account_id'] = $_POST['account_id'];
require_once('../inc/globals.php');

$ret = [
	"locations" => false
];

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
else if(empty($_POST['campagne_id'])) {
	$ret['error'][] = 'No correct campagne_id given';
}
else if(!empty($_POST['needle'])) {

	$needle = strtoupper($_POST['needle']);

	$owner_id 		= $_POST['client_id'];
	$campagne_id 	= $_POST['campagne_id'];
	$account_id 	= $_POST['account_id'];
	$user_id 		= $_POST['user_id'];

	$locations = $db->query("
		SELECT  
			`groups`.*,
		 	`group_addresses`.`id` as `group_address_id` 
		FROM `groups` 
		LEFT JOIN `group_addresses` ON `group_addresses`.`owner_id` = `groups`.`id` 
		WHERE ( `groups`.`name` LIKE '%" . $needle . "%' OR `groups`.`external_id` LIKE '" . $needle . "%') AND `groups`.`owner_id` = ".$_POST['account_id']." AND `groups`.`type` = 'location' ") or die($db->error);

	if($locations->num_rows > 0) {

		$ret['locations'] = [];
		
		while($location = $locations->fetch_assoc()) {

			$location_data = [
				"external_id" => $location['external_id'],
				"name" => $location['name'],
				"email" => $location['email'],
				"phone" => $location['phone'],
				"group_id" => $location['id'],
				"group_address_id" =>  $location['group_address_id'],
				"dc_id" => false,
				"campagne_dc_id" => false,
				"truck_id" => false,
				"picklist_id" => false
			];

			$truck_containers = $db->query("SELECT * FROM `campagne_dc_trucks_containers` WHERE `external_id` = '".$location['external_id']."' AND `campagne_id` = ".$campagne_id." ") or die( $db->error );

			if($truck_containers->num_rows === 1) {

				$truck_container = $truck_containers->fetch_assoc();

				$location_data['dc_id'] = $truck_container['dc_id'];
				$location_data['truck_id'] = $truck_container['dc_truck_id'];

			}

			$lc_containers = $db->query("SELECT * FROM `campagne_logistic_centers` WHERE `location_data` LIKE '%".$location['external_id']."%' AND `campagne_id` = ".$campagne_id." LIMIT 0,1") or die( $db->error );

			if($lc_containers->num_rows === 1) {
				$lc_container = $lc_containers->fetch_assoc();
				$location_data['lc_id'] = $lc_container['dcs_logistic_center_id'];
			}


			$ret['locations'][] = $location_data;
		}

	}

}


if(count($ret['error']) > 0) {
	echo json_encode($ret['error']);		
}
else {
	echo json_encode($ret);	
}


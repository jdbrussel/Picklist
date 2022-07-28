<?php
require_once('../inc/globals.php');

$ret = [];

$ret['error'] = [];

if(!array_key_exists('owner_id', $_POST) || (array_key_exists('owner_id', $_POST) && !is_numeric($_POST['owner_id'])) )  {
	$ret['error'][] = 'No correct owner_id given';
} 
else if(!array_key_exists('user_id', $_POST) || (array_key_exists('user_id', $_POST) && !is_numeric($_POST['user_id'])) )  {
	$ret['error'][] = 'No correct user_id given';
}
else if(!empty($_POST['table']) && !empty($_POST['id'])) {

	$table = $_POST['table'];
	$table_id = $_POST['id'];
	$user_id = $_POST['user_id'];

	$select = $db->query("SELECT * FROM `".$table."` WHERE `id` = ".$table_id." LIMIT 1");
	if($select->num_rows === 1) {

		// $ret['data'] = $select->fetch_assoc();
		$data =  json_encode($select->fetch_assoc());
		$delete = $db->query("DELETE FROM `".$table."` WHERE `id` = '".$table_id."'");

		if($db->error) {
			$ret['error'][] = $db->error;
		}
		else {

			$log_blame_user = $db->query("INSERT INTO `deletes` (`table`,`table_id`,`data`,`blame_user_id`) VALUES ('".$db->real_escape_string($table)."','".$db->real_escape_string($table_id)."','". $db->real_escape_string($data) ."' , '".$db->real_escape_string($user_id)."')");

			if($db->error) {
				$ret['error'][] = $db->error;
			}

		}

	}
	else {
		$ret['error'][] = 'No record found in  ' . $table . ' width id ' . $table_id;
	}

	if($db->error) {
		$ret['error'][] = $db->error;
	}
	else {
		$ret['deleted_id'] = $_POST['id'];
	}

}

echo json_encode($ret);	
	
?>
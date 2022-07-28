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
else if(!empty($_POST['table']) && count($_POST['columns']) > 0 && (count($_POST['columns']) === count($_POST['data']))) {

	$table = $_POST['table'];
	$columns = $_POST['columns'];
	$postdata = $_POST['data'];
	$query = '';

	$cols = [];
	$vals = [];

	foreach($columns as $column_id => $column) {
		$cols[] = $column;
		$vals[] = $db->real_escape_string($postdata[$column_id]);
	}
	
	$columns = $cols;
	$postdata = $vals;

	$keys = '`' . implode('`,`' , $columns) . '`,`created`,`created_user_id`,`blame_user_id`';
	$values =  '\'' . implode('\',\'' , $postdata) . '\', NOW(), \''.$_POST['user_id'].'\',\''.$_POST['user_id'].'\'';

	$query = "INSERT INTO `".$table."` (".$keys.") VALUES (".$values.")";

	$db->query($query);

	if($db->error) {
		$ret['error'][] = $db->error;
	}
	else {
		$ret['insert_id'] = $db->insert_id;
	}

}

	echo json_encode($ret);	
	



?>
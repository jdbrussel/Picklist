<?php
require_once('../inc/globals.php');

$ret = [];
$ret['POST'] = $_POST;
$ret['errors'] = [];

if(empty($_POST['user_id'])) {
	$ret['errors'][] = 'Geen user_id';
}
else if(empty($_POST['table']) || count($_POST['data']) < 0) {
	$ret['errors'][] = 'Verkeerde input';
}

$table = $_POST['table'];

$ret['update_querys'] = [];

foreach($_POST['data'] as $row) {

	if( gettype($row) === 'array') {

		$ret['update'] = [];
		$ret['where'] = [];

		foreach($row as $updatekey => $updatevalue) {

			if( gettype($updatevalue) === 'string') {

				if($updatevalue === 'NULL' || is_numeric($updatevalue)) {
						$updatevalue = $updatevalue;
				}
				else {
					$updatevalue = '\'' . $updatevalue . '\'';
				}

				$ret['update'][] = '`' . $updatekey . '` = '. $updatevalue;

			}
			else if( $updatekey === 'WHERE' && gettype($updatevalue) === 'array') {
				foreach($updatevalue as $wherekey => $wherevalue) {
					if(is_numeric($wherevalue)) {
							$wherevalue = $wherevalue;
					}
					else {
						$wherevalue = '\'' . $wherevalue . '\'';
					}
					$ret['where'][] = '`' . $wherekey . '` =  '. $wherevalue; 
				} 
			}
		}

		$ret['update_querys'][] = 'UPDATE `' . $_POST['table'] . '` SET ' . implode(', ' , $ret['update']) . (count($ret['where']) > 0 ? ' WHERE ' . implode(' AND ' , $ret['where']) : '') . ' LIMIT 1';

		foreach($ret['update_querys'] as $query) {

			//$update = $db->query('' . $query . '');
			
			if($db->error) {
				$ret['error'][] = $db->error;
			}

		}
	}
		



}

// $i=0;
// $ret['update_query'] = [];
// foreach($ret['query_keys'] as $key => $item) {
	
// 	$value = $ret['query_values'][$key];
	
// 	if($value === 'auto_increment') {
// 		$value = $key;
// 	}

// 	$ret['update_query'][] .= '`'.$ret['query_keys'][$key].'` = "'.$value.'"';
// 	$i++;
// }

// // $query = mysqli_prepare("UPDATE `".$table."` SET approved = ?");

echo json_encode($ret);	
?>
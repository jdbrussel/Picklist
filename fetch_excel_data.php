<?php
$_GET['account_id'] = $_POST['account_id'];
require_once('../inc/globals.php');

$ret = [];
$ret['error'] = [];

if(empty($_POST['file'])) {
	$ret['error'][] = "No file given";
}
$file = $_POST['filename'];

if(empty($ret['error'])) {
	try {
		$inputFileType = PHPExcel_IOFactory::identify($file);
		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
		$objPHPExcel = $objReader->load($file);
		$ret['data'] = array(1, $objPHPExcel->getActiveSheet()->toArray(null, false, true, true));
	} catch (\Exception $exc) {
		var_dump($exc);
	}
}

if(count($ret['error']) > 0) {
	echo json_encode($ret['error']);		
}
else {
	echo json_encode($ret);	
}


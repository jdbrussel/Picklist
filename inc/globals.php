<?php
$whitelist = [
    '172.31.255.1'
];
$bekrange = [
    '89.20.87.132',
    '89.20.87.133',
    '89.20.87.134',
    '89.20.87.135',
    '89.20.87.136',
    '89.20.87.137',
    '89.20.87.138',
    '89.20.87.139',
    '89.20.87.140',
    '89.20.87.141',
    '89.20.87.142',
    '89.20.89.228',
    '89.20.89.229',
    '89.20.89.230',
    '89.20.89.231',
    '89.20.89.232',
    '89.20.89.233',
    '89.20.89.234',
    '89.20.89.235',
    '89.20.89.236',
    '89.20.89.237',
    '89.20.89.238'
];
if(!in_array(''.$_SERVER['REMOTE_ADDR'].'', $bekrange) && !in_array(''.$_SERVER['REMOTE_ADDR'].'', $whitelist)) {
    $subject = 'Add to whitelist';
    $body = 'Please add me to the whitelist of your application.%0D%0A%0D%0ARequest date: '.date("d-m-Y H:i").'%0D%0AApplication: '.$_SERVER['REMOTE_ADDR'].$_SERVER['SCRIPT_NAME'].'%0D%0A%0D%0AYour IP address: '.$_SERVER['REMOTE_ADDR'].'%0D%0AYour name:%0D%0AYour function:%0D%0AYour Phonenumber:%0D%0A';
    die('<center><br/><h3>Deze applicatie is afgeschermd.</h3><p>Vraag de <a href="mailto:jasper.brussel@bek.nl?SUBJECT='. trim($subject). '&BODY='.trim($body).'">beheerder</a> of hij jouw ip-adres ('.$_SERVER['REMOTE_ADDR'].') in de whitelist wil zetten om toegang te kunnen krijgen tot deze applicatie.</p>');
}

define('OWNER_ID', 1);

ob_start();
session_start();
error_reporting(E_ALL & ~E_NOTICE);

ini_set('auto_detect_line_endings', true);
set_time_limit('3600000000');

$today  = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
require_once '/var/www/picklists/inc/database.php';

require_once 'functions.php';

ini_set("display_errors", 1);
ini_set('memory_limit', '2048M');


define('API_BASE_URL', 'https://vega.bek.nl/');
define('API_KEY', 'd4b9d56b-f5c2-530e-9d0f-28014534ff41');

define('DIR_PICKLIST', '/var/www/picklists/');
define('DIR_LIBS', DIR_PICKLIST . '/libs/');
define('DIR_PICKLIST_EXCEL_FILES', DIR_PICKLIST . 'excel/picklists/');
define('DIR_WEEKBOX_EXCEL_FILES', DIR_PICKLIST . 'excel/picklists/');
define('DIR_DISTRIBUTION_EXCEL_FILES', DIR_PICKLIST . 'excel/distributionlists/');
define('DIR_LOCATION_DATA_EXCEL_FILES', DIR_PICKLIST . 'excel/location_data/');
define('DIR_MAILORDER_EXCEL_FILES', DIR_PICKLIST . 'excel/mailorders/');

define('DIR_CONVERT_EXCEL_FILES', DIR_PICKLIST . 'inc/convert_data/data_in/');


$excepted_product_external_ids = ["NVT", "ONBEKEND", "NIET BEKEND"];

define('DATE_PATTERNS', '(^([0-9]{1,2})(\/|-)([0-9]{1,2})(\/|-)([0-9]{4})$)');
// preg_match_all(DATE_PATTERNS, '11/12/2019', $matches, PREG_SET_ORDER, 0);
// arr($matches, false);

define('PRODUCT_EXTERNAL_SET_ID_PATTERN', '((PS)[0-9]{5,10})');
define('SKIP_PRODUCTNAMES', ['niet in gebruik', 'eindtotaal']);

require_once DIR_LIBS . 'PHPExcel-1.8/Classes/PHPExcel.php';

require_once('/var/www/picklists/login/index.php');

if(!not_empty('account_id', $_GET)) {
    if(!empty($user['accounts'])) {
            $_GET['account_id'] = $user['accounts'][0];
    }
    else {
            $_GET['account_id'] = 1;
    }
}
if(not_empty('account_id', $_GET)) {
	define('ACCOUNT_ID', $_GET['account_id']);
} else {
	define('ACCOUNT_ID', false);
}

if(not_empty('campagne_id', $_GET)) {
	define('CAMPAGNE_ID', $_GET['campagne_id']);
} else {
	define('CAMPAGNE_ID', false);
}

if (!empty(ACCOUNT_ID) && !empty(CAMPAGNE_ID)) {
	define('CAMPAGNE_URL', '?account_id=' . ACCOUNT_ID . '&campagne_id=' . CAMPAGNE_ID);
} else {
	define('CAMPAGNE_URL', false);
}

if(not_empty('week', $_GET)) {
	define("WEEK", $_GET['week']);
} else {
	define('WEEK', false);
}
if (!empty(ACCOUNT_ID) && !empty(WEEK)) {
	define('WEEKBOX_URL', '?account_id=' . ACCOUNT_ID . '&page=weekbox&week=' . WEEK);
} else {
	define('WEEKBOX_URL', false);
}

if(not_empty('product_file_id', $_GET)) {
	define('PRODUCT_FILE_ID', $_GET['product_file_id']);
	define('PRODUCT_FILE_URL', CAMPAGNE_URL . '&product_file_id=' . PRODUCT_FILE_ID);

} else {
	define('PRODUCT_FILE_ID', false);
	define('PRODUCT_FILE_URL', false);
}

if (!is_dir(DIR_PICKLIST_EXCEL_FILES)) {
	if (mkdir(DIR_PICKLIST_EXCEL_FILES, 0755)) {
		echo 'dir created';
	} else {
		echo 'dir not-created';
	}
}

if (is_numeric(CAMPAGNE_ID) && is_numeric(USER_ID)) {
	require_once DIR_PICKLIST . 'inc/posts.php';
}

if (OWNER_ID) {

	$my_accounts = "";
	if(count($user['accounts']) > 0) {
		$my_accounts = "AND `id` IN (". implode( ',' , $user['accounts'] ) .")";
	}

	$account = [];
	$q_accounts = $db->query("SELECT * FROM `groups` WHERE `type` = 'account' ".$my_accounts." ");

	if (ACCOUNT_ID) {

		$account = account_fetch(OWNER_ID, ACCOUNT_ID);

		if(!empty($account) && not_empty('patterns', $account)) {
			define('LOCATION_ID_PATTERN', $account['patterns']['pattern_location_id']);
			define('LOCATION_EXTERNAL_ID_PATTERN', $account['patterns']['pattern_location_id']);
			define('PRODUCT_EXTERNAL_ID_PATTERN', $account['patterns']['pattern_external_id_single']);
			define('PRODUCTS_EXTERNAL_IDS_PATTERN', $account['patterns']['pattern_external_ids_multi']);
			define('WEEKBOX_EXTERNAL_ID_PATTERN', $account['patterns']['pattern_weekbox_fixed_item']);
		}

		// $q_campagnes = $db->query("SELECT * FROM `campagnes` WHERE `owner_id` = " . $_GET['account_id'] . " ORDER BY `created` DESC");
		
		
		$account['mailorders'] = [];

		$q_b2c_mailorders = $db->query("SELECT * FROM `mailorders` 
				WHERE 
					`owner_id` = " . $_GET['account_id'] . " 
				AND 
					`type` = 'b2c'  
				ORDER BY `name` ASC
		");
		
		if($q_b2c_mailorders->num_rows > 0) {
			
			$account['mailorders']['b2c'] = [];

			while($mailorder = $q_b2c_mailorders->fetch_assoc()) {
				
				$q_mailorder_vardata = $db->query("
					SELECT 
							* 
					FROM 
							`mailorder_vardata` 
					WHERE 
							`mailorder_id` = " . $mailorder['id'] . " 
					ORDER BY 
								`name` ASC
				");

				$mailorder['vardata'] = [];

				while($vardata = $q_mailorder_vardata->fetch_assoc()) {
					$mailorder['vardata']['data'][] = [
						'name' => $vardata['name'], 
						'display_name' => $vardata['display_name'],
						'column' => $vardata['column']
					];
				}

				$q_mailorder_vardata_documents = $db->query("
					SELECT 
							* 
					FROM 
							`mailorder_vardata_documents` 
					WHERE 
							`mailorder_id` = " . $mailorder['id'] . " 
				");

				while($vardata_document = $q_mailorder_vardata_documents->fetch_assoc()) {
					$mailorder['vardata']['document'] = $vardata_document['pdf'];
				}

				$q_mailorder_packages = $db->query("
					SELECT 
							* 
					FROM 
							`mailorder_packages` 
					WHERE 
							`mailorder_id` = " . $mailorder['id'] . " 
					ORDER BY 
							`order` ASC,
							`name` ASC
				");

				while($package = $q_mailorder_packages->fetch_assoc()) {
					$mailorder['packages'][] = $package;
				}

				$account['mailorders']['b2c'][$mailorder['id']] = $mailorder;

			}
		}

	}
	
	if (CAMPAGNE_ID) {
		$campagne = campagne_fetch(OWNER_ID, ACCOUNT_ID, CAMPAGNE_ID);
	}

}

$debug = '';

if (isset($_GET['debug'])) {

	$debug = '&debug=' . $_GET['debug'];

	if (isset($_GET['arr'])) {
		$debug .= '&arr=' . $_GET['arr'];
	}

	echo '<i id="toggle-debug" class="material-icons open">chevron_right</i>';

	echo '<pre id="debug" class="open" style="display:none;">';

	if ($_GET['debug'] === 'account') {

		$arr = $account;
		if (!empty($_GET['arr']) && array_key_exists($_GET['arr'], $arr)) {
			$arr = $arr['' . $_GET['arr'] . ''];
		}

		echo '<h1>Account</h1>';

	} else if ($_GET['debug'] === 'campagne') {

		$arr = $campagne;
		if (!empty($_GET['arr']) && array_key_exists($_GET['arr'], $arr)) {
			$arr = $arr['' . $_GET['arr'] . ''];
		}

		echo '<h1>Campagne</h1>';

		// foreach($arr as $key => $item) {
		// 	if(is_array($item)) {
		// 		echo '<strong>' . $key . '</strong><br/>';
		// 		foreach($item as $key => $item) {
		// 			if(isset($item['name'])) {
		// 				echo '<i>' . $item['name'] . '</i><br/>';
		// 			}
		// 		}
		// 	}

		// }

	}

	print_r($arr);

	echo '</pre>';

}

// echo'<pre style="background: #FFF;margin: 15px;padding: 15px;">';
// print_r($_POST);
// print_r($_FILES);
// echo'</pre>';

$stations = [
	'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
	'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ',
	'CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM','CN','CO','CP','CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ',
	'DA','DB','DC','DD','DE','DF','DG','DH','DI','DJ','DK','DL','DM','DN','DO','DP','DQ','DR','DS','DT','DU','DV','DW','DX','DY','DZ'
];

$maanden = [
	'januari', 'jan',
	'februari', 'feb',
	'maart', 'ma',
	'april', 'apr',
	'mei', 'me',
	'juni', 'jun',
	'juli', 'jul',
	'augustus', 'aug',
	'september', 'sept',
	'oktober', 'okt',
	'november', 'nov',
	'december', 'dec',
];
$maanden_replace = [
	'01', '01',
	'02', '02',
	'03', '03',
	'04', '04',
	'05', '05',
	'06', '06',
	'07', '07',
	'08', '08',
	'09', '09',
	'10', '10',
	'11', '11',
	'12', '12',
];

$dagen = [
	'maandag', 'ma',
	'dinsdag', 'di',
	'woensdag', 'wo',
	'donderdag', 'do',
	'vrijdag', 'vr',
	'zaterdag', 'za',
	'zondag', 'zo',
];
$dagen_replace = [
	'', '',
	'', '',
	'', '',
	'', '',
	'', '',
	'', '',
	'', '',
];

function match_mailorder_package($package_name_original, $mailorder) {

	// if(strpos($package_name_original, 'Yorkshire') > -1) {
	// 	die(htmlentities($package_name_original, ENT_QUOTES));
	// }

	$additional = $mailorder['name'] .  " ";
	if(strpos(strtolower($package_name_original), strtolower($mailorder['name'])) > -1) {
		$additional = '';
	}

	$input_package_name = htmlentities(strtolower($additional.$package_name_original));

	$arr = [];

	if(!not_empty('packages', $mailorder)) {
		return $package_name_original;
	}

	foreach($mailorder['packages'] as $package) {

		$package_name = join(" ", [ $mailorder['name'], $package['name'] ]);
		
		// if(strpos($package_name, 'Yorkshire') > -1) {
		//  	arr($package_name);
		// }

		$package_id  = strtolower($package_name);
		
		$arr["".$package_id.""] = [ 
			"id" => $package['id'],
			"name" => $package['name'],
			"external_id" => $package['external_id']
		];

	}

	if(array_key_exists("".$input_package_name ."", $arr)) {
		return $arr["". $input_package_name .""];
	}

	return $package_name_original;

}
function return_article_number($external_id, $prefix = '') {
	
	if(!empty($prefix)) {
		$prefixlen = 8 - strlen($prefix);
		$external_id = $prefix . str_pad($external_id, $prefixlen, '0', STR_PAD_LEFT);
	}

	preg_match(PRODUCT_EXTERNAL_ID_PATTERN, $external_id, $matches);

	if (count($matches) > 0) {
		return $matches[0];
	}
	else {
		return false;
	}

	return $article_number;
}

function update_external_id($campagne_product_id, $external_id=false, $prefix='B') {
	
	global $db;

	if(!$external_id) {
		$external_id  = return_article_number($campagne_product_id,  $prefix);
	}

	$update = $db->query("
			UPDATE 
				`campagne_products` 
			SET 
				`external_id` = '". $external_id ."' 
			WHERE 
				`id` = ".$campagne_product_id." 
			LIMIT 1
		") or die($db->error);

	if($update) {
		return $external_id;
	}
	else {
		return false;
	}
}

function return_datetime($str, $date_time = 'date') {
	return $str;
	$str = trim(strtolower($str));
	return str_replace('  ', ' ', $str);
}

function array_sort($array, $json = false) {

		asort($array);
		$array = array_values($array);
		if ($json) {
			$array = json_encode($array);
		}
		return $array;
}


function return_str($string, $option = false) {

	$string = str_replace("  ", " ", trim($string));
	$string = str_replace("  ", " ", $string);
	$string = str_replace("\n", " ", $string);
	$string = nl2br($string);
	$string = str_replace("<br />", " ", $string);
	$string = str_replace("<br/>", " ", $string);
	$string = str_replace("<br>", " ", $string);
	$string = str_replace("'", "", $string);
	
	$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

	if(strtolower($option) === 'to_upper') {
		$string = strtoupper($string);
	}
	elseif(strtolower($option) === 'ucfirst') {
		$string = UCfirst($string);
	}
	elseif(strtolower($option) === 'ucwords') {
		$string = UCWords($string);
	}

	return $string;

}

function return_name($str) {
	$str = htmlentities($str, ENT_QUOTES);
	return $str;
}
function return_index($index) {
	return str_pad($index, 2, "0", STR_PAD_LEFT);
}

// define('PRODUCT_EXTERNAL_ID_PATTERN', '((P|PS)[0-9]{3,10})');
// define('PRODUCT_EXTERNAL_SET_ID_PATTERN', '((PS)[0-9]{3,10})');

function return_location_external_id($external_id = false) {

	global $account;
        
	$external_id = strtoupper(trim($external_id));

        $external_id = str_replace(['-RB', ' RB', ' - RB', 'RB', '-NB', ' NB', ' - NB' , 'NB'], 'V', $external_id);
        if(strpos($external_id, 'V') > -1) {
            return false;
        }
	if(!empty($external_id)){

		if(not_empty('pattern_location_id', $account['patterns'])) {

			$pattern = $account['patterns']['pattern_location_id'];

			if(preg_match($pattern, $external_id, $return_array)) {
				
				if(is_array($return_array) && count($return_array) > 0) {
					return $return_array[0];
				}

			}
		}

		

		return false;

	}
	
	return false;
}

function return_external_id($external_id) {
	
	$external_id = strtoupper(trim($external_id));

	$error = false; 
	
	if(empty($external_id) || is_array($external_id) || strlen($external_id) < 1) {
		return '';
	}

	preg_match(PRODUCT_EXTERNAL_ID_PATTERN, $external_id, $matches);

	if (count($matches) > 0) {
		return $matches[0];
	}
	else {
		return ' ';
	}
	return '' . $external_id;
}
function return_variation_product_name($str,  $space_replacer=false) {
	$replace = ['voorraad ', 'bestelaantal ', 'artikelnummers ', ' '];
	$str = str_replace($replace, '', strtolower($str));
	return UCfirst(trim($str));
}

function return_product_variant_name($str, $space_replacer='') {
	$str = UCWords($str);
	$str = str_replace(' ', $space_replacer, $str);
	if(in_array(strtolower($str), SKIP_PRODUCTNAMES)) {
		return false;
	}
	return $str;
}

function arr($arr, $die=true) {

	 if(USER_ID > 1) {
	 	return true;
	 }

	if(is_array($arr)) {
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
	}
	if(gettype($arr) === "string" || gettype($arr) === "integer") {
		echo $arr . " (".gettype($arr).")";
		echo "<hr>";
	}
	if($die) {
		die();
	}
}

function not_empty($needle, $haystack) {
	if(!is_array($haystack)) {
		return false;
	}
	if(gettype($needle) === 'string' && gettype($haystack) === 'array') {
		if(array_key_exists("".$needle."", $haystack)) {
			return true;
		}
	}
	return false;
}

function return_included_products($external_products_str) {
	
	preg_match_all(PRODUCTS_EXTERNAL_IDS_PATTERN, $external_products_str, $matches, PREG_SET_ORDER, 0);

	$preg_match_external_id_index = 1;
	$preg_match_name_index = 4;
	
	$included_external_products = [];

	foreach($matches as $match) {

		if(not_empty(''. $preg_match_external_id_index . '', $match) && not_empty('' . $preg_match_name_index . '', $match)) {
			
			$included_external_ids[] = return_article_number($match[$preg_match_external_id_index]);

			$included_external_products[] = [ 
				'external_id' => return_article_number($match[$preg_match_external_id_index]),
				'name' => trim($match[$preg_match_name_index])
			];

		}

	}

	return $included_external_products;

}
?>

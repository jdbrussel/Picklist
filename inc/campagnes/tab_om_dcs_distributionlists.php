<?php

if(!empty($_FILES['dclist'])) {

	$file = upload_excel_file($_FILES['dclist'], DIR_DISTRIBUTION_EXCEL_FILES, true);

	if(!empty($_POST['dc_id'])) {

		$dc_query = $db->query("
			SELECT 
				* 
			FROM 
				`dcs` 
			WHERE 
				`id` = ".$_POST['dc_id']." 
			LIMIT 1
		");

		$dc = $dc_query->fetch_assoc();

		if(not_empty('id',$dc)) {

			if($file) {

				try {

					$inputFileType = PHPExcel_IOFactory::identify(DIR_DISTRIBUTION_EXCEL_FILES . $file['filename']);
					$objReader = PHPExcel_IOFactory::createReader($inputFileType);
					$objPHPExcel = $objReader->load(DIR_DISTRIBUTION_EXCEL_FILES . $file['filename']);
					$data = array(1,$objPHPExcel->getActiveSheet()->toArray(null,false,true,true));

				} catch (\Exception $exc) {
					var_dump($exc);
				}

				if(count($data[1]) > 0) {

					dclist_delete($_POST['dc_id']);

					$db->query("
							INSERT INTO 
								`campagne_dc` 
								(
								`campagne_id`, 
								`dc_id`,
								`filename`,
								`src`
								) 
							VALUES 
								(
								'".$campagne['id']."', 
								'".$_POST['dc_id']."', 
								'".$db->real_escape_string($file['filename'])."',
								'".$db->real_escape_string($file['src'])."'
							)")or die($db->error);

					$campagne_dc_id = $db->insert_id;
					
					$delivery = [
						'data' => [
							"campagne_id" => $campagne['id'],
							"dc_id" => $_POST['dc_id'],
							"num_trucks" => 0
						],
						'trucks' => []
					];

					$trucknr = 0;
					$cur_date_time = false;
 
					foreach($data[1] as $i => $row) {

						if($i >= $dc['xls_start_row']){
					
							$date_time = $row[$dc['xls_column_due_date']] . ($dc['xls_column_due_time'] !== $dc['xls_column_due_date'] ? $row[$dc['xls_column_due_time']] : '');

							if($date_time !== $cur_date_time) {
 								
 								$cur_date_time = $date_time;

 								$date = return_datetime($row[$dc['xls_column_due_date']], 'date');
 								$time = return_datetime($row[$dc['xls_column_due_time']], 'time');
 								$date_time = return_datetime($date_time, 'date_time');
 							
 								$trucknr++;
 								$delivery['trucks'][$trucknr] = [
 									'data' => [
 										"due_date" => $date,
 										"due_time" => $time,
 										"due_datetime" => $date_time
 									],  
 									'containers' => []
 								];

							}

							$location_id = $row[$dc['xls_column_location_id']];
							$location_name = return_str($row[$dc['xls_column_location_name']]);

							$delivery['trucks'][$trucknr]['containers'][] = [ 
								'external_id' => $row[$dc['xls_column_location_id']],
								'name' => $location_name 
							];

							//echo 'Location : ' . $row[$dc['xls_column_location_id']] . '<br/>';
						
						} 

					}

				}


				if(count($delivery) > 0) {
			
					foreach($delivery['trucks'] as $truck) {

						if(empty($truck['data']['due_date']) || empty($truck['data']['due_time'])) {
							continue;
						}

						if($truck['data']['due_date'] === $truck['data']['due_time']) {
							$truck['data']['due_time'] = '';
						}

						$db->query("
							INSERT INTO 
									`campagne_dc_trucks` 
								(
									`campagne_id`, 
									`dc_id`, 
									`due_date`, 
									`due_time`,
									`created`
								) 
							VALUES
							 	(
							 		'".$delivery['data']['campagne_id']."', 
							 		'".$delivery['data']['dc_id']."', 
							 		'".$truck['data']['due_date']."',  
							 		'".$truck['data']['due_time']."', 
							 		NOW()
							 	)
						") or die($db->error);

						$container_id = $db->insert_id;

						foreach($truck['containers'] as  $container) {
							
							$external_id = $container['external_id'];
							
							if(array_key_exists("" . $external_id . "", $account['locations'])) {
								$location_group_id = $account['locations']["" . $external_id . ""]['group_id'];
							}
                                                        else {
                                                            $location_group_id = 'NULL';
                                                        }

							$delete = $db->query("DELETE FROM `campagne_logistic_centers_containers` WHERE `external_id` = '" . $external_id . "' AND `campagne_id` = '".CAMPAGNE_ID."'")or die($db->error);

							$db->query("
								INSERT INTO 
									`campagne_dc_trucks_containers` 
								(
									`campagne_id`, 
									`dc_id`, 
									`dc_truck_id`, 
									`location_group_id`, 
									`external_id`,
									`created`
								) 
							VALUES
							 	(
							 		'".$delivery['data']['campagne_id']."', 
							 		'".$delivery['data']['dc_id']."', 
							 		'".$container_id."', 
							 		$location_group_id,  
							 		'".$external_id."', 
							 		NOW()
							 	)
							") or die($db->error);
							
						}
					}

					header('Location: '. CAMPAGNE_URL .'#dcs');

				}

			}

		}
	}
}
if(not_empty('delete_campagne_dc', $_GET) && not_empty('dc_id', $_GET) ) {

	$delete = dclist_delete($_GET['dc_id'], $_GET['delete_campagne_dc']);
	
	if($delete) {
		header('Location: '. CAMPAGNE_URL .'#dcs');
	}

}


$num_dcs = 0;
if(count($account['dcs']) > 0) {
	$num_dcs = count($account['dcs']);
}
$num_dclists = 0;
if(count($campagne['distribution']['dcs']) > 0) {
	$num_dclists = count($campagne['distribution']['dcs']);
}


?><div class="tab" data-tab-id="dcs" data-name="Distributielijsten (<?php echo $num_dclists;?>/<?php echo $num_dcs;?>)" data-icon="local_shipping">
    <?php
    if(USER_ID  === "1") {
       // arr($campagne['distribution']['no_dc'], false);
    }
?>
        <div class="row">
            <div class="col-12">

                <h4>
                    Distributielijsten
                    <input type="text" id="search_location" placeholder="Zoeken..." style="float:right;font-size:13px;padding: 0 5px;line-height:23px;">
                </h4>
                <hr>

                <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 15px auto;">
                        <thead>
                          <tr>
                                <th class="id">#</th>
                                <th>Distibution centre</th> 
                                <th class="small">Filialen</th>
                                <th class="small">Ritten</th>
                                <th class="small">Max. filialen per rit</th>
                                <th class="small">Toegevoegd</th>
                                <th class="small" colspan=2>Bronbestand</th>
                          </tr>
                        </thead>
                        <tbody>
                        <?php

                                if(count($account['dcs']) > 0) {

                                        $i=1;

                                        foreach($account['dcs'] as $item) {

                                                if(array_key_exists($item['id'], $campagne['distribution']['dcs'])) {

                                                        $dc = $campagne['distribution']['dcs'][$item['id']];

                                                        $item['added'] = $dc['added'];
                                                        $item['filename'] = $dc['filename'];
                                                        $item['num_trucks'] = count($dc['trucks']);
                                                        $item['num_containers'] = count($dc['locations']);


                                                }
                                                else {
                                                        $item['added'] = false;
                                                        $item['filename'] = false;
                                                        $item['num_trucks'] = 0;
                                                        $item['num_containers'] = 0;
                                                }

                                                ?>

                                                <form method="post" action="#dcs" enctype="multipart/form-data" id="form_<?php echo $item['id'];?>">
                                                        <tr class="searchable-row"  data-search-id="dc_id_<?php echo $item['id'];?>">
                                                                <td class="id small">
                                                                        <input type="hidden" name="dc_id" value="<?php echo $item['id'];?>">
                                                                        <?php echo str_pad($i, 2 , "0", STR_PAD_LEFT);?>
                                                                </td>
                                                                <td class="text-overflow lg"><?php echo $item['name'];?></td>
                                                                <td class="index"><?php echo $item['num_containers']; ?></td>
                                                                <td class="index"><?php echo number_format($item['num_containers']/$item['max_containers_per_truck'],1,',','');?></td>
                                                                <td class="text-overflow stretch small"><?php echo $item['max_containers_per_truck'];?>&nbsp;x&nbsp;<?php echo str_replace(' ','&nbsp;', $campagne['distribution']['container']['name']);?></td>
                                                                <?php
                                                                if(!$item['filename'] || $item['num_trucks'] === 0) {
                                                                        ?>
                                                                        <td colspan=3><input type="file" name="dclist" data-id="form_<?php echo $item['id'];?>" class="in_row auto-upload" style="line-height:17px;" accept=".xls,.xlsx"></td>
                                                                        <?php
                                                                }
                                                                else {
                                                                        ?>
                                                                        <td class="date time"><?php echo $item['added'];?></td>
                                                                        <td style="padding: 3px 7px 3px 7px;" class="text-overflow stretch small">
                                                                                <a class="download" href="<?php echo (str_replace('var/www/','',DIR_DISTRIBUTION_EXCEL_FILES) . $item['filename']);?>"><?php echo $item['filename'];?></a>		
                                                                        </td>
                                                                        <td>
                                                                                <a href="<?php echo CAMPAGNE_URL;?>&delete_campagne_dc=<?php echo $dc['id'];?>&dc_id=<?php echo $dc['dc_id'];?>#dcs" class="in_row delete" onclick="return confirm('Weet u het zeker?');"><i class="material-icons">delete_forever</i><span></span></a>
                                                                        </td>
                                                                        <?php
                                                                }
                                                                ?>
                                                </tr>
                                                </form>
                                                <?php
                                        $i++;
                                        }
                                }
                                ?>
                        </tbody>
                </table>

                <br/>


               <?php
         
                $logistic_center_locations = [];
                if(array_key_exists($account['default_dcs_logistic_center_id'], $campagne['distribution']['logistic_centers'])) {
                        $logistic_center_locations = json_decode($campagne['distribution']['logistic_centers'][''. $account['default_dcs_logistic_center_id'] . '']['locations'], true);
                }
                
                if(count($logistic_center_locations) > 0) {

                    $order_name = '';
                    if(array_key_exists('name',$campagne) && !empty($campagne['name'])) {
                       $order_name = str_replace([" ", "/"],"_",strtoupper($campagne['name']));
                    }

                    $erp_order_id = '';
                    if(array_key_exists('erp_id',$campagne) && !empty($campagne['erp_id'])) {
                       $erp_order_id = $campagne['erp_id'];
                    }

                    $file_folder = '/var/www/picklists/output/gls/';
                    $filename = $file_folder . 'GLS_'. $erp_order_id . '_' . $order_name . '.csv';

                    $csv = fopen($filename, 'w');

                    $row = [];
                    $row[] = 'Naam 1 weekpakket';
                    $row[] = 'sleeves';
                    $row[] = 'aantal sleeves';
                    $row[] = 'Extra regel 02';
                    $row[] = 'extra regel 01';
                    $row[] = 'Naam 2';
                    $row[] = 'Adres';
                    $row[] = 'postcode';
                    $row[] = 'plaats';
                    $row[] = 'LAND';
                    $row[] = 'Expresszending';
                    $row[] = 'SATURDAYSERVICE';
                    $row[] = 'contactpersoon';
                    $row[] = 'Telefoonnummer';
                    $row[] = 'Emailadres';
                    $row[] = 'Type Adres';
                    $row[] = 'Referentie';
                    $row[] = 'Aantal eenheden';
                    $row[] = 'Verpakking';
                    $row[] = 'Gewicht';
                    $row[] = 'Klantnummer';

                    fputcsv($csv, $row, ';', '"');
                    $num_addresses = 0;
                    foreach($logistic_center_locations as $loc) {

                        $num_addresses++;
                        
                        $row = [];
                        $row[] = $loc['external_id'];
                        $row[] = '';
                        $row[] = '';
                        $row[] = $loc['rayon'];
                        $row[] = $loc['formule'];
                        $row[] = $loc['name'];
                        $row[] = $loc['address_1'];
                        $row[] = $loc['postal_code'];
                        $row[] = $loc['city'];
                        $row[] = $loc['country'];
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = $erp_order_id;
                        $row[] = 1;
                        $row[] = 'PCO';
                        $row[] = 1;
                        $row[] = '54660364';

                        fputcsv($csv, $row, ';', '"');

                    }
                    fclose($csv);
                    
                    $gls_list_name = str_replace('/var/www/picklists/', '', $filename);
                }
                ?>
                <div class="row">
                    
                        <div class="col-5" >
                                <?php
                                if(count($logistic_center_locations) > 0 && !empty($gls_list_name)) {
                                ?>
                                
                                <h4>
                                    GLS lijst
                                </h4>
                                <hr>
                                <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 15px auto;">
                                    <thead>
                                        <tr>
                                            <th class="small stretch">Bestandsnaam</th>
                                            <th class="small">Aantal</th>
                                            <th>&nbsp;</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $gls_list_name; ?>" class="download">
                                                    <?php echo str_replace('output/gls/','',$gls_list_name); ?>
                                                </a>
                                            </td>
                                            <td class="small quantity">
                                                <?php echo $num_addresses; ?>&nbsp;Adressen
                                            </td>
                                            <td style="padding-left:0px;padding-right:0px !important;">
                                                <a href="<?php echo $gls_list_name; ?>" class="in_row download"><i class="material-icons">save_alt</i><span></span></a>
                                            </td>
                                            </tr> 
                                    </tbody>
                                    
                                </table>
                                
                               
                                <h4>
                                    In depot BEK (<?php echo count($campagne['distribution']['no_dc']);?> containers)
                                </h4>
                                <hr>

                                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 15px auto;">
                                                <thead>
                                                  <tr>
                                                        <th class="id small">#</th>
                                                        <th class="small">ID</th>
                                                        <th class="small stretch" >Filiaalnaam</th>
                                                        <th class="small lg" colspan="2" style="text-align:right;padding-right:12px !important;">Leverdatum</th>
                                                  </tr>
                                                </thead>
                                                <tbody class="scroll_browser">
                                                <?php
                                                $total = (string) count($logistic_center_locations);
                                                $str_pad_len = strlen($total);
                                                
                                                $id=0;
                                                foreach($logistic_center_locations as $logistic_center_locations_container) {

                                                        $id++;
                                                        $new = true;
                                                        $delivery_item = [
                                                                'id' 			=> 'temp_' . $id,
                                                                'date' 			=> false,
                                                                'time'			=> false,
                                                                'delivery_note' => ''
                                                        ];


                                                        if(array_key_exists('campagne_logistic_centers_container', $logistic_center_locations_container)) {

                                                                $new = false;
                                                                $delivery_item = $logistic_center_locations_container['campagne_logistic_centers_container'];

                                                                if(array_key_exists('delivery_note', $logistic_center_locations_container) && !empty($logistic_center_locations_container['delivery_note'])) {
                                                                        $delivery_item['delivery_note'] = $logistic_center_locations_container['delivery_note'];
                                                                }
                                                        }

                                                        ?>
                                                        <tr class="searchable-row" data-search-id="external_id_<?php echo $logistic_center_locations_container['external_id'];?>" data-datetime_id="delivery_datetime_<?php echo $delivery_item['id'];?>">
                                                            <td class="index small" style="padding: 0px;"><?php echo str_pad($id,$str_pad_len,"0",STR_PAD_LEFT);;?></td>
                                                            <td class="" style="min-width: 55px !important;padding-right:0px;"><?php echo $logistic_center_locations_container['external_id'];?></td>
                                                            <td class="small text-overflow"><?php echo $logistic_center_locations_container['name'];?> <?php echo (!empty($logistic_center_locations_container['rayon']) ? '('.$logistic_center_locations_container['rayon'].')' : '');?></td>
                                                            <td class="date datetime" style="text-align:right;">
                                                                <?php echo (array_key_exists('datetime', $delivery_item) ? $delivery_item['datetime'] . ' uur' : '-');?>
                                                            </td>
                                                            <td style="padding-left: 0px !important;">
                                                                <a class="in_row datepicker <?php echo (array_key_exists('datetime', $delivery_item) ? 'date-set' : '');?>" onclick="datepicker('datetime','campagne_logistic_centers_containers','delivery_datetime','<?php echo $delivery_item['id'];?>','<?php echo $delivery_item['date'];?>','<?php echo $delivery_item['time'];?>','<?php echo ($new ? 'true' : 'false');?>', ['external_id', 'campagne_id'], ['<?php echo $logistic_center_locations_container['external_id'];?>', <?php echo CAMPAGNE_ID;?> ], ['delivery_note', 'notitie'] , '<?php echo $delivery_item['delivery_note'];?>','true');" style="margin-right: 0px !important;"><i class="material-icons" style="font-size:16px;">local_shipping</i><span></span></a>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                }
                                                ?>
                                                </tbody>
                                        </table>
                                 <?php
                                }
                                ?>


                        </div>
                        <div class="col-7">

                                <h4>
                                Ritten per Distributie centrum
                                </h4>
                                <hr>
                                <?php
                                if($num_dclists > 0) {
                                ?>

                                <div class="row">

                                        <?php
                                                $campagne_dc_locations = [];
                                                $mincolwidth = 4;
                                                $num_logistic_centers = count($campagne['distribution']['logistic_centers']);
                                                $num_dcs = count($account['dcs']);
                                                $colwidth = floor(12 / $num_dcs);
                                                if($colwidth < $mincolwidth) {
                                                        $colwidth = floor($colwidth * 2);
                                                }
                                                $minrows = 4;

                                        foreach($account['dcs'] as $dc) {
                                                ?>
                                                <div class="col-sm-12 col-md-6 col-lg-4 col-xl-<?php echo $colwidth;?>" style="margin-bottom: 15px;">
                                                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 15px auto;">
                                                                <thead>
                                                                  <tr>
                                                                        <th class="stretch" colspan=2><strong><?php echo $dc['name'];?></strong></th>
                                                                        <th class="quantity small">Gepland</th>
                                                                        <th class="quantity small">Gevuld</th>
                                                                  </tr>
                                                                </thead>
                                                                <tbody>
                                                                        <?php
                                                                        $dc['num_containers'] = 0; 
                                                                        $dc['num_filled_containers'] = 0;

                                                                        if(array_key_exists($dc['id'], $campagne['distribution']['dcs'])) {

                                                                                $l = 0;
                                                                                if(count($campagne['distribution']['dcs']['' . $dc['id'] . '']['trucks']) > 0) {

                                                                                        foreach( $campagne['distribution']['dcs']['' . $dc['id'] . '']['trucks'] as $campagne_dc) {

                                                                                                $locations = json_decode($campagne_dc['locations'], true);

                                                                                                foreach($locations as $location) {
                                                                                                        if(!array_key_exists("". $location['external_id']."", $campagne_dc_locations)) {
                                                                                                                $campagne_dc_locations["" . $location['external_id']. ""] = $location;
                                                                                                        }
                                                                                                }

                                                                                                $campagne_dc['containers']['scheduled'] = json_decode($campagne_dc['containers']['scheduled'], true);
                                                                                                $campagne_dc['containers']['filled'] 	= json_decode($campagne_dc['containers']['filled'], true);
                                                                                                $campagne_dc['containers']['empty'] 	= json_decode($campagne_dc['containers']['empty'], true);


                                                                                                $campagne_dc['num_containers'] = count($campagne_dc['containers']['scheduled']);

                                                                                                $num_filled_containers =  count($campagne_dc['containers']['filled']);
                                                                                                $l++;
                                                                                                if(array_key_exists($dc['id'], $campagne['distribution']['dc_containers']) && array_key_exists($campagne_dc['id'], $campagne['distribution']['dc_containers'][''. $dc['id']. ''])) {
                                                                                                        $dc['num_filled_containers'] = $dc['num_filled_containers'] + $num_filled_containers;
                                                                                                }

                                                                                                $dc['num_containers'] = $dc['num_containers'] + $campagne_dc['num_containers'];

                                                                                                ?>	


                                                                                                <tr data-datetime_id="due_datetime_<?php echo $campagne_dc['id'];?>" class="searchable-row" data-search-id="truck_id_<?php echo $campagne_dc['id'];?>" >
                                                                                                        <td style="padding-left: 2px !important;">
                                                                                                                <a class="in_row datepicker <?php
                                                                                                                        if(!empty($campagne_dc['due_datetime'])) {
                                                                                                                                echo "date-set";
                                                                                                                        }
                                                                                                                        ?>" data-original="<?php echo $campagne_dc['due_date'];?> <?php echo $campagne_dc['due_time'];?>" onclick="datepicker(
                                                                                                                                'datetime',
                                                                                                                                'campagne_dc_trucks',
                                                                                                                                'due_datetime',  
                                                                                                                                <?php echo $campagne_dc['id'];?>, 
                                                                                                                                '<?php echo $campagne_dc['date'];?>', 
                                                                                                                                '<?php echo $campagne_dc['time'];?>',
                                                                                                                                false,
                                                                                                                                [], 
                                                                                                                                [],
                                                                                                                                ['delivery_note', 'notitie'],
                                                                                                                                '<?php if(not_empty('delivery_note',$campagne_dc)) { echo $campagne_dc['delivery_note']; } ?>',
                                                                                                                                'clear');
                                                                                                                        "><i class="material-icons" style="font-size:16px;">local_shipping</i><span></span></a>
                                                                                                        </td>
                                                                                                        <td class="small datetime stretch text-overflow"><?php
                                                                                                                if(empty($campagne_dc['due_datetime'])) {
                                                                                                                        echo $campagne_dc['due_date'] .  ' '. $campagne_dc['due_time'];
                                                                                                                }
                                                                                                                else {
                                                                                                                        echo $campagne_dc['due_datetime'] . ' uur';
                                                                                                                }
                                                                                                        ?></td>
                                                                                                        <!-- <td class="small quantity"><?php echo $campagne_dc['num_containers'];?></td>
                                                                                                        <td class="small quantity" data-value="truck-containers" data-truck-id="<?php echo $campagne_dc['id'];?>"><?php echo $num_filled_containers;?></td> -->
                                                                                                        <td class="small quantity"><?php echo count($campagne_dc['containers']['scheduled']);?></td>
                                                                                                        <td class="small quantity" data-value="truck-containers" data-truck-id="<?php echo $campagne_dc['id'];?>"><?php echo count($campagne_dc['containers']['filled']);?></td>
                                                                                                </tr>
                                                                                                <?php

                                                                                                if( count($campagne_dc['containers']['empty']) > 0 && count($campagne['products']) > 0 ) {
                                                                                                        echo '<tr><td colspan=4 class="small" style="font-size:10px !important;text-align:right;color:#999;"><i>';
                                                                                                        echo implode(', ',$campagne_dc['containers']['empty']) . (count($campagne_dc['containers']['empty']) > 1 ? ' ontbreken' : ' ontbreekt');
                                                                                                        echo '</i></td></tr>';
                                                                                                }


                                                                                        }
                                                                                        for($x=$l; $x<$minrows; $x++) {
                                                                                                echo  '<tr><td colspan="4">&nbsp;</td></tr>';
                                                                                        }
                                                                                }
                                                                        }
                                                                        ?>
                                                                </tbody>
                                                                <?php
                                                                        if($dc['num_containers'] > 0) {
                                                                ?>
                                                                <tfooter>
                                                                        <tr class="total">
                                                                                <td colspan=2 class="small">Totaal aantal containers</td>
                                                                                <td class="quantity small"><?php echo $dc['num_containers'];?></td>
                                                                                <td class="quantity small"><?php echo $dc['num_filled_containers'];?></td>
                                                                        </tr>
                                                                </tfooter>
                                                                <?php
                                                                        }
                                                                ?>
                                                        </table>
                                                </div>
                                                <?php
                                        }
                                        ?>
                                </div>
                                <?php
                                }
                                ?>
                        </div>
                </div>
            
            
            
            
            
            
            
            
            
            
            
            
        </div>
    </div>
	
        
        
        
</div>


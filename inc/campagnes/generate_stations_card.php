<?php


if(array_key_exists('delete_station_cards', $_GET) && is_numeric($_GET['delete_station_cards'])) {
    $delete_query = "DELETE FROM `campagne_station_cards` WHERE `campagne_id` = ". (integer) CAMPAGNE_ID ." AND `id` = ".(integer) $_GET['delete_station_cards'] ." LIMIT 1";
    $delete = $db->query($delete_query) or die($db->error());
    if($delete) {
        header("location: " . CAMPAGNE_URL . "#picklists");
    }
}

$this_box = false;
if(array_key_exists('generate_stations_card', $_GET) && $_GET['generate_stations_card'] === '1') {
    if(array_key_exists('account_id', $_GET) && array_key_exists('campagne_id', $_GET) && array_key_exists('boxes', $_GET) && array_key_exists('box_id', $_GET)) {
        $campagne_boxes = $campagne['distribution']['container']['boxes'][$_GET['boxes']]['campagne_boxes'];
        foreach($campagne_boxes as $campagne_box) {
            if((integer) $_GET['box_id'] === (integer) $campagne_box['id']) {
                $campagne_box['campagne'] = $campagne['name'];
                $campagne_box['erp_id'] = $campagne['erp_id'];
                $this_box = $campagne_box;
            }
        }
    }
}
if($this_box) {

    $query = "SELECT * FROM `campagne_station_cards` WHERE `campagne_id` = ". (integer) CAMPAGNE_ID ." AND `campagne_container_box_id` = ".(integer) $_GET['box_id'] ."";
    $check_if_exists = $db->query($query) or die($db->error);
    
    if(USER_ID && $check_if_exists->num_rows === 0) {
    
        $query = "INSERT INTO  `campagne_station_cards` (
            `campagne_id`,
            `campagne_container_box_id`,
            
            `created_user_id`,
            `created`,
            `content`
            ) 
            VALUES (
                ".(integer) CAMPAGNE_ID.",
                ".(integer) $_GET['box_id'].", 
                
                ".(integer) USER_ID.",
                NOW(),
                 '" . $db->real_escape_string(json_encode($this_box)) . "'
            )";
        
        $insert = $db->query($query) or die($db->error);
        if($insert) {
            $station_card_id = $db->insert_id;
            if($station_card_id) {
                header("location: " . CAMPAGNE_URL . "#picklists");
            }
        }
    }
}

header("location: " . CAMPAGNE_URL . "#picklists");
<?php
$do_delete = true;
$limit = 10;
$date = new DateTime();
$campagne_delete_date_limit = $date->modify('-2 month')->format('Y-m-d');

$db = new mysqli('localhost', 'root', 'bfESVn1fL', 'picklists');
$query = "SELECT `id`, `created`FROM `campagnes` WHERE `archive` = 1 AND `created` < '".$campagne_delete_date_limit."' ORDER BY `created` ASC LIMIT ".$limit."";
$campagnes = $db->query($query);

$campagne_ids = [];
while($campagne = $campagnes->fetch_assoc()) {
    $campagne_ids[] = $campagne['id'];
}

$queries = [];
$queries['campagne_station_cards'] = "DELETE FROM `campagne_station_cards` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_product_files'] = "DELETE FROM `campagne_product_files` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_products'] = "DELETE FROM `campagne_products` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_picklists'] = "DELETE FROM `campagne_picklists` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_logistic_centers_containers'] = "DELETE FROM `campagne_logistic_centers_containers` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_logistic_centers'] = "DELETE FROM `campagne_logistic_centers` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_dc_trucks_containers'] = "DELETE FROM `campagne_dc_trucks_containers` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_dc_trucks'] = "DELETE FROM `campagne_dc_trucks` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_dc'] = "DELETE FROM `campagne_dc` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['campagne_container_boxes'] = "DELETE FROM `campagne_container_boxes` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";
$queries['sscc_containers'] = "DELETE FROM `sscc_containers` WHERE `campagne_id` IN(".implode(',', $campagne_ids).")";

$error = false;
foreach($queries as $related_table => $query) {
    if($do_delete) {
        $delete_related_table = $db->query($query);
        if(!$delete_related_table) {
            $error = true;
        }
        else {
            echo "DELETED: ". strtoupper($related_table) . " -> ". $db->affected_rows ."<br/>";
        }
    }
    else {
        echo "DELETE: ". strtoupper($related_table) . "<br/>";
        echo $query . "<hr>";
    }
}

if(!$error) {
    $query = "DELETE FROM `campagnes` WHERE `id` IN(".implode(',', $campagne_ids).")";
    if($do_delete) {
        $delete_campagne = $db->query($query);
        if(!$delete_campagne) {
            $error = true;
        }
        else {
            echo "DELETED: CAMPAGNES (".implode(',', $campagne_ids).") -> ". $db->affected_rows ."<br/>";
        }
    }
    else {
         echo "DELETE: CAMPAGNES<br/>";
         echo $query . "<hr>";
    }
}
<?php
    if(!empty($_POST)) {
        if(array_key_exists('update_campagne_status', $_POST) && !empty($_POST['update_campagne_status'])) {
            if(array_key_exists('campagne_status', $_POST) && !empty($_POST['campagne_status']) && array_key_exists('campagne_id', $_POST) && !empty($_POST['campagne_id'])) {
                
                $campagne_id = $_POST['campagne_id'];
                $status = (string) $_POST['campagne_status'];
                
                if($status !== $campagne['status']) {
                    if($campagne['status'] === 'fulfilment') {
                        if($status != 'fulfilment') {
                            $delete_from = [
                                'campagne_picklists', 
                                'campagne_station_cards'
                            ];
                            foreach($delete_from as $table_name) {
                                $db->query("DELETE FROM `".$table_name."` WHERE `campagne_id` = " . $campagne['id']);
                            }
                        }
                    }
                    
                    $query = "UPDATE `campagnes` SET `status` = '".$db->real_escape_string($status)."' WHERE `id` = ".(integer) $campagne_id ."";
                    $update = $db->query($query)or die();
                }
                header('Location: /picklists/?account_id='.ACCOUNT_ID.'&campagne_id='.CAMPAGNE_ID.'');
            }
        }
    }
?>

    <div class="row" style="margin: 0px auto;">
        <div class="col-8">
            <?php 
            echo '<h2 class="header">Campagne ' . $account['name'] . ': ' . $campagne['name'] . ' ' . ($campagne['archive'] === 1 ? '(archief)' : '') . '</h2>';
            ?>
            <?php
            $num_files = count($campagne['product_files']);
            $num_products = count($campagne['products']);
            $num_locations = count($campagne['distribution']['locations_in_campagne']);
            $has_picklists = count($campagne['picklists']);
            $has_station_cards = false;
            
//            arr($campagne['picklists'], false);
            ?>
        </div>
        <div class="col-4">
            <script>
            var current_status = '<?php echo (array_key_exists('status', $campagne) ? $campagne['status'] : 'pending'); ?>';
            function checkStatusChange() {
                return window.confirm('Weet je het zeker?');
            }
            </script>
            <form method="post" style="margin-top:10px;" onsubmit="return checkStatusChange();">
                <div class="row">
                    <div class="col-12"><label class="select">Orderstatus</label></div>
                </div>
                <div class="row">
                    <div class="col-7" style="padding-right:0px;">
                        <select name="campagne_status" style="width:100%;">
                            <option value="pending" <?php echo ($campagne['status'] == "pending" ? "selected" : "" ); ?>>Ordermanagement</option>
                        <?php
                        if($num_files > 0) {
                         ?>
                            <option value="handling" <?php echo ($campagne['status'] == "handling" ? "selected" : "" ); ?>>Handling</option>   
                            <option value="fulfilment" <?php echo ($campagne['status'] == "fulfilment" ? "selected" : "" ); ?>>In Fulfilment</option>
                        <?php
                           }
                        ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <input type="hidden" name="campagne_id" value="<?=$campagne['id'];?>">
                        <input type="submit" name="update_campagne_status" value="Orderstatus wijzigen" >
                    </div>
                </div>
            </form>
        </div>
    </div>

<div class="tab" data-tab-id="settings" data-name="Instellingen" data-icon="settings">
    
	<form method="post" action="#settings">
		<div class="row">
			<div class="col-6">
				<h4>Campagne</h4>
				<hr>

				<label class="select">Naam Campagne</label>
				<input type="text" name="campagne_name" value="<?=$campagne['name'];?>" class="regular" <?=($campagne['archive'] == "1" ? "READONLY" : "" );?>>

				<label class="select">XGram Order</label>
				<input type="text" name="erp_id" value="<?=$campagne['erp_id'];?>" class="regular" <?=($campagne['archive'] == "1" ? "READONLY" : "" );?>>

                                
                                
				<label class="select">Pickdatum</label>
				<div class="datepickerdiv" data-datetime_id="pick_datetime_<?=$campagne['id'];?>">
                                    <input type="text" name="pick_datetime" value="<?=$campagne['pick_datetime'];?>" class="regular datetime" READONLY >
                                    <?php
                                    if($campagne['archive'] == "0") {
                                    ?>
                                    <a class="in_row datepicker <?php
                                            if(!empty($campagne['pick_datetime'])) {
                                                    echo "date-set";
                                            }
                                    ?>" onclick="datepicker('datetime','campagnes','pick_datetime',  <?=$campagne['id'];?>, '<?=$campagne['pick_date'];?>', '<?=$campagne['pick_time'];?>');"><i class="material-icons">date_range</i><span></span></a>
                                    <?php
                                    }
                                    ?>
				</div>
                                
                                <h4>Archief</h4>
				<hr>
				<label class="select">Campagne in archief</label>
				<select name="archive">
                                    <option value="0" <?php echo ($campagne['archive'] == "0" ? "selected" : "" ); ?>>Nee</option>
                                    <option value="1" <?php echo ($campagne['archive'] == "1" ? "selected" : "" ); ?>>Ja</option>
				</select>
  
				
			</div>
			<div class="col-6">
                            
                                

				<h4>Container</h4>
				<hr>
                                <label class="select">Type Campagne</label>
				<select name="type">
                                    <option value="campagne" <?php echo ($campagne['type'] == "campagne" ? "selected" : "" ); ?>>Thema Campagne</option>
                                    <option value="dagdoos" <?php echo ($campagne['type'] == "dagdoos" ? "selected" : "" ); ?>>Dagdoos</option>
                                    <option value="weekdoos" <?php echo ($campagne['type'] == "weekdoos" ? "selected" : "" ); ?>>Weekdoos</option>
				</select>
                                
                                
				<label class="select">Container</label>
				<select name="container_id" action="#dcs" <?=($campagne['archive'] == "1" ? "DISABLED" : "" );?>>
                                    <option value="">Selecteer een container</option>
                                    <?php
                                    foreach($account['containers'] as $item) {
                                            echo '<option value="'.$item['id'].'" '. ($campagne['distribution']['container']['id'] === $item['id'] ? 'selected' : '') .'>'.$item['name'].' ('.count($item['boxes']) . ' '. (count($item['boxes']) === 1 ? 'doos' : 'dozen')  .')</option>';
                                    }
                                    ?>
				</select>
                                
                                <h4>Palletlist</h4>
                                <hr>
                                <label class="select">Afleveradres</label>
				<input type="text" name="palletlist_address" value="<?=$campagne['palletlist_address'];?>" class="regular" <?=($campagne['archive'] == "1" ? "READONLY" : "" );?>>

                                <label class="select">Aantal items op pallet</label>
				<input type="number" name="palletlist_num_items" value="<?=$campagne['palletlist_num_items'];?>" class="regular" <?=($campagne['archive'] == "1" ? "READONLY" : "" );?>>

                                
                                
                                
                               
			</div>
			<div class="col-12">
				<br/>
				<input type="hidden" name="campagne_id" value="<?=$campagne['id'];?>">
				<input type="submit" name="update_campagne" value="Opslaan">
			</div>
			<!--
			<div class="col-4">
				<h4>Container/Dozen</h4>
				<hr>
				<div class="container row" style="background: #c9e3ff;padding:5px 10px;margin: 15px 0px;">
					<div class="col-12" style="font-weight:bold;margin: 5px 0px;padding: 5px 0 0 2px;font-size:15px;line-height:20px;">
						<?=$container['name'];?>
					</div>

					<?php
						foreach($campagne['container'] as $item) {
							?>
							<div class="box col-12"  style="box-sizing: border-box;background: #FFF;margin: 5px 0px;padding: 0px 12px;font-size:15px;line-height:50px;height:<?=(50 * $item['col']); ?>px;"><?=$item['name'];?></div>
							<?php
						}
					?>
				</div>	
			</div> 
			-->
		</div>
	</form>
</div>
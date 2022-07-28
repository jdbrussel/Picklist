<div class="tab" data-tab-id="picklists" data-name="Picklijsten"  data-icon="list_alt" >
		
    
		<div class="row">
			
			<div class="col-8">



				<style>
					#trucks_order li {
						height:39px !important;
						line-height:39px !important;
						font-size: 12px;
						color: #666;
					}
					span.truck_id {
						font-size: 12px;
						padding: 0 12px;
					}
					span.name {
						font-size: 13px;
						display: inline-block;
						font-weight:bold;
						width: 160px !important;
						min-width: 160px !important;
						max-width: 360px !important;
					}
					span.datetime {
						margin-right: 25px;
					}
					span.date {
						margin-right: 15px;
					}
					span.loading_datetime {
						float:right;
						margin: 5px;
					}
				</style>
	
				<h4>Te picken</h4>
				<hr>

				<ul class="chips" id="trucks_order">
					<?php
					// arr($campagne['distribution']['trucks']);
					foreach($campagne['distribution']['trucks'] as $truck) {

						$cmyk = explode(" ", $truck['dc_color_cmyk']);

						$cmykcolor = [];
						foreach($cmyk as $color) {
							$cmykcolor[] = ($color * 100) . "%";
						}

						?>
						<li class="ui-state-default" data-truck-id="<?php echo $truck['id'];?>" id="<?php echo $truck['id'];?>" >
							<div data-w3-color="cmyk(<?php echo trim(implode(", ", $cmykcolor)); ?>)" style="float: left; display: inline-block; color:#FFF; margin-right:15px;">
								<span class="truck_id">T<?php echo $truck['id'];?></span> 
							</div>
								<span class="name"><?php echo $truck['dc_name'];?></span>
							
							<span class="datetime">
								<span class="date"><?php 
									if(!empty($truck['date'])) { 
										echo $truck['date'];  
									} else { 
										echo $truck['due_date']; 
									} ?></span>
								<span class="time"><?php 
									if(!empty($truck['time'])) { 
										echo $truck['time'] . " uur";  
									} else { 
										echo $truck['due_time']; 
									} ?></span>
							</span>
							<span class="loading_datetime" data-datetime_id="loading_datetime_<?php echo $truck['id']; ?>">
								<?php
								$ld = false;
								if(not_empty('loading_datetime', $truck) && !empty($truck['loading_datetime'])) {
									$loading_datetime = $truck['loading_datetime'];
									$ld = true;
								}
								?>
								<a class="in_row datepicker <?php echo ($ld ? "date-set" : ""); ?>" data-original="" 
									onclick="datepicker(
										'datetime',
										'campagne_dc_trucks',
										'loading_datetime',  
										<?php echo $truck['id']; ?>, 
										'<?php echo $truck['loading_date']; ?>', 
										'<?php echo $truck['loading_time']; ?>',
										false,
										['loading_user_id'], 
										['<?php echo USER_ID;?>'],
										[
											'delivery_note', 
											'notitie'
										],
										'',
										'clear'
								);" style="float: left;min-width:160px;"><i class="material-icons" style="font-size:16px;margin-right:6px;">date_range</i>
									<span class="datetime"><?php echo ($ld ? $loading_datetime . " uur" : ""); ?></span>
								</a>
							</span>
							
						</li>
						<?php
					}
					?>
				</ul>

				<script>
					$("#trucks_order").sortable({
						placeholder: "ui-state-highlight",
						update: function( event, ui ) {
							
							var items = $(this).sortable("toArray");

							$('body').addClass('loading-wait');
							for(var i in items) {
								// console.log(i + ' - ' + items[i]);
								ajax_db_save('campagne_dc_trucks', 'loading_order', i, items[i]);
							}
							$('body').removeClass('loading-wait');

						}
					});
				</script>


				

			</div>

			<div class="col-4">
				
				<h4>Gepicked</h4>
				<hr>

				<?php
				arr($campagne['distribution']['trucks'], false);
				//arr($campagne['distribution']['trucks'], false);
				?>

			</div>

		</div>

</div>
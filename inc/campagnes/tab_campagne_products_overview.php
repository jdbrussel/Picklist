<div class="tab" data-tab-id="products" data-name="Campagne Producten (<?=count($campagne['products']);?>)"  data-icon="style">
	
	<div class="row" >
	
		<div class="col-8">

			<h4>
				Inbegrepen producten (<?=count($campagne['products']);?>)
				<input type="text" id="search" placeholder="Zoeken..." style="float:right;font-size:13px;padding: 0 5px;line-height:23px;"></input>
			</h4>
			<hr>
			
			<table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 15px auto;">
				<thead>
					<tr>
						<th class="small id">#</th>
						<th class="small stretch">Productomschrijving</th>
						<th class="small">Inhoud</th>
						<th class="small">Levering</th>
						<th class="small">Artikelnummer</th>
						<th colspan=3 class="small">Nodig</th>
					   
					</tr>
				</thead>
				<tbody>
						<?php
						$i=0;
						// echo '<pre>';
						// print_r($campagne['products']);
						// die();
			
						foreach($campagne['products'] as $item) {

							$i++;
							$stock = false;
							$included_products = json_decode($item['included_external_ids']);
							$included_external_products = json_decode($item['included_external_products']);
							
							$in_backorder = 0;
							if(array_key_exists('in_backorder', $item)) {
								$in_backorder = $item['in_backorder'];
							}
							$id_warning = '';
							if(($item['product_type'] === 'product' || $item['set_delivery'] === 'set') && empty($item['external_id'])){
								$id_warning = 'warning';
							}
							?>
							<tr class="campagne_product autoclick <?=$id_warning;?> search_row" data-campagne-product-id="<?=$item['id'];?>">
								<td class="id small"><?=return_index($i);?></td>
								<td class="text-overflow searchable" data-value="campagne_product_name"><?=return_str($item['name']);?></td>
								<td class="text-overflow extra small sm" data-value="campagne_product_content">
								<?php
									
									$item['variations'] = 1;
									if(!empty($item['variations_data'])) {
										$variations_data = json_decode($item['variations_data'], true);
										if(!empty($variations_data)) {
											$item['variations'] = count($variations_data);
										}
									}

									$product_description = "1 artikel";

									if($item['product_type'] === 'set' && $item['set_delivery'] === 'seperate') {
										$product_description = "Set van ".count($included_external_products) ." artikelen";
									}
									else if($item['product_type'] === 'set' && $item['set_delivery'] === 'set') {
										$product_description = "Complete set";
									}
									else if($item['product_type'] === 'product' && $item['variations'] > 1) { 
											$product_description = $item['variations'] ." varianten";
									}
									echo $product_description;
								?>
								</td>
								<td class="md extra small">
								<?php
								if($item['product_type'] === 'set' && count($included_external_products) > 0) {
									?>
									<select data-campagne-product-id="<?=$item['id'];?>" class="set_delivery in_row medium" data-value="campagne_product_set_delivery" style="min-width:100px !important;font-size:10px !important;margin-top:2px;;">
										<option value="set" <?=($item['set_delivery'] === 'set' ? 'selected' : '');?> style="font-size:10px !important;">Complete set</option>
										<option value="seperate" <?=($item['set_delivery'] === 'seperate' ? 'selected' : '');?> style="font-size:10px !important;">Losse artikelen</option>
									</select>
									<?php
								}
								else if($item['unit_quantity'] > 1) {
											echo '<span data-value="unit_quantity">Verpakt per <span class="quantity">' . trim($item['unit_quantity']) . '</span></span>';
									} 
								else {
									echo'-';
								}
								?>
								</td>
								<td>
									<input type="text" <?=($item['set_delivery'] === 'seperate' ? 'readonly' : '');?> data-campagne-product-id="<?=$item['id'];?>" value="<?=($item['set_delivery'] !== 'seperate' ? $item['external_id'] : '');?>" data-original-value="<?=($item['set_delivery'] !== 'seperate' ? $item['external_id'] : '');?>" class="campagne_product_external_id in_row id capitalize stocksearch <?=($item['set_delivery'] === 'seperate' ? 'inactive' : '');?> searchable" data-value="campagne_product_external_id" style="width:90px;">
								</td>
								<td class="quantity" data-value="campagne_product_quantity" data-val="<?=$item['quantity'];?>"><?php
								if($item['quantity'] > 0) {
									echo round($item['quantity']);
								}
								?></td>
								
								<td class="small" data-value="campagne_product_type_item">
								<?php
								$unit = "stuks";
								if($item['product_type']==='set') {
                                                                    $unit = "sets";
								} 
								echo $unit;
								?></td>
								<td class="status <?=$item['status']['icon']['class'];?>"  data-value="campagne_product_complete"><i class="material-icons"><?=$item['status']['icon']['html'];?></i></td>
							</tr> 
							<?php
						}
						?>
				</tbody>
			</table>
		</div>
		</form>
		<div class="col-4 right-menu ">
			<div class="sticky">
				<div id="backorders_container"></div>
			</div>
		</div>
	</div>
</div>
	
	
<script>
	$('#search').unbind('keyup').on('keyup', function(){
		
		var needle = $(this).val().toUpperCase();
		
		if(needle.length < 3) {
			$('.search_row').show();
			return true;
		}

		$('.search_row').hide();
		
		$('.searchable').each(function(){
										
			var haystack = $(this).html().toUpperCase();
			if(!haystack) {
				haystack = $(this).val().toUpperCase();
			}
			if(haystack.indexOf(needle) > -1) {
				$(this).closest('.search_row').show();
				// console.log(needle);
				// console.log(haystack);
			}

		});
	});
</script>
<?php
    $columns = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','x','y','z'];
?>
<div class="tab" data-tab-id="product-files" data-name="Productlijst<?= (count($campagne['product_files']) > 1 ? 'en' : ''); ?> (<?= count($campagne['product_files']); ?>)" data-icon="attach_file">	
   
    <div class="row">

        <div class="col-8">

            <h4>Aangeleverd (<?= count($campagne['product_files']); ?>)</h4>
            <hr>

            <?php
            $default = false;
            if (count($campagne['product_files']) > 0) {
                ?>

                <table border="0" cellpadding="0" cellspacing="0" >
                    <thead>
                        <tr>
                            <th class="id">#</th>
                            <th class="stretch">Naam bestand</th>
                            <th class="small">Added</th>
                            <th class="small quantity">Products</th>
                            <th colspan="<?php echo ($campagne['status'] === 'pending' ? '3' : '2'); ?>"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 0;
                        foreach ($campagne['product_files'] as $item) {
                            $i++;
                            ?>
                            <tr>
                                <td class="id small"><?= str_pad($i, 2, "0", STR_PAD_LEFT); ?></td>
                                <td class="stretch text-overflow">
                                    <a href="<?= CAMPAGNE_URL; ?>&product_file_id=<?= $item['id']; ?>"><?= $item['name']; ?></a>
                                <td class="date time"><?= $item['created_date']; ?></td>
                                <td style="text-align:right;padding-right:10px;"class="small sm text-overflow"><?= $item['num_products']; ?>&nbsp;artikelen</td>
                                <?php
                                //if($campagne['status'] === 'pending') {
                                ?>
                                    <td style="padding-left:0px;padding-right:0px !important;">
                                        <a href="<?= CAMPAGNE_URL; ?>&product_file_id=<?= $item['id']; ?>" class="in_row edit"><i class="material-icons">edit</i><span></span></a>
                                    </td>
                                <?php
                               // }
                                ?>
                                <td style="padding-left:0px;padding-right:0px !important;">
                                    <a href="<?= (str_replace('var/www/', '', DIR_PICKLIST_EXCEL_FILES) . $item['name']); ?>" class="in_row download"><i class="material-icons">save_alt</i><span></span></a>
                                </td>
                                <?php
                                if($campagne['status'] === 'pending') {
                                ?>
                                    <td style="padding-left:0px;padding-right:0px !important;">

                                        <a href="<?= CAMPAGNE_URL; ?>&delete_product_file_id=<?= $item['id']; ?>" class="in_row delete" onclick="return confirm('Weet u het zeker?');"><i class="material-icons">delete_forever</i><span></span></a>
                                    </td>
                                <?php
                                }
                                ?>
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
        
        
        <div class="col-4 right-menu">
        <?php
        
        if($campagne['status'] !== 'pending') {
            ?>
            <div class="sticky">
            <h4>Nieuwe Productlijst</h4>
            <hr>
            <p>De status is voorbij Ordermanagement, hierdoor kunnen er geen producten meer worden toegevoegd aan de campagne.</p>
            </div>
            <?php
        }
        else {
        ?>

    <div class="sticky">
        <h4>Nieuwe Productlijst</h4>
        <hr>
        <form method="post" enctype="multipart/form-data" id="product_file" style="margin: 0 auto;" onsubmit="return check_file_upload_form();">
            <div class="file-upload excel productlist">
                <div class="row">
                    <div class="col-12">
                        <h5><i class="material-icons"></i><span></span></h5>
                    </div>
                </div>
                <div class="row settings">

                    <div class="col-12">
                        <h4>Producten</h4>
                        <label class="select">Artikelnummers rij</label>
                        <select name="products_id_row_index" data-default="9">
                            <option value="">Selecteer een rij</option>
                            <?php
                            for ($i = 1; $i < 34; $i++) {
                                echo '<option value="' . $i . '" >' . $i . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">

                        <label class="select">Productnamen rij</label>
                        <select name="products_name_row_index" data-default="10">
                            <option value="">Selecteer een rij</option>
                            <?php
                            for ($i = 1; $i < 34; $i++) {
                                echo '<option value="' . $i . '" >' . $i . '</option>';
                            }
                            ?>
                        </select>
                    </div>



                    <div class="col-12">
                        <label class="select">Versie Details</label>
                        <select name="products_version_details_row_index" data-default="12">
                            <option value="">Selecteer een rij</option>
                            <?php
                            for ($i = 1; $i < 34; $i++) {
                                echo '<option value="' . $i . '" >' . $i . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="select">Aantal Versies</label>
                        <select name="products_version_row_index" data-default="27">
                            <option value="">Selecteer een rij</option>
                            <?php
                            for ($i = 1; $i < 34; $i++) {
                                echo '<option value="' . $i . '" >' . $i . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="select">Aantal per versie</label>
                        <select name="products_version_multiplier_row_index" data-default="28">
                            <option value="">Selecteer een rij</option>
                            <?php
                            for ($i = 1; $i < 34; $i++) {
                                echo '<option value="' . $i . '" >' . $i . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                            <label class="select">Verpakkingstype</label>
                            <select name="products_packaging_type_row_index" data-default="23">
                                <option value="">Selecteer een rij</option>
                                <?php
                                for ($i = 1; $i < 34; $i++) {
                                    echo '<option value="' . $i . '" >' . $i . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    <?php
                    if (USER_ID == 1) {
                        ?>
                        <div class="col-12">
                            <label class="select">Verpakkingseenheid</label>
                            <select name="products_unit_quantity_row_index" data-default="">
                                <option value="">Selecteer een rij</option>
                                <?php
                                for ($i = 1; $i < 34; $i++) {
                                    echo '<option value="' . $i . '" >' . $i . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <?php
                    }
                    ?>






                    <div class="col-12">
                        <h4>Filialen</h4>
                        <label class="select">Filiaalnamen rij</label>
                        <select name="locations_name_row_index" data-default="34">
                            <option value="">Selecteer een rij</option>
                            <?php
                            for ($i = 1; $i < 50; $i++) {
                                echo '<option value="' . $i . '" >' . $i . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row settings">
                    <div class="col-12">
                        <label class="select">Filialen vanaf</label>
                        <select name="locations_start_row_index" data-default="35">
                            <option value="">Selecteer een rij</option>
                            <?php
                            for ($i = 1; $i < 50; $i++) {
                                echo '<option value="' . $i . '" >' . $i . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="select">Filiaalnummer</label>
                        <select name="locations_id_column_index" data-default="A">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="select">Filiaalnaam</label>
                        <select name="locations_name_column_index" data-default="B">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '">' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="select">Adres</label>
                        <select name="locations_address_column_index" data-default="F">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '">' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label class="select">Huisnummer</label>
                        <select name="locations_address_number_column_index" data-default="">
                            <option value="">Selecteer een kolom</option>
                            <option value="">Niet gebruiken</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '">' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="select">Postcode</label>
                        <select name="locations_postal_code_column_index" data-default="I">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="select">Plaats</label>
                        <select name="locations_city_column_index" data-default="J">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="select">Winkeltype</label>
                        <select name="locations_formule_column_index" data-default="Q">
                            <option value="">Selecteer een kolom</option>
                            <option value="">Niet gebruiken</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="select">Rayonnr</label>
                        <select name="locations_rayon_column_index" data-default="">
                            <option value="">Selecteer een kolom</option>
                            <option value="">Niet gebruiken</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row settings">
                    <div class="col-12">
                        &nbsp;
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <input type="file" id="upload_productlist_input" name="product_file">
                        <div class="settings">
                            <input type="submit" id="upload_confirm" name="post_file" value="Bestand uploaden">
                            <input type="button" id="upload_cancel" value="Annuleren" onclick="$('#upload_productlist_input').val('').show().trigger('change');">
                        </div>
                    </div>
                </div>
            </div>
        </form>


        <h4>Nieuwe Variatielijst</h4>
        <hr>
        <form method="post" action="<?= CAMPAGNE_URL; ?>#product-files" enctype="multipart/form-data" id="variation_file" style="margin: 0 auto;" onsubmit="">
            <div class="file-upload excel">
                <div class="row">
                    <div class="col-12">
                        <h5><i class="material-icons"></i><span></span></h5>
                    </div>
                </div>


                <div class="row settings">

                    <div class="col-12">
                        <h4>Producten</h4>
                        <label class="select">Tabblab Producten</label>
                        <select name="variation_data_sheet_index" data-default="0">
                            <option value="">Selecteer een tabblab</option>
                            <?php
                            for ($i = 0; $i < 5; $i++) {
                                echo '<option value="' . $i . '" >Tabblab ' . ($i + 1) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="select">Product variant kolom</label>
                        <select name="variations_name_column_index" data-default="A">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <h4>Filialen</h4>
                        <label class="select">Tabblad Filialen</label>
                        <select name="location_data_sheet_index" data-default="1">
                            <option value="">Selecteer een tabblab</option>
                            <?php
                            for ($i = 0; $i < 5; $i++) {
                                echo '<option value="' . $i . '" >Tabblab ' . ($i + 1) . '</option>';
                            }
                            ?>
                        </select>
                    </div>



                    <div class="col-12">
                        <label class="select">Filiaal variant match kolom</label>
                        <select name="variantions_location_match_column" data-default="H">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="select">Filiaalnummer kolom</label>
                        <select name="locations_id_column_index" data-default="A">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="select">Filiaalnaam kolom</label>
                        <select name="locations_name_column_index" data-default="C">
                            <option value="">Selecteer een kolom</option>
                            <?php
                            foreach ($columns as $column) {
                                echo '<option value="' . strtoupper($column) . '" >' . strtoupper($column) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-12">
                        &nbsp;
                    </div>

                </div>

                <div class="row">
                    <div class="col-12">
                        <input type="file"  id="upload_variation_list" name="variation_list">
                        <div class="settings">
                            <input type="submit" name="upload_variation_list" value="Bestand uploaden">
                            <input type="button" value="Annuleren" onclick="$('#upload_variation_list').val('').show().trigger('change');">
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
    <?php
    }
    ?>
</div>
        
    
    
    
    </div>
</div>
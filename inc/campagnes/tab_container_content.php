<?php
if (array_key_exists('container', $campagne['distribution'])) {
    $container = $campagne['distribution']['container'];
?>
    <div class="tab" data-tab-id="boxes" data-name="Container inhoud" data-icon="move_to_inbox">
        <?php
        $disable_all_functions = false;
        if($campagne['status'] === 'fulfilment' && (count($campagne['picklists']) > 0 || count($campagne['picklist_station_cards']) > 0)) {
            $disable_all_functions = true;
        }
        
        if($disable_all_functions) {
            ?>
            <span class='warning'>
                <h1>
                    De orderstatus is reeds In Fulfilment
                </h1>
                <p>
                    Er zijn al picklijsten gegenereerd, hierdoor kan de container inhoud niet meer gewijzigd worden! Verwijder eerst de bestaande picklijsten of wijzig de orderstatus naar 'Handling' om toch wijzigen aan te brengen. Wanneer je de status wijzigd om aanpassingen te doen aan de container inhoud zullen reeds gegenereerde picklijsten en tafelkaarten automatisch worden verwijderd.
                </p>
            </span>           
            <?php
        }
       ?>
 <span class='warning bad_campgane_products' style="display: none;">
    <h1>
        Foute Campagne Producten gevonden!
    </h1>
    <p>
        Onderstaande artikelen zullen niet op de picklijst voorkomen!
    </p>
    <ul class='bad_campgane_products_items'></ul>
</span> 
        <?php
        if (!empty($campagne)) {
            ?>
            <div class="row" >
                <div class="col-4" style="overflow:auto;height:950px;">

                    <h4><?php echo  $container['name']; ?></h4>
                    <hr>

                    <table>
                        <thead>
                            <tr>
                                <th class="id">#</th>
                                <th>Inhoud container</th>
                                <th colspan=2 class="action-btns"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $boxnr = 1;
                            foreach ($container['boxes'] as $box) {
                                $box_id = 1;
                                foreach ($box['campagne_boxes'] as $campagne_box) {
                                    echo "<tr class='campagne_box ".($box_id === 1 ? 'autoclick' : '')."' data-campagne-box_id='" . $campagne_box['id'] . "'>";
                                    echo "<td class='id small'>" . return_index($boxnr) . "</td>";
                                    echo "<td class='stretch' data-value='box_name'>" . $campagne_box['name'] . "</td>";
                                    //echo "<td style='padding-left:0px;padding-right:0px !important;'><a data-campagne-box-id='" . $campagne_box['id']. "' href='#' class='in_row edit dc_campagne_box' onclick='return false;'><i class='material-icons'>edit</i><span></span></a></td>";
                                    if ($campagne['status'] !== 'fulfilment') {
                                        echo "<td style='padding-left:0px;padding-right:0px !important;'><a class='in_row edit' data-campagne-box-id='" . $campagne_box['id'] . "'><i class='material-icons' data-action='edit'>edit</i></a></td>";
                                        echo "<td style='padding-left:0px;padding-right:0px !important;'><a href='" . CAMPAGNE_URL . "&delete_campagne_box=" . $campagne_box['id'] . "#dcs' class='in_row delete' onclick=\"return confirm('Weet u het zeker?');\"><i class='material-icons'>delete_forever</i></a></td>";
                                    } else {
                                        echo"<td></td>";
                                    }
                                    echo "</tr>";
                                    $box_id++;
                                    $boxnr++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>

                    <br/>

                    <div class="inline_form">

                        <label>Doos toevoegen aan container</label>

                        <form method="POST" action="#boxes">
                            <div class="row">
                                <div class="col-6">
                                    <select name="container_box_id">
                                        <option value="">Selecteer een doos</option>
                                        <?php
                                        foreach ($container['boxes'] as $box) {
                                            if (count($box['campagne_boxes']) < $box['max_boxes'] && $box['max'] === 'false') {
                                                echo '<option value="' . $box['id'] . '">' . $box['name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-6" style="padding-left:0px;">
                                    <input type="submit" value="Doos toevoegen" name="add_campagne_box">
                                </div>
                            </div>
                        </form>

                    </div>

                    
                    <script>

                        $(function () {

                            $('.campagne_box').unbind('click').bind('click', function (event) {

                                if (!$(this).attr('data-campagne-box_id')) {
                                    return false;
                                }

                                $('.warning.bad_campgane_products').hide();
                                $('.bad_campgane_products_items li').remove();
                                
                                $('tr.campagne_box').removeClass('active');
                                $(this).addClass('active');

                                var campagne_box_id = $(this).attr('data-campagne-box_id');
                                var campagne_box_name = $(this).find('[data-value=box_name]').html();

                                var action = $(event.target).attr('data-action') || false;

                                if (action === 'edit' && campagne_box_id) {
                                    init_campagne_box(campagne_box_id);
                                }

                                $('.box_name').html(campagne_box_name);
                                $('ul.box_content').find('li').remove();
                                $('#box_content').attr('data-campagne-box-id', campagne_box_id);
                                $('#box_content').attr('data-campagne-box-name', campagne_box_name);

                                $.ajax({

                                    method: "POST",
                                    url: "search/campagne_box_products.php",

                                    data: {
                                        'owner_id': <?php echo OWNER_ID; ?>,
                                        'user_id': <?php echo USER_ID; ?>,
                                        'campagne_id': <?php echo CAMPAGNE_ID; ?>,
                                        'campagne_box_id': campagne_box_id
                                    }

                                }).done(function (data) {

                                    var result = JSON.parse(data);

                                    $('ul#box_content').find('li').remove();
                                    
                                    
                                    if(result.bad_campagne_products.length > 0) {   
                                        $('.bad_campgane_products_items li').remove();
                                        var n = 0;
                                        for(var index in result.bad_campagne_products) {
                                            n++;
                                            var item = result.bad_campagne_products[index];
                                            console.log(item['product_type']);
                                            var st = 'stuks';
                                            if(item['product_type'] === 'set') {
                                                st = 'sets';
                                            }
                                                
                                            $('.bad_campgane_products_items').append('<li><span>'+ n +'</span><strong>' + item['name'] + ':</strong><br/>Artikelnummer ontbreekt. Hiervan moeten er ' + item['quantity_allocated'] + ' ' + st + ' geleverd worden.</li>');
                                        }
                                        $('.warning.bad_campgane_products').show();
                                    }
                                    
                                    if (result.campagne_box_products.length > 0) {
                                        for (var index in result.campagne_box_products) {
                                            var item = result.campagne_box_products[index];
                                            $('ul#box_content').append('<li id="' + item.id + '" class="ui-state-default sortable-item stretch text-overflow"><span class="external_id">' + item.external_id + '</span>' + item.name + '</li>');
                                            if (item.num_products) {
                                                $('ul#box_content li#' + item.id).append('<span class="bullet products">' + item.num_products + '</span>');
                                            }
                                            /*if(item.num_variants) {
                                             $('ul#box_content li#' + item.id).append('<span class="bullet variants">' + item.num_variants + '</span>');
                                             }*/
                                        }
                                    } else {
                                        // alert('empty box ' +  campagne_box_name + ' (' + campagne_box_id + ')');
                                    }

                                    $('ul#available_products').find('li').remove();

                                    if (result.available_campagne_products.length > 0) {
                                        for (var index in result.available_campagne_products) {
                                            var item = result.available_campagne_products[index];
                                            $('ul#available_products').append('<li id="' + item.id + '" class="ui-state-default sortable-item stretch text-overflow"><span class="external_id">' + item.external_id + '</span>' + item.name + '</li>');
                                            if (item.num_products) {
                                                $('ul#available_products li#' + item.id).append('<span class="bullet products">' + item.num_products + '</span>');
                                            }
                                            /*if(item.num_variants) {
                                             $('ul#available_products li#' + item.id).append('<span class="bullet variants">' + item.num_variants + '</span>');
                                             }*/
                                        }
                                    }

                                });
                               <?php
                                if (!$disable_all_functions) {
                                ?>
                                $("#box_content, #available_products").sortable({
                                    connectWith: ".box_content",
                                    placeholder: "ui-state-highlight",
                                    update: function (event, ui) {
                                        save_campagne_box_order(campagne_box_id);
                                    }
                                }).disableSelection();
                                 <?php
                                }
                                ?>
                            });

                        });

                        function save_campagne_box_order(campagne_box_id) {

                            var bulk_id = 0;
                            var bulkdata = [];

                            var campagne_box_products = jQuery.unique($("#box_content").sortable("toArray"));
                            if (campagne_box_products.length === 0) {
                                campagne_box_products = [];
                            }
                            console.log(campagne_box_products);
                            campagne_box_products = JSON.stringify(campagne_box_products);
                            ajax_db_save('campagne_container_boxes', 'campagne_products', campagne_box_products, campagne_box_id);

                        }

                    </script>
                   
                </div>

                <div class="col-4" id="box">
                    <h4 class="box_name">In deze doos:</h4>
                    <hr>
                    <?php
                    if(USER_ID === '1' || 1 === 1) {
                    ?>
                    <style>
                        .dropzone {
                             border: 1px solid #eee;
                            width: 100%;
                            min-height: auto;
                            list-style-type: none;
                            margin: 0 auto 10px auto;
                            padding: 5px 0 0 0;
                            margin-right: 10px;
                        }
                        .dropzone div {
                            display: block;
                            position: relative;
                            margin: 0 5px 5px 5px;
                            padding: 0px;
                            line-height: 66px;
                            min-height: 66px;
                            font-size: 13px;
                            width: auto;
                            background: #ebf5ff;
                            color: #333;
                            border: 1px solid rgba(0,0,0,.1);
                        }
                        .dropzone.droppable div {
                            
                        }
                        
                        .dropzone div p {
                            position: absolute;
                            top: 50%;
                            left: 0;
                            right:0;
                            transform: translateY(-50%);
                            text-align:center;
                            color: #a0a0a0;
                        }
                        .dropzone.dropit div {
                            background: #9e9e9e;
                        }
                        .dropzone.dropit div p {
                            color: #CCC;
                        }
                    </style>
                    
                    <div class='dropzone' id='dropzone'>
                        <div>
                            <p>Drop items here!</p>
                        </div>
                    </div>
                    
                    <script>
                        var campagne_box_id = $('#box_content').attr('data-campagne-box-id');
                        var campagne_box_name = $('#box_content').attr('data-campagne-box-name');
                        $( "#dropzone" ).droppable({
                            accept: "ul#available_products .sortable-item",
                            classes: {
                              "ui-droppable-active": "droppable",
                              "ui-droppable-hover": "ui-state-hover"
                            },
                            out: function( event, ui ) {
                                $( this ).removeClass('dropit').find( "p" ).html( "Drop items here" );
                            },
                            over: function( event, ui ) {
                                $( this ).addClass('dropit').find( "p" ).html( "Drop it.." );
                            },
                            drop: function( event, ui ) {
                              $( this ).find( "p" ).html( "Dropped!." );
                              var campagne_box_id  = $('#box_content').attr('data-campagne-box-id');
                              $id = $(ui.draggable).attr('id');
                              
                              if($id) {
                                  console.log($id);
                                  $('ul#box_content').append('<li class="ui-state-default sortable-item stretch text-overflow" id='+ $id + '>' + $(ui.draggable).html() + '</li>');
                                  $(ui.draggable).remove();
                                  save_campagne_box_order(campagne_box_id);
                                  $( this ).removeClass('dropit').find( "p" ).html( "Drop items here" );
                              }
                              
                             
                            }
                          });
                    </script>
                    <?php
                    }
                    //
                    ?>
                   
                    <div style="display:block;overflow:auto;height:525px;padding: 0 15px 0px 0px;">
                        <ul id="box_content" class="box_content chips" style="min-height:600px;">
                        </ul>
                    </div>
                </div>

                <div class="col-4"  id="products">
                    <h4>Producten:</h4>
                    <hr>
                    <div style="display:block;overflow:auto;height:525px;padding: 0 15px 0px 0px;">
                        <ul id="available_products" class="box_content chips" style="min-height:600px;">
                        </ul>
                    </div>
                </div>

            </div>
        <?php
    }
    ?>
    </div>
    <?php
    }
    

<?php
if ($campagne['archive'] === "0" && $campagne['status'] === 'fulfilment') {
    echo '<div class="tab" data-tab-id="picklists" data-refresh-url="' . CAMPAGNE_URL . '&refresh=picklists" data-name="Picklijsten" data-icon="list_alt">';
} else {
    echo '<div class="tab" data-tab-id="picklists"  data-name="Picklijsten" data-icon="list_alt">';
}


if ($campagne['status'] !== 'fulfilment') {
    echo '<h4>Nog niet beschikbaar</h4><hr>';
    echo '<p>De status is nog niet "Fulfilment". Hierdoor kan de container inhoud nog wijzigen.</p>';
    echo '</div>';
}
if ($campagne['status'] === 'fulfilment') {

    $container = [
        'name' => $campagne['distribution']['container']['name'],
        'boxes' => []
    ];

    foreach ($campagne['distribution']['container']['boxes'] as $mainbox) {

        if (!array_key_exists('campagne_boxes', $mainbox)) {
            continue;
        }

        foreach ($mainbox['campagne_boxes'] as $box) {
            $container['boxes'][] = [
                'box_id' => 'B' . return_index($box['id']),
                'id' => $box['id'],
                'name' => $box['name']
            ];
        }
    }

    $picklists = get_campagne_picklists();
    ?>

    <script>
        function are_you_sure(question, url) {

            $('body').addClass('loading');

            var confirm = window.confirm(question);

            if (confirm) {
                window.location.href = url;
            } else {
                $('body').removeClass('loading modal-open');
                return false;
            }
        }
    </script>


 <?php
        if ($campagne['status'] === 'fulfilment') {
            ?>
            <div class="row">
                <div class="col-md-12">
                    <h4>Tafelkaarten Complete Campagne</h4>
                    <hr>
                    <?php
                    //arr($campagne['distribution']['container']['boxes']);
                    ?>
                    <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 15px auto;">
                        <thead>
                            <tr>
                                <th class="index small text-overflow">ID</th>
                                <th class="stretch small text-overflow">Naam Doos</th>
                                <th class="stretch small text-overflow">Stations</th>
                                <th class="stretch small text-overflow">Laatste update Doos</th>
                                <th class="stretch small text-overflow">Gebruiker</th>
                                <th class="stretch small text-overflow">Aangemaakt</th>
                                <th class="stretch small text-overflow" colspan="2">Gerbuiker</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $i = 0;
                            foreach ($campagne['distribution']['container']['boxes'] as $box_type_id => $box_type) {
                                if (count($box_type['campagne_boxes']) > 0) {
                                    foreach ($box_type['campagne_boxes'] as $_box) {
                                        if (count($_box['products']) > 0) {
                                            $i++;
                                            echo '<tr>';
                                            echo '<td class="index small">B' . $_box['id'] . '</td>';
                                            echo '<td class="stretch small text-overflow">' . $_box['name'] . '</td>';
                                            echo '<td class="stretch small text-overflow">' . count($_box['products']) . '</td>';
                                            echo '<td class="stretch small text-overflow">' . ($_box['update_warning'] === "1" ? "!!!" : "") . '' . $_box['last_update'] . '</td>';
                                            echo '<td class="stretch small text-overflow">' . $_box['blame_user'] . '</td>';
                                            echo '<td class="stretch small text-overflow">' . $_box['station_card_created'] . '</td>';
                                            echo '<td class="stretch small text-overflow">' . $_box['station_card_blame_user'] . '</td>';
                                            echo '<td style="min-width:140px;">';

                                            $station_card_id = (integer) $_box['station_card_id'];

                                            if (empty($station_card_id)) {
                                                $generate_url = CAMPAGNE_URL . "&generate_stations_card=1&boxes=" . $box_type['id'] . "&box_id=" . $_box['id'];
                                                echo "<a class='in_row generate text' href='" . $generate_url . "' ><i class='material-icons'>add_box</i><span>Genereren</span></a>";
                                            } else if (!empty($station_card_id)) {
                                                $file_url = "/picklists/templates/scripts/pdflib_campagne_station_cards.php?campagne_id=" . CAMPAGNE_ID . "&box_id=" . $_box['id'] . "&campagne_station_card_id=" . $station_card_id;
                                                echo "<div class='button-combined'>";
                                                echo "<a class='in_row download text' href='" . $file_url . "' target='_blank'><i class='material-icons'>save_alt</i><span>S" . return_index($station_card_id) . ".pdf</span></a>";
                                                echo "<a class='in_row delete_one' href='" . CAMPAGNE_URL . "&delete_station_cards=" . $station_card_id . "' onclick=\"return confirm('Weet u het zeker?');\"><i class='material-icons'>cancel</i></a>";
                                                echo "</div>";
                                            }


                                            echo '</td>';

                                            echo '</tr>';
                                        }
                                    }
                                }
                            }
                            ?>

                        </tbody>
                    </table>

                </div>
            </div>
            <?php
        }
        ?>

    <div class="row">
        <div class="col-12">	
            <div class="row">

    <?php
    ksort($account['dcs']);
    $x = 0;
    foreach ($account['dcs'] as $dc) {
        $x++;
        if (!array_key_exists($dc['id'], $campagne['distribution']['dcs'])) {
            continue;
        }
        ?>
                    <div class="col-md-12">

                        <h4>
                    <?= $dc['name']; ?>
        <?php
        if ($x === 1) {
            ?>
                                <input type="text" id="search_picklists" placeholder="Zoeken..." style="float:right;font-size:13px;padding: 0 5px;line-height:23px;">
                                <?php
                            }
                            ?>
                        </h4>
                        <hr>



                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 15px auto;">
                            <thead>
                                <tr>
                                    <th class="index small  text-overflow">ID</th>
                                    <th class="stretch small text-overflow">Leverdatum DC</th>
                                    <th class="stretch small text-overflow">Laaddatum BEK</th>
                                    <th class="small">Containers</th>
        <?php
        foreach ($container['boxes'] as $box) {
            ?>
                                        <th class="md small text-overflow">B<?= return_index($box['id']); ?> - <?= $box['name']; ?></th>
                                        <?php
                                    }
                                    ?>
                                    <th class="md small" colspan=2>Stickers</th>
                                </tr>
                            </thead>
                            <tbody>
        <?php
        foreach ($campagne['distribution']['dcs'][$dc['id']]['trucks'] as $truck) {

            $disabled = false;

            if (empty($truck['due_datetime'])) {

                $disabled = true;

                $truck['due_datetime'] = "Onbekend";
            } else {

                $truck['due_datetime'] .= ' uur';
            }


            $has_files = false;

// 'pending',
// 'ready-for-picking',
// 'picking',
// 'picked',
// 'waiting-for-delivery',
// 'delivered'

            $num_containers = count(json_decode($truck['containers']['filled'], true));
            ?>
                                    <tr class="searchable-row" data-search-id="truck_id_<?= $truck['id']; ?>">
                                        <td class="index small">T<?= return_index($truck['id']); ?></td>
                                        <td class="text-overflow"><?= $truck['due_datetime']; ?></td>
                                        <td class="md" data-datetime_id="loading_datetime_<?php echo $truck['id']; ?>" style="padding-right:0px !important;">
                                    <?php
                                    $ld = false;
                                    if (not_empty('loading_datetime', $truck) && !empty($truck['loading_datetime'])) {
                                        $loading_datetime = $truck['loading_datetime'];
                                        $ld = true;
                                    }
                                    ?>
                                            <a class="in_row datepicker <?php echo ($ld ? "date-set" : ""); ?>"  data-original="" 
                                               onclick="datepicker(
                                                               'datetime',
                                                               'campagne_dc_trucks',
                                                               'loading_datetime',
                                            <?php echo $truck['id']; ?>,
                                                               '<?php echo $truck['loading_date']; ?>',
                                                               '<?php echo $truck['loading_time']; ?>',
                                                               false,
                                                               ['loading_user_id'],
                                                               ['<?php echo USER_ID; ?>'],
                                                               [
                                                                   'delivery_note',
                                                                   'notitie'
                                                               ],
                                                               '',
                                                               'clear'
                                                               );" style="float: right;min-width:160px;"><i class="material-icons" style="font-size:16px;margin-right:6px;">date_range</i>
                                                <span class="datetime"><?php echo ($ld ? $loading_datetime . " uur" : ""); ?></span>
                                            </a>
                                        </td>
                                        <td class="quantity"><?= $num_containers; ?></td>
                                               <?php
                                               // arr(count(json_decode($truck['containers']['filled'], true)));
                                               foreach ($container['boxes'] as $box) {

                                                   $file_url = false;
                                                   $generate_url = CAMPAGNE_URL . "&generate_picklists=1&box_id=" . $box['id'] . "&truck_id=" . $truck['id'];

                                                   if (array_key_exists("" . $box['id'] . "", $picklists)) {
                                                       if (array_key_exists("" . $dc['id'] . "", $picklists["" . $box['id'] . ""]["dcs"])) {
                                                           if (array_key_exists("" . $truck['id'] . "", $picklists["" . $box['id'] . ""]["dcs"]["" . $dc['id'] . ""]["trucks"])) {
                                                               $picklist_id = $picklists["" . $box['id'] . ""]["dcs"]["" . $dc['id'] . ""]["trucks"]["" . $truck['id'] . ""];
                                                               $file_url = "/picklists/templates/scripts/pdflib_stations_overview.php?picklist_id=" . $picklist_id;
                                                               $has_files = true;
                                                           }
                                                       }
                                                   }
                                                   ?>
                                            <td>
                                            <?php
                                            if ($file_url) {

                                                echo "<div class='button-combined'>";
                                                echo "<a class='in_row download text' href='" . $file_url . "' target='_blank'><i class='material-icons'>save_alt</i><span>P" . return_index($picklist_id) . ".pdf</span></a>";
                                                echo "<a class='in_row delete_one' onclick=\"return are_you_sure('Weet u het zeker?', '" . CAMPAGNE_URL . "&delete_picklist_id=" . $picklist_id . "');\"><i class='material-icons'>cancel</i></a>";
                                                echo "</div>";
                                            } else {

                                                if (!$disabled) {
                                                    echo "<a class='in_row generate text' href='" . $generate_url . "' ><i class='material-icons'>add_box</i><span>Genereren</span></a>";
                                                } else {
                                                    echo '-';
                                                }
                                            }
                                            ?>
                                            </td>
                                                <?php
                                            }
                                            ?>
                                        <td>
                                            <?php
                                            if ($has_files) {
                                                $file_url = "/picklists/templates/scripts/pdflib_picklist_locations_containers.php?campagne_id=" . CAMPAGNE_ID . "&truck_id=" . $truck['id'];
                                                echo "<a class='in_row download text' style='min-width:100%;' href='" . $file_url . "' target='_blank'><i class='material-icons'>save_alt</i><span>T" . return_index($truck['id']) . ".pdf</span></a>";
                                            } else {
                                                echo "-";
                                            }
                                            ?></td>
                                        <td style="padding: 0px !important; width:20px;">
                                        <?php
                                        if ($has_files) {
                                            ?>
                                                <a class="in_row delete" onclick="return are_you_sure('Weet u het zeker?', '<?= CAMPAGNE_URL; ?>&delete_picklist_truck=<?= $truck['id']; ?>');"><i class="material-icons">delete_forever</i><span></span></a>
                                                <?php
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                            <?php
                                        }
                                        ?>
                            </tbody>
                        </table>

                    </div>
                                        <?php
                                    }
                                    ?>




                            <?php
                            //  arr($campagne);



                            foreach ($campagne['distribution']['logistic_centers'] as $lc) {

                                $num_containers = count(json_decode($lc['location_ids'], true));
                                if ($num_containers === 0) {
                                    continue;
                                }
                                ?>
                    <div class="col-md-12">

                        <h4><?= $lc['data']['name']; ?></h4>
                        <hr>

                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 15px auto;">
                            <thead>
                                <tr>
                                    <th class="index small text-overflow">ID</th>
                                    <th class="stretch small text-overflow">Bestemming</th>
                                    <th class="stretch small text-overflow">Containers</th>
                    <?php
                    foreach ($container['boxes'] as $box) {
                        ?>
                                        <th class="md small  text-overflow">B<?= return_index($box['id']); ?> - <?= $box['name']; ?></th>
            <?php
        }
        ?>
                                    <th class="md small" colspan="2">Stickers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="searchable-row" data-search-id="lc_id_<?= $lc['data']['id']; ?>">
                                    <td class="index small">LC<?= return_index($lc['data']['id']); ?></td>
                                    <td class="small"><?= $lc['data']['name']; ?></td>
                                    <td class="small quantity"><?= $num_containers; ?></td>
                                    <?php
                                    $has_files = false;
                                    foreach ($container['boxes'] as $box) {

                                        $file_url = false;


                                        if (array_key_exists("" . $box['id'] . "", $picklists)) {
                                            if (array_key_exists("" . $lc['data']['id'] . "", $picklists["" . $box['id'] . ""]["lcs"])) {
                                                if (array_key_exists("trucks", $picklists["" . $box['id'] . ""]["lcs"]["" . $lc['data']['id'] . ""])) {
                                                    $picklist_id = $picklists["" . $box['id'] . ""]["lcs"]["" . $lc['data']['id'] . ""]["trucks"][0];
                                                    $file_url = "/picklists/templates/scripts/pdflib_stations_overview.php?picklist_id=" . $picklist_id;
                                                }
                                            }
                                        }
                                        ?>
                                        <td>
                                        <?php
                                        $generate_url = CAMPAGNE_URL . "&generate_picklists=1&box_id=" . $box['id'] . "&lc_id=" . $lc['data']['id'];

                                        if ($file_url) {
                                            $has_files = true;
                                            echo "<div class='button-combined'>";
                                            echo "<a class='in_row download text' href='" . $file_url . "' target='_blank'><i class='material-icons'>save_alt</i><span>P" . return_index($picklist_id) . ".pdf</span></a>";
                                            echo "<a class='in_row delete_one' href='" . CAMPAGNE_URL . "&delete_picklist_id=" . $picklist_id . "' onclick=\"return confirm('Weet u het zeker?');\"><i class='material-icons'>cancel</i></a>";
                                            echo "</div>";
                                        } else {
                                            echo "<a class='in_row generate text' href='" . $generate_url . "' ><i class='material-icons'>add_box</i><span>Genereren</span></a>";
                                        }
                                        ?>
                                        </td>
                                            <?php
                                        }
                                        ?>
                                    <td>
                                        <?php
                                        if ($has_files) {
                                            $file_url = "/picklists/templates/scripts/pdflib_picklist_locations_containers.php?campagne_id=" . CAMPAGNE_ID . "&lc_id=" . $lc['data']['id'];
                                            echo "<a class='in_row download text' style='min-width:100%;' href='" . $file_url . "' target='_blank'><i class='material-icons'>save_alt</i><span>LC" . return_index($lc['data']['id']) . ".pdf</span></a>";
                                        } else {
                                            echo "-";
                                        }
                                        ?>
                                    </td>
                                    <td style="padding: 0px !important; width:20px;">
                                        <?php
                                        if ($has_files) {
                                            ?>
                                            <a href="<?= CAMPAGNE_URL; ?>&delete_picklist_lc=<?= $lc['data']['id']; ?>#picklists" class="in_row delete" onclick="return confirm('Weet u het zeker?');"><i class="material-icons">delete_forever</i><span></span></a>
                                        <?php
                                    }
                                    ?>	
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                                        <?php
                                    }
                                    ?>

            </div>
        </div>
    </div>

    </div>
                                    <?php
                                }
                                ?>
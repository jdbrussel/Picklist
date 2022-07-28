<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <link rel="stylesheet"  href="css/main.css" >
        <link href="https://fonts.googleapis.com/css?family=Archivo+Narrow|Raleway|Roboto" rel="stylesheet">
        <script src="libs/JQSortable/sortable.min.js"></script>
        <link rel="stylesheet" href="libs/JQSortable/sortable-theme-minimal.css" />
        <script src="libs/cmykW3Js/w3color.js"></script>
        <?php
        require_once 'file_upload.php';
        require_once 'libs/GuzzleHttp/autoloader.php';
        ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <script>
            $(function () {
                w3SetColorsByAttribute();

            });
            function number_format(number, decimals, dec_point, thousands_sep) {

                var checkint = number / 2;
                if (!checkint) {
                    // console.log(checkint  + ' -> ' + number);
                    return number;
                }


                // Strip all characters but numerical ones.
                number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
                var n = !isFinite(+number) ? 0 : +number,
                        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                        s = '',
                        toFixedFix = function (n, prec) {
                            var k = Math.pow(10, prec);
                            return '' + Math.round(n * k) / k;
                        };
                // Fix for IE parseFloat(0.55).toFixed(0) = 0;
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            }

            function check_file_upload_form() {

                error = [];

                if ($('[name=products_name_row_index]').val() === '') {
                    error.push('products_name_row_index');
                }
                if ($('[name=products_id_row_index]').val() === '') {
                    error.push('products_id_row_index');
                }
                if ($('[name=locations_name_row_index]').val() === '') {
                    error.push('locations_name_row_index');
                }
                if ($('[name=locations_start_row_index]').val() === '') {
                    error.push('locations_start_row_index');
                }
                if ($('[name=locations_id_column_index]').val() === '') {
                    error.push('locations_id_column_index');
                }
                if ($('[name=locations_name_column_index]').val() === '') {
                    error.push('locations_name_column_index');
                }
                if (error.length > 0) {
                    console.log(error);
                    $('[name=' + error[0] + ']').focus();
                    alert('Vul alles in aub');
                    return false;
                }

                return true;
            }

            function window_init() {
                $('#main-content, #debug').css({
                    'min-height': $(window).height()
                }).show();
            }

            $(function () {

                window_init();

                $(window).resize(function () {
                    window_init();
                });

                var hash = window.location.hash.replace('#', '');

                $('div.tabs').each(function () {

                    var tabs = this;
                    var tabdefault = false;

                    if (!tabdefault && $(tabs).attr('data-default')) {
                        tabdefault = $(tabs).attr('data-default');
                    }

                    $(tabs).prepend('<ul class="tabnav"></ul>');

                    if ($(tabs).attr('data-back') !== undefined) {
                        $(tabs).find('.tabnav').append('<li class="navitem back-button" data-id="" data-back="' + $(tabs).attr('data-back') + '"><i class="material-icons">keyboard_arrow_left</i>Terug</li>');
                    }

                    var tabids = [];
                    var tabnames = [];
                    var tabicons = [];
                    var tabactions = [];
                    var tabrefreshurls = [];

                    $(tabs).children('.tab').each(function () {

                        var tab = this;

                        var tabid = $(tab).attr('data-tab-id');
                        if (tabid) {
                            tabids.push(tabid);
                        }

                        var tabname = $(tab).attr('data-name');
                        if (tabname) {
                            tabnames.push(tabname);
                        }

                        var tabicon = $(tab).attr('data-icon');
                        if (tabicon) {
                            tabicons['' + tabname + ''] = tabicon;
                        }

                        var tabaction = $(tab).attr('data-action');
                        if (!tabaction) {
                            tabaction = '';
                        }
                        tabactions.push(tabaction);

                        var tabrefreshurl = $(tab).attr('data-refresh-url');
                        if (!tabrefreshurl) {
                            tabrefreshurl = '';
                        }
                        tabrefreshurls.push(tabrefreshurl);

                    });
                    
                    for (var i in tabnames) {

                        var icon = '';

                        if (tabicons['' + tabnames[i] + '']) {
                            var icontext = tabicons['' + tabnames[i] + ''];
                            icon = '<i class="material-icons">' + icontext + '</i>';
                        }

                        $(tabs).find('.tabnav').append('<li class="navitem" data-id="' + tabids[i] + '"  data-name="' + tabnames[i] + '" data-refresh-url="' + tabrefreshurls[i] + '" data-action="' + tabactions[i] + '">' + icon + tabnames[i] + '</li>');

                        $(tabs).find('.back-button').unbind('click').bind('click', function () {
                            document.location.href = $(this).attr('data-back');
                        });

                    }

                    $(tabs).find('.navitem:not(.back-button)').unbind('click').bind('click', function () {

                        var tabname = $(this).attr('data-name');
                        var tabid = $(this).attr('data-id');
                        var tabaction = $(this).attr('data-action');
                        var refresh_url = $(this).attr('data-refresh-url');
                        var hashtag = window.location.hash.replace('#', '');

                        if (hashtag !== tabid) {
                            if (!refresh_url) {
                            }
                            if (refresh_url) {
                                document.location.href = refresh_url;
                                return true;
                            }
                        }

                        $(this).parent('ul').parent('.tabs').find('.active').removeClass('active');
                        $(this).parent('ul').parent('.tabs').find('[data-name="' + tabname + '"]').addClass('active');

                        init_sheets();

                        window.location.hash = '#' + tabid;

                        $('.search, #search').val('').trigger('keyup');
                        
                        return false;

                    });

                    if (!tabdefault) {
                        $(tabs).find('.tab').removeClass('active');
                        $(tabs).find('[data-name="' + tabnames[0] + '"]').addClass('active');
                    } else {
                        $('.navitem[data-id="' + tabdefault + '"]').trigger('click');
                    }
                    if (hash && $('.tab[data-tab-id=' + hash + ']').length === 1) {
                        $('.navitem[data-id="' + hash + '"]').trigger('click');
                    }
                    
                });



                $('.file-upload').each(function () {
                    if ($(this).hasClass('excel')) {
                        $(this).find('input[type=file]').attr('accept', '.xls,.xlsx');
                    } else if ($(this).hasClass('pdf')) {
                        $(this).find('input[type=file]').attr('accept', '.pdf');
                    } else if ($(this).hasClass('image')) {
                        $(this).find('input[type=file]').attr('accept', '.pdf,.jpeg,.jpg,.png');
                    }
                });

                function reset_upload_form() {
                    $('.file-upload').find('h5 span').html('Upload een nieuw bestand');
                    $('.file-upload').find('h5 i').text('cloud_upload');
                    $('.file-upload').find('.settings, .upload_options').hide();
                }

                $('.file-upload input[type=file]').each(function () {

                    reset_upload_form();

                    $div = $(this);

                    $(this).unbind('change').bind('change', function () {

                        var num_selected = $(this)[0].files.length;

                        $form = $(this).closest('form');

                        reset_upload_form();

                        if (num_selected === 1) {

                            $form.find('select').val('');
                            $form.find('h5 span').html($(this)[0].files[0].name);
                            $form.find('.settings, .upload_options').show();
                            $form.find('h5 i').text('cloud_done');

                            $(this).hide();

                            $form.find('select').each(function () {
                                if ($(this).attr('data-default')) {
                                    $(this).val('' + $(this).attr('data-default') + '');
                                }
                            });

                        } else {

                            reset_upload_form();

                            $(this).show();

                        }

                    });

                });

                $('input[type=file].auto-upload').each(function () {
                    $input = $(this);
                    $input.unbind('change').bind('change', function () {
                        var num_selected = $(this)[0].files.length;
                        if (num_selected === 1) {
                            $('body').addClass('loading');
                            var filename = $(this)[0].files[0].name;
                            if (filename) {
                                console.log($(this).attr('data-id'));
                                $('#' + $(this).attr('data-id') + '').submit();
                                console.log(filename);
                            }
                        }
                    });
                });

                $('td.space').each(function () {
                    $(this).html($(this).html().replace(' ', '&nbsp;'));
                });
                $('td.text-overflow, td.note').each(function () {
                    $(this).html('<span>' + $(this).html() + '</span>');
                });
                $('input.quantity').each(function () {
                    var val = $(this).val().replace(/\./g, '');
                    $(this).val(number_format(val, 0, '', '.'));
                });
                $('input[type=text].quantity').unbind('blur').bind('blur', function () {
                    var val = $(this).val().replace(/\./g, '');
                    $(this).val(number_format(val, 0, '', '.'));
                });

                $('.autosubmit').each(function () {
                    $(this).bind('change', function () {
                        $(this).parent('form').submit();
                    });
                });


                $(".stocksearch").autocomplete({

                    source: "search/searchstock.php",
                    minLength: 2,
                    search: function (event, ui) {

                        var external_id = $(this).val();
                        $(this).removeClass('invalid');

                        if (!external_id.match(/<?= PRODUCT_EXTERNAL_ID_PATTERN; ?>/gmi)) {
                            $(this).addClass('invalid');
                        }

                    },
                    select: function (event, ui) {

                        $(this).removeClass('invalid');
                        $(this).blur();
                        // var campagne_product_id = $(this).attr('data-campagne-product-id');

                        // $target = $('[name="in_stock['+ campagne_product_id + ']"').removeClass('negative');
                        // $target.val(ui.item.in_stock);

                        // if(ui.item.in_stock < 0) {
                        //   $target.addClass('negative');
                        // }

                    }
                });

                $('input[type=submit], a:not(.delete, .delete_one, .download, .datepicker, .campagne_box_edit)').unbind('click').bind('click', function () {
                    $('body').addClass('loading');
                });

                init_sheets();

                $('body').removeClass('loading');

            });

            function init_campagne_box(campagne_box_id) {

                $('#modal-header, .row.form').html('<div class="row"><div class="col-12">Bezig met laden</div></div>');
                $('body').addClass('modal-open');

                $.ajax({
                    method: "POST",
                    url: "ajax/fetch.php",
                    data: {
                        'client_id': '<?= OWNER_ID; ?>',
                        'account_id': '<?= ACCOUNT_ID; ?>',
                        'user_id': '<?= USER_ID; ?>',
                        'table': 'campagne_container_boxes',
                        'id': campagne_box_id
                    }
                }).done(function (results) {

                    var result = JSON.parse(results);

                    $('#modal-header').html(result.item.name);
                    $('.row.form').html('<div class="col-12" id="campagne_box_data"></div>');
                    $('#campagne_box_data').append('<input type="hidden" id="campagne_box_id" value="' + result.item.id + '">');
                    $('#campagne_box_data').append('<label>Naam:</label><input type="text" id="campagne_box_name" value="' + result.item.name + '">');

                    $('#modal-save').unbind('click').bind('click', function () {

                        var box_id = $('#campagne_box_id').val();
                        var box_name = $('#campagne_box_name').val();

                        var columns = Array();
                        columns.push('name');

                        var values = Array();
                        values.push(box_name);

                        $.ajax({
                            method: "POST",
                            url: "ajax/save.php",
                            data: {
                                'owner_id': <?= OWNER_ID; ?>,
                                'user_id': <?= USER_ID; ?>,
                                'table': 'campagne_container_boxes',
                                'columns': columns,
                                'data': values,
                                'id': box_id
                            }
                        }).done(function (results) {

                            var result = JSON.parse(results);
                            console.log(result);

                            $('body').removeClass('loading modal-open');

                            $('tr.campagne_box[data-campagne-box_id=' + box_id + ']').find('[data-value=box_name]').html("" + box_name + "");
                            $('.box_name').html("" + box_name + "");
                        });
                    });

                });

                $('.close-modal').unbind('click').bind('click', function () {
                    $('body').removeClass('loading modal-open');
                });

            }

            function init_sheets() {

                $('.sheet-container').each(function () {
                    $(this).find('.sheet').removeClass('current').first().addClass('current');
                });
                $('ul.sheet-selector').each(function () {
                    $(this).find('li a.sheet-selector').removeClass('current').first().addClass('current');
                });

                $('a.sheet-selector').bind('click', function () {
                    $('a.sheet-selector').removeClass('current');
                    $(this).addClass('current');
                    $('.sheet').removeClass('current');
                    $('[data-id="sheet-' + $(this).attr('data-id') + '"]').addClass('current');
                    $('body').removeClass('loading');
                });

                $(function () {
                    if ($('.tab.active').find('.autoclick').length > 0) {
                        $('.tab.active').find('.autoclick').first().trigger('click');
                    }
                });

            }

            function ajax_db_save(table, columns, values, id = false, after_insert = false) {

                if (typeof columns === 'string' && typeof values === 'string') {
                    columns = [columns];
                    values = [values];
                }

                $.ajax({
                    method: "POST",
                    url: "ajax/save.php",
                    data: {
                        'owner_id': <?= OWNER_ID; ?>,
                        'user_id': <?= USER_ID; ?>,
                        'table': table,
                        'columns': columns,
                        'data': values,
                        'id': id
                    }
                }).done(function (results) {

                    var result = JSON.parse(results);

                    console.log(result);

                    if (result.insert_id && typeof result.inserted_item === 'object') {

                        if (table === 'groups') {

                            var item = after_insert.data;

                            item.id = result.insert_id;
                            item.group_address_id = result.group_address_id;

                            item.data = {
                                'group_id': result.insert_id,
                                'group_address_id': result.group_address_id || false
                            };

                            if (after_insert.name === 'init_locations_modal') {
                                init_locations_modal(JSON.stringify(item));
                            }

                        }
                    } else {

                        if (columns.includes('included_products_string')) {
                            version_modal_open(id)
                        } else {
                            $('body').removeClass('loading modal-open');
                            if (table === 'campagne_container_boxes') {
                                // campagne_container_box_fetch(id);
                            }

                            if (table === 'campagne_products') {
                                campagne_product_fetch(id);
                            }
                        }



                    }
                });
            }

            $(function () {

                $('#toggle-debug').unbind('click').bind('click', function () {

                    $('#debug').toggleClass('open');
                    $(this).toggleClass('open');
                    if ($('#debug').hasClass('open')) {
                        $(this).html('chevron_right');
                    } else {
                        $(this).html('chevron_left');
                    }

                });

            });

            function new_input() {

                $('.external_ids.new').unbind('keydown, blur').bind('keydown', function () {
                    if ($(this).val() && $('.new_2').length === 0) {
                        $(this).after('<input class="stocksearch capitalize external_ids new_2" type="text" value="">');
                    }
                }).bind('blur', function () {
                    if ($(this).val()) {
                        $('.new').removeClass('new');
                        $('.new_2').removeClass('new_2').addClass('new');
                        new_input();
                    }
                });
                $(".stocksearch").autocomplete({
                    source: "search/searchstock.php",
                    minLength: 2
                });

            }
            
            function save_date(date, time) {
                alert(date + ' ' + time);
            }

            function datepicker(type, table, column, id, date, time, insertnew = false, insertcolumns = [], insertvalues = [], extracolumn = [], extravalue = false, deleteoption = false) {

                $('#modal-header, .row.form').html('');
                $('.delete-modal, .clear-modal').remove();

                if (!type) {
                    $('body').removeClass('modal-open');
                    return false;
                }

                var temp_id = false;
                var insert_new = false;

                var original = false;
                if (id) {
                    $object = $('[data-datetime_id="' + column + '_' + id + '"]');
                    original = $object.find('a.datepicker').attr('data-original');
                }

                if ((insertnew === 'true' && table) && (insertcolumns.length > 0 && insertvalues.length > 0)) {
                    temp_id = id;
                    id = false;
                    insert_new = true;
                }


                if (deleteoption === 'clear' && (date || time)) {

                    $('.row.buttons #cancel').after('<input type="button" class="clear-modal" id="clear" value="Datum wissen">');
                    $('#clear').unbind('click').bind('click', function () {

                        var confirm_delete = confirm('Weet je zeker dat je deze wilt wissen?');

                        if (!confirm_delete) {
                            return false;
                        }

                        $('#selected_date').val('NULL');
                        $('#selected_time').val('NULL');
                        $('#delivery_note').html('');

                        $('.ui-selected').removeClass('ui-selected');
                        $('.ui-state-active').removeClass('ui-state-active');

                        $('#modal-save').trigger('click');

                    });

                } else if (deleteoption === 'true' && table && id) {

                    $('.row.buttons #cancel').after('<input type="button" class="delete-modal" id="delete" value="Verwijderen">');

                    $('.delete-modal').unbind('click').bind('click', function () {

                        var confirm_delete = confirm('Weet je zeker dat je deze wilt verwijderen?');

                        if (!confirm_delete) {
                            return false;
                        }

                        $.ajax({
                            method: "POST",
                            url: "ajax/delete.php",
                            data: {
                                'owner_id': <?= OWNER_ID; ?>,
                                'user_id': <?= USER_ID; ?>,
                                'table': table,
                                'id': id
                            }
                        }).done(function (results) {

                            var result = JSON.parse(results);

                            if (result.deleted_id) {

                                $object = $('[data-datetime_id="' + column + '_' + result.deleted_id + '"]');
                                $object.find('.datetime').html('-').val('-');
                                $object.find('a.datepicker').removeClass('date-set').unbind('click').bind('click', function () {

                                    datepicker(
                                            '' + type + '',
                                            '' + table + '',
                                            '' + column + '',
                                            'del_' + result.deleted_id,
                                            '',
                                            '',
                                            'true',
                                            insertcolumns,
                                            insertvalues,
                                            extracolumn,
                                            '',
                                            '' + deleteoption + ''
                                            );

                                });

                                $object.attr('data-datetime_id', '' + column + '_del_' + result.deleted_id + '');

                                datepicker();

                            }

                        });

                    });
                }

                $('#modal-header').html('Pick date/time');

                if (original && !date) {
                    $('#modal-header').html(original);
                } else {
                    $('#modal-header').html('Change date/time');
                }

                $('#modal-container .row.form').append('<div class="col-6" id="date"><div id="datepicker"><label>Datum:</label></div></div><div class="col-6" id="time" style="padding-right:0px;"><input type="hidden" id="selected_date" value="' + date + '"><input type="hidden" id="selected_time" value="' + time + '"><label>Tijd:</label><ul id="hourpicker" class="chips time"></ul></div>');

                for (var h = 6; h <= 18; h++) {
                    var hour = h;
                    if (h < 10) {
                        hour = '0' + hour;
                    }
                    var hour_whole = hour + ':00';
                    $('#hourpicker').append('<li class="ui-widget-content time" data-value="' + hour_whole + '" style="padding-left: 7px">' + hour_whole + ' uur</li>');
                }

                $('#hourpicker').before('<input type="text" id="exact_time" value="' + time + '">');

                $('body').addClass('modal-open');

                $('#datepicker').datepicker({
                    numberOfMonths: 1,
                    dateFormat: "dd/mm/yy",
                    firstDay: 1,
                    dayNamesMin: ["Zo", "Ma", "Di", "Wo", "Do", "Vr", "Za"],
                    onSelect: function (str, obj) {
                        $('#selected_date').val('' + str + '');
                    }
                });

                if (date) {
                    $('#datepicker').datepicker("setDate", date);
                }

                if (extracolumn.length > 0) {
                    if (!extravalue) {
                        extravalue = '';
                    }
                    $('#datepicker').append('<br><label>' + extracolumn[1] + ':</label><textarea id="' + extracolumn[0] + '" style="width:107% !important;min-height: 133px;margin:0 -15px 0 0px !important;">' + extravalue + '</textarea>');
                }

                function _return_time(time_in, _return = "hour_min") {
                    var time = time_in.split(':');
                    var hour = false;
                    time[0] = parseInt(time[0]);
                    if (time[0] > -1 && time[0] < 24) {
                        hour = time[0];
                    }
                    if (parseInt(hour) < 10) {
                        hour = '0' + time[0];
                    }
                    var min = '';
                    if (time[1] > -1 && time[1] < 59) {
                        min = time[1];
                    }
                    if (!hour) {
                        return time_in;
                    }
                    if (_return === "hour_min") {
                        return hour + ':' + min;
                }
                }


                $('#exact_time').unbind('keyup').bind('keyup', function () {

                    var time = _return_time($(this).val());
                    $(this).val(time);

                    $('.time').removeClass('ui-selected');

                    if (time) {
                        $('.time[data-value="' + time + '"]').addClass('ui-selected');
                    }
                });

                $('#hourpicker').selectable({
                    stop: function (event, ui) {
                        var time = $(this).find('.ui-selected').last().attr('data-value');
                        if (time) {
                            $('#selected_time').val('' + time + '');
                            $('#exact_time').val('' + time + '');
                        }
                    }
                });

                if (time) {
                    $('li.time[data-value="' + time + '"]').addClass('ui-selected');
                }


                $('#modal-save').unbind('click').bind('click', function () {

                    var selected_date = $('#selected_date').val();
                    var selected_time = $('#exact_time').val();


                    if (!selected_date) {
                        alert('Selecteer een datum');
                        return;
                    } else if (type === 'datetime' && !selected_time) {
                        alert('Selecteer een tijd');
                        return;
                    }

                    if (selected_date !== 'NULL') {
                        var d = selected_date.split('/');
                        var date = d[2] + '-' + d[1] + '-' + d[0];
                        var datetime = date;
                        if (selected_time) {
                            datetime += ' ' + selected_time + ':00';
                        }
                    } else {
                        datetime = 'NULL'
                    }


                    var updatecolumns = ['' + column + ''];
                    var updatevalues = ['' + datetime + ''];

                    if (extracolumn.length > 0) {

                        var extracolumn_name = extracolumn[0];
                        var extracolumn_description = extracolumn[1];
                        var extravalue = $('#' + extracolumn_name + '').val();

                        updatecolumns.push('' + extracolumn_name + '');
                        updatevalues.push(extravalue);

                    }

                    var value_str = selected_date + ' ' + selected_time + ' uur';

                    if (insert_new) {

                        if ((type === 'datetime' && selected_date && selected_time) || (type === 'date' && selected_date)) {

                            insertcolumns.push('' + column + '');
                            insertvalues.push('' + datetime + '');

                            if (extravalue) {
                                insertcolumns.push('' + extracolumn_name + '');
                                insertvalues.push('' + extravalue + '');
                            }

                            $.ajax({
                                method: "POST",
                                url: "ajax/insert.php",
                                data: {
                                    'owner_id': <?= OWNER_ID; ?>,
                                    'user_id': <?= USER_ID; ?>,
                                    'table': table,
                                    'columns': insertcolumns,
                                    'data': insertvalues,
                                    'id': 'insert'
                                }
                            }).done(function (results) {

                                var result = JSON.parse(results);

                                if (result.insert_id) {

                                    $object = $('[data-datetime_id="' + column + '_' + temp_id + '"]');
                                    $object.attr('data-datetime_id', column + '_' + result.insert_id);
                                    $object.find('.datetime').html(value_str).val(value_str);

                                    $object.find('a.datepicker').addClass('date-set').unbind('click').bind('click', function () {

                                        datepicker(
                                                '' + type + '',
                                                '' + table + '',
                                                '' + column + '',
                                                '' + result.insert_id + '',
                                                '' + selected_date + '',
                                                '' + selected_time + '',
                                                'false',
                                                insertcolumns,
                                                insertvalues,
                                                extracolumn,
                                                '' + extravalue + '',
                                                '' + deleteoption + ''
                                                );

                                    });

                                    datepicker();

                                }
                            });

                        }
                        return;
                    } else if (!insert_new) {

                        for (var i in insertcolumns) {
                            updatecolumns.push(insertcolumns[i]);
                            updatevalues.push(insertvalues[i]);
                        }

                        ajax_db_save(table, updatecolumns, updatevalues, id);

                        $object = $('[data-datetime_id="' + column + '_' + id + '"]');
                        $object.find('.datetime').html(value_str).val(value_str);

                        $object.find('a.datepicker').addClass('date-set').unbind('click').bind('click', function () {

                            datepicker(
                                    '' + type + '',
                                    '' + table + '',
                                    '' + column + '',
                                    '' + id + '',
                                    '' + selected_date + '',
                                    '' + selected_time + '',
                                    'false',
                                    insertcolumns,
                                    insertvalues,
                                    extracolumn,
                                    '' + extravalue + '',
                                    '' + deleteoption + ''
                                    );

                        });
                        if (datetime === 'NULL') {
                            $object.find('a.datepicker').removeClass('date-set');
                            $object.removeClass('date-set').find('.datetime').html('' + $object.find('a.datepicker').attr('data-original') + '').val(value_str);
                        }
                        datepicker();

                    }

                });

                $('#cancel, .close-modal').val('Annuleren').unbind('click').bind('click', function () {
                    datepicker();
                });
            }

            function init_campagnebox_modal(campagne_box_data) {

                $('#modal-header, .row.form').html('');

                if (!campagne_box_id) {
                    $('body').removeClass('modal-open');
                    return false;
                }

            }

            function init_locations_modal(location_data) {

                count_filter_items();

                $('#modal-header, .row.form').html('');

                if (!location_data) {
                    $('body').removeClass('modal-open');
                    return false;
                }

                var location = JSON.parse(location_data);

                $('#modal-container .row.form').append('<div class="col-8 form_1"></div><div class="col-4 form_2"></div>');

                if (typeof location.data !== 'object') {

                    var data = {
                        'external_id': location.external_id,
                        'location_type_id': location.location_type_id,
                        'name': location.name,
                        'phone': location.phone || '',
                        'email': location.email || '',
                        'address_1': location.address,
                        'address_2': '',
                        'postal_code': location.postal_code,
                        'city': location.city,
                        'country': location.country || 'Nederland',
                        'country_code': location.country_code || 'NL'
                    };

                    create_location_data_modal(location, data);

                } else {

                    var data = {
                        'external_id': location.external_id,
                        'group_id': location.data.group_id,
                        'group_address_id': location.data.group_address_id || '',
                        'name': '',
                        'phone': '',
                        'email': '',
                        'address_1': '',
                        'address_2': '',
                        'postal_code': '',
                        'city': '',
                        'country': '',
                        'country_code': ''
                    };


                    if (data.group_id) {

                        $.ajax({
                            method: "POST",
                            url: "ajax/fetch.php",
                            data: {
                                'client_id': '<?= OWNER_ID; ?>',
                                'account_id': '<?= ACCOUNT_ID; ?>',
                                'user_id': '<?= USER_ID; ?>',
                                'table': 'groups',
                                'id': data.group_id,
                                'where': {
                                    'type': 'location'
                                }
                            }}).done(function (results) {

                            var result = JSON.parse(results);

                            data.name = result.item.name;
                            data.phone = result.item.phone;
                            data.email = result.item.email;
                            data.external_id = result.item.external_id;
                            data.location_type_id = result.item.location_type_id;
                            data.group_address_id = result.item.group_address_id;
                            data.address_1 = result.item.delivery_address_1 || location.address;
                            data.address_2 = result.item.delivery_address_2 || '';
                            data.postal_code = result.item.delivery_postal_code || location.postal_code;
                            data.city = result.item.delivery_city || location.city;
                            data.country = result.item.delivery_country || location.country;
                            data.country_code = result.item.delivery_country_code || location.country_code || "NL";


                            create_location_data_modal(location, data);

                        });

                    } else {
                        $('body').removeClass('modal-open');
                        return false;
                    }

                }

            }


            function save_location() {

                var location_external_id = $('#location_external_id').val();

                var group_id = $('#location_group_id').val();
                var group_address_id = $('#location_group_address_id').val();

                var location_name = $('#location_name').val();
                var phone = $('#location_phone').val() || '';
                var email = $('#location_email').val() || '';
                var address_1 = $('#location_address_1').val() || '';
                var address_2 = $('#location_address_2').val() || '';
                var postal_code = $('#location_postal_code').val() || '';
                var city = $('#location_city').val() || '';
                var country = $('#location_country').val() || 'Nederland';
                var country_code = $('#location_country_code').val() || 'NL';

                var location_type = $('#location_type_id option:selected').text();
                var location_type_id = $('#location_type_id').val();

                var tr = $('table#locations-overview tbody tr.location_group_' + group_id + '');

                $(tr).attr('data-location_type', location_type_id);
                $(tr).find('.location_type span').html(location_type);
                $(tr).find('.external_id span').html(location_external_id);
                $(tr).find('.location_name span').html(location_name);
                $(tr).find('.location_city span').html(city);

                /// alert(location_name);

                count_filter_items();

                if (group_id && location_external_id) {

                    ajax_db_save('groups',
                            [
                                'name',
                                'external_id',
                                'location_type_id',
                                'phone',
                                'email'
                            ], [
                        location_name,
                        location_external_id,
                        location_type_id,
                        phone,
                        email

                    ],
                            group_id);

                    if (group_address_id) {

                        ajax_db_save('group_addresses',
                                [
                                    'address_1',
                                    'address_2',
                                    'postal_code',
                                    'city',
                                    'country',
                                    'country_code'
                                ], [
                            address_1,
                            address_2,
                            postal_code,
                            city,
                            country,
                            country_code

                        ],
                                group_address_id);

                    } else if (group_id && !group_address_id) {

                        console.log('Insert new');
                        return;

                    }

                } else if (location_name && location_external_id) {

                    var after_insert = {
                        'name': 'init_locations_modal',
                        'data': location
                    };

                    $('#modal-header').html('Filiaal ' + location_external_id + ' opslaan');
                    $('#modal-save, #cancel, .close-modal').hide();

                    var delivery_address = {
                        'type': 'delivery',
                        'address_1': address_1,
                        'address_2': address_2,
                        'postal_code': postal_code,
                        'city': city,
                        'country': country,
                        'country_code': country_code
                    };

                    ajax_db_save('groups',
                            [
                                'name',
                                'external_id',
                                'phone',
                                'email',
                                'type',
                                'delivery_address'
                            ], [
                        location_name,
                        location_external_id,
                        phone,
                        email,
                        'location',
                        delivery_address
                    ],
                            false,
                            after_insert
                            );

                }


            }


            function create_location_data_modal(location, data) {

                $('#modal-container .row.form .form_1, #modal-container .row.form .form_2').html('');

                $('#modal-container .row.form .form_2').append('<label>Filiaalnummer:</label>');
                $('#modal-container .row.form .form_2').append('<input type="text" id="location_external_id" value="' + data.external_id + '"></input>');

                if (location.location_types.length > 0) {

                    $('#modal-container .row.form .form_2').append('<label>Type Filiaal:</label>');
                    $('#modal-container .row.form .form_2').append('<select id="location_type_id"></select>');

                    var selected = false;
                    $('#location_type_id').append('<option value="">Type Filiaal</option>');
                    for (var i in location.location_types) {
                        selected = false;
                        if (data.location_type_id === location.location_types[i].id) {
                            selected = true;
                        }
                        $('#location_type_id').append('<option value="' + location.location_types[i].id + '" ' + (selected ? 'selected' : '') + '>' + location.location_types[i].name + '</option>');
                    }
                }

                if (data.group_id) {
                    $('#modal-container .row.form .form_2').append('<br/><label>Group id:</label>');
                    $('#modal-container .row.form .form_2').append('<input type="text" readonly disabled id="location_group_id" value="' + data.group_id + '"></input>');
                }

                if (data.group_address_id) {
                    $('#modal-container .row.form .form_2').append('<label>Address id:</label>');
                    $('#modal-container .row.form .form_2').append('<input type="text" readonly disabled id="location_group_address_id" value="' + data.group_address_id + '"></input>');
                }

                $('#modal-container .row.form .form_1').append('<label>Filiaal naam:</label>');
                $('#modal-container .row.form .form_1').append('<input type="text" id="location_name" value="' + data.name + '"></input>');

                $('#modal-container .row.form .form_1').append('<label>Telefoonnummer:</label>');
                $('#modal-container .row.form .form_1').append('<input type="text" id="location_phone" value="' + data.phone + '"></input>');

                $('#modal-container .row.form .form_1').append('<label>E-mailadres:</label>');
                $('#modal-container .row.form .form_1').append('<input type="text" id="location_email" value="' + data.email + '"></input>');

                $('#modal-container .row.form .form_1').append('<br/><label>Afleveradres:</label>');
                $('#modal-container .row.form .form_1').append('<input type="text" id="location_address_1" value="' + data.address_1 + '"></input>');
                $('#modal-container .row.form .form_1').append('<label>Postcode:</label>');
                $('#modal-container .row.form .form_1').append('<input type="text" id="location_postal_code" value="' + data.postal_code + '"></input>');
                $('#modal-container .row.form .form_1').append('<label>Plaats:</label>');
                $('#modal-container .row.form .form_1').append('<input type="text" id="location_city" value="' + data.city + '"></input>');



                $('#modal-header').html('Filiaal ' + location.id);

                $('body').addClass('modal-open');

                bind_events();

                $('#modal-save').show().val('Opslaan').unbind('click').bind('click', function () {
                    save_location();
                });

                $(document).unbind('keyup').bind('keyup', function (event) {

                    if (event.keyCode == 27) {
                        init_locations_modal();
                    } else if (event.which == 13) {
                        save_location();
                        event.preventDefault();
                    }
                });

                $('#cancel, .close-modal').show().val('Sluiten').unbind('click').bind('click', function () {
                    init_locations_modal();
                });

            }

            function init_picklist_note_modal(campagne_product) {

                $('#modal-header, .row.form').html('');

                if (!campagne_product) {
                    $('body').removeClass('modal-open');
                    return false;
                }

                $('#modal-header').html(campagne_product.name);

                $('.row.form').append('<div class="col-12"><label>Productnaam</label><input type="text" id="campagne_product_name" value="' + campagne_product.name + '" style="width:100% !important;"></div>');
                if (!campagne_product.picklist_note) {
                    campagne_product.picklist_note = '';
                }
                $('.row.form').append('<div class="col-8" style="margin-right:-15px;"><label>Opmerking voor picklijst</label><textarea style="display:block;min-height:360px;" id="picklist_note">' + campagne_product.picklist_note + '</textarea></div>');

                var external_ids = '';
                if (campagne_product.included_external_ids) {
                    external_ids = JSON.parse(campagne_product.included_external_ids);
                }

                $('.row.form').append('<div class="col-4" id="right-form-col"></div>');

                if (campagne_product.product_type === 'set') {
                    $('#right-form-col').append('<label>Geleverd als:</label>');
                    $('#right-form-col').append('<select id="set_delivery"><option value="set" ' + (campagne_product.set_delivery === 'set' ? 'selected' : '') + '>Complete set</option><option value="seperate" ' + (campagne_product.set_delivery !== 'set' ? 'selected' : '') + '>Losse producten</option></select>');
                }

                if (campagne_product.product_type === 'set' && campagne_product.set_delivery === 'seperate') {
                    $('#right-form-col').append('<label>Station indeling:</label>');
                    $('#right-form-col').append('<select id="stations"><option value="separate" ' + (campagne_product.stations === 'separate' ? 'selected' : '') + '>Aparte stations</option><option value="combined" ' + (campagne_product.stations === 'combined' ? 'selected' : '') + '>1 station delen</option></select>');
                }

                $('#right-form-col').append('<div id="div-external_id"><label>Artikelnummer</label></div>');
                $('#div-external_id').append('<input class="stocksearch capitalize" id="external_id" type="text" value="' + campagne_product.external_id + '">');

                $('#right-form-col').append('<label>Waarde product:</label>');
                $('#right-form-col').append('<select id="value_product"><option value="0">Nee</option><option value="1" ' + (campagne_product.value_product === '1' ? 'selected' : '') + '>Ja</option></select>');

                $('#right-form-col').append(' <label>Verpakkingseenheid</label><input type="number" class="quantity" id="campagne_product_unit_quantity" style="float: left;min-width:100%;" value="' + campagne_product.unit_quantity + '">');


                $('#right-form-col').append('<div id="div-included_external_ids"><label>Producten</label></div>');

                var count = 0;
                for (var i in external_ids) {
                    $('#div-included_external_ids').append('<input class="stocksearch capitalize external_ids" type="text" value="' + external_ids[count] + '">');
                    count++;
                }
                ;
                count++;
                $('#div-included_external_ids').append('<input class="stocksearch capitalize external_ids new" type="text" value="">');

                new_input();

                if (campagne_product.set_delivery === 'set' || campagne_product.set_delivery === '') {
                    $('#div-included_external_ids').hide();
                    $('#div-external_id').show();
                } else if (campagne_product.set_delivery === 'seperate') {
                    $('#div-included_external_ids').show();
                    $('#div-external_id').hide();
                }

                $('#set_delivery').unbind('change').bind('change', function () {

                    var value = $(this).val();

                    if (value === 'set') {
                        $('#div-included_external_ids').hide();
                        $('#div-external_id').show();
                    } else if (value === 'seperate') {
                        $('#div-included_external_ids').show();
                        $('#div-external_id').hide();
                    }

                });

                $('#cancel, .close-modal').val('Annuleren').unbind('click').bind('click', function () {
                    init_picklist_note_modal();
                });

                $('#modal-save').val('Opslaan').unbind('click').bind('click', function () {

                    if (campagne_product.id) {

                        var included_external_ids = [];

                        $('.row.form').find('.external_ids').each(function () {
                            if ($(this).val()) {
                                included_external_ids.push($(this).val());
                            }
                        });

                        var external_id = $('#external_id').val();
                        if ($('#set_delivery').val() === 'seperate') {
                            external_id = '';
                        }

                        var picklist_note = $('#picklist_note').val();
                        if (!picklist_note) {
                            picklist_note = '';
                        }
                        var set_delivery = $('#set_delivery').val();
                        var incl_ids = JSON.stringify(included_external_ids);

                        var campagne_product_name = $('#campagne_product_name').val();
                        var campagne_product_unit_quantity = $('#campagne_product_unit_quantity').val();
                        var value_product = $('#value_product').val();
                        var stations = $('#stations').val();

                        // $('tr[data-campagne-product-id='+ campagne_product.id +']').find('[data-value=external_id]').html('' + external_id + '');
                        // $('tr[data-campagne-product-id='+ campagne_product.id +']').find('[data-value=num_products_included]').html('' + included_external_ids.length + '');
                        // $('tr[data-campagne-product-id='+ campagne_product.id +']').find('[data-value=campagne_product_set_delivery]').val(set_delivery);

                        ajax_db_save(
                                'campagne_products',
                                [
                                    `name`,
                                    `unit_quantity`,
                                    'picklist_note',
                                    'set_delivery',
                                    'stations',
                                    'external_id',
                                    'value_product',
                                    'included_external_ids'
                                ], [
                            campagne_product_name,
                            campagne_product_unit_quantity,
                            picklist_note,
                            set_delivery,
                            stations,
                            external_id,
                            value_product,
                            incl_ids
                        ],
                                campagne_product.id
                                );
                        return false;
                    }
                });
            }

            $(function () {
                bind_events();
            });


            function bind_events() {

                $('a[data-action=add-new-location-group], a[data-action=edit-location-group]').unbind('click').bind('click', function () {
                    var item = $(this).attr('data-item');
                    console.log(item);
                    init_locations_modal(item);

                });

                $(window).unbind('scroll').bind('scroll', function (event) {
                    // console.log(document.getElementById('sticky'));
                    // console.log($('.right-menu').position());
                    // console.log($('.right-menu').offset());
                });

                $('.quantity').each(function () {
                    var val = $(this).text().replace(/\./g, '');
                    $(this).val('' + number_format(val, 0, '', '.') + '').html('' + number_format(val, 0, '', '.') + '');
                });

                $('.collapsable .collaps-row').unbind('click').bind('click', function () {
                    var open = $(this).hasClass('open');
                    $('.collapsable .open').removeClass('open');
                    if (!open) {
                        $(this).addClass('open');
                        $(this).next('tr').addClass('open');
                    }
                });

                $('.toggle').each(function () {

                    let length = $(this).children('div').length;

                    $(this).children('div').css({
                        'width': '' + 100 / length + '%'
                    }).unbind('click').bind('click', function () {

                        $toggle = $(this).parent('.toggle');

                        $toggle.children('div').removeClass('active');
                        $(this).addClass('active');

                        let table = $toggle.attr('data-table');
                        let columns = $toggle.attr('data-columns');
                        columns = JSON.parse("" + columns.replace(/'/gmi, '"') + "");

                        let values = $(this).attr('data-values');
                        values = JSON.parse("" + values.replace(/'/gmi, '"') + "");

                        let id = $toggle.attr('data-id');

                        ajax_db_save(table, columns, values, id);

                    });

                });

                $('input[type=number]').unbind('focus').bind('focus', function () {
                    if ($(this).val() === 0) {
                        $(this).val('');
                    }
                }).unbind('blur').bind('blur', function () {
                    if (!$(this).val() || $(this).val() < 1) {
                        $(this).val('0');
                    }
                }).unbind('change keyup mouseup').bind('change keyup mouseup', function () {
                    if ($(this).val() >= 0) {
                        $(this).val(parseInt($(this).val()));
                    }
                    if ($(this).val() < 1) {
                        $(this).addClass('zero');
                    } else {
                        $(this).removeClass('zero');
                    }
                });

                // $('a[data-action=picklist_note]').find('.material-icons').html('event_note');

                $('a[data-action=picklist_note]').unbind('click').bind('click', function (event) {

                    var campagne_product_id = $(this).attr('data-campagne-product-id');

                    if (!campagne_product_id) {
                        return false;
                    }

                    $('#modal-header, .row.form').html('<div class="row"><div class="col-12">Bezig met laden</div></div>');
                    $('body').addClass('modal-open');

                    $.ajax({
                        method: "POST",
                        url: "search/searchproduct.php",
                        data: {
                            'owner_id': <?= OWNER_ID; ?>,
                            'account_id': <?= ACCOUNT_ID; ?>,
                            'product_id': campagne_product_id
                        }
                    }).done(function (data) {
                        var campagne_product = JSON.parse(data);
                        init_picklist_note_modal(campagne_product);
                    });

                });


                function return_external_id(id, prefix) {
                    if (!prefix || !id) {
                        return false;
                    }
                    var size = 8 - prefix.length;
                    var s = id + "";
                    while (s.length < size) {
                        s = "0" + s;
                    }
                    var article_number = prefix + s;
                    return article_number;
                }


                $('[data-value=campagne_product_external_id]').unbind('blur').bind('blur', function () {

                    var campagne_product_id = $(this).attr('data-campagne-product-id');
                    var external_id = $(this).val();
                    var original_external_id = $(this).attr('data-original-value');

                    if (external_id === original_external_id || (!campagne_product_id || $(this).hasClass('inactive'))) {
                        return false;
                    }

                    $(this).removeClass('invalid');

                    if (!external_id.match(/<?= PRODUCT_EXTERNAL_ID_PATTERN; ?>/gmi) || external_id.length < 3) {
                        if (external_id.length > 0) {
                            external_id = '';
                            $(this).addClass('invalid');
                        }
                    }
                    ajax_db_save('campagne_products', 'external_id', external_id, campagne_product_id);

                });

                $('select[data-value=campagne_product_set_delivery]').unbind('change').bind('change', function () {

                    var campagne_product_id = $(this).attr('data-campagne-product-id');

                    var set_delivery = $(this).val();

                    if (!campagne_product_id) {
                        return false;
                    }

                    var external_id = '';

                    $external_id_obj = $('input[data-campagne-product-id=' + campagne_product_id + '][data-value=campagne_product_external_id]');

                    if (set_delivery === 'set') {
                        $external_id_obj.attr('readonly', false).val('').removeClass('inactive');
                        ajax_db_save('campagne_products', ['set_delivery', 'external_id'], [set_delivery, return_external_id(campagne_product_id, 'BS')], campagne_product_id);
                    } else {
                        $external_id_obj.val('').addClass('inactive').attr('readonly', true);
                        ajax_db_save('campagne_products', ['set_delivery', 'external_id'], [set_delivery, ''], campagne_product_id);
                    }

                });

                $('tr.campagne_product').unbind('click').bind('click', function (event) {

                    // console.log($(event.target));
                    if ($(this).hasClass('active')) {
                        return false;
                    }

                    var campagne_product_id = $(this).attr('data-campagne-product-id');

                    if (!campagne_product_id) {
                        return false;
                    }

                    campagne_product_fetch(campagne_product_id);

                });

                $('input[type=number].item-quantity').unbind('change').bind('change', function () {

                    var picklist_data = [];

                    var items = $(this).closest('div.picklist-container').find('.picklist-item');
                    var campagne_product_id = $(this).closest('div.picklist-container').attr('data-picklist-campagne-product-id');

                    if (campagne_product_id && items.length > 0) {

                        var numLocs = 0;
                        var totalQuantity = 0;

                        $(items).each(function () {

                            var location_id = $(this).find('.external_id').val();
                            var location_name = $(this).find('.location_name').val();
                            var quantity = parseInt($(this).find('.item-quantity').val());
                            var unit_quantity = parseInt($(this).find('.item-quantity').attr('data-unit-quantity'));

                            if (unit_quantity > 1) {
                                quantity = (quantity * unit_quantity);
                            }

                            var picklist_data_item = {
                                'external_id': location_id,
                                'name': location_name.replace(/'/, "\'"),
                                'quantity': quantity
                            };

                            if (quantity > 0) {
                                numLocs++;
                                totalQuantity = totalQuantity + quantity;
                            }

                            picklist_data.push(picklist_data_item);

                        });

                        $.ajax({
                            method: "POST",
                            url: "ajax/save.php",
                            data: {
                                'owner_id': <?= OWNER_ID; ?>,
                                'user_id': <?= USER_ID; ?>,
                                'table': 'campagne_products',
                                'id': campagne_product_id,
                                'columns': [
                                    'picklist_data',
                                    'locations',
                                    'quantity'
                                ],
                                'data': [
                                    JSON.stringify(picklist_data),
                                    numLocs,
                                    totalQuantity
                                ]
                            }
                        }).done(function (results) {
                            var result = JSON.parse(results);
                            console.log(result.data.unit_quantity);

                            var quantity = result.data.quantity / result.data.unit_quantity;

                            console.log(quantity + ' / ' + totalQuantity);

                            $('[data-campagne-product-id=' + campagne_product_id + '].quantity.num_locations').html(number_format(numLocs, 0, '', '.'));
                            $('[data-campagne-product-id=' + campagne_product_id + '].quantity.num_products').html(number_format(quantity, 0, '', '.'));
                        });

                    }

                });

                $('.close-modal').unbind('click').bind('click', function (event) {

                    if ($(event.target).hasClass('close-modal')) {
                        // $('body').removeClass('modal-open');
                        // init_picklist_note_modal();
                    }

                });

                $('a[data-action=backorder]').unbind('click').bind('click', function (event) {

                    var backorder_id = parseInt($(this).attr('data-backorder-id'));
                    var quantity_needed = parseInt($(this).attr('data-quantity-needed')) || 0;
                    var expected_date = $(this).attr('data-expected-date');
                    var external_id = $(this).attr('data-external-id');
                    var campagne_product_id = $(this).attr('data-campagne-product-id');

                    if (!external_id) {
                        return false;
                    }

                    $('#modal-header, #modal-container .row.form').html('');
                    $('#modal-header').html('Backorder invoeren voor ' + external_id);

                    $form = $('<div class="col-6"></div>');

                    $form.append('<label>Artikelnummer</label>');
                    $form.append('<input type="text" data-value="external_id" value="' + external_id + '" readonly>');

                    $suppliers = $('<?= $account['formdata']['suppliers']; ?>');
                    $form.append('<label>Leverancier</label>');
                    $form.append($suppliers);

                    $form.append('<input type="hidden" data-value="quantity_needed" value="' + quantity_needed + '" readonly>');

                    if (quantity_needed > 0) {
                        $form.append('<label>Aantal nodig in deze campagne: ' + quantity_needed + '</label>');
                    } else {
                        $form.append('<label>Verwacht aantal</label>');
                    }
                    $form.append('<input type="number" data-value="quantity" value="0" min="' + quantity_needed + '">');

                    $form.append('<label>Verwachte leverdatum</label>');
                    $form.append('<input type="date" data-value="expected-date" value="' + (expected_date ? expected_date : '') + '">');

                    $('#modal-container .row.form').append($form);

                    $form = $('<div class="col-6"></div>');
                    $form.append('<label>Opmerking bij levering voor Logistics</label>');
                    $form.append('<textarea data-value="note" id="note"></textarea>');

                    $('#modal-container .row.form').append($form);

                    if (backorder_id > 0) {

                        $.ajax({
                            method: "POST",
                            url: "ajax/fetch.php",
                            data: {
                                'client_id': '<?= OWNER_ID; ?>',
                                'account_id': '<?= ACCOUNT_ID; ?>',
                                'user_id': '<?= USER_ID; ?>',
                                'table': 'stock_backorders',
                                'id': backorder_id,
                                'where': {'status': 'pending'}
                            }
                        }).done(function (result) {

                            var data = JSON.parse(result);

                            if ((!data.error || data.error.length === 0) && data.item) {

                                var item = data.item;

                                $('#modal-header').html('Backorder #' + item.id + ' voor ' + item.external_id);
                                $('#modal-container .row.form').find('[data-value=quantity]').val(item.quantity);
                                $('#modal-container .row.form').find('[data-value=supplier_id]').val(item.supplier_id);
                                $('#modal-container .row.form').find('[data-value=note]').html(item.note);

                            }

                        });

                    }

                    $('body').addClass('modal-open');

                    $('.close-modal').unbind('click').bind('click', function (event) {
                        $('body').removeClass('modal-open');
                    });


                    if (backorder_id > 0) {
                        $('#modal-save').val('Backorder opslaan');
                    } else {
                        $('#modal-save').val('Backorder aanmaken');
                    }

                    $('#modal-save').unbind('click').bind('click', function (event) {

                        var quantity = $('#modal-container .row.form').find('[data-value=quantity]').val();
                        var quantity_needed = $('#modal-container .row.form').find('[data-value=quantity_needed]').val();
                        var expected_date = $('#modal-container .row.form').find('[data-value=expected-date]').val() + ' 00:00:00';
                        var external_id = $('#modal-container .row.form').find('[data-value=external_id]').val();
                        var supplier_id = $('#modal-container .row.form').find('[data-value=supplier_id]').val();
                        var note = $('#modal-container .row.form').find('[data-value=note]').val();


                        if (!supplier_id) {
                            alert('Kies een leverancier');
                            $('#modal-container .row.form').find('[data-value=supplier_id]').focus();
                            return false;
                        } else if (quantity < 1) {
                            alert('Geef een correct aantal op');
                            $('#modal-container .row.form').find('[data-value=quantity]').focus();
                            return false;
                        } else if (quantity < quantity_needed) {
                            alert('Er zijn er ' + quantity_needed + ' nodig voor deze campagne!');
                            $('#modal-container .row.form').find('[data-value=quantity]').focus();
                            return false;
                        } else if (!$('#modal-container .row.form').find('[data-value=expected-date]').val()) {
                            alert('Geef een correcte afleverdatum op');
                            $('#modal-container .row.form').find('[data-value=expected-date]').focus();
                            return false;
                        } else {

                            $.ajax({
                                method: "POST",
                                url: "ajax/save.php",
                                data: {
                                    'owner_id': <?= OWNER_ID; ?>,
                                    'user_id': <?= USER_ID; ?>,
                                    'table': 'stock_backorders',
                                    'id': backorder_id,
                                    'columns': [
                                        'campagne_product_id',
                                        'external_id',
                                        'supplier_id',
                                        'quantity',
                                        'expected_date',
                                        'note'
                                    ],
                                    'data': [
                                        campagne_product_id,
                                        external_id,
                                        supplier_id,
                                        quantity,
                                        expected_date,
                                        note
                                    ]
                                }
                            }).done(function (result) {
                                campagne_product_fetch(campagne_product_id);
                                var data = JSON.parse(result);
                                if (!data.error) {
                                    $('body').removeClass('modal-open');
                                } else {
                                    alert(data.error[0]);
                                }
                            });

                        }

                    });

                    // $('#save').unbind('click').bind('click', function(event){
                    //   $('body').removeClass('modal-open');
                    // });

                });

                $('#search_location, #search_picklists').unbind('keyup').on('keyup', function () {

                    var needle = $(this).val().toUpperCase();

                    $('.searchable-row').removeClass('highlight');

                    if (needle.length > 3) {

                        $.ajax({
                            method: "POST",
                            url: "ajax/search_location_dc_picklist.php",
                            data: {
                                'client_id': '<?= OWNER_ID; ?>',
                                'account_id': '<?= ACCOUNT_ID; ?>',
                                'user_id': '<?= USER_ID; ?>',
                                'campagne_id': '<?= CAMPAGNE_ID; ?>',
                                'needle': needle
                            }
                        }).done(function (results) {

                            var result = JSON.parse(results);

                            if (result.locations.length > 0) {

                                for (var i in result.locations) {

                                    var location = result.locations[i];

                                    if (location.dc_id) {
                                        $('.searchable-row[data-search-id="dc_id_' + location.dc_id + '"]').addClass('highlight');
                                    }
                                    if (location.external_id) {
                                        $('.searchable-row[data-search-id="external_id_' + location.external_id + '"]').addClass('highlight');
                                    }
                                    if (location.truck_id) {
                                        $('.searchable-row[data-search-id="truck_id_' + location.truck_id + '"]').addClass('highlight');
                                    }
                                    if (location.lc_id) {
                                        $('.searchable-row[data-search-id="lc_id_' + location.lc_id + '"]').addClass('highlight');
                                    }

                                }

                            }

                            console.log(result);
                        });

                    }

                });
            }


            function campagne_product_fetch(campagne_product_id) {

                if (!campagne_product_id) {
                    return false;
                }

                $.ajax({
                    method: "POST",
                    url: "search/searchproduct.php",
                    data: {
                        'owner_id': <?= OWNER_ID; ?>,
                        'account_id': <?= ACCOUNT_ID; ?>,
                        'userId': <?= USER_ID; ?>,
                        'product_id': campagne_product_id
                    }
                }).done(function (data) {


                    $('tr.campagne_product').removeClass('active');
                    $('tr[data-campagne-product-id=' + campagne_product_id + ']').addClass('active');

                    $('tr[data-campagne-product-id=' + campagne_product_id + ']').find('.status').removeClass('done');
                    $('tr[data-campagne-product-id=' + campagne_product_id + ']').find('[data-value=campagne_product_complete]').find('i.material-icons').html('more_horiz');

                    var item = JSON.parse(data);

                    var picklist_data = JSON.parse(item.picklist_data);

                    if (item.product_type === 'set' && item.set_delivery === 'set') {

                        var product = item.products[0];

                    } else if (item.product_type === 'set' && item.products.length > 0 && item.set_delivery === 'seperate') {

                        for (var i in item.products) {
                            var product = item.products[i];
                        }

                    }

                    item.variations = 1;

                    if (item.variations_data) {

                        var variations_data = JSON.parse(item.variations_data);

                        if (Object.keys(variations_data).length > 1) {
                            item.variations = Object.keys(variations_data).length;
                        }


                        for (var variation_name in variations_data) {
                            var variation = variations_data[variation_name];
                            console.log(variation_name);
                        }

                    }


                    $('tr[data-campagne-product-id=' + campagne_product_id + ']').removeClass('warning');

                    if (item.product_type === 'product' || item.set_delivery === 'set') {
                        if (item.external_id) {
                            $('tr[data-campagne-product-id=' + campagne_product_id + ']').removeClass('warning');
                        } else if (!item.external_id) {
                            $('tr[data-campagne-product-id=' + campagne_product_id + ']').addClass('warning');
                        }
                    }

                    $('#backorders_container').html('').attr('data-campagne-product-id', campagne_product_id);
                    $('#backorders_container').append('<h4 data-value="campagne_product_name"><span>-</span></h4><hr>');
                    $('#backorders_container').append('<h2 data-value=campagne_product_content><span>--</span></h2>');


                    $obj = $('[data-campagne-product-id=' + campagne_product_id + ']');

                    $obj.find('[data-value=external_id]').val(item.external_id).html(item.external_id);

                    $obj.find('[data-value=campagne_product_name]').val(item.name).find('span').html(item.name);

                    $obj.find('[data-value=campagne_product_complete].status').removeClass('pending done warning').addClass(item.status.icon.class).find('i.material-icons').html(item.status.icon.html);

                    $obj.find('[data-value=unit_quantity]').html('Verpakt per <span class="quantity">' + number_format(item.unit_quantity, 0, '', '.') + '</span><span>');

                    $obj.find('[data-value=campagne_product_quantity]').html(number_format(item.quantity, 0, '', '.'));

                    var ids = false;

                    if (item.included_external_ids) {
                        var ids = (item.included_external_ids.length > 0 ? JSON.parse(item.included_external_ids) : '');
                    }

                    if (ids.length === 0) {

                        $('#backorders_container').append('<label class="select">Nodig in deze campgane</label>');

                        $('#backorders_container').append('<div class="toggle" data-table="campagne_products" data-columns="[\'product_type\',\'set_delivery\']" data-id="' + item.id + '"><div id="toggle-product" data-values="[\'product\', \'\']"><span class="quantity">' + item.quantity + '</span> products</div><div id="toggle-set" data-values="[\'set\',\'set\']"><span class="quantity">' + item.quantity + '</span> sets</div></div>');

                        if (item.product_type === 'set') {
                            $('#toggle-set').addClass('active');
                        } else if (item.product_type === 'product') {
                            $('#toggle-product').addClass('active');
                        }

                    }

                    $obj.find('[data-value=campagne_product_type_item]').html('stuks');

                    if (item.product_type === 'set') {

                        $obj.find('[data-value=campagne_product_type_item]').html('sets');

                        if (item.set_delivery === 'set') {
                            $obj.find('[data-value=campagne_product_content]').find('span').html('Complete set');
                        }

                    }

                    if (item.products.length === 0) {

                        $('#backorders_container').append('Geen artikelnummer gevonden');

                    } else {

                        var num_articles = 0;

                        for (var external_id in item.products) {
                            num_articles++;
                        }

                        $obj.find('[data-value=campagne_product_external_id]').val(item.external_id).html(item.external_id);
                        $obj.find('[data-value=campagne_product_quantity]').val(item.quantity);

                        $product_description = '1 artikel';

                        if (item.product_type === 'set' && item.set_delivery === 'seperate' && num_articles > 0) {
                            $product_description = 'Set van ' + num_articles + ' artikelen';
                        } else if (item.product_type === 'set' && item.set_delivery === 'set') {
                            $product_description = 'Complete set';
                        } else if (item.product_type === 'product' && item.variations > 1) {
                            $product_description = item.variations + ' varianten';
                        }

                        $obj.find('[data-value=campagne_product_content]').find('span').html($product_description);

                        $obj.find('[data-value=campagne_product_complete]').removeClass('complete');

                        if (item.complete) {
                            $obj.find('[data-value=campagne_product_complete]').addClass('complete');
                        }

                        var complete = false;

                        $('#backorders_container').append('<label class="select">Backorder(s)</label>');

                        $('#backorders_container').append('<table border=0 cellpadding="0" cellspacing="0" width="100%">');
                        $('#backorders_container table').append('<thead></tead>');
                        $('#backorders_container table thead').append('<tr>');
                        $('#backorders_container table thead tr').append('<th class="small">#</th>');
                        $('#backorders_container table thead tr').append('<th class="small" style="text-align:left;padding-left: 0px;">Artikel</th>');
                        $('#backorders_container table thead tr').append('<th class="small stretch">Naam</th>');
                        $('#backorders_container table thead tr').append('<th class="quantity small">Nodig</th>');
                        $('#backorders_container table thead tr').append('<th class="quantity small">Voorraad</th>');
                        $('#backorders_container table thead tr').append('<th class="quantity small" colspan="2" style="padding-right:12px !important;">Backorder</th>');
                        $('#backorders_container table').append('<tbody id="backorders_table">');

                        var set_ready_for_picking = true;

                        for (var external_id in item.products) {

                            var product = item.products[external_id];

                            var create_backorder_icon;
                            var product_backorder_status_class = 'inactive';
                            complete = false;

                            if (product.cur_stock > 0 && product.cur_stock >= item.quantity) {
                                complete = true;
                            } else {
                                set_ready_for_picking = false;
                            }

                            var in_backorder = 0;
                            var last_backorder_id = false;

                            var expected_date = false;

                            for (var backorder_id in product.backorders) {
                                var backorder = product.backorders[backorder_id];
                                in_backorder = in_backorder + parseInt(backorder.quantity);
                                expected_date = backorder.expected_date;
                                last_backorder_id = backorder_id;
                            }

                            in_backorder = parseInt(in_backorder)
                            product.cur_stock = parseInt(product.cur_stock);

                            if (in_backorder === 0) {
                                create_backorder_icon = 'playlist_add';
                            } else if (in_backorder > 0) {

                                create_backorder_icon = 'playlist_add_check';
                                product_backorder_status_class = 'pending';

                                if ((in_backorder + product.cur_stock) < item.quantity) {
                                    create_backorder_icon = 'playlist_add';
                                    product_backorder_status_class = 'warning';
                                }

                            } else if (in_backorder >= item.quantity) {

                                create_backorder_icon = 'playlist_add_check';
                                product_backorder_status_class = 'done';

                            }

                            product.name = product.name || item.name;

                            var product_class = '';
                            var status_icon = 'more_horiz';

                            if (complete) {
                                product_class = 'done';
                                status_icon = 'check_box';
                                create_backorder_icon = 'more_horiz';
                            } else if (last_backorder_id > 0) {
                                product_class = 'pending';
                                status_icon = 'check_box_outline_blank';
                            }

                            if (in_backorder > 0 || product.cur_stock > 0) {
                                if ((in_backorder + product.cur_stock) < item.quantity) {
                                    $obj.find('[data-value=campagne_product_complete].status').addClass('warning');
                                    product_class += ' warning';
                                }
                            }

                            if (product.cur_stock === 0) {
                                product.cur_stock = '-';
                            }
                            
                            if (in_backorder === 0) {
                                in_backorder = '-';
                            }

                            $('#backorders_table').append('<tr data-external_id="' + external_id + '">');
                            $('#backorders_table tr[data-external_id=' + external_id + ']').append('<td class="status ' + product_class + '"><i class="material-icons">' + status_icon + '</i></td><td class="index small" style="text-align: left !important;padding-left:0px !important">' + external_id + '</td>');
                            $('#backorders_table tr[data-external_id=' + external_id + ']').append('<td class="text-overflow sm">' + (product.name_addition !== undefined ? product.name_addition : '') + '</td>');
                            $('#backorders_table tr[data-external_id=' + external_id + ']').append('<td class="quantity small" data-value="needed" style="color:#999;">' + item.quantity + '</td>');
                            $('#backorders_table tr[data-external_id=' + external_id + ']').append('<td class="quantity">' + product.cur_stock + '</td>');
                            $('#backorders_table tr[data-external_id=' + external_id + ']').append('<td class="quantity small" data-value="in_backorder">' + in_backorder + '</td>');

                            $('#backorders_table tr[data-external_id=' + external_id + ']').append('<td class="index action-buttons"><a \
                                class="icon_button backorder ' + product_backorder_status_class + '"\
                                data-action = "backorder"\
                                data-external-id = "' + external_id + '"\
                                data-backorder-id = "' + last_backorder_id + '"\
                                data-quantity-needed = "' + item.quantity + '"\
                                data-expected-date = "' + expected_date + '"\
                                data-campagne-product-id = "' + campagne_product_id + '">\
                                  <i class="material-icons">' + create_backorder_icon + '</i>\
                                </a>\
                                </td>');
                        }
                        
                        if (item.product_type === 'set' && item.set_delivery === 'seperate' && set_ready_for_picking) {
                            $obj.find('[data-value=campagne_product_complete]').removeClass('complete done').addClass('pending').find('i').html('list_alt');
                        }

                        $('#backorders_container').prepend('<a style="float:right;margin: 0px 0px 0 0px !important;" class="button block md" data-action="picklist_note" data-campagne-product-id="' + campagne_product_id + '"><i class="material-icons" style="font-size:14px;">edit</i><span style="padding: 0 3px;">Product bewerken<span></button>');

                        $('#backorders_container').append('<p class="last_update">Laatste update: ' + item.last_update + '<br/>Door: ' + item.blame_user + '</p>');
                    }
                    bind_events();
                });
            }
        </script>
    </head>
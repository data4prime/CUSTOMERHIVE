@push('bottom')
<script type="text/javascript">
    $(document).ready(function () {
        var $window = $(window);

        function checkWidth() {
            var windowsize = $window.width();
            if (windowsize > 500) {
                // console.log(windowsize);
                $('#box-body-table').removeClass('table-responsive');
            } else {
                // console.log(windowsize);
                $('#box-body-table').addClass('table-responsive');
            }
        }

        checkWidth();
        $(window).resize(checkWidth);

        $('.selected-action ul li a').click(function () {
            var name = $(this).data('name');
            $('#form-table input[name="button_name"]').val(name);
            var title = $(this).attr('title');

            if (title != 'Mass Edit') {
            swal({
                title: "{{trans("crudbooster.confirmation_title")}}",
                text: "{{trans("crudbooster.alert_bulk_action_button")}} " + title + " ?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#008D4C",
                confirmButtonText: "{{trans('crudbooster.confirmation_yes')}}",
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            },
                function () {
                    $('#form-table').submit();
                });
            }



        })

        $('table tbody tr .button_action a').click(function (e) {
            e.stopPropagation();
        })
    });
</script>
@endpush

<form id='form-table' method='post' action='{{CRUDBooster::mainpath("action-selected")}}'>
    <input type='hidden' name='button_name' value='' />
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <table id='table_dashboard' class="table table-hover table-striped table-bordered">
        <thead>
            <tr class="active">
                <?php if($button_bulk_action):?>
                <th width='3%'><input type='checkbox' id='checkall' /></th>
                <?php endif;?>
                <?php if($show_numbering):?>
                <th width="1%">{{ trans('crudbooster.no') }}</th>
                <?php endif;?>
                <?php
            foreach ($columns as $col) {
                if (isset($col['visible']) && $col['visible'] === FALSE) continue;

                $sort_column = Request::get('filter_column');
                $colname = $col['label'];
                $name = $col['name'];
                $field = $col['field_with'];
                $width = isset($col['width']) ?: "auto";
				$style = isset($col['style']) ?: "";
                $mainpath = trim(CRUDBooster::mainpath(), '/').$build_query;
                echo "<th width='$width' $style>";
                if (isset($sort_column[$field])) {
                    switch ($sort_column[$field]['sorting']) {
                        case 'asc':
                            $url = CRUDBooster::urlFilterColumn($field, 'sorting', 'desc');
                            echo "<a href='$url' title='Click to sort descending'>$colname &nbsp; <i class='fa fa-sort-desc'></i></a>";
                            break;
                        case 'desc':
                            $url = CRUDBooster::urlFilterColumn($field, 'sorting', 'asc');
                            echo "<a href='$url' title='Click to sort ascending'>$colname &nbsp; <i class='fa fa-sort-asc'></i></a>";
                            break;
                        default:
                            $url = CRUDBooster::urlFilterColumn($field, 'sorting', 'asc');
                            echo "<a href='$url' title='Click to sort ascending'>$colname &nbsp; <i class='fa fa-sort'></i></a>";
                            break;
                    }
                } else {
                    $url = CRUDBooster::urlFilterColumn($field, 'sorting', 'asc');
                    echo "<a href='$url' title='Click to sort ascending'>$colname &nbsp; <i class='fa fa-sort'></i></a>";
                }

                echo "</th>";
            }
            ?>

                @if($button_table_action)
                @if(CRUDBooster::isUpdate() || CRUDBooster::isDelete() || CRUDBooster::isRead())
                <th width='{{$button_action_width?:"auto"}}' style="text-align:center">
                    {{trans("crudbooster.action_label")}}</th>
                @endif
                @endif
            </tr>
        </thead>
        <tbody>
            @if(count($result)==0)
            <tr class='warning'>
                <?php if($button_bulk_action && $show_numbering):?>
                <td colspan='{{count($columns)+3}}' align="center">
                    <?php elseif( ($button_bulk_action && ! $show_numbering) || (! $button_bulk_action && $show_numbering) ):?>
                <td colspan='{{count($columns)+2}}' align="center">
                    <?php else:?>
                <td colspan='{{count($columns)+1}}' align="center">
                    <?php endif;?>

                    <i class='fa fa-search'></i> {{trans("crudbooster.table_data_not_found")}}
                </td>
            </tr>
            @endif

            @foreach($html_contents['html'] as $i=>$hc)

            @if($table_row_color)
            <?php $tr_color = NULL;?>
            @foreach($table_row_color as $trc)
            <?php
                    $query = $trc['condition'];
                    $color = $trc['color'];
                    $row = $html_contents['data'][$i];
                    foreach ($row as $key => $val) {
                        $query = str_replace("[".$key."]", '"'.$val.'"', $query);
                    }

                    @eval("if($query) {
                                      \$tr_color = \$color;
                                  }");
                    ?>
            @endforeach
            <?php echo "<tr class='$tr_color'>";?>
            @else
            <tr>
                @endif

                @foreach($hc as $j=>$h)
                <td {{ isset($columns[$j]['style']) ? $columns[$j]['style'] : '' }}>{!! $h !!}</td>
                @endforeach

            </tr>
            @endforeach
        </tbody>


        <tfoot>
            <tr>
                <?php if($button_bulk_action):?>
                <th>&nbsp;</th>
                <?php endif;?>

                <?php if($show_numbering):?>
                <th>&nbsp;</th>
                <?php endif;?>

                <?php
            foreach ($columns as $col) {
                if (isset($col['visible']) && $col['visible'] === FALSE) continue;
                $colname = $col['label'];
                $width = isset($col['width']) ? $col['width'] : "auto";
				$style = isset($col['style']) ? $col['style']: "";
                echo "<th width='$width' $style>$colname</th>";
            }
            ?>

                @if($button_table_action)
                @if(CRUDBooster::isUpdate() || CRUDBooster::isDelete() || CRUDBooster::isRead())
                <th> -</th>
                @endif
                @endif
            </tr>
        </tfoot>
    </table>

</form><!--END FORM TABLE-->

<div>{!! urldecode(str_replace("/?","?",$result->appends(Request::all())->appends('vendor.pagination.custom') )) !!}</div>


<?php
$from = $result->count() ? ($result->perPage() * $result->currentPage() - $result->perPage() + 1) : 0;
$to = $result->perPage() * $result->currentPage() - $result->perPage() + $result->count();
$total = $result->total();
?>

<!--
<div class="col-md-4" style="margin:30px 0;">
    <span class="pull-right">{{ trans("crudbooster.filter_rows_total") }}
        : {{ $from }} {{ trans("crudbooster.filter_rows_to") }} {{ $to }} {{ trans("crudbooster.filter_rows_of") }} {{
        $total }}</span>
</div>
-->

@if($columns)
@push('bottom')
<script>
    $(function () {
        $('.btn-filter-data').click(function () {
            $('#filter-data').modal('show');
        })

        $('.btn-export-data').click(function () {
            $('#export-data').modal('show');
        })



        if ($('#export-data input[name="fileformat"]:checked').val() == 'pdf') {
            $(".toggle_advanced_report").show();

        } else {
            $(".toggle_advanced_report").hide();
        }

        $("#csv-options").toggle($('#export-data input[name="fileformat"]:checked').val() == 'csv');

        //on change of input with name fileformat (era una <select>, ora radio a
        //controllo segmentato - stesso evento "change", cambia solo il selettore)

        $('#export-data input[name="fileformat"]').change(function () {
            var fileformat = $(this).val();
            //show advanced export if fileformat is pdf (class toggle_advanced_report)
            if (fileformat == 'pdf') {
                $(".toggle_advanced_report").show();
                //$("#advanced_export").slideDown();
            } else {
                $(".toggle_advanced_report").hide();
                $("#advanced_export").slideUp();
                $(".toggle_advanced_report").removeClass('is-open');
            }

            $("#csv-options").toggle(fileformat == 'csv');
        })

        /*
         * Prima: il click sostituiva l'intero contenuto del link
         * (icona+testo) via .html(). Il link ora porta anche un badge
         * "Solo per PDF" e una chevron - sostituendo l'html si perderebbero.
         * Stesso comportamento (slideDown/slideUp), solo una classe
         * "is-open" al posto dello scambio di icona/testo (la chevron
         * ruota via CSS in base a quella classe).
         */
        $(".toggle_advanced_report").click(function () {
            var $this = $(this);
            if (!$this.hasClass('is-open')) {
                $("#advanced_export").slideDown();
                $this.addClass('is-open');
            } else {
                $("#advanced_export").slideUp();
                $this.removeClass('is-open');
            }
        })

        /* Sezione colonne esportazione: chiudibile, per non allungare
           subito il popup sui moduli con molte colonne. */
        $("#export-columns-disclosure .export-disclosure-head").click(function () {
            $(this).closest('.export-disclosure')
                .toggleClass('is-open')
                .find('.export-disclosure-body')
                .slideToggle(150);
        })

        function updateExportColumnsCount() {
            var $checkboxes = $('.export-column-checkbox');
            var checkedCount = $checkboxes.filter(':checked').length;
            $('.export-columns-checked-count').text(checkedCount);
            /*
             * Testo del link in italiano hardcoded, non via trans(): stesso
             * principio già seguito per il sottotitolo di #advanced_filter_modal
             * (vedi "colonna disponibile"/"colonne disponibili" qui sotto) -
             * testo introdotto dal revamp, non fa parte delle chiavi
             * crudbooster.* già tradotte in 9 lingue.
             */
            $('.export-columns-toggle-all').text(
                checkedCount === $checkboxes.length ? 'Deseleziona tutto' : 'Seleziona tutto'
            );
        }

        $('.export-column-checkbox').change(updateExportColumnsCount);

        $('.export-columns-toggle-all').click(function () {
            var $checkboxes = $('.export-column-checkbox');
            var shouldCheck = $checkboxes.filter(':checked').length !== $checkboxes.length;
            $checkboxes.prop('checked', shouldCheck);
            updateExportColumnsCount();
        })


        $("#table_dashboard .checkbox").click(function () {
            var is_any_checked = $("#table_dashboard .checkbox:checked").length;
            if (is_any_checked) {
                $(".btn-delete-selected").removeClass("disabled");
            } else {
                $(".btn-delete-selected").addClass("disabled");
            }
        })

        $("#table_dashboard #checkall").click(function () {
            var is_checked = $(this).is(":checked");
            $("#table_dashboard .checkbox").prop("checked", !is_checked).trigger("click");
        })

        $('#btn_advanced_filter').click(function () {
            $('#advanced_filter_modal').modal('show');
        })

        $(".filter-combo").change(function () {
            var n = $(this).val();
            var p = $(this).parents('.row-filter-combo');
            var type_data = $(this).attr('data-type');
            var filter_value = p.find('.filter-value');

            p.find('.between-group').hide();
            p.find('.between-group').find('input').prop('disabled', true);
            filter_value.val('').show().focus();
            switch (n) {
                default:
                    filter_value.removeAttr('placeholder').val('').prop('disabled', true);
                    p.find('.between-group').find('input').prop('disabled', true);
                    break;
                case 'like':
                case 'not like':
                    filter_value.attr('placeholder', '{{trans("crudbooster.filter_eg")}} : {{trans("crudbooster.filter_lorem_ipsum")}}').prop('disabled', false);
                    break;
                case 'asc':
                    filter_value.prop('disabled', true).attr('placeholder', '{{trans("crudbooster.filter_sort_ascending")}}');
                    break;
                case 'desc':
                    filter_value.prop('disabled', true).attr('placeholder', '{{trans("crudbooster.filter_sort_descending")}}');
                    break;
                case '=':
                    filter_value.prop('disabled', false).attr('placeholder', '{{trans("crudbooster.filter_eg")}} : {{trans("crudbooster.filter_lorem_ipsum")}}');
                    break;
                case '>=':
                    filter_value.prop('disabled', false).attr('placeholder', '{{trans("crudbooster.filter_eg")}} : 1000');
                    break;
                case '<=':
                    filter_value.prop('disabled', false).attr('placeholder', '{{trans("crudbooster.filter_eg")}} : 1000');
                    break;
                case '>':
                    filter_value.prop('disabled', false).attr('placeholder', '{{trans("crudbooster.filter_eg")}} : 1000');
                    break;
                case '<':
                    filter_value.prop('disabled', false).attr('placeholder', '{{trans("crudbooster.filter_eg")}} : 1000');
                    break;
                case '!=':
                    filter_value.prop('disabled', false).attr('placeholder', '{{trans("crudbooster.filter_eg")}} : {{trans("crudbooster.filter_lorem_ipsum")}}');
                    break;
                case 'in':
                    filter_value.prop('disabled', false).attr('placeholder', '{{trans("crudbooster.filter_eg")}} : {{trans("crudbooster.filter_lorem_ipsum_dolor_sit")}}');
                    break;
                case 'not in':
                    filter_value.prop('disabled', false).attr('placeholder', '{{trans("crudbooster.filter_eg")}} : {{trans("crudbooster.filter_lorem_ipsum_dolor_sit")}}');
                    break;
                case 'between':
                    filter_value.val('').hide();
                    p.find('.between-group input').prop('disabled', false);
                    p.find('.between-group').show().focus();
                    p.find('.filter-value-between').prop('disabled', false);
                    break;
            }
        })

        /* Remove disabled when reload page and input value is filled */
        $(".filter-value").each(function () {
            var v = $(this).val();
            if (v != '') $(this).prop('disabled', false);
        })

    })
</script>

<!-- MODAL FOR SORTING DATA-->
<div class="modal fade" tabindex="-1" role="dialog" id='advanced_filter_modal'>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="justify-content: space-between;">
                <div>
                    <h4 class="modal-title"><i class='fa fa-filter'></i> {{trans("crudbooster.filter_dialog_title")}}</h4>
                    @isset($module_name)
                    <div class="modal-subtitle">{{ $module_name }} &middot; {{ count($columns) }} {{ count($columns) == 1 ? 'colonna disponibile' : 'colonne disponibili' }}</div>
                    @endisset
                </div>
<button class="btn-close" aria-label="Close" type="button" data-bs-dismiss="modal">
                    </button>
            </div>
            <form method='get' action=''>
                <div class="modal-body">
                    <div class="filter-row-head">
                        <div>Colonna</div>
                        <div>Condizione</div>
                        <div>Valore</div>
                        <div>Ordinamento</div>
                    </div>
                    <?php foreach($columns as $key => $col):?>
                    <?php if (isset($col['image']) || isset($col['download']) || (isset($col['visible']) && $col['visible'] === FALSE)) continue;?>

                    <div class='mb-3 row'>

                        <div class='row-filter-combo row {{ CRUDBooster::getTypeFilter($col["field_with"]) ? "is-active" : "" }}'>

                            <div class="col-sm-2">
                                <strong>{{$col['label']}}</strong>
                            </div>

                            <div class='col-sm-3'>
                                <select name='filter_column[{{$col["field_with"]}}][type]'
                                    data-type='{{$col["type_data"]}}' class="filter-combo form-control">
                                    <option value=''>{{trans("crudbooster.filter_select_operator_type")}}</option>
                                    @if(in_array($col['type_data'],['string','varchar','text','char']))
                                    <option {{ (CRUDBooster::getTypeFilter($col["field_with"])=='like' )?"selected":""
                                        }} value='like'>{{trans("crudbooster.filter_like")}}</option> @endif
                                    @if(in_array($col['type_data'],['string','varchar','text','char']))
                                    <option {{ (CRUDBooster::getTypeFilter($col["field_with"])=='not like'
                                        )?"selected":"" }} value='not like'>{{trans("crudbooster.filter_not_like")}}
                                    </option>@endif

                                    <option typeallow='all' {{ (CRUDBooster::getTypeFilter($col["field_with"])=='='
                                        )?"selected":"" }} value='='>{{trans("crudbooster.filter_equal_to")}}</option>
                                    @if(in_array($col['type_data'],['int','integer','smallint','tinyint','mediumint','bigint','double','float','decimal','time']))
                                    <option {{ (CRUDBooster::getTypeFilter($col["field_with"])=='>=' )?"selected":"" }}
                                        value='>='>{{trans("crudbooster.filter_greater_than_or_equal")}}</option>@endif
                                    @if(in_array($col['type_data'],['int','integer','smallint','tinyint','mediumint','bigint','double','float','decimal','time']))
                                    <option {{ (CRUDBooster::getTypeFilter($col["field_with"])=='<=' )?"selected":"" }}
                                        value='<='>{{trans("crudbooster.filter_less_than_or_equal")}}</option>@endif
                                    @if(in_array($col['type_data'],['int','integer','smallint','tinyint','mediumint','bigint','double','float','decimal','time']))
                                    <option {{ (CRUDBooster::getTypeFilter($col["field_with"])=='<' )?"selected":"" }}
                                        value='<'>{{trans("crudbooster.filter_less_than")}}</option>@endif
                                    @if(in_array($col['type_data'],['int','integer','smallint','tinyint','mediumint','bigint','double','float','decimal','time']))
                                    <option {{ (CRUDBooster::getTypeFilter($col["field_with"])=='>' )?"selected":"" }}
                                        value='>'>{{trans("crudbooster.filter_greater_than")}}</option>@endif
                                    <option typeallow='all' {{ (CRUDBooster::getTypeFilter($col["field_with"])=='!='
                                        )?"selected":"" }} value='!='>{{trans("crudbooster.filter_not_equal_to")}}
                                    </option>
                                    <option typeallow='all' {{ (CRUDBooster::getTypeFilter($col["field_with"])=='in'
                                        )?"selected":"" }} value='in'>{{trans("crudbooster.filter_in")}}</option>
                                    <option typeallow='all' {{ (CRUDBooster::getTypeFilter($col["field_with"])=='not in'
                                        )?"selected":"" }} value='not in'>{{trans("crudbooster.filter_not_in")}}
                                    </option>
                                    @if(in_array($col['type_data'],['date','time','datetime','int','integer','smallint','tinyint','mediumint','bigint','double','float','decimal','timestamp']))
                                    <option {{ (CRUDBooster::getTypeFilter($col["field_with"])=='between'
                                        )?"selected":"" }} value='between'>{{trans("crudbooster.filter_between")}}
                                    </option>@endif
                                    <option {{ (CRUDBooster::getTypeFilter($col["field_with"])=='empty' )?"selected":""
                                        }} value='empty'>Empty ( or
                                        Null)
                                    </option>
                                </select>
                            </div><!--END COL_SM_4-->


                            <div class='col-sm-5'>
                                <input type='text' class='filter-value form-control' style='{{ isset($col["field_with"]) &&
                                    (CRUDBooster::getTypeFilter($col["field_with"])=='between' ) ? "display:none"
                                    :"display:block"}}' disabled name='filter_column[{{$col["field_with"]}}][value]'
                                    value='{{ (!is_array(CRUDBooster::getValueFilter($col["field_with"])))?CRUDBooster::getValueFilter($col["field_with"]):"" }}'>

                                <div class='row between-group'
                                    style="{{ (CRUDBooster::getTypeFilter($col['field_with'])=='between' )?'display:block':'display:none' }}">
                                    <div class='col-sm-6'>
                                        <div
                                            class='input-group {{ ($col["type_data"] == "time")?"bootstrap-timepicker":"" }}'>
                                            <span class="input-group-text">{{trans("crudbooster.filter_from")}}:</span>
                                            @php
                                            if(in_array($col["type_data"], ["date","datetime","timestamp"])){
                                            $class_td = "datepicker";
                                            }else if(in_array($col["type_data"], ["time"])){
                                            $class_td = "timepicker";
                                            } else {
                                            $class_td = "";
                                            }

                                            @endphp
                                            <input {{ (CRUDBooster::getTypeFilter($col["field_with"]) !='between'
                                                )?"disabled":"" }} type='text'
                                                class='filter-value-between form-control {{ $class_td}}' {{
                                                (in_array($col["type_data"],["date","datetime","timestamp","time"]))?"readonly":""
                                                }} placeholder='{{$col["label"]}} {{trans("crudbooster.filter_from")}}'
                                                name='filter_column[{{$col["field_with"]}}][value][]' value='<?php
                                                    $value = CRUDBooster::getValueFilter($col["field_with"]);
                                                    echo (CRUDBooster::getTypeFilter($col["field_with"]) == ' between')
                                                ? $value[0] : "" ; ?>'>
                                        </div>
                                    </div>
                                    <div class='col-sm-6'>
                                        <div
                                            class='input-group {{ ($col["type_data"] == "time")?"bootstrap-timepicker":"" }}'>
                                            <span class="input-group-text">{{trans("crudbooster.filter_to")}}:</span>
                                            <input {{ (CRUDBooster::getTypeFilter($col["field_with"]) !='between'
                                                )?"disabled":"" }} type='text'
                                                class='filter-value-between form-control {{ $class_td}}' {{
                                                (in_array($col["type_data"],["date","datetime","timestamp","time"]))?"readonly":""
                                                }} placeholder='{{$col["label"]}} {{trans("crudbooster.filter_to")}}'
                                                name='filter_column[{{$col["field_with"]}}][value][]' value='<?php
                                                    $value = CRUDBooster::getValueFilter($col["field_with"]);
                                                    echo (CRUDBooster::getTypeFilter($col["field_with"]) == ' between')
                                                ? $value[1] : "" ; ?>'>
                                        </div>
                                    </div>
                                </div>
                            </div><!--END COL_SM_6-->


                            <div class='col-sm-2'>
                                <select class='form-control' name='filter_column[{{$col["field_with"]}}][sorting]'>
                                    <option value=''>Ordinamento</option>
                                    <option {{ (CRUDBooster::getSortingFilter($col["field_with"])=='asc' )?"selected":""
                                        }} value='asc'>{{trans("crudbooster.filter_ascending")}}</option>
                                    <option {{ (CRUDBooster::getSortingFilter($col["field_with"])=='desc'
                                        )?"selected":"" }} value='desc'>{{trans("crudbooster.filter_descending")}}
                                    </option>
                                </select>
                            </div><!--END_COL_SM_2-->

                        </div>

                    </div>
                    <?php endforeach;?>

                </div>
                <div class="modal-footer" align="right">
                    <button class="btn btn-default" type="button"
                        data-bs-dismiss="modal">{{trans("crudbooster.button_close")}}</button>
                    <button class="btn btn-default btn-reset" type="reset"
                        onclick='location.href="{{Request::get("lasturl")}}"'>{{trans("crudbooster.button_reset")}}</button>
                    <button class="btn btn-primary btn-submit"
                        type="submit">{{trans("crudbooster.button_submit")}}</button>
                </div>
                {!! CRUDBooster::getUrlParameters(['filter_column','lasturl']) !!}
                <input type="hidden" name="lasturl" value="{{Request::get('lasturl')?:Request::fullUrl()}}">
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
</div>

<!--
  Qui nel markup originale c'era un secondo <script> che ri-registrava
  gli stessi handler già legati sopra (.btn-filter-data/.btn-export-data)
  più una vecchia versione di .toggle_advanced_report basata su .html()
  (sostituiva icona+testo del link) - un doppione preesistente, non
  introdotto da questo intervento. Rimosso: con il nuovo markup del link
  (badge "Solo per PDF" + chevron) quel .html() avrebbe cancellato il
  contenuto ad ogni click, e il doppio binding causava comunque un
  secondo toggle non voluto sullo stesso click.
-->

<!-- MODAL FOR EXPORT DATA-->
<div class="modal fade" tabindex="-1" role="dialog" id='export-data'>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="justify-content: space-between;">
                <div>
                    <h4 class="modal-title"><i class='fa fa-download'></i> {{trans("crudbooster.export_dialog_title")}}</h4>
                    @isset($module_name)
                    <div class="modal-subtitle">{{ $module_name }} &middot; {{ $total }} {{ $total == 1 ? 'riga corrisponde ai filtri attivi' : 'righe corrispondono ai filtri attivi' }}</div>
                    @endisset
                </div>
                <button class="btn-close" aria-label="Close" type="button" data-bs-dismiss="modal">
                    </button>
            </div>

            <form method='post' target="_blank" action='{{ CRUDBooster::mainpath("export-data?t=".time()) }}'>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                {!! CRUDBooster::getUrlParameters() !!}
                <div class="modal-body">
                    <div class="mb-3 row">
                        <label>{{trans("crudbooster.export_dialog_filename")}}</label>
                        <input type='text' name='filename' class='form-control' required
                            value='{{ CRUDBooster::getCurrentModule()->name }} - {{date("d M Y")}}' />
                        <div class='help-block'>
                            {{trans("crudbooster.export_dialog_help_filename")}}
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label>{{trans("crudbooster.export_dialog_maxdata")}}</label>
                        <div class="export-input-suffix">
                            <input type='number' name='limit' class='form-control' required value='100' max="100000"
                                min="1" />
                            <span>righe</span>
                        </div>
                        <div class='help-block'>{{trans("crudbooster.export_dialog_help_maxdata")}}</div>
                    </div>

                    <div class='mb-3 form-group'>
                        <div class="export-disclosure" id="export-columns-disclosure">
                            <div class="export-disclosure-head">
                                <i class="fa fa-columns"></i>
                                {{trans("crudbooster.export_dialog_columns")}}
                                <span class="export-count-pill"><span class="export-columns-checked-count">{{ count($columns) }}</span> di {{ count($columns) }} selezionate</span>
                                <span class="export-disclosure-spacer"></span>
                                <i class="fa fa-chevron-down export-disclosure-chevron"></i>
                            </div>
                            <div class="export-disclosure-body" style="display:none">
                                <div class="export-columns-toolbar">
                                    <span class="help-block" style="margin:0;">Deseleziona le colonne che non ti servono nel file esportato.</span>
                                    <a href='javascript:void(0)' class='export-columns-toggle-all'>Deseleziona tutto</a>
                                </div>
                                <div class='export-columns-grid'>
                                    @foreach($columns as $col)
                                    <div class='checkbox inline'><label><input type='checkbox' checked class='export-column-checkbox' name='columns[]'
                                                value='{{$col["name"]}}'>{{$col["label"]}}</label></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label>{{trans("crudbooster.export_dialog_format_export")}}</label>
                        <div class="export-seg" role="radiogroup">
                            <label class="export-seg-option">
                                <input type="radio" name="fileformat" value="pdf" checked>
                                <span><i class="fa fa-file-pdf-o"></i> PDF</span>
                            </label>
                            <label class="export-seg-option">
                                <input type="radio" name="fileformat" value="xls">
                                <span><i class="fa fa-file-excel-o"></i> Microsoft Excel (xls)</span>
                            </label>
                            <label class="export-seg-option">
                                <input type="radio" name="fileformat" value="csv">
                                <span><i class="fa fa-file-text-o"></i> CSV</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3 row" id="csv-options" style="display:none">
                        <div class="export-pdf-grid">
                            <div class="mb-3 row">
                                <label>Separatore campi</label>
                                <select class='form-control' name='csv_delimiter'>
                                    <option value=',' selected>Virgola (,)</option>
                                    <option value=';'>Punto e virgola (;)</option>
                                    <option value='tab'>Tabulazione</option>
                                    <option value='|'>Pipe (|)</option>
                                </select>
                            </div>
                            <div class="mb-3 row">
                                <label>Delimitatore testo</label>
                                <select class='form-control' name='csv_enclosure'>
                                    <option value='&quot;' selected>Doppio apice (")</option>
                                    <option value="'">Apice singolo (')</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <p><a href='javascript:void(0)' class='toggle_advanced_report'><i class='fa fa-sliders'></i>
                            {{trans("crudbooster.export_dialog_show_advanced")}}
                            <span class="export-pdf-pill">Solo per PDF</span>
                            <span class="export-disclosure-spacer"></span>
                            <i class="fa fa-chevron-down export-disclosure-chevron"></i></a></p>

                    <div id='advanced_export' style='display: none'>

                        <div class="export-pdf-grid">
                            <div class="mb-3 row">
                                <label>{{trans("crudbooster.export_dialog_page_size")}}</label>
                                <select class='form-control' name='page_size'>
                                    <option <?php (isset($setting->default_paper_size) && $setting->default_paper_size ==
                                        'Letter') ? "selected" : ""?>
                                        value='Letter'>Letter</option>
                                    <option <?php (isset($setting->default_paper_size) && $setting->default_paper_size ==
                                        'Legal') ? "selected" : ""?>
                                        value='Legal'>Legal</option>
                                    <option <?php (isset($setting->default_paper_size) && $setting->default_paper_size ==
                                        'Ledger') ? "selected" : ""?>
                                        value='Ledger'>Ledger</option>
                                    <?php for($i = 0;$i <= 8;$i++):
                                            $select = (isset($setting->default_paper_size) && $setting->default_paper_size == 'A'.$i) ? "selected" : "";
                                            ?>
                                    <option <?php $select?> value='A{{$i}}'>A{{$i}}</option>
                                    <?php endfor;?>

                                    <?php for($i = 0;$i <= 10;$i++):
                                            $select = (isset($setting->default_paper_size) && $setting->default_paper_size == 'B'.$i) ? "selected" : "";
                                            ?>
                                    <option <?php $select?> value='B{{$i}}'>B{{$i}}</option>
                                    <?php endfor;?>
                                </select>
                            </div>

                            <div class="mb-3 row">
                                <label>{{trans("crudbooster.export_dialog_page_orientation")}}</label>
                                <div class="export-seg export-seg-sm" role="radiogroup">
                                    <label class="export-seg-option">
                                        <input type="radio" name="page_orientation" value="potrait" checked>
                                        <span>Verticale</span>
                                    </label>
                                    <label class="export-seg-option">
                                        <input type="radio" name="page_orientation" value="landscape">
                                        <span>Orizzontale</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class='help-block export-default-paper-check'><label><input type='checkbox' name='default_paper_size' value='1' />
                                {{trans("crudbooster.export_dialog_set_default")}}</label></div>
                    </div>

                </div>
                <div class="modal-footer" align="right">
                    <button class="btn btn-default" type="button"
                        data-bs-dismiss="modal">{{trans("crudbooster.button_close")}}</button>
                    <button class="btn btn-primary btn-submit"
                        type="submit">{{trans('crudbooster.button_submit')}}</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
</div>

<script>
$('#mass_editing_button').click(function () {

    $('#mass_editing_modal').modal('show');

    //get inputs with checkbox name
    var checkboxes = $("input[name='checkbox[]']:checked");

    /*
     * Pulisce gli ids[] di un'apertura precedente del popup (senza
     * ricaricare la pagina) - altrimenti si accumulano ids gia' inseriti
     * insieme ai nuovi ad ogni riapertura. Bug preesistente (non
     * introdotto qui), diventato piu' rilevante ora che il conteggio
     * "N righe selezionate" e' mostrato nel popup.
     */
    $('#form-mass-editing input[name="ids[]"]').remove();

    //for each checkbox, create an input and insert into the form
    checkboxes.each(function () {
        var id = $(this).val();
        var input = $("<input>")
            .attr("type", "hidden")
            .attr("name", "ids[]")
            .val(id);
        $('#form-mass-editing').append($(input));
    });

    var rowsLabel = checkboxes.length + (checkboxes.length == 1 ? ' riga selezionata' : ' righe selezionate');
    $('#mass-edit-count-label').text(rowsLabel);
    $('#mass-edit-count-suffix').text('· ' + rowsLabel);
    updateMassEditApplyButton();

})


 

</script>



<div class="modal fade" tabindex="-1" id="mass_editing_modal" aria-labelledby="mass_editing_modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="{{ CRUDBooster::mainpath('mass-edit') }}" id="form-mass-editing">
                <div class="modal-header" style="justify-content: space-between;">
                    <div>
                        <h4 class="modal-title" id="mass_editing_modalLabel"><i class="fa fa-pencil"></i> Mass Edit</h4>
                        <div class="modal-subtitle">@isset($module_name){{ $module_name }} &middot; @endisset<span id="mass-edit-count-label">0 righe selezionate</span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="ch-mass-edit-notice">
                    <i class="fa fa-info-circle"></i>
                    Attiva la spunta solo sui campi che vuoi aggiornare: gli altri resteranno invariati sulle righe selezionate.
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="table" value="{{ $table }}">
                    @include("crudbooster::mass_edit.form_body")
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">{{ trans("crudbooster.button_close") }}</button>
                    <button type="submit" class="btn btn-primary btn-submit" id="mass-edit-submit-btn" disabled>{{ trans('crudbooster.button_submit') }} <span id="mass-edit-count-suffix"></span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script defer>

//prendi tutti gli input nel form mass_editing_modal col nome che inizia con 'mass_edit_'
var inputs = $('#mass_editing_modal input[name^="mass_edit_"]');
console.log("inputs");
console.log(inputs);

//per ogni input, ricaare il div che lo segue, e inserire l'input nel div
inputs.each(function () {
    var name = $(this).attr('name');
    var div = $(this).prev();
    $(this).appendTo(div);
});




/*
quando un campo nel form mass_editing_modal cambia, selezionail checkbox corrispondente

*/
$('#mass_editing_modal input, #mass_editing_modal select').change(function () {
    //console.log("CHANGE");
    var name = $(this).attr('name');
    //console.log(name);
    var value = $(this).val();
    //console.log(value);
    var checkbox = $("input[name='mass_edit_"+name+"']");
    //console.log(checkbox);
    var type = $(this).attr('type');
    //console.log(type);
    if(type == 'checkbox'){
        console.log("CHECKBOX");
        //checkbox.prop('checked', $(this).is(':checked'));
        checkbox.prop('checked', $(this).is(':checked'));

    }else{
        //if value is not empty, check the checkbox
        if(value != ''){
            checkbox.prop('checked', true);
        }else{
            checkbox.prop('checked', false);
        }
    }

    updateMassEditApplyButton();
})

/*
 * Il pulsante "Submit" resta disabilitato finche' nessun campo e'
 * selezionato per l'aggiornamento (tutti gli input mass_edit_* sono
 * checkbox, indipendentemente dal tipo del campo reale - vedi
 * mass_edit/form_body.blade.php) - evita un invio che non cambierebbe
 * nulla sulle righe selezionate.
 */
function updateMassEditApplyButton() {
    var anyChecked = $('#mass_editing_modal input[name^="mass_edit_"]:checked').length > 0;
    $('#mass-edit-submit-btn').prop('disabled', !anyChecked);
}



</script>
@endpush
@endif
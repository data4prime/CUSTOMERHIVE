@extends('crudbooster::admin_template')

@push('bottom')
<script type="text/javascript">
    $(function () {
        var colorSwatch = {
            'normal': '#444',
            'red': '#dd4b39',
            'green': '#00a65a',
            'aqua': '#00c0ef',
            'light-blue': '#3c8dbc',
            'yellow': '#f39c12',
            'muted': '#777'
        };

        function formatColor(option) {
            var value = option.id || (option.element && option.element.value);
            if (!value) {
                return option.text;
            }
            var swatchColor = colorSwatch[value] || value;
            return $('<span><span style="display:inline-block;width:12px;height:12px;border-radius:2px;margin-right:6px;vertical-align:middle;background:' + swatchColor + ';"></span>' + option.text + '</span>');
        }

        // Il campo Color usa il componente select2 generico condiviso da
        // tutto l'admin (gia' inizializzato con $('#color').select2() senza
        // template): questa init va fatta DOPO, altrimenti lo span che
        // select2 crea per contenerlo verrebbe ripreso e reinizializzato da
        // quella generica, rompendo il widget (stesso bug del picker icone
        // del Module Generator - campo che appare vuoto).
        setTimeout(function () {
            $('#color').select2({
                width: '100%',
                templateResult: formatColor,
                templateSelection: formatColor
            });
        }, 0);
    })
</script>
@endpush

@section('content')
<div>

    @if(CRUDBooster::getCurrentMethod() != 'getProfile' && $button_cancel)
    @if(g('return_url'))
    <p><a title='Return' href='{{g("return_url")}}'><i class='fa fa-chevron-circle-left '></i>
            &nbsp; {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}</a></p>
    @else
    <p><a title='Main Module' href='{{CRUDBooster::mainpath()}}'><i class='fa fa-chevron-circle-left '></i>
            &nbsp; {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}</a></p>
    @endif
    @endif
 
    <div class="card card-default">
        <div class="card-header">
            <strong><i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> {!! $page_title !!}</strong>
        </div>

        <div class="card-body" style="padding:20px 0px 0px 0px">
            <?php
                $action = (@$row) ? CRUDBooster::mainpath("edit-save/$row->id") : CRUDBooster::mainpath("add-save");
                $return_url = isset($return_url) ? $return_url: g('return_url');
                ?>
            <form class='form-horizontal' method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type='hidden' name='return_url' value='{{ @$return_url }}' />
                <input type='hidden' name='ref_mainpath' value='{{ CRUDBooster::mainpath() }}' />
                <input type='hidden' name='ref_parameter' value='{{urldecode(http_build_query(@$_GET))}}' />
                @if($hide_form)
                <input type="hidden" name="hide_form" value='{!! serialize($hide_form) !!}'>
                @endif
                <div class="box-body" id="parent-form-area">

                    @if( isset($command) && isset($command) && $command == 'detail')
                    @include("crudbooster::default.form_detail")
                    @else
                    @include("crudbooster::menus.form_body")
                    @endif
                </div><!-- /.box-body -->

                <div class="box-footer" style="background: #F5F5F5">

                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-2"></label>
                        <div class="col-sm-10">
                            @if($button_cancel && CRUDBooster::getCurrentMethod() != 'getDetail')
                            @if(g('return_url'))
                            <a href='{{g("return_url")}}' class='btn btn-default'><i
                                    class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}</a>
                            @else
                            <a href='{{CRUDBooster::mainpath("?".http_build_query(@$_GET)) }}'
                                class='btn btn-default'><i class='fa fa-chevron-circle-left'></i>
                                {{trans("crudbooster.button_back")}}</a>
                            @endif
                            @endif
                            @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())

                            @if(CRUDBooster::isCreate() && $button_addmore==TRUE && isset($command) && $command ==
                            'add')
                            <input type="submit" name="submit" value='{{trans("crudbooster.button_save_more")}}'
                                class='btn btn-success'>
                            @endif

                            @if($button_save && isset($command) && $command != 'detail')
                            <input type="submit" name="submit" value='{{trans("crudbooster.button_save")}}'
                                class='btn btn-success'>
                            @endif

                            @endif
                        </div>
                    </div>


                </div><!-- /.box-footer-->

            </form>

        </div>
    </div>
</div><!--END AUTO MARGIN-->

@endsection
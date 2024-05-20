@extends('crudbooster::admin_template')
@section('content')

<div>

  @if(CRUDBooster::getCurrentMethod() != 'getProfile' && $button_cancel)
  @if(g('return_url'))
  <p>
    <a title='Return' href='{{g("return_url")}}'>
      <i class='fa fa-chevron-circle-left '></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>
  @else
  <p>
    <a title='Main Module' href='{{CRUDBooster::mainpath()}}'>
      <i class='fa fa-chevron-circle-left '></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>
  @endif
  @endif

  <div class="row">
    <div class="col-xs-2">
    </div>
    <div class="col-xs-8">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">
            <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> {!! $page_title !!}
          </h3>
          <div class="box-tools">
          </div>
        </div>

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
            @include("tenants.form_detail")
            @else
            @include("tenants.form_body")
            @endif
            <?php
              $loginURI = TenantHelper::loginPath($row->id);
            ?>
            <div class="form-group header-group-0 " id="form-group-domain_name" style="">
              <label class="control-label col-sm-2">
                Login URI
              </label>

              <div class="col-sm-9">
                <a target="_blank" style="border:none;" class="form-control" href="{{ $loginURI }}">{{ $loginURI }}</a>
              </div>
            </div>
          </div><!-- /.box-body -->

          <div class="box-footer" style="background: #F5F5F5">

            <div class="form-group">
              <label class="control-label col-sm-2"></label>
              <div class="col-sm-10">
                @if($button_cancel && CRUDBooster::getCurrentMethod() != 'getDetail')
                @if(g('return_url'))
                <a href='{{g("return_url")}}' class='btn btn-default'><i class='fa fa-chevron-circle-left'></i>
                  {{trans("crudbooster.button_back")}}</a>
                @else
                <a href='{{CRUDBooster::mainpath("?".http_build_query(@$_GET)) }}' class='btn btn-default'><i
                    class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}</a>
                @endif
                @endif
                @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())

                @if(CRUDBooster::isCreate() && $button_addmore==TRUE && isset($command) && $command == 'add')
                <input type="submit" name="submit" value='{{trans("crudbooster.button_save_more")}}'
                  class='btn btn-success'>
                @endif

                @if($button_save && isset($command) && $command != 'detail')
                <input type="submit" name="submit" value='{{trans("crudbooster.button_save")}}' class='btn btn-success'>
                @endif

                @endif
              </div>
            </div>


          </div><!-- /.box-footer-->

        </form>

      </div>
    </div>
  </div>
</div><!--END AUTO MARGIN-->

@endsection
@extends('crudbooster::admin_template',['target_layout' => $target_layout])

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
    <div class="col-sm-12 col-md-12">
      <div class="box box-info collapsed-box">
        <div class="box-header with-border">
          <h3 class="box-title">
            <strong>
              <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> Testata
            </strong>
          </h3>
          <div class="box-tools pull-right">
            <!-- <span class="label label-info">0 righe</span> -->
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>

        <div class="box-body no-padding">
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
              @include("crudbooster::ordini.form_detail")
              @else
              @include("crudbooster::ordini.form_body")
              @endif
            </div><!-- /.box-body -->

            <div class="box-footer" style="background: #F5F5F5">

              <div class="form-group">
                <label class="control-label col-sm-2"></label>
                <div class="col-sm-10">
                  @if($button_cancel && CRUDBooster::getCurrentMethod() != 'getDetail')
                  @if(g('return_url'))
                  <a href='{{g("return_url")}}' class='btn btn-default'>
                    <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
                  </a>
                  @else
                  <a href='{{CRUDBooster::mainpath("?".http_build_query(@$_GET)) }}' class='btn btn-default'>
                    <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
                  </a>
                  @endif
                  @endif
                  @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())

                  @if(CRUDBooster::isCreate() && $button_addmore==TRUE && isset($command) && $command == 'add')
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
    </div>
  </div>

  <div class="row">
    <div class="col-sm-12 col-md-12">
      <div class="box box-success collapsed-box">
        <div class="box-header with-border">
          <h3 class="box-title">
            <strong>
              <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> Aggiungi riga
            </strong>
          </h3>
          <div class="box-tools pull-right">
            <span class="label label-sucess">0 righe</span>
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>

        <div class="box-body table-responsive no-padding">
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
              @include("crudbooster::ordini.form_detail")
              @else
              @include("crudbooster::ordini.form_body")
              @endif
            </div><!-- /.box-body -->

            <div class="box-footer" style="background: #F5F5F5">

              <div class="form-group">
                <label class="control-label col-sm-2"></label>
                <div class="col-sm-10">
                  @if($button_cancel && CRUDBooster::getCurrentMethod() != 'getDetail')
                  @if(g('return_url'))
                  <a href='{{g("return_url")}}' class='btn btn-default'>
                    <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
                  </a>
                  @else
                  <a href='{{CRUDBooster::mainpath("?".http_build_query(@$_GET)) }}' class='btn btn-default'>
                    <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
                  </a>
                  @endif
                  @endif
                  @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())

                  @if(CRUDBooster::isCreate() && $button_addmore==TRUE && isset($command) && $command == 'add')
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
    </div>
  </div>

  <div class="row">
    <div class="col-sm-12 col-md-12">
      <div class="box box-warning">
        <div class="box-header with-border">
          <h3 class="box-title">
            <strong>
              <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> Righe
            </strong>
          </h3>
          <div class="box-tools pull-right">
            <span class="label label-sucess">0 righe</span>
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
              <i class="fa fa-minus"></i>
            </button>
          </div>
        </div>

        <div class="box-body table-responsive no-padding">
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
              @include("crudbooster::ordini.form_detail")
              @else
              @include("crudbooster::ordini.form_body")
              @endif
            </div><!-- /.box-body -->

            <div class="box-footer" style="background: #F5F5F5">

              <div class="form-group">
                <label class="control-label col-sm-2"></label>
                <div class="col-sm-10">
                  @if($button_cancel && CRUDBooster::getCurrentMethod() != 'getDetail')
                  @if(g('return_url'))
                  <a href='{{g("return_url")}}' class='btn btn-default'>
                    <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
                  </a>
                  @else
                  <a href='{{CRUDBooster::mainpath("?".http_build_query(@$_GET)) }}' class='btn btn-default'>
                    <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
                  </a>
                  @endif
                  @endif
                  @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())

                  @if(CRUDBooster::isCreate() && $button_addmore==TRUE && isset($command) && $command == 'add')
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
    </div>
  </div>

</div>

@endsection
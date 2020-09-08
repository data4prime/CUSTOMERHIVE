@extends('crudbooster::admin_template')

@section('content')
<!-- Add item -->
<div class="box-body table-responsive">
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

      <div class="panel panel-default">
          <div class="panel-heading">
            <strong><i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> Allow Item</strong>
          </div>

          <div class="panel-body" style="padding:20px 0px 0px 0px">
              <form class='form-horizontal' method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
                  <input type="hidden" name="_token" value="{{ csrf_token() }}">
                  <input type='hidden' name='return_url' value='{{ @$return_url }}'/>
                  <input type='hidden' name='ref_mainpath' value='{{ CRUDBooster::mainpath() }}'/>
                  <input type='hidden' name='ref_parameter' value='{{urldecode(http_build_query(@$_GET))}}'/>
                  @if($hide_form)
                      <input type="hidden" name="hide_form" value='{!! serialize($hide_form) !!}'>
                  @endif
                  <div class="box-body" id="parent-form-area">

                      @if($command == 'detail')
                          @include("crudbooster::default.form_detail")
                      @else
                          @include("crudbooster::default.form_body")
                      @endif
                  </div><!-- /.box-body -->

                  <div class="box-footer" style="background: #F5F5F5">

                      <div class="form-group">
                          <label class="control-label col-sm-2"></label>
                          <div class="col-sm-10">
                              @if($button_cancel && CRUDBooster::getCurrentMethod() != 'getDetail')
                                  @if(g('return_url'))
                                      <a href='{{g("return_url")}}' class='btn btn-default'><i
                                                  class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}</a>
                                  @else
                                      <a href='{{CRUDBooster::mainpath("?".http_build_query(@$_GET)) }}' class='btn btn-default'><i
                                                  class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}</a>
                                  @endif
                              @endif
                              @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())

                                  @if(CRUDBooster::isCreate() && $button_addmore==TRUE && $command == 'add')
                                      <input type="submit" name="submit" value='{{trans("crudbooster.button_save_more")}}' class='btn btn-success'>
                                  @endif

                                  @if($button_save && $command != 'detail')
                                      <input type="submit" name="submit" value='{{trans("crudbooster.button_add_item")}}' class='btn btn-success'>
                                  @endif

                              @endif
                          </div>
                      </div>


                  </div><!-- /.box-footer-->

              </form>

          </div>
      </div>
  </div><!--END AUTO MARGIN-->
<!-- List members -->
<div class="box">
  <div class="box-header">
    <h4>{{ $group->name }} Allowed Items</h4>
  </div>
  <div class="box-body table-responsive no-padding">
    <form id='form-table' method='post' action='{{CRUDBooster::mainpath("action-selected")}}'>
      <input type='hidden' name='button_name' value=''/>
      <input type='hidden' name='_token' value='{{csrf_token()}}'/>
      <table class='table table-striped table-bordered'>
        <thead>
          <tr>
            <th>Title</th>
            <th>Subtitle</th>
            <th>Url</th>
            <th>Description</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $item)
          <tr>
            <td>{{$item->title}}</td>
            <td>{{$item->subtitle}}</td>
            <td>{{$item->url}}</td>
            <td>{{$item->description}}</td>
            <td>
              @if(CRUDBooster::isDelete() && $button_edit)
              <a title='Remove' class='btn btn-danger btn-sm' href='{{CRUDBooster::mainpath("$group_id/remove_item/$item->id")}}'><i class="fa fa-trash"></i></a>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </form>
  </div>
</div>
@endsection

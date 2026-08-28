@extends('crudbooster::admin_template')

@section('content')
<div>

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

  <div class="card card-default">
    <div class="card-header">
      <strong>
        <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> {!! $page_title !!}
      </strong>
    </div>
    <div class="card-body" style="padding:20px 0px 0px 0px">

      <div class="box-body">
        <div class='mb-3 row'>
          <label class='col-form-label col-sm-2'>Layout Name</label>
          <div class="col-sm-10">
            <p class="form-control-static">{{ $row->layoutname }}</p>
          </div>
        </div>

        <div class='mb-3 row'>
          <label class='col-form-label col-sm-2'>Preview</label>
          <div class="col-sm-10">
            @if(trim($code_layout_html) === '')
            <p class="help-block">Nessuna riga configurata per questo layout.</p>
            @else
            <div class="dl-preview">
              {!! $code_layout_html !!}
            </div>
            @endif
          </div>
        </div>
      </div>

      <div class="box-footer" style="background: #F5F5F5">
        <div class="mb-3 row">
          <label class="col-form-label col-sm-2"></label>
          <div class="col-sm-10">
            @if(g('return_url'))
            <a href='{{g("return_url")}}' class='btn btn-default'>
              <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
            </a>
            @else
            <a href='{{CRUDBooster::mainpath()}}' class='btn btn-default'>
              <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
            </a>
            @endif
            <a href='{{CRUDBooster::mainpath("edit/".$row->id)}}' class='btn btn-success'>
              <i class='fa fa-pencil'></i> {{trans("crudbooster.button_edit")}}
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

<style>
  .dl-preview .statistic-row.row {
    margin-bottom: 10px;
  }
  .dl-preview .connectedSortable {
    border: 2px dashed #b0b7c3;
    border-radius: 4px;
    background: #f8f9fa;
    min-height: 80px;
    position: relative;
    padding: 18px 6px 6px 6px;
    box-sizing: border-box;
  }
  .dl-preview .connectedSortable::before {
    content: 'Area: ' attr(id);
    position: absolute;
    top: 2px;
    left: 6px;
    font-size: 11px;
    color: #888;
  }
</style>
@endsection

@extends('crudbooster::admin_template')

@section('content')
<div>

  <div class="card card-default">
    <div class="card-header">
      <strong>
        <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> {!! $page_title !!}
      </strong>
    </div>
    <div class="card-body">
      <div class="alert alert-warning">
        <i class="fa fa-exclamation-triangle"></i>
        {{ trans('crudbooster.api_tokens_reveal_warning') }}
      </div>

      <div class="mb-3 row">
        <div class="col-sm-10">
          <div class="input-group">
            <input type="text" class="form-control" id="plain_text_token" value="{{ $plain_text_token }}" readonly onclick="this.select();">
            <button type="button" class="btn btn-outline-secondary" onclick="
              navigator.clipboard.writeText(document.getElementById('plain_text_token').value);
              this.innerText = '{{ trans('crudbooster.api_tokens_reveal_copied') }}';
            ">{{ trans('crudbooster.api_tokens_reveal_copy') }}</button>
          </div>
        </div>
      </div>

      <p>
        <a title='Main Module' href='{{CRUDBooster::mainpath()}}' class="btn btn-primary">
          <i class='fa fa-chevron-circle-left '></i>&nbsp;
          {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
        </a>
      </p>
    </div>
  </div>

</div>
@endsection

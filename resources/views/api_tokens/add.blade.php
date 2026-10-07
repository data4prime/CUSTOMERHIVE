@extends('crudbooster::admin_template')

@section('content')
@push('head')
<link href="{{ asset('css/ch-api.css') }}?v={{ @filemtime(public_path('css/ch-api.css')) }}" rel="stylesheet" type="text/css" />
@endpush
@php $expiry = old('expiry_choice', '30'); @endphp
<div>

  <p>
    <a title='Main Module' href='{{CRUDBooster::mainpath()}}'>
      <i class='bi bi-chevron-left'></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>

  <form method='post' action='{{ $action }}'>
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class="api-card">
      <div class="api-card-b">
        <p class="api-help mt-0 mb-4">{{ trans('crudbooster.api_tokens_add_intro') }}</p>

        <div class="row g-4">
        <div class='col-lg-6 {{ $errors->first("tokenable_id")?"has-error":"" }}'>
          <label class='api-lbl' for="tokenable_id">{{ trans('crudbooster.api_tokens_field_user') }} <span class='text-danger'>*</span></label>
          <select class="form-select" name="tokenable_id" id="tokenable_id" required>
            <option value="">-</option>
            @foreach($api_clients as $client)
              <option value="{{ $client->id }}" {{ old('tokenable_id') == $client->id ? 'selected' : '' }}>
                {{ $client->name }} ({{ $client->email }})
              </option>
            @endforeach
          </select>
          <div class="api-help">{{ trans('crudbooster.api_tokens_field_user_help') }}</div>
          <div class="text-danger">{!! $errors->first('tokenable_id') !!}</div>
        </div>

        <div class='col-lg-6 {{ $errors->first("name")?"has-error":"" }}'>
          <label class='api-lbl' for="token_name">{{ trans('crudbooster.api_tokens_field_name') }} <span class='text-danger'>*</span></label>
          <input type='text' class='form-control' name='name' id="token_name" required value='{{ old("name") }}' />
          <div class="api-help">{{ trans('crudbooster.api_tokens_field_name_help') }}</div>
          <div class="text-danger">{!! $errors->first('name') !!}</div>
        </div>

        <div class='col-12 {{ $errors->first("expiry_choice")?"has-error":"" }}'>
          <span class='api-lbl'>{{ trans('crudbooster.api_tokens_field_expiry') }} <span class='text-danger'>*</span></span>
          <div class="btn-group flex-wrap" role="group">
            @foreach(['30' => 'api_tokens_expiry_30', '90' => 'api_tokens_expiry_90', '365' => 'api_tokens_expiry_365', 'never' => 'api_tokens_expiry_never', 'custom' => 'api_tokens_expiry_custom_option'] as $val => $key)
              <input type="radio" class="btn-check" name="expiry_choice" id="expiry-{{ $val }}" value="{{ $val }}" {{ $expiry == $val ? 'checked' : '' }} required>
              <label class="btn btn-secondary" for="expiry-{{ $val }}">{{ trans('crudbooster.'.$key) }}</label>
            @endforeach
          </div>
          <div class="api-help" id="expiry-hint"></div>
          <div class="text-danger">{!! $errors->first('expiry_choice') !!}</div>
        </div>

        <div class='col-lg-6 {{ $errors->first("expiry_custom_date")?"has-error":"" }}' id="expiry_custom_date_wrapper" style="display: {{ $expiry === 'custom' ? 'block' : 'none' }};">
          <label class='api-lbl' for="expiry_custom_date">{{ trans('crudbooster.api_tokens_field_expiry_custom') }}</label>
          <input type='date' class='form-control' name='expiry_custom_date' id="expiry_custom_date" value='{{ old("expiry_custom_date") }}' />
          <div class="text-danger">{!! $errors->first('expiry_custom_date') !!}</div>
        </div>
        </div>
      </div>
      <div class="api-card-f d-flex justify-content-end gap-2">
        <a class="btn btn-secondary" href="{{ CRUDBooster::mainpath() }}">{{ trans('crudbooster.api_cancel') }}</a>
        <button type="submit" class="btn btn-primary">{{ trans('crudbooster.api_tokens_submit') }}</button>
      </div>
    </div>
  </form>

</div>

@push('bottom')
<script>
$(function () {
  var never = {!! json_encode(trans('crudbooster.api_tokens_expiry_never_hint')) !!};
  var expiresOn = {!! json_encode(trans('crudbooster.api_tokens_expires_on')) !!};
  var loc = {!! json_encode(str_replace('_', '-', app()->getLocale())) !!};
  function refresh() {
    var v = $('input[name=expiry_choice]:checked').val();
    $('#expiry_custom_date_wrapper').toggle(v === 'custom');
    var hint = '';
    if (v === 'never') hint = never;
    else if (v && v !== 'custom') {
      var d = new Date(); d.setDate(d.getDate() + parseInt(v, 10));
      hint = expiresOn.replace(':date', d.toLocaleDateString(loc, {day: 'numeric', month: 'long', year: 'numeric'}));
    }
    $('#expiry-hint').text(hint);
  }
  $('input[name=expiry_choice]').on('change', refresh);
  refresh();
});
</script>
@endpush
@endsection

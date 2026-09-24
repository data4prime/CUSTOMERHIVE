@extends('crudbooster::admin_template')

@section('content')
<div>

  <p>
    <a title='Main Module' href='{{CRUDBooster::mainpath()}}'>
      <i class='fa fa-chevron-circle-left '></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>

  <div class="card card-default">
    <div class="card-header">
      <strong>
        <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> {!! $page_title !!}
      </strong>
    </div>
    <div class="card-body">
      <form method='post' action='{{ $action }}'>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div class='mb-3 row {{ $errors->first("tokenable_id")?"has-error":"" }}'>
          <label class='col-form-label col-sm-2'>
            {{ trans('crudbooster.api_tokens_field_user') }}
            <span class='text-danger'>*</span>
          </label>
          <div class="col-sm-6">
            <select class="form-control" name="tokenable_id" required>
              <option value="">-</option>
              @foreach($api_clients as $client)
                <option value="{{ $client->id }}" {{ old('tokenable_id') == $client->id ? 'selected' : '' }}>
                  {{ $client->name }} ({{ $client->email }})
                </option>
              @endforeach
            </select>
            <small class="form-text text-muted">{{ trans('crudbooster.api_tokens_field_user_help') }}</small>
            <div class="text-danger">{!! $errors->first('tokenable_id') !!}</div>
          </div>
        </div>

        <div class='mb-3 row {{ $errors->first("name")?"has-error":"" }}'>
          <label class='col-form-label col-sm-2'>
            {{ trans('crudbooster.api_tokens_field_name') }}
            <span class='text-danger'>*</span>
          </label>
          <div class="col-sm-6">
            <input type='text' class='form-control' name='name' required value='{{ old("name") }}' />
            <small class="form-text text-muted">{{ trans('crudbooster.api_tokens_field_name_help') }}</small>
            <div class="text-danger">{!! $errors->first('name') !!}</div>
          </div>
        </div>

        <div class='mb-3 row {{ $errors->first("expiry_choice")?"has-error":"" }}'>
          <label class='col-form-label col-sm-2'>
            {{ trans('crudbooster.api_tokens_field_expiry') }}
            <span class='text-danger'>*</span>
          </label>
          <div class="col-sm-6">
            <select class="form-control" name="expiry_choice" id="expiry_choice" required onchange="document.getElementById('expiry_custom_date_wrapper').style.display = (this.value === 'custom') ? 'block' : 'none';">
              <option value="30" {{ old('expiry_choice') == '30' ? 'selected' : '' }}>{{ trans('crudbooster.api_tokens_expiry_30') }}</option>
              <option value="90" {{ old('expiry_choice') == '90' ? 'selected' : '' }}>{{ trans('crudbooster.api_tokens_expiry_90') }}</option>
              <option value="365" {{ old('expiry_choice') == '365' ? 'selected' : '' }}>{{ trans('crudbooster.api_tokens_expiry_365') }}</option>
              <option value="never" {{ old('expiry_choice') == 'never' ? 'selected' : '' }}>{{ trans('crudbooster.api_tokens_expiry_never') }}</option>
              <option value="custom" {{ old('expiry_choice') == 'custom' ? 'selected' : '' }}>{{ trans('crudbooster.api_tokens_expiry_custom_option') }}</option>
            </select>
            <div class="text-danger">{!! $errors->first('expiry_choice') !!}</div>
          </div>
        </div>

        <div class='mb-3 row {{ $errors->first("expiry_custom_date")?"has-error":"" }}' id="expiry_custom_date_wrapper" style="display: {{ old('expiry_choice') === 'custom' ? 'block' : 'none' }};">
          <label class='col-form-label col-sm-2'>
            {{ trans('crudbooster.api_tokens_field_expiry_custom') }}
          </label>
          <div class="col-sm-6">
            <input type='date' class='form-control' name='expiry_custom_date' value='{{ old("expiry_custom_date") }}' />
            <div class="text-danger">{!! $errors->first('expiry_custom_date') !!}</div>
          </div>
        </div>

        <div class="mb-3 row">
          <div class="col-sm-10 offset-sm-2">
            <button type="submit" class="btn btn-primary">{{ trans('crudbooster.api_tokens_submit') }}</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection

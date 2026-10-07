@extends('crudbooster::admin_template')

@section('content')
@push('head')
<link href="{{ asset('css/ch-api.css') }}?v={{ @filemtime(public_path('css/ch-api.css')) }}" rel="stylesheet" type="text/css" />
@endpush
<div class="api-narrow">

  <div class="alert alert-warning d-flex gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span><strong>{{ trans('crudbooster.api_tokens_reveal_title') }}</strong> {{ trans('crudbooster.api_tokens_reveal_warning') }}</span>
  </div>

  <div class="api-card">
    <div class="api-card-b d-flex flex-column gap-3">
      <span class="api-lbl m-0">{{ trans('crudbooster.api_tokens_reveal_label') }}</span>
      <div class="api-token" id="plain_text_token">{{ $plain_text_token }}</div>
      <div>
        <button type="button" class="btn btn-primary" id="copy_token">
          <i class="bi bi-clipboard"></i> <span>{{ trans('crudbooster.api_tokens_reveal_copy') }}</span>
        </button>
      </div>
    </div>
  </div>

  <div class="api-card">
    <div class="api-card-h">{{ trans('crudbooster.api_tokens_reveal_howto') }}</div>
    <div class="api-card-b">
      <pre class="api-code">curl {{ url('api2') }}/<span style="opacity:.6">{{ trans('crudbooster.api_doc_example_slug') }}</span> \
  -H "Authorization: Bearer {{ \Illuminate\Support\Str::limit($plain_text_token, 9, '…') }}"</pre>
    </div>
  </div>

  <a href="{{ CRUDBooster::mainpath() }}" class="btn btn-secondary">
    <i class='bi bi-chevron-left'></i> {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
  </a>

</div>

@push('bottom')
<script>
$(function () {
  var copied = {!! json_encode(trans('crudbooster.api_tokens_reveal_copied')) !!};
  $('#copy_token').on('click', function () {
    var btn = $(this), text = document.getElementById('plain_text_token').textContent.trim();
    var done = function () { btn.find('span').text(copied); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () {});
    }
  });
});
</script>
@endpush
@endsection

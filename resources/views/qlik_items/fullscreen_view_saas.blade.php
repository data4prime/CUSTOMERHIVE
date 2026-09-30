@php
$debug_url = '';
if ($debug == 'Active') {
$debug_url = $item_url;
}


@endphp

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">


<div class="card">
  <div class="card-header">
    <h4 class="qi_subtitle">{{ $page_title }}</h4>
    <h6 class="qi_subtitle">{{ $subtitle }}</h6>
    <a href="{{$debug_url}}" target="_blank">{{$debug_url}}</a>
  </div>

</div>

<iframe id="qlik_frame" class="qi_iframe" data-src="{{$item_url}}" src=""  style="border:none;"></iframe>




<style>
  /*set iframe size*/
  .qi_iframe {
    width: {{ \App\Helpers\QlikHelper::safeCssSize($row->frame_width) }} !important;

    height: {{ \App\Helpers\QlikHelper::safeCssSize($row->frame_height) }} !important;

    border: none;
    @if($row->target_layout ==1) margin-left: auto;
    margin-right: auto;
    display: block;
    @endif
  }

  body {
    margin: 0px;
  }
</style>

<script>
  //const TENANT = '{{ $tenant }}/{{$prefix}}';

  const TENANT = {!! json_encode((string) $tenant) !!};

  const PREFIX = {!! json_encode((string) $prefix) !!};

  const WEBINTEGRATIONID = {!! json_encode((string) $web_int_id) !!};
  const APPID = '##APP##';
  const JWTTOKEN = {!! json_encode((string) $token) !!};
  const QLIK_I18N = {!! json_encode(['login_failed' => trans('crudbooster.qlik_login_failed')]) !!};
</script>
<script src="@php echo asset($js_login) @endphp"></script>


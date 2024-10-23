@php
$debug_url = '';
if ($debug == 'Active') {
$debug_url = $item_url;
}


@endphp

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">


<div class="card">
  <div class="card-header">
    <h4 class="qi_subtitle">{{ title }}</h4>
    <h6 class="qi_subtitle">{{ $subtitle }}</h6>
    <a href="{{$debug_url}}" target="_blank">{{$debug_url}}</a>
  </div>
  <div class="card-body">
    <iframe id="qlik_frame" class="qi_iframe" data-src="{{$item_url}}" src=""  style="border:none;"></iframe>
  </div>
</div>




<style>
  /*set iframe size*/
  .qi_iframe {
    width: @php echo $row->frame_width @endphp !important;

    height: @php echo $row->frame_height @endphp !important;

    !important;
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

  const TENANT = '{{ $tenant }}';

  const PREFIX = '{{ $prefix }}';

  const WEBINTEGRATIONID = '{{ $web_int_id }}';
  const APPID = '##APP##';
  const JWTTOKEN = "{{ $token}}";
</script>
<script src="@php echo asset($js_login) @endphp"></script>


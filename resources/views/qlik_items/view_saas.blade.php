@extends('crudbooster::admin_template',['target_layout' => isset($row->target_layout) ? $row->target_layout : null ])
@php
$debug_url = '';
if ($debug == 'Active') {
$debug_url = $item_url;
}


@endphp
@if(isset($row->target_layout) && $row->target_layout == 2)
<!-- fill content settings -->
@section('content')
<div class="card qi_iframe_container">
  <div class="card-header">
    <h4 class="qi_subtitle">{{ $subtitle }}</h4>
    <a href="{{$debug_url}}" target="_blank">{{$debug_url}}</a>
  </div>
  <div class="card-body">
    <iframe class="qi_iframe" data-src="{{$item_url}}" src=""  style="border:none;"></iframe>
  </div>
  
  
</div>
@endsection
@else
<!-- default -->
@section('content')
<div class="card qi_box">
  <div class="card-header">
    <h4 class="qi_subtitle">{{ $subtitle }}</h4>
    <a href="{{$debug_url}}" target="_blank">{{$debug_url}}</a>
  </div>

<!--qi_iframe_container-->
  <div class="card-body">
    
    <iframe class="qi_iframe" data-src="{{$item_url}}" src=""  style="border:none;"></iframe>
  </div>
</div>
@endsection
@endif

@push('bottom')
<script type="text/javascript">
  $(document).ready(function () {
    //aggiungi icona al titolo delle pagine iframe qlik
    var menu_item = $('li.active').first();
    if (menu_item.hasClass('treeview')) {
      //prendi icona dal child
      icon = $('li.active:not(.treeview):first i')[0].className;
    }
    else {
      //prendi icona
      if ($('li.active i')[0]) {
        icon = $('li.active i')[0].className;
      }
      else {
        icon = '';
      }
    }
    $('#title_icon').addClass('fa ' + icon);

    if ($('#title_icon').hasClass('qlik_icon')) {
      //prendi icona dal child
      var qlik_logo = '<img class="qlik_logo" src=/images/qlik_logo.png />';
      $(qlik_logo).insertBefore($('#title_icon'));
    }





  })
</script>



@endpush
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
@push('head')
<style>
  /*set iframe size*/
  .qi_iframe {
    width: {{ \App\Helpers\QlikHelper::safeCssSize($row->frame_width) }} !important;

    height: {{ \App\Helpers\QlikHelper::safeCssSize($row->frame_height) }} !important;
  }
</style>
@endpush
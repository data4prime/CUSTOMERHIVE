@php
$debug_url = '';
if ($debug == 'Active') {
$debug_url = $item_url;
}


@endphp

<a href="{{$debug_url}}" target="_blank">{{$debug_url}}</a>
<iframe class="qi_iframe" data-src="{{$item_url}}" src=""  style="border:none;"></iframe>

<style>
  /*set iframe size*/
  .qi_iframe {
    width: @php echo $row->frame_width @endphp !important;

    height: @php echo $row->frame_height @endphp !important;
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
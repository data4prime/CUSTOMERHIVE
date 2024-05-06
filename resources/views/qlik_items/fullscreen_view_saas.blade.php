@php
$debug_url = '';
if ($debug == 'Active') {
$debug_url = $item_url;
}


@endphp


<a href="{{$debug_url}}" target="_blank">{{$debug_url}}</a><iframe id="qlik_frame" class="qi_iframe"
  src="{{ $item_url }}"  style="border:none;"></iframe>

<style>
  /*set iframe size*/
  .qi_iframe {
    width: {
        {
        $row->frame_width
      }
    }

    !important;

    height: {
        {
        $row->frame_height
      }
    }

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
<script defer src="{{asset('js/qliksaas_login.js')}}"></script>
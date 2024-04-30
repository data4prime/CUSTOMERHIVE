<a target="_blank" href="{{ $debug == 'Active' ? html_entity_decode($item_url)  : '' }}">{{ $debug == 'Active' ?
  html_entity_decode($item_url) : '' }}</a><iframe id="qlik_frame" class="qi_iframe" src="{{ $item_url }}"
   style="border:none;"></iframe>

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
<script defer src="{{asset(' js/qliksaas_login.js')}}"></script>
<iframe class="qi_iframe" src="{{ $item_url }}" style="border:none;"></iframe>

<style>
  /*set iframe size*/
  .qi_iframe{
    width: {{ $row->frame_width }} !important;
    height: {{ $row->frame_height }} !important;
    border: none;
    @if($row->target_layout == 1)
    margin-left: auto;
    margin-right: auto;
    display: block;
    @endif
  }
  body{
    margin: 0px;
  }
</style>

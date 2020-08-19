@extends('crudbooster::admin_template')

@if($row->full_page)
  <!-- full page settings -->
  @push('bottom')
    <script type="text/javascript">
      $( document ).ready(function() {
        //shrink sidebar
        $('.sidebar-toggle').click();
        //expand container
        $('#content_section').css('padding','0px');
        $('#content_section').css('height','calc(100vh - 42px');
        $('.qi_iframe_container').css('height','100%');
        $('.qi_iframe').css('padding-bottom','0px');
        $('.content-wrapper').css('min-height','0px !important');
        //hide section header
        $('.content-header').css('display','none');

      })
    </script>
  @endpush
  @section('content')
    <div class="qi_iframe_container">
        <iframe class="qi_iframe" src="{{ $item_url }}" style="border:none;"></iframe>
    </div>
  @endsection
@else
  <!-- default -->
  @section('content')
  <div class="box qi_box">
    <h4 class="qi_subtitle">{{ $subtitle }}</h4>
    <div class="qi_iframe_container">
        <iframe class="qi_iframe" src="{{ $item_url }}" style="border:none;"></iframe>
    </div>
  </div>
  @endsection
@endif

@push('bottom')
<script type="text/javascript">
  $( document ).ready(function() {
    //aggiungi icona al titolo delle pagine iframe qlik
    var menu_item = $('li.active').first();
    if(menu_item.hasClass('treeview')){
      //prendi icona dal child
      icon = $('li.active:not(.treeview):first i')[0].className;
    }
    else{
      //prendi icona
      if($('li.active i')[0]){
        icon = $('li.active i')[0].className;
      }
      else{
        icon = '';
      }
    }
    $('#title_icon').addClass('fa '+ icon);

    if($('#title_icon').hasClass('qlik_icon')){
      //prendi icona dal child
      var qlik_logo = '<img class="qlik_logo" src=/images/qlik_logo.png />';
      $(qlik_logo).insertBefore($('#title_icon'));
    }

  })
</script>
@endpush

@push('head')
<style>
  /*set iframe size*/
  .qi_iframe{
    width: {{ $row->frame_width }} !important;
    height: {{ $row->frame_height }} !important;
  }
</style>
@endpush

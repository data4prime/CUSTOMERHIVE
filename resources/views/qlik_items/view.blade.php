@extends('crudbooster::admin_template')

@section('content')
<div class="box">
  <h4 class="qi_subtitle">{{ $subtitle }}</h4>
  <div class="qi_iframe_container">
      <iframe class="qi_iframe" src="{{ $item_url }}" style="border:none;"></iframe>
  </div>
</div>
@endsection

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
      icon = $('li.active i')[0].className;
    }
    $('#title_icon').addClass('fa '+ icon);

  })
</script>
@endpush

@push('head')
<style>
  #help_icon{
    font-size: 0.7em;
    vertical-align: top;
  }
  /*set iframe size*/
  .qi_iframe{
    width:{{ $row->frame_width }} !important;
    height:{{ $row->frame_height }} !important;
    margin-left: auto;
    margin-right: auto;
    padding-bottom: 8px;
    display: block;
  }
  .qi_iframe_container{
    height: 94%;
    width: 100%;
  }
  .box{
    height: 100%;
  }
  #content_section{
    height: calc(100vh - 50px - 51px - 42px);
    width: 100%;
  }
</style>
@endpush

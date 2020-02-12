@extends('crudbooster::admin_template')

@section('content')
<div class="box">
  <h4 class="qi_subtitle">{{ $subtitle }}</h4>
  <div class="qi_iframe_container">
      <iframe class="qi_iframe" src="{{ $item_url }}" style="border:none;width:100%;height:100%;"></iframe>
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
</style>
@endpush

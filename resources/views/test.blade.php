@extends('crudbooster::admin_template')

@section('content')
<div class="box">
  <div class="iframe_container">
      <iframe class="myIframe" src="{{ $item_url }}" style="border:none;width:100%;height:100%;"></iframe>
  </div>
</div>
@endsection

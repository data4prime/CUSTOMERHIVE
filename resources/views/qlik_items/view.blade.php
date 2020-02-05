@extends('crudbooster::admin_template')

@section('content')
<div class="box">
  <h4 class="qi_subtitle">{{ $subtitle }}</h4>
  <div class="qi_iframe_container">
      <iframe class="qi_iframe" src="{{ $item_url }}" style="border:none;width:100%;height:100%;"></iframe>
  </div>
  <p class="qi_description">{{ $description }}</p>
</div>
@endsection

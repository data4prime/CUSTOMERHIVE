@extends('crudbooster::admin_template')

@section('content')
<div class="box">
  <h4 class="qi_subtitle">{{ $subtitle }}</h4>
  <div class="callout callout-warning"><b>Warning!</b> {{ $error }}</div>
  <p class="qi_description">{{ $description }}</p>
</div>
@endsection

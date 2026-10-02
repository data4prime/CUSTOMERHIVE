@extends('crudbooster::admin_template')

@section('content')

    <!-- Custom Tabs -->
    <ul class="nav nav-tabs">
        <li class="active"><a href="{{ CRUDBooster::mainpath('documentation') }}"><i class='bi bi-file-earmark-fill'></i> API Documentation</a></li>
        <li><a href="{{ CRUDBooster::mainpath('screet-key') }}"><i class='bi bi-key-fill'></i> API Secret Key</a></li>
        <li><a href="{{ CRUDBooster::mainpath('generator') }}"><i class='bi bi-gear-fill'></i> API Generator</a></li>
    </ul>

    <div class='box'>
        <div class='box-header mb-3'><h3 class='box-title'>API Documentation</h3></div>
        <div class='box-body'>
            @include('crudbooster::api_documentation')
        </div>
    </div>

    <!-- nav-tabs-custom -->

@endsection
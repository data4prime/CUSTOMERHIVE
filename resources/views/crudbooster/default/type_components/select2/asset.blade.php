@push('head')

<link rel='stylesheet' href='{{ asset("vendor/crudbooster/assets/select2/dist/css/select2.min.css") }}' />
    {{-- Aspetto di select2: public/css/ch-components.css (token --ch-*) --}}
@endpush
@push('bottom')
<!-- <script src='<?php echo asset("vendor/crudbooster/assets/select2/dist/js/select2.full.min.js")?>'></script> -->
<script src='{{ asset("vendor/crudbooster/assets/select2/dist/js/select2.full.js") }}'></script>
@endpush
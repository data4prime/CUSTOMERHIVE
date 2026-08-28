@extends("crudbooster::admin_template")

@push('head')
<link rel='stylesheet' href='<?php echo asset("vendor/crudbooster/assets/select2/dist/css/select2.min.css")?>'/>
<style>
.select2-container--default .select2-selection--single {
  border-radius: 0px !important
}

.select2-container .select2-selection--single {
  height: 35px
}
</style>
@endpush

@push('bottom')
<script src='<?php echo asset("vendor/crudbooster/assets/select2/dist/js/select2.full.min.js")?>'></script>
<script>
$(function () {
  $('.select2').select2();
})
</script>
@endpush

@section("content")
  @include('crudbooster::module_generator.navigation')
  @yield('inner_content')
@endsection

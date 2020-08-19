@extends('crudbooster::admin_template')

@section('content')
<div class="row">
  <div class="col-md-3 col-sm-6 col-xs-12">
    <div class="info-box">
      <span class="info-box-icon bg-aqua"><i class="ion ion-ios-gear-outline"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Modules generated</span>
        <span class="info-box-number">{{$modules_count}}</small></span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
  <!-- /.col -->
  <div class="col-md-3 col-sm-6 col-xs-12">
    <div class="info-box">
      <span class="info-box-icon bg-red"><i class="fa fa-area-chart"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Qlik items published</span>
        <span class="info-box-number">{{$qlik_items_count}}</span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
  <!-- /.col -->

  <!-- fix for small devices only -->
  <div class="clearfix visible-sm-block"></div>

  <div class="col-md-3 col-sm-6 col-xs-12">
    <div class="info-box">
      <span class="info-box-icon bg-green"><i class="fa fa-users"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Total Groups</span>
        <span class="info-box-number">{{$total_groups}}</span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
  <!-- /.col -->
  <div class="col-md-3 col-sm-6 col-xs-12">
    <div class="info-box">
      <span class="info-box-icon bg-yellow"><i class="fa fa-user"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Users log in</span>
        <span class="info-box-number">{{$log_in_count}}</span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
  <!-- /.col -->
</div>
@endsection

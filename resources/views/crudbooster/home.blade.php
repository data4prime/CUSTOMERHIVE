@extends('crudbooster::admin_template')

@section('content')
<div class="row">
  <div class="col-md-3 col-sm-6 col-12">
    <div class="info-box">
      <span class="info-box-icon bg-aqua"><i class="bi bi-gear"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Modules generated</span>
        <span class="info-box-number">{{$modules_count}}</small></span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
  <!-- /.col -->
  <div class="col-md-3 col-sm-6 col-12">
    <div class="info-box">
      <span class="info-box-icon bg-red"><i class="bi bi-graph-up"></i></span>

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

  <div class="col-md-3 col-sm-6 col-12">
    <div class="info-box">
      <span class="info-box-icon bg-green"><i class="bi bi-people-fill"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Total Groups</span>
        <span class="info-box-number">{{$total_groups}}</span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
  <!-- /.col -->
  <div class="col-md-3 col-sm-6 col-12">
    <div class="info-box">
      <span class="info-box-icon bg-yellow"><i class="bi bi-person-fill"></i></span>

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
  <div class="row">
    <div class="col-md-4 col-sm-6 col-12">
      <div class="box box-danger">
        <div class="box-header mb-3 with-border">
          <h3 class="box-title">Latest Members</h3>

          <div class="box-tools float-end">
            <span class="badge text-bg-danger">{{$weekly_new_users_count}} New Members</span>
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="bi bi-dash-lg"></i>
            </button>
            <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
        <!-- /.box-header mb-3 -->
        <div class="box-body no-padding">
          <ul class="users-list clearfix">
            <?php foreach ($latest_users as $key => $user): ?>
            <li>
              <div class="d-flex justify-content-center">{!! \App\Http\Controllers\System\LogsController::avatarHtml($user->name, $user->photo, $user->id, 56) !!}</div>
              <a class="users-list-name" href="/admin/users/edit/{{ $user->id }}">{{ $user->name }}</a>
              <span class="users-list-date">{{ date('d-m-yy', strtotime($user->created_at)) }}</span>
            </li>
            <?php endforeach; ?>
          </ul>
          <!-- /.users-list -->
        </div>
        <!-- /.box-body -->
        <div class="box-footer text-center">
          <a href="/admin/users" class="uppercase">View All Users</a>
        </div>
        <!-- /.box-footer -->
      </div>
    </div>
  </div>
@endsection

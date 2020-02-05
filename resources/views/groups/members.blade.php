@extends('crudbooster::admin_template')

@section('content')
<div class="box">
  <div class="box-header">
    <h4>{{ $group->name }} Members</h4>
  </div>
  <div class="box-body table-responsive no-padding">
    <form id='form-table' method='post' action='{{CRUDBooster::mainpath("action-selected")}}'>
      <input type='hidden' name='button_name' value=''/>
      <input type='hidden' name='_token' value='{{csrf_token()}}'/>
      <table class='table table-striped table-bordered'>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Photo</th>
            <th>Privilege</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($members as $member)
          <tr>
            <td>{{$member->name}}</td>
            <td>{{$member->email}}</td>
            <td><img width="40" src="/{{$member->photo}}" class="user-image" alt="User Image"></td>
            <td>{{$member->privilege}}</td>
            <td>
              @if(CRUDBooster::isDelete() && $button_edit)
              <a class='btn btn-danger btn-sm' href='{{CRUDBooster::mainpath("$group_id/remove_member/$member->id")}}'><i class="fa fa-trash"></i></a>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </form>
  </div>
</div>
@endsection

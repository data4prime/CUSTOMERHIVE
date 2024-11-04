@extends('crudbooster::admin_template')

@section('content')

  @if(g('return_url'))
      <p>
        <a title='Return' href='{{g("return_url")}}'>
          <i class='fa fa-chevron-circle-left '></i>
          &nbsp; {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
        </a>
      </p>
  @else
      <p>
        <a title='Main Module' href='{{CRUDBooster::mainpath()}}'>
          <i class='fa fa-chevron-circle-left '></i>
          &nbsp; {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
        </a>
      </p>
  @endif

<!-- List members -->
<div class="box">
  <div class="box-header mb-3 mb-3">
    <h4>{{ $content_title }}</h4>
  </div>
  <div class="box-body table-responsive no-padding">
    <form id='form-table' method='post' action='{{CRUDBooster::mainpath("action-selected")}}'>
      <input type='hidden' name='button_name' value=''/>
      <input type='hidden' name='_token' value='{{csrf_token()}}'/>
      <table class='table table-striped table-bordered'>
        <thead>
          <tr>
            <th>{!! __('crudbooster.name') !!}</th>
            <th>Email</th>
            <th>{!! __('crudbooster.photo') !!}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($members as $member)
          <tr>
            <td>{{$member->name}}</td>
            <td>{{$member->email}}</td>
            <td><img width="40" src="{{UserHelper::icon($member->id)}}" class="user-image" alt="User Image"></td>
            <td>
              <a title='Move' class='btn btn-success btn-sm' href='{{CRUDBooster::adminpath("users/edit/$member->id")}}'><i class="fa fa-pencil"></i></a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </form>
  </div>
</div>
@endsection

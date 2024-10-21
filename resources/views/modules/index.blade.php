<!-- First you need to extend the CB layout -->
@extends('crudbooster::admin_template')

@section('content')
<div class="box">
  <div class="box-header mb-3 mb-3">
  </div>
  <div class="box-body table-responsive no-padding">
    <table class='table table-striped table-bordered'>
      <thead>
          <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Price</th>
            <th>Action</th>
           </tr>
      </thead>
      <tbody>
        @foreach($result as $row)
          <tr>
            <td>{{$row->name}}</td>
            <td>{{$row->description}}</td>
            <td>{{$row->price}}</td>
            <td>
              <!-- To make sure we have read access, wee need to validate the privilege -->
              @if(CRUDBooster::isUpdate() && $button_edit)
              <a class='btn btn-success btn-sm' href='{{CRUDBooster::mainpath("edit/$row->id")}}'>Edit</a>
              @endif

              @if(CRUDBooster::isDelete() && $button_edit)
              <a class='btn btn-success btn-sm' href='{{CRUDBooster::mainpath("delete/$row->id")}}'>Delete</a>
              @endif
            </td>
           </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- pagination -->
<p>{!! urldecode(str_replace("/?","?",$result->appends(Request::all())->render())) !!}</p>
@endsection

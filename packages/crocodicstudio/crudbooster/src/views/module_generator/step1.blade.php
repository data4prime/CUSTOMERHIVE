@extends("crudbooster::module_generator.template")
@section("inner_content")

    @push('bottom')
        <script>
            $(function () {
                $('select[name=table]').change(function () {
                    var v = $(this).val().replace(".", "_");
                    $.get("{{CRUDBooster::mainpath('check-slug')}}/" + v, function (resp) {
                        if (resp.total == 0) {
                            $('input[name=path]').val(v);
                        } else {
                            v = v + resp.lastid;
                            $('input[name=path]').val(v);
                        }
                    })

                })
            })
        </script>
    @endpush

    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Module Information</h3>
        </div>
        <div class="box-body">
            <form method="post" action="{{Route('ModulsControllerPostStep1')}}">
                <input type="hidden" name="_token" value="{{csrf_token()}}">
                <input type="hidden" name="id" value="{{$row->id}}">
                <div class="form-group">
                    <label for="">Table</label>
                    <select name="table" id="table" required class="select2 form-control" value="{{$row->table_name}}">
                        <option value="new">** Create a new table</option>
                        @foreach($tables_list as $table)

                            <option {{($table == $row->table_name)?"selected":""}} value="{{$table}}">{{$table}}</option>

                        @endforeach
                    </select>
                    <div class="help-block">
                      Create a new table or use an existing one
                    </div>
                </div>
                <div class="form-group">
                    <label for="">Module Name</label>
                    <input type="text" class="form-control" required name="name" value="{{$row->name}}">
                </div>

                <div class="form-group">
                  <label for="">Icon</label>
                  <select name="icon" id="icon" required class="select2 form-control">
                    @foreach($fontawesome as $f)
                      <option {{($row->icon == 'fa fa-'.$f)?"selected":""}} value="fa fa-{{$f}}">{{$f}}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group">
                    <label for="">Module Slug</label>
                    <input type="text" class="form-control" required name="path" value="{{$row->path}}">
                    <div class="help-block">Alphanumeric and underscore characters only</div>
                </div>
        </div>
        <div class="box-footer">

            <input checked type='checkbox' name='create_menu' value='1'/> Also create menu for this module <a href='#'
                                                                                                              title='If you check this, we will create the menu for this module'>(?)</a>

            <div class='pull-right'>
                <a class='btn btn-default' href='{{Route("ModulsControllerGetIndex")}}'> {{trans('crudbooster.button_back')}}</a>
                <input type="submit" class="btn btn-primary" value="Step 2 &raquo;">
            </div>
        </div>
        </form>
    </div>


@endsection

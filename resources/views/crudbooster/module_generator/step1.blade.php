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

<form method="post" action="{{Route('ModulsControllerPostStep1')}}">
    <input type="hidden" name="_token" value="{{csrf_token()}}">
    <input type="hidden" name="id" value="{{isset($row->id) ? $row->id : ''}}">

    <div class="card box-default">
        <div class="card-header mb-3 with-border">
            <h5 class="box-title">Module Information</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <label for="table" class="col-sm-2 col-form-label">Table</label>
                <div class="col-sm-10">
                    <select name="table" id="table" required class="select2 form-control"
                        value="{{ isset($row->table_name) ? $row->table_name : ''}}">
                        <option value="new">** Create a new table</option>
                        @foreach($tables_list as $table)

                        <option {{(isset($row->table_name) && $table==$row->table_name)?"selected":""}}
                            value="{{$table}}">{{$table}}</option>

                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <label for="name" class="col-sm-2 col-form-label">Module Name</label>
                <div class="col-sm-10">
                    <input  type="text" class="form-control" required name="name"
                    value="{{ isset($row->name) ? $row->name : ''}}">
                </div>
            </div>

            {{-- Icona: stesso selettore del mockup e del form di modifica del modulo (ch_icon_picker);
                 il valore inviato resta "bi bi-<nome>" come prima. --}}
            <div class="row mb-3">
                <label class="col-sm-2 col-form-label">Icon</label>
                <div class="col-sm-10">
                    @include('crudbooster::partials.ch_icon_picker', [
                        'name' => 'icon',
                        // Nuovo modulo: come prima, parte dalla prima icona dell'elenco (il vecchio select non poteva essere vuoto).
                        'current' => isset($row->icon) && $row->icon ? \App\Helpers\IconMap::toBi($row->icon) : 'bi bi-' . (collect($fontawesome)->first() ?? 'circle'),
                        'prefix' => 'bi bi-',
                        'icons' => $fontawesome,
                    ])
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-2 col-form-label">
                    Also create menu for this module <a href='#'
            title='If you check this, we will create the menu for this module'>(?)</a>
                </label>
                <div class="col-sm-10 d-flex align-items-center">
                    @include('crudbooster::partials.ch_check', ['name' => 'create_menu', 'value' => '1', 'label' => '', 'checked' => true, 'switch' => true, 'aria' => 'Also create menu for this module', 'input_class' => 'mg-menu-switch'])
                </div>
            </div>
        </div>
    </div>

    @include('crudbooster::module_generator._nav', [
        'nav_back' => Route("ModulsControllerGetIndex"),
        'nav_back_ajax' => false,
        'nav_next' => trans('crudbooster.mg_nav_next'),
    ])
</form>


@endsection

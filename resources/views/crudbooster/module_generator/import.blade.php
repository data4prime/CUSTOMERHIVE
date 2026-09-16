@extends('crudbooster::admin_template')

@section('content')

<p>
    <a href='{{CRUDBooster::mainpath()}}'>{{trans("crudbooster.form_back_to_list",['module'=>'Module Generator'])}}</a>
</p>

<div class="card card-primary">
    <div class="card-header mb-3 with-border">
        <h5 class="card-title">{{ $page_title }}</h5>
    </div>
    <form method="post" action="{{Route('ModulsControllerPostImport')}}" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{csrf_token()}}">
        <div class="card-body">
            <div class="row mb-3">
                <label for="json_file" class="col-sm-2 col-form-label">File JSON</label>
                <div class="col-sm-10">
                    <input type="file" name="json_file" id="json_file" accept="application/json,.json" class="form-control" required>
                    <div class="help-block">
                        File generato dal pulsante "Export" di un modulo (definizione: tabella, colonne, form -
                        non i dati). Il modulo non viene assegnato automaticamente a nessun menu o ruolo: va
                        abilitato da Privileges dopo l'import.
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="pull-right">
                <a class="btn btn-default" href="{{Route('ModulsControllerGetIndex')}}">{{trans('crudbooster.button_back')}}</a>
                <input type="submit" class="btn btn-primary" value="Import">
            </div>
        </div>
    </form>
</div>

@endsection

<!-- Bootstrap 3.3.2 -->
<link href="{{ asset("vendor/crudbooster/assets/adminlte/bootstrap/css/bootstrap.min.css") }}" rel="stylesheet" type="text/css"/>
<!-- Font Awesome Icons -->
<link href="{{asset("vendor/crudbooster/assets/adminlte/font-awesome/css")}}/font-awesome.min.css" rel="stylesheet" type="text/css"/>
<!-- Ionicons -->
<link href="{{asset("vendor/crudbooster/ionic/css/ionicons.min.css")}}" rel="stylesheet" type="text/css"/>
<!-- Theme style -->
<link href="{{ asset("vendor/crudbooster/assets/adminlte/dist/css/AdminLTE.min.css")}}" rel="stylesheet" type="text/css"/>
<link href="{{ asset("vendor/crudbooster/assets/adminlte/dist/css/skins/_all-skins.min.css")}}" rel="stylesheet" type="text/css"/>

@include('crudbooster::admin_template_plugins')

<?php
//array dei tenant id del gruppo di cui sto modificando i membri
$group_tenants = \App\GroupTenants::where('group_id',Request::get('select_to'))->pluck('tenant_id')->all();

//bypass CBController getModalData per custom query
$result = DB::table('cms_users')
              ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                ->from('users_groups')
                ->whereRaw(
                    'users_groups.group_id = '.Request::get('select_to').'
                    AND users_groups.user_id = cms_users.id
                    AND users_groups.deleted_at = null
                    ');
              })
              ->whereIn('cms_users.tenant',$group_tenants);

if(UserHelper::isTenantAdmin()) {
	//can only see users of his own tenant
	$result = $result->where('cms_users.tenant',UserHelper::tenant(CRUDBooster::myId()));
}

if($q){
  //filtra la lista in base alla ricerca fatta dall'utente
  $result = $result->where(function ($query) use ($columns, $q) {
                            foreach ($columns as $c => $col) {
                              if ($c == 0) {
                                $query->where('cms_users.'.$col, 'like', '%'.$q.'%');
                              } else {
                                $query->orWhere('cms_users.'.$col, 'like', '%'.$q.'%');
                              }
                            }
                          });
}
$result = $result->orderby('id', 'asc')
                  ->get();

$name = Request::get('name_column');
$coloms_alias = explode(',', 'ID,'.Request::get('columns_name_alias'));
if (count($coloms_alias) < 2) {
    $coloms_alias = $columns;
}
?>
<form method='get' action="">
    {!! CRUDBooster::getUrlParameters(['q']) !!}
    <input type="text" placeholder="{{trans('crudbooster.datamodal_search_and_enter')}}" name="q" title="{{trans('crudbooster.datamodal_enter_to_search')}}"
           value="{{$q}}" class="form-control">
</form>

<table id='table_dashboard' class='table table-striped table-bordered table-condensed' style="margin-bottom: 0px">
    <thead>
    @foreach($coloms_alias as $col)
        <th>{{ $col }}</th>
    @endforeach
    <th width="5%">{{trans('crudbooster.datamodal_select')}}</th>
    </thead>
    <tbody>
    @foreach($result as $row)
        <tr>
            @foreach($columns as $col)
                <?php
                $img_extension = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
                $ext = pathinfo($row->$col, PATHINFO_EXTENSION);
                if ($ext && in_array($ext, $img_extension)) {
                    echo "<td><a href='".asset($row->$col)."' data-lightbox='roadtrip'><img src='".asset($row->$col)."' width='50px' height='30px'/></a></td>";
                } else {
                    echo "<td>".str_limit(strip_tags($row->$col), 50)."</td>";
                }
                ?>
            @endforeach
            <?php
            $select_data_result = [];
            $select_data_result['datamodal_id'] = $row->id;
            $select_data_result['datamodal_label'] = $row->{$columns[1]} ?: $row->id;
            $select_data_result['datamodal_email'] = $row->email;
            $select_data = Request::get('select_to');
            if ($select_data) {
                $select_data = explode(',', $select_data);
                if ($select_data) {
                    foreach ($select_data as $s) {
                        $s_exp = explode(':', $s);
                        $field_name = $s_exp[0];
                        
                        $target_field_name = isset($s_exp[1]) ? $s_exp[1] : $field_name;
                        $select_data_result[$target_field_name] = isset($row->$field_name) ? $row->$field_name : '';
                    }
                }
            }
            ?>
            <td><a class='btn btn-primary' href='javascript:void(0)' onclick='parent.selectAdditionalData{{$name}}({!! json_encode($select_data_result) !!})'><i
                            class='fa fa-check-circle'></i> {{trans('crudbooster.datamodal_select')}}</a></td>
        </tr>
    @endforeach
    </tbody>
</table>

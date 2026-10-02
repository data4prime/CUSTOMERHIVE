{{-- Popup di scelta: query specifica del tipo, tabella e ricerca in datamodal_relation/browser.blade.php (docs/refactoring/215) --}}

<?php
//array dei tenant id del gruppo di cui sto modificando i membri
$group_tenants = \App\GroupTenants::where('group_id',Request::get('select_to'))->pluck('tenant_id')->all();

//bypass CBController getModalData per custom query
$result = DB::table('cms_users')
              ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                ->from('users_groups')
                ->whereRaw(
                    'users_groups.group_id = '.(int) Request::get('select_to').'
                    AND users_groups.user_id = cms_users.id
                    AND users_groups.deleted_at IS NULL
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

// Oltre a id/label, al form padre va l'email (input "email").
$datamodal_extra = ['datamodal_email' => 'email'];
?>

@include('crudbooster::default.datamodal_relation.browser')

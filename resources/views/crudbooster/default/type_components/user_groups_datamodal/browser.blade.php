{{-- Popup di scelta: query specifica del tipo, tabella e ricerca in datamodal_relation/browser.blade.php (docs/refactoring/215) --}}

<?php
  //tenant id dell'utente di cui sto modificando i gruppi
  $user_tenant_id = UserHelper::tenant(Request::get('select_to'));
  //bypass CBController getModalData per custom query
  $result = DB::table('groups')->whereNull('deleted_at')
                ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('users_groups')
                      ->whereRaw(
                          'users_groups.user_id = '.(int) Request::get('select_to').'
                          AND users_groups.group_id = groups.id
                          AND users_groups.deleted_at IS NULL'
                        );
            })
            ->join('group_tenants','group_tenants.group_id','groups.id')
            ->where('group_tenants.tenant_id',$user_tenant_id)
            ->distinct('groups.id')
            ->select('groups.*');
  if($q){
    //filtra la lista in base alla ricerca fatta dall'utente
    $result = $result->where(function ($query) use ($columns, $q) {
                            foreach ($columns as $c => $col) {
                              if ($c == 0) {
                                $query->where('groups.'.$col, 'like', '%'.$q.'%');
                              } else {
                                $query->orWhere('groups.'.$col, 'like', '%'.$q.'%');
                              }
                            }
                          });
  }
  $result = $result->orderby('groups.id', 'asc')
                    ->get();

?>

@include('crudbooster::default.datamodal_relation.browser')

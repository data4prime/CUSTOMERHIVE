{{-- Popup di scelta: query specifica del tipo, tabella e ricerca in datamodal_relation/browser.blade.php (docs/refactoring/215) --}}

<?php

  //bypass CBController getModalData per custom query
  $result = DB::table('groups')
                ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('group_tenants')
                      ->whereRaw('group_tenants.tenant_id = '.(int) Request::get('select_to').' AND group_tenants.group_id = groups.id');
            });
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
  $result = $result->orderby('id', 'asc')
                    ->get();

?>

@include('crudbooster::default.datamodal_relation.browser')

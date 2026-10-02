{{-- Popup di scelta: query specifica del tipo, tabella e ricerca in datamodal_relation/browser.blade.php (docs/refactoring/215) --}}

<?php

  //bypass CBController getModalData per custom query
  $result = DB::table('tenants')
                ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('tenants_allowed')
                      ->whereRaw('tenants_allowed.item_id = '.(int) Request::get('select_to').' AND tenants_allowed.tenant_id = tenants.id');
            });
  if($q){
    //filtra la lista in base alla ricerca fatta dall'utente
    $result = $result->where(function ($query) use ($columns, $q) {
                            foreach ($columns as $c => $col) {
                              if ($c == 0) {
                                $query->where('tenants.'.$col, 'like', '%'.$q.'%');
                              } else {
                                $query->orWhere('tenants.'.$col, 'like', '%'.$q.'%');
                              }
                            }
                          });
  }
  $result = $result->orderby('id', 'asc')
                    ->get();

?>

@include('crudbooster::default.datamodal_relation.browser')

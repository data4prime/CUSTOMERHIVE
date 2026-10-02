{{-- Popup di scelta: query specifica del tipo, tabella e ricerca in datamodal_relation/browser.blade.php (docs/refactoring/215) --}}

<?php

  //bypass CBController getModalData per custom query
  $result = DB::table('qlik_items')
              //escludi dalla lista gli item su cui questo gruppo è già autorizzato
              ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('items_allowed')
                      ->whereRaw('items_allowed.group_id = '.(int) Request::get('select_to').' AND items_allowed.item_id = qlik_items.id');
                });

  if(!CRUDBooster::isSuperadmin()){
    //mostra solo gli item che l'utente corrente può vedere
    $result = $result->join('items_allowed','qlik_items.id','=','items_allowed.item_id')
                      ->join('users_groups','users_groups.group_id','=','items_allowed.group_id')
                      ->where('users_groups.user_id','=',CRUDBooster::myId());
  }

  if($q){
    //filtra la lista in base alla ricerca fatta dall'utente
    $result = $result->where(function ($query) use ($columns, $q) {
                foreach ($columns as $c => $col) {
                  if ($c == 0) {
                    $query->where('qlik_items.'.$col, 'like', '%'.$q.'%');
                  } else {
                    $query->orWhere('qlik_items.'.$col, 'like', '%'.$q.'%');
                  }
                }
              });
  }
  $result = $result->orderby('qlik_items.id', 'asc')
                    ->select('qlik_items.id', 'qlik_items.title', 'qlik_items.subtitle')
                    ->get();

  // Oltre a id/label, al form padre va il sottotitolo (input "subtitle").
  $datamodal_extra = ['datamodal_subtitle' => 'subtitle'];

?>

@include('crudbooster::default.datamodal_relation.browser')

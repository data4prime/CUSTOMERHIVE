{{-- Popup di scelta: query specifica del tipo, tabella e ricerca in datamodal_relation/browser.blade.php (docs/refactoring/215) --}}

<?php

  // Tabella delle autorizzazioni scelta da una whitelist fissa (mai dalla query
  // string): 'qlik' (default) = items_allowed, 'chatai' = chatai_groups_allowed.
  $allowedSources = [
    'qlik' => ['table' => 'items_allowed', 'key' => 'item_id'],
    'chatai' => ['table' => 'chatai_groups_allowed', 'key' => 'chatai_conf_id'],
  ];
  $allowedScope = Request::get('allowed_scope');
  $allowedSource = $allowedSources[is_string($allowedScope) ? $allowedScope : ''] ?? $allowedSources['qlik'];

  //bypass CBController getModalData per custom query
  $result = DB::table('groups')
                ->whereNotExists(function ($query) use ($allowedSource) {
                $query->select(DB::raw(1))
                      ->from($allowedSource['table'])
                      ->whereRaw($allowedSource['table'].'.'.$allowedSource['key'].' = '.(int) Request::get('select_to').' AND '.$allowedSource['table'].'.group_id = groups.id');
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

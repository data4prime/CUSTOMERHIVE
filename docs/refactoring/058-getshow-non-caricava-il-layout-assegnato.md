# 058 - Statistic Builder: getShow() ignorava il layout assegnato alla dashboard

- **Data**: 2026-08-28
- **Stato**: Completato
- **Area**: Bug fix
- **File/aree di codice coinvolte**:
  - `app/Http/Controllers/System/StatisticBuilderController.php`

## Contesto

Segnalato dall'utente: sul builder (`/admin/statistic_builder/builder/2`)
il widget Table risultava sulla seconda riga del layout, ma sulla
dashboard vera (`/admin/statistic_builder/show/test-dashboard?m=2`)
appariva come ultima colonna della prima riga — stesso widget, stessa
dashboard (id 2), posizione diversa.

Causa: `getBuilder()` e `getDashboard()` risolvono correttamente il
layout assegnato alla dashboard (`cms_statistics.layout` →
`dashboard_layouts.code_layout`), ma **`getShow($slug)`** (la funzione
dietro l'URL `/statistic_builder/show/{slug}`, quella usata qui) non
lo faceva affatto:

```php
public function getShow($slug)
{
    $this->cbLoader();
    $row = CRUDBooster::first($this->table, ['slug' => $slug]);
    $id_cms_statistics = $row->id;
    $page_title = $row->name;

    return view('crudbooster::statistic_builder.show', compact('page_title', 'id_cms_statistics'));
}
```

Nessun `$code_layout` passato alla vista. `index.blade.php` (inclusa
da `show.blade.php`), quando `$code_layout` è vuoto/assente, ricade su
una griglia di **default hardcoded a 9 aree** (4 colonne uguali su
riga 1, 4 su riga 2, 1 a tutta larghezza su riga 3) — completamente
scollegata dal layout realmente configurato. Il widget, salvato con
`area_name='area4'`, nel layout vero configurato (id 1: riga 1 = area1
+ area2 + area3, riga 2 = area4 a tutta larghezza) è sulla riga 2; ma
nella griglia di fallback usata per errore da `getShow()`, "area4" è
la quarta (ultima) colonna della riga 1 — esattamente la discrepanza
segnalata.

`getDashboard()` (usato per la dashboard di default, non per
`/show/{slug}`) aveva invece **già** la logica corretta, duplicata
localmente nel metodo.

## Situazione prima

`getShow()` non calcolava mai `$layout`/`$code_layout`; la logica per
farlo esisteva solo dentro `getDashboard()`, duplicata (non
condivisa).

## Situazione dopo

Estratta la logica di risoluzione del layout in un metodo privato
condiviso:

```php
private function resolveDashboardCodeLayout($layoutId)
{
    $layout = DB::table('dashboard_layouts')->where('id', $layoutId)->first();
    if ($layout) {
        return [$layout, html_entity_decode($layout->code_layout)];
    }
    // ... griglia di default a 9 aree (invariata) ...
    return [null, $code_layout];
}
```

Sia `getDashboard()` che `getShow()` ora la chiamano:

```php
$layoutId = isset($row->layout) ? $row->layout : 0;
[$layout, $code_layout] = $this->resolveDashboardCodeLayout($layoutId);
```

## Motivazione

- Il bug era esattamente una duplicazione di logica lasciata
  incompleta in una delle due copie — estrarre un metodo condiviso
  risolve il bug attuale e previene lo stesso tipo di disallineamento
  in futuro (un'unica fonte di verità per "come si risolve il layout
  di una dashboard").
- `getShow($slug)` è il percorso usato per l'URL diretto
  `/statistic_builder/show/{slug}` (con parametro `?m=` per il menu),
  probabilmente il modo più comune con cui un utente reale visita una
  dashboard — il bug non era un caso limite.

## Test

- `php -l`: nessun errore.
- `resolveDashboardCodeLayout(1)` (layout reale esistente): ritorna
  l'oggetto layout e l'HTML corretto (`area4` con `col-sm-12`, seconda
  riga) — coincide con quanto già verificato per il builder.
- `resolveDashboardCodeLayout(9999)` (id inesistente): ritorna
  `null` + la griglia di fallback a 9 aree, invariata.
- **`getShow('test-dashboard')` chiamato direttamente** (con contesto
  di rotta simulato): `id_cms_statistics=2`, `layout` presente
  (`id=1`), `code_layout` ora identico a quello usato dal builder
  (area4 sulla seconda riga) — prima di questo fix sarebbe stato
  assente/vuoto, forzando il fallback a 9 aree.
- `curl` senza sessione su `/admin/statistic_builder/show/test-dashboard`
  (con e senza `?m=2`) e `/admin/statistic_builder/builder/2`: tutti
  302, nessun 500.
- Route count invariato (513).

**Non verificato visivamente in browser** — lasciato al giro di test
manuale dell'utente, che dovrebbe ora vedere il widget Table nella
stessa posizione (riga 2) sia sul builder che sulla dashboard reale.

## Rischi e note

- `getDashboard()` (dashboard di default, menu con `is_dashboard=1`)
  non cambia comportamento: stessa logica, solo estratta in un metodo
  condiviso.
- La griglia di fallback a 9 aree resta duplicata anche in
  `index.blade.php` (usata quando `$code_layout` è vuoto per un motivo
  diverso, es. nessuna riga configurata nel builder) — non consolidata
  in questo intervento, fuori scope rispetto al bug segnalato.

## Rollback

`git revert` del commit — `getShow()` torna a non passare
`layout`/`code_layout`, ricadendo sempre sulla griglia di default a 9
aree indipendentemente dal layout configurato.

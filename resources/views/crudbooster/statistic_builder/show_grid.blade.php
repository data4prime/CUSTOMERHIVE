{{--
    Fase 5 del piano "dashboard a griglia libera" (vedi
    docs/piano-dashboard-griglia-libera.md, docs/refactoring/113-*).

    Vista di SOLA LETTURA per le dashboard con layout_mode='grid' (vedi
    StatisticBuilderController::renderDashboardShow()) - stesso tema
    admin di show.blade.php, ma layout in puro CSS Grid da
    pos_x/pos_y/width/height, niente gridstack.js/palette/sidebar (non
    servono: qui i widget non si spostano ne' si ridimensionano). $components
    arriva gia' pronto (HTML di ciascun widget gia' renderizzato) da
    renderComponentPayload(), stessa funzione usata dal builder - una
    sola query per l'intera dashboard invece degli N+1 round-trip AJAX
    del renderer legacy (index.blade.php).
--}}
@extends('crudbooster::admin_template')

@section('content')

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
<style>
    /* Stile ispirato al mockup approvato (Idea 1: griglia libera) per la
       dashboard vista dal menu - qui SENZA alcuna possibilita' di
       modifica (niente maniglie/drag/pulsanti, la modifica resta solo
       nel builder): stessa tipografia (Manrope) e stessa impostazione a
       card di quel mockup, applicate ai widget reali con i loro dati
       veri, non a un contenuto statico di esempio. Scope limitato a
       .ch-grid-view apposta, per non toccare il resto del tema admin
       usato da tutte le altre pagine.
    */
    .ch-grid-view, .ch-grid-view * { font-family: 'Manrope', system-ui, sans-serif; }

    /* Card bianca per ciascun widget, stesso aspetto del builder
       (.grid-stack-item-content in builder_grid.blade.php) - senza,
       un widget non configurato (solo icona/testo, niente sfondo
       proprio) restava a galleggiare senza alcun contorno visibile.

       margin/padding qui riproducono ESATTAMENTE la geometria di
       GridStack nel builder (margin: 8 nell'init di GridStack.init(),
       .grid-stack-item-content { padding: 4px } in
       builder_grid.blade.php) invece di affidarsi al `gap` di CSS Grid
       sul contenitore (rimosso, vedi sotto): GridStack fissa l'altezza
       di un item a `righe * cellHeight` px e inseta il suo contenuto di
       `margin` px su ogni lato SENZA aggiungere altro tra le righe che
       occupa, mentre un `gap` di CSS Grid su una cella che copre piu'
       righe si somma dentro la sua stessa altezza (gap incluso per
       ogni riga interna) - stessa altezza nominale (altezza*40px) ma
       area di contenuto finale diversa tra le due pagine (per un widget
       alto 2 righe: 56px qui vs 40*2-16=64px nel builder prima di
       questo fix, quasi il doppio con la formula precedente basata sul
       gap), con lo spazio in eccesso scaricato dal
       `margin-top: auto` del footer del link dell'Indicatore KPI -
       bug segnalato dall'utente. Con margin/padding invece di gap,
       l'area di contenuto e' identica in entrambe le pagine per
       qualunque altezza, non solo per il caso di oggi.

       padding: 0 (docs/refactoring/142, richiesta successiva
       dell'utente, stessa cosa gia' fatta qui una volta in 130): un
       padding diverso da zero creava un margine/doppio bordo visibile
       tra il bordo del widget (es. .kpi-indicator-card, che ha gia' il
       proprio bordo/sfondo/ombra) e quello di questa cella - tolto in
       entrambe le pagine (vedi builder_grid.blade.php) per restare
       allineate; il `margin: 8px` resta invariato (e' lo spazio TRA due
       widget, non dentro uno), la geometria di 137 (altezza contenuto =
       righe*40 - 2*8) non cambia. */
    .ch-grid-view-cell {
        background: #FFFFFF;
        border: 1px solid #E4E7EC;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        overflow: auto;
        margin: 8px;
        padding: 0;
        scrollbar-width: thin;
    }
    .ch-grid-view-cell::-webkit-scrollbar { width: 6px; height: 6px; }
    .ch-grid-view-cell::-webkit-scrollbar-thumb { background: #D0D5DD; border-radius: 999px; }
    .ch-grid-view-cell::-webkit-scrollbar-button { display: none; }
    /* Pulsanti "modifica"/"elimina" (.action, dentro ogni .border-box):
       pensati per il builder legacy (che li stila/posiziona/nasconde
       via il CSS di index.blade.php, mai caricato qui) - su questa
       vista di sola lettura non devono comparire affatto: la modifica
       si fa solo dal builder. */
    .ch-grid-view .border-box .action { display: none !important; }
    /* Stesso fix di builder_grid.blade.php: senza altezza esplicita
       .border-box resta alto quanto il suo contenuto invece di riempire
       .ch-grid-view-cell, lasciando spazio bianco sotto un widget piu'
       basso della cella che occupa nella griglia. */
    .ch-grid-view .border-box { height: 100%; box-sizing: border-box; }

    /* Stessa base minima di .small-box/.card usata nel builder
       (builder_grid.blade.php) - qui con un filo di rifinitura in piu'
       (radius, peso dei numeri) per avvicinarsi alla resa del mockup. */
    .ch-grid-view .small-box { position: relative; border-radius: 10px; overflow: hidden; color: #FFFFFF; height: 100%; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; }
    .ch-grid-view .small-box .inner-box { padding: 16px 18px; }
    .ch-grid-view .small-box .inner-box h3 { font-size: 26px; font-weight: 800; margin: 0 0 2px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ch-grid-view .small-box .inner-box p { font-size: 12.5px; margin: 0; opacity: 0.9; font-weight: 500; }
    .ch-grid-view .small-box .icon { position: absolute; top: 6px; right: 10px; font-size: 36px; opacity: 0.3; line-height: 1; }
    .ch-grid-view .small-box .small-box-footer { display: block; padding: 6px; text-align: center; background: rgba(0,0,0,0.12); color: rgba(255,255,255,0.95); font-size: 11px; font-weight: 600; }
    .ch-grid-view .small-box .small-box-footer:hover { background: rgba(0,0,0,0.2); }
    .ch-grid-view .card { border: none; margin: 0; height: 100%; box-sizing: border-box; }
    .ch-grid-view .card-header { font-size: 13px; font-weight: 700; color: #101828; padding: 10px 6px; border-bottom: 1px solid #F2F4F7; margin-bottom: 8px; }
    .ch-grid-view .card-body { padding: 2px 6px; }
</style>

@include('crudbooster::statistic_builder.components._widget_card_style')

{{-- Morris.js per i widget Chart Area/Line/Bar "classici" (non *_v2): senza,
     `new Morris.*` lancia ReferenceError e il grafico resta vuoto dopo la
     conversione di una dashboard legacy. Raphael (richiesto da Morris) arriva
     gia' da admin_template_plugins.blade.php, come in index.blade.php. --}}
@push('bottom')
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
<script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
@endpush

<div class="ch-grid-view" style="display: grid; grid-template-columns: repeat(12, 1fr); grid-auto-rows: 40px; gap: 0; align-items: stretch;">
    @foreach($components as $component)
    <div class="ch-grid-view-cell" style="grid-column: {{ $component['pos_x'] + 1 }} / span {{ $component['width'] }}; grid-row: {{ $component['pos_y'] + 1 }} / span {{ $component['height'] }};">
        {!! $component['layout'] !!}
    </div>
    @endforeach
</div>

@endsection

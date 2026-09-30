{{--
    Sezione "Sorgente dati" completa (riepilogo + popup con toggle SQL
    libera/Query guidata, pannello SQL, Query guidata e filtri) -
    estratta da smallbox.blade.php (docs/refactoring/132/133/139, vedi
    quei documenti per la storia del popup) in docs/refactoring/148-* per
    essere condivisa con qualunque widget che interroga dati, non solo
    l'Indicatore KPI (richiesta esplicita dell'utente: "la parte di
    sorgenti dati falla sempre in una modale").

    Il chiamante deve gia' avere $componentID e $config in scope (li passa
    con compact() all'@include). Parametri opzionali:
    - $sqlLabel (string): etichetta del campo SQL libera (default 'Query')
    - $sqlPlaceholder (string): placeholder della textarea SQL (default '')
    - $sqlHelp (string|null): testo di aiuto sotto la textarea SQL (default nessuno)
    - $groupBySupported (bool): passato a _query_builder_fields.blade.php,
      mostra "Raggruppa per" per i widget che producono una serie di punti
      (grafici), non per un widget a valore singolo come il KPI (default false)
    - $fieldPrefix (default 'config') e $scopeId (default $componentID):
      docs/refactoring/152-*, permettono di includere questo partial piu'
      volte nello stesso widget (una per "linea" extra del Grafico a
      linee) senza id DOM ne' name= di campo in collisione.
--}}
<?php
    $currentMode = $config->mode ?? 'sql';
    $sqlLabel = $sqlLabel ?? 'Query';
    $sqlPlaceholder = $sqlPlaceholder ?? '';
    $sqlHelp = $sqlHelp ?? null;
    $groupBySupported = $groupBySupported ?? false;
    $fieldPrefix = $fieldPrefix ?? 'config';
    $scopeId = $scopeId ?? $componentID;
    // Widget Tabella "Elenco record": $builderSupported=false nasconde la
    // Query guidata, $recordsSupported=true aggiunge la modalita' 'records'
    // (partial _records_list_fields). Default = comportamento di sempre.
    $builderSupported = $builderSupported ?? true;
    $recordsSupported = $recordsSupported ?? false;
?>
<style>
    /* Pannello di configurazione riorganizzato in sezioni apribili/
       chiudibili (docs/refactoring/132-*) - CSS auto-contenuto in questo
       partial (non nel <style> del comando 'layout' del widget chiamante:
       le due risposte sono due render Blade indipendenti) cosi' lo stile
       funziona identico sia iniettato nella sidebar del builder a griglia
       (builder_grid.blade.php) sia nella modale del builder legacy
       (#modal-statistic), che non condividono un foglio di stile comune.
       Stessi colori/raggi/font gia' usati in builder_grid.blade.php, per
       coerenza visiva. */
    .ch-config-section { border: 1px solid #E4E7EC; border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
    .ch-config-section > summary { list-style: none; cursor: pointer; padding: 10px 12px; font-size: 12.5px; font-weight: 700; color: #101828; background: #F9FAFB; display: flex; align-items: center; justify-content: space-between; user-select: none; font-family: 'Segoe UI', system-ui, sans-serif; }
    .ch-config-section > summary::-webkit-details-marker { display: none; }
    .ch-config-section > summary::after { content: ''; width: 7px; height: 7px; border-right: 2px solid #667085; border-bottom: 2px solid #667085; transform: rotate(45deg); transition: transform .15s; margin-left: 8px; flex-shrink: 0; }
    .ch-config-section[open] > summary::after { transform: rotate(-135deg); }
    .ch-config-section-body { padding: 14px 12px 2px; }

    .ch-btn { border: 1px solid #D0D5DD; background: #FFFFFF; color: #344054; border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Segoe UI', system-ui, sans-serif; }
    .ch-btn-active { background: #3B5BDB; border-color: #3B5BDB; color: #FFFFFF; }
    .ch-source-summary { font-size: 12.5px; color: #667085; margin: 0 0 10px; }
    .ch-source-summary div { margin-bottom: 2px; }
    .ch-source-summary strong { color: #101828; }
    .ch-source-configure-row { text-align: center; margin-bottom: 10px; }

    .ch-source-modal-overlay { position: fixed; inset: 0; background: rgba(16,24,40,0.45); display: none; align-items: center; justify-content: center; z-index: 1000; }
    .ch-source-modal-overlay.is-open { display: flex; }
    {{-- Popup allargato su richiesta dell'utente (139: 420px->560px;
         145: 560px->640px, le opzioni piu' lunghe dell'operatore come
         "negli ultimi N giorni" restavano tagliate anche a 560px - vedi
         anche la larghezza fissa data a .ch-builder-filter-operator in
         _query_builder_fields.blade.php, stesso intervento). --}}
    .ch-source-modal { background: #FFFFFF; border-radius: 12px; box-shadow: 0 8px 24px rgba(16,24,40,0.2); width: 640px; max-width: 90vw; max-height: 88vh; overflow-y: auto; padding: 20px; font-family: 'Segoe UI', system-ui, sans-serif; }
    .ch-source-modal-title { font-size: 15px; font-weight: 700; color: #101828; margin-bottom: 14px; }
    .ch-source-modal-hint { font-size: 12px; color: #B54708; background: #FFFAEB; border: 1px solid #FEDF89; border-radius: 8px; padding: 8px 10px; margin-top: 16px; }
    .ch-source-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; border-top: 1px solid #F2F4F7; padding-top: 14px; }
    .ch-source-modal label { display: block; font-size: 12px; font-weight: 600; color: #344054; margin-bottom: 4px; }
    .ch-source-modal .form-control { width: 100%; border: 1px solid #D0D5DD; border-radius: 8px; padding: 8px 10px; font-size: 13px; margin-bottom: 2px; box-sizing: border-box; }
    .ch-source-modal .mb-3 { margin-bottom: 14px; }
    .ch-source-modal .help-block { font-size: 11px; color: #98A2B3; margin-top: 4px; }

    {{-- Pulse sul VERO pulsante "Salva" (.ch-sidebar-save, aggiunto da
         builder_grid.blade.php fuori da questo partial) quando si chiude
         il popup "Sorgente dati": il pulsante "Fatto" del popup chiude
         solo la finestra, non salva nulla sul server - segnale visivo che
         resta da fare un passo in piu' (docs/refactoring/139), in aggiunta
         al testo di avviso sotto. `infinite` invece di un numero fisso di
         ripetizioni (docs/refactoring/150, richiesta esplicita
         dell'utente): con solo 2 ripetizioni (1.6s totali) l'animazione
         poteva finire prima che l'utente se ne accorgesse - ora pulsa
         finche' il pulsante non viene davvero cliccato (vedi
         pulseSaveButton() sotto, che smette all'evento click). --}}
    @keyframes ch-pulse-save {
        0%, 100% { box-shadow: 0 0 0 0 rgba(59, 91, 219, 0.55); }
        50% { box-shadow: 0 0 0 8px rgba(59, 91, 219, 0); }
    }
    .ch-sidebar-save.ch-pulse { animation: ch-pulse-save 0.8s ease-in-out infinite; }
</style>

<details class="ch-config-section" open>
    <summary>Sorgente dati</summary>
    <div class="ch-config-section-body">
        <div class="ch-source-summary" id="ch-source-summary-{{$scopeId}}"></div>
        <div class="ch-source-configure-row">
            <button type="button" class="ch-btn" id="ch-source-configure-btn-{{$scopeId}}">Configura sorgente</button>
        </div>

        {{-- Campi veri, sempre nel form (letti da .serialize() al Salva
             generale della sidebar) - spostati via JS dentro il popup
             subito sotto, qui restano solo finche' lo script non e'
             ancora partito. --}}
        <div id="ch-source-fields-{{$scopeId}}" style="display:none;">
            @include('crudbooster::statistic_builder.components._query_mode_toggle', compact('componentID', 'config', 'fieldPrefix', 'scopeId', 'builderSupported', 'recordsSupported'))

            <div id="ch-panel-sql-{{ $scopeId }}" data-mode-panel="sql" style="{{ $currentMode === 'sql' ? '' : 'display:none' }}">
                <div class="mb-3 row">
                    <label>{{ $sqlLabel }}</label>
                    <textarea name='{{ $fieldPrefix }}[sql]' rows="5" class='form-control' placeholder="{{ $sqlPlaceholder }}">{{@$config->sql}}</textarea>
                    @if($sqlHelp)
                    <div class="help-block">{{ $sqlHelp }}</div>
                    @endif
                </div>
                <button type="button" class="ch-btn ch-sql-preview-btn">Prova</button>
                <div class="ch-sql-preview-result" style="margin-top:10px; font-size:13px;"></div>
            </div>

            @if($builderSupported)
            @include('crudbooster::statistic_builder.components._query_builder_fields', compact('componentID', 'config', 'groupBySupported', 'fieldPrefix', 'scopeId'))
            @endif
            @if($recordsSupported)
            @include('crudbooster::statistic_builder.components._records_list_fields', compact('componentID', 'config', 'fieldPrefix', 'scopeId'))
            @endif
        </div>
    </div>
</details>

{{-- Il popup DEVE restare dentro il <form> del chiamante: lo script
     subito sotto sposta i campi veri (config[sql]/config[mode]/
     config[dataset]/...) da ch-source-fields-{id} dentro
     ch-source-modal-body-{id}, appena sotto - se il popup fosse fuori dal
     form, quello spostamento li toglierebbe dal sottoalbero del form e
     .serialize() smetterebbe di raccoglierli (vedi docs/refactoring/134). --}}
<div class="ch-source-modal-overlay" id="ch-source-modal-overlay-{{$scopeId}}">
    <div class="ch-source-modal">
        <div class="ch-source-modal-title">Sorgente dati</div>
        <div id="ch-source-modal-body-{{$scopeId}}"></div>
        <div class="ch-source-modal-hint">{{ trans('crudbooster.statistic_builder_smallbox_source_popup_hint') }}</div>
        <div class="ch-source-modal-actions">
            {{-- "Fatto", non "Salva" (docs/refactoring/139): questo
                 bottone chiude solo il popup e aggiorna il riepilogo
                 nella sidebar, NON salva nulla sul server - il salvataggio
                 vero resta solo il pulsante .ch-sidebar-save in fondo alla
                 sidebar (evidenziato con un pulse alla chiusura, vedi JS
                 sotto). --}}
            <button type="button" class="ch-btn ch-btn-active" id="ch-source-modal-close-{{$scopeId}}">{{ trans('crudbooster.statistic_builder_smallbox_source_popup_done') }}</button>
        </div>
    </div>
</div>

<script>
(function () {
    // scopeId (non componentID) costruisce i selettori DOM: con piu'
    // istanze di questo partial nello stesso widget (una per "linea"
    // extra, docs/refactoring/152) componentID sarebbe lo stesso per
    // tutte - scopeId e' invece unico per istanza (default componentID
    // per gli usi esistenti, dove le due cose coincidono).
    var scopeId = {!! json_encode($scopeId) !!};
    var fieldPrefix = {!! json_encode($fieldPrefix) !!};
    var $fields = $('#ch-source-fields-' + scopeId);
    var $overlay = $('#ch-source-modal-overlay-' + scopeId);
    var $summary = $('#ch-source-summary-' + scopeId);

    function updateSummary() {
        var mode = $fields.find('input[name="' + fieldPrefix + '[mode]"]').val() || 'sql';
        var modeLabel = mode === 'builder' ? 'Query guidata' : (mode === 'records' ? {!! json_encode(trans('crudbooster.statistic_builder_records_mode')) !!} : 'SQL libera');
        var html = '<div>Modalità attuale: <strong>' + modeLabel + '</strong></div>';
        if (mode === 'builder' || mode === 'records') {
            var $datasetSelect = $fields.find(mode === 'records' ? '.ch-records-dataset' : '.ch-builder-dataset');
            if ($datasetSelect.val()) {
                // Le label auto-generate sono gia' "Tabella: Nome" (vedi
                // DashboardDatasetRegistry::humanizeName()) - tolto il
                // prefisso qui per non ripeterlo due volte, i dataset
                // curati a mano (senza quel prefisso) restano invariati.
                var datasetLabel = $datasetSelect.find('option:selected').text().replace(/^Tabella:\s*/, '');
                html += '<div>Tabella: <strong>' + datasetLabel + '</strong></div>';
            }
        }
        $summary.html(html);
    }

    // Evidenzia il VERO pulsante "Salva" della sidebar (.ch-sidebar-save,
    // aggiunto da builder_grid.blade.php DOPO che questo script e' gia'
    // girato una prima volta - per questo va cercato qui dentro
    // l'handler, non salvato in una variabile all'avvio: al momento in cui
    // l'utente chiude il popup quel pulsante esiste gia' nel DOM, quindi
    // .closest('form').find(...) lo trova sempre). Solo un aiuto visivo
    // (docs/refactoring/139): il testo di avviso sopra il pulsante "Fatto"
    // spiega gia' perche' serve un secondo click.
    //
    // Pulsa finche' non viene davvero cliccato (docs/refactoring/150,
    // richiesta esplicita dell'utente: con una durata fissa breve
    // rischiava di finire prima che l'utente se ne accorgesse) - lo
    // stop e' l'evento click sul pulsante stesso, non un timer.
    // .off(...).one(...) con lo stesso namespace evita di accumulare piu'
    // handler se il popup si apre/chiude piu' volte senza mai premere
    // Salva (ogni chiamata sostituisce l'handler precedente invece di
    // affiancarne uno nuovo).
    function pulseSaveButton() {
        var $saveBtn = $overlay.closest('form').find('.ch-sidebar-save');
        if (!$saveBtn.length) {
            return;
        }
        $saveBtn.addClass('ch-pulse');
        $saveBtn.off('click.chPulse').one('click.chPulse', function () {
            $saveBtn.removeClass('ch-pulse');
        });
    }

    // I campi veri restano nello stesso <form> (.serialize() li legge
    // comunque, indipendentemente da dove sono annidati nel DOM) - qui si
    // spostano solo visivamente dentro il corpo del popup.
    $('#ch-source-modal-body-' + scopeId).append($fields.show());

    $('#ch-source-configure-btn-' + scopeId).on('click', function () {
        $overlay.addClass('is-open');
    });
    $('#ch-source-modal-close-' + scopeId).on('click', function () {
        updateSummary();
        $overlay.removeClass('is-open');
        pulseSaveButton();
    });
    $overlay.on('click', function (e) {
        if (e.target === this) {
            updateSummary();
            $overlay.removeClass('is-open');
            pulseSaveButton();
        }
    });

    updateSummary();
    // Riflette in tempo reale i cambi fatti dentro al popup (modalita' e
    // dataset scelto), senza aspettare la chiusura.
    $fields.on('click', '.ch-mode-btn', function () { setTimeout(updateSummary, 0); });
    $fields.on('change', '.ch-builder-dataset, .ch-records-dataset', function () { setTimeout(updateSummary, 50); });
})();
</script>

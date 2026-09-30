@if($command=='layout')
<div id='{{$componentID}}' class='border-box'>

    @if(empty($config->name))
    @include('crudbooster::statistic_builder.components._empty_widget_state', [
        'icon' => '#',
        'title' => 'Widget non configurato',
        'subtitle' => 'Seleziona per impostare nome, dati e icona',
        'link' => $editUrl ?? null,
    ])
    @else
    <div class="kpi-indicator-card" style="--kpi-accent: [color]">
        {{-- L'icona e' facoltativa (docs/refactoring/139): l'intera
             pastiglia (non solo l'icona dentro) resta fuori dal markup se
             non impostata - non solo per via del token grezzo [icon] mai
             sostituito (stesso principio gia' applicato a [sql] in 134),
             ma anche perche' altrimenti la pastiglia vuota (solo sfondo,
             color-mix() dell'accent scelto - bianco di default da 141,
             quindi spesso invisibile su una card gia' bianca) restava
             comunque a occupare 34px + gap in testa alla card, spostando
             l'etichetta anche quando "senza icona" nella pratica
             (docs/refactoring/146, mockup discusso con l'utente). --}}
        <div class="kpi-indicator-head">
            @if(!empty($config->icon))
            <div class="kpi-indicator-icon">
                <span class="lucide-icon" data-lucide-icon="[icon]"></span>
            </div>
            @endif
            <span class="kpi-indicator-label">[name]</span>
        </div>
        <?php
            // Se la query (SQL libera o dataset guidato) non e' ancora stata
            // impostata, renderComponentPayload() non trova nessun valore
            // truthy da sostituire a [sql] (vedi il ciclo su $config e il
            // blocco 'builder' li' sotto) - il placeholder grezzo restava
            // quindi visibile cosi' com'e', poco intuitivo quanto i
            // segnaposto di _empty_widget_state.blade.php (vedi
            // docs/refactoring/120). Qui si decide PRIMA se la query esiste,
            // cosi' quando non esiste il token "[sql]" non compare proprio
            // nell'output (niente da sostituire lato controller) e resta
            // visibile solo il messaggio.
            $hasQuery = (($config->mode ?? 'sql') === 'builder')
                ? !empty($config->dataset)
                : !empty($config->sql);
        ?>
        {{-- Valore (+ descrizione, se presente) raggruppati in un unico
             contenitore che occupa tutto lo spazio libero tra intestazione
             e link (flex: 1, centrato in verticale - docs/refactoring/146,
             stessa idea del mockup discusso con l'utente) invece di stare
             ancorati subito sotto l'intestazione con lo spazio vuoto
             scaricato tutto sotto: altezza/larghezza della card restano
             quelle scelte nel builder (`height: 100%` sul contenitore, mai
             un valore fisso qui), ma con poco contenuto (niente
             descrizione/link) il valore ora si centra nello spazio
             disponibile invece di restare in alto con un vuoto sproporzionato
             sotto. --}}
        <div class="kpi-indicator-body">
            <div class="kpi-indicator-value small-box-sql-value">@if($hasQuery)[sql]@else<span class="small-box-sql-error">{{ trans('crudbooster.statistic_builder_smallbox_no_query') }}</span>@endif</div>
            @if(!empty($config->description))
            <p class="kpi-indicator-description">[description]</p>
            @endif
        </div>
        @if(!empty($config->link))
        <div class="kpi-indicator-footer">
            <a href="[link]" class="kpi-indicator-link">{{ !empty($config->link_label) ? $config->link_label : 'Dettagli' }}
                <svg viewBox="0 0 24 24" class="kpi-indicator-link-icon" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="12,5 19,12 12,19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
        @endif
    </div>
    @endif

    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Small Box'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
</div>

<style>
    /* .small-box-sql-error compare al posto del numero quando la query SQL
       del widget fallisce - testo piu' piccolo e a capo, altrimenti un
       messaggio d'errore lungo rompe il layout della card (pensata per un
       numero corto) */
    .small-box-sql-value .small-box-sql-error {
        display: block;
        font-size: 13px;
        line-height: 1.3;
        white-space: normal;
        word-break: break-word;
    }

    /* Restyle "Indicatore KPI" (docs/refactoring/126-*): riusa le custom
       property --ch-* definite in :root da public/css/theme.css (Fase 6a,
       gia' applicate a Panel Area/Module Panel/Chart/Table) invece di
       introdurre una scala colori a parte - stessa card, stesso raggio,
       stessa ombra del resto della dashboard a griglia. L'unico colore
       "libero" resta l'accent configurabile dall'utente (--kpi-accent,
       da [color]), stemperato con color-mix() per la pastiglia dell'icona
       sullo stesso principio delle coppie --ch-accent/--ch-accent-soft. */
    {{-- Niente background/border/border-radius qui (docs/refactoring/143):
         dopo aver tolto il padding del contenitore esterno in 142
         (.grid-stack-item-content nel builder, .ch-grid-view-cell nella
         vista pubblica), questa card e quel contenitore occupano
         esattamente lo stesso rettangolo - avere ENTRAMBI un proprio
         sfondo/bordo/raggio disegnava due bordi sovrapposti (segnalato
         dall'utente). Il contenitore esterno fa gia' da bordo visibile
         per qualunque widget (compresi quelli senza stile proprio, vedi
         il commento in show_grid.blade.php), quindi qui basta il resto
         (padding interno/layout/hover) senza ridisegnarlo. --}}
    .kpi-indicator-card {
        border-radius: var(--ch-radius-lg, 14px);
        padding: 18px;
        height: 100%;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: box-shadow 150ms ease, transform 150ms ease;
    }
    .kpi-indicator-card:hover {
        box-shadow: var(--ch-shadow-md, 0 4px 12px rgba(16, 16, 24, 0.08));
        transform: translateY(-1px);
    }
    .kpi-indicator-head {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .kpi-indicator-icon {
        width: 34px;
        height: 34px;
        border-radius: var(--ch-radius-md, 10px);
        background: color-mix(in srgb, var(--kpi-accent, var(--ch-accent, #4f46e5)) 14%, white);
        color: var(--kpi-accent, var(--ch-accent, #4f46e5));
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 17px;
    }
    /* Icona Lucide (docs/refactoring/129-*): stessa tecnica a CSS mask del
       reskin Font Awesome (128), ma qui la URL della singola icona e'
       impostata via JS (vedi script sotto) perche' e' dinamica per
       componente - CSS puro non puo' interpolare un attributo dentro
       url(). */
    .kpi-indicator-icon .lucide-icon {
        display: inline-block;
        width: 18px;
        height: 18px;
        background-color: currentColor;
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
        -webkit-mask-size: contain;
        mask-size: contain;
    }
    .kpi-indicator-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--ch-text-secondary, #55555f);
    }
    /* Raggruppa valore (+ descrizione) e li centra nello spazio libero tra
       intestazione e link (docs/refactoring/146) - flex: 1 sul contenitore
       della card fa si' che questo blocco si prenda tutto lo spazio non
       usato da intestazione/link, min-height: 0 e' necessario perche' un
       figlio flex non si restringa mai sotto l'altezza del proprio
       contenuto per default (altrimenti in una card bassa il centraggio
       verticale non avrebbe alcun effetto). */
    .kpi-indicator-body {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
    }
    .kpi-indicator-value {
        font-size: 30px;
        font-weight: 600;
        color: var(--ch-text, #16161d);
        line-height: 1.15;
    }
    .kpi-indicator-description {
        margin: 0;
        font-size: 12.5px;
        line-height: 1.4;
        color: var(--ch-text-muted, #8b8b96);
    }
    .kpi-indicator-footer {
        padding-top: 10px;
        border-top: 1px solid var(--ch-border, #e6e6ea);
    }
    .kpi-indicator-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--ch-accent, #4f46e5);
        text-decoration: none;
    }
    .kpi-indicator-link:hover {
        color: var(--ch-accent-dark, #3730a3);
        text-decoration: underline;
    }
    .kpi-indicator-link-icon {
        width: 13px;
        height: 13px;
        flex-shrink: 0;
    }
</style>

<script defer>
(function () {
    // Icona Lucide: il nome arriva dal config salvato (stessi nomi file di
    // public/vendor/lucide/icons/, popolati dalla select in modalita'
    // 'configuration' qui sotto) - whitelist sul pattern prima di comporre
    // la URL, per difesa in profondita' anche se il valore arriva sempre
    // dalla nostra select, mai da input libero.
    // getElementById invece di un selettore CSS #id: componentID e' un
    // md5 esadecimale, spesso inizia con una cifra - un selettore CSS
    // "#3fe1..." non e' valido (le regole CSS non ammettono un ID che
    // comincia per cifra senza escaping) e querySelector lo rifiuta con
    // una SyntaxError.
    var componentContainer = document.getElementById('{{$componentID}}');
    var iconEl = componentContainer ? componentContainer.querySelector('.lucide-icon[data-lucide-icon]') : null;
    if (iconEl) {
        var name = iconEl.getAttribute('data-lucide-icon') || '';
        if (/^[a-z0-9-]+$/.test(name)) {
            var url = "{{ asset('vendor/lucide/icons') }}/" + name + '.svg';
            iconEl.style.webkitMaskImage = 'url("' + url + '")';
            iconEl.style.maskImage = 'url("' + url + '")';
        }
    }
})();

if (!window.location.href.includes('statistic_builder/builder')) {
    //jquery get div with action class
    var action = $('#{{$componentID}}').find('.action');
    //make it disappear
    action.hide();


    

}
</script>

@elseif($command=='configuration')
<?php
    //elenco delle icone Lucide davvero disponibili, letto dagli SVG gia'
    //vendorizzati (public/vendor/lucide/icons/, vedi docs/refactoring/128 e
    //129), cosi' l'elenco nella select coincide sempre con quello che il
    //widget puo' effettivamente mostrare - niente lista statica da tenere
    //allineata a mano. Prima di 129 leggeva ionicons.min.css (Ionicons v2)
    //ma il widget renderizzava con Ionicons v7 (<ion-icon>): nomi
    //incompatibili tra loro, icona scelta mai quella mostrata (vedi 118).
    $lucideIconFiles = glob(public_path('vendor/lucide/icons/*.svg')) ?: [];
    $lucideIconNames = array_map(fn ($path) => basename($path, '.svg'), $lucideIconFiles);
    sort($lucideIconNames);
    // Query guidata (docs/refactoring/131-*): toggle + pannello reintrodotti
    // qui, erano stati tolti in 124 in attesa di dataset utili da offrire -
    // ora DashboardDatasetRegistry genera un dataset per ogni tabella reale
    // del DB (non solo le 2 curate a mano), vedi 131.
    $currentMode = $config->mode ?? 'sql';
?>
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />

    <details class="ch-config-section" open>
        <summary>Nome e descrizione</summary>
        <div class="ch-config-section-body">
            <div class="mb-3 row">
                <label>Name</label>
                <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}' />
            </div>

            <div class="mb-3 row">
                <label>{{ trans('crudbooster.statistic_builder_smallbox_description_label') }}</label>
                <input class="form-control" name='config[description]' type='text' value='{{@$config->description}}' />
                <div class="help-block">{{ trans('crudbooster.statistic_builder_smallbox_description_help') }}</div>
            </div>
        </div>
    </details>

    <details class="ch-config-section">
        <summary>Colore e icona</summary>
        <div class="ch-config-section-body">
            <div class="mb-3 row">
                <label>Icona (opzionale)</label>
                <select class="form-control" id="smallbox-icon-select" name='config[icon]' style="width:100%">
                    <option value="">-- nessuna icona --</option>
                    @foreach($lucideIconNames as $lucideIconName)
                    <option value="{{ $lucideIconName }}" {{ (@$config->icon == $lucideIconName) ? 'selected' : '' }}>{{ $lucideIconName }}</option>
                    @endforeach
                </select>
                <div class="help-block">Cerca digitando, es. "trash". Anteprima su <a target='_blank'
                    href='https://lucide.dev/icons/'>lucide.dev</a></div>
            </div>

            <div class="mb-3 row">
                <label>Colore (opzionale)</label>
                <input type='color' class='form-control' name='config[color]' value='{{ @$config->color ?: "#ffffff" }}' />
                <div class="help-block">Se non lo cambi resta il colore di default (bianco).</div>
            </div>
        </div>
    </details>

    <details class="ch-config-section">
        <summary>Link</summary>
        <div class="ch-config-section-body">
            <div class="mb-3 row">
                <label>Link (opzionale)</label>
                <input class="form-control" name='config[link]' type='text' value='{{@$config->link}}' />
                <div class="help-block">Se lasciato vuoto, il widget non mostra alcun link.</div>
            </div>

            <div class="mb-3 row">
                <label>Etichetta del link</label>
                <input class="form-control" name='config[link_label]' type='text' placeholder="Dettagli" value='{{@$config->link_label}}' />
            </div>
        </div>
    </details>

    {{-- Sezione "Sorgente dati" (riepilogo + popup con SQL libera/Query
         guidata/filtri) estratta in un partial condiviso
         (docs/refactoring/148-*) cosi' qualunque widget che interroga dati
         - non solo l'Indicatore KPI - la mostra sempre nella stessa
         modale, invece di riscriverla ogni volta. --}}
    @include('crudbooster::statistic_builder.components._source_config', compact('componentID', 'config'))
</form>

{{-- Il CSS di select2 (usato dalla select Icona qui sotto) viene ormai
     caricato una volta sola da _query_builder_fields.blade.php
     (docs/refactoring/149-*, incluso da _source_config poco sopra) - non
     serve ripeterlo qui, la pagina ne ha gia' una copia. --}}
<script>
    (function () {
        // Stesso standard gia' usato altrove in questo progetto per un
        // picker icona+nome (list_icon.blade.php, module_generator/
        // step1.blade.php): icona a sinistra, nome a destra, stessa
        // spaziatura - qui con la stessa tecnica a CSS mask del widget
        // vero (vedi sopra), cosi' l'anteprima nella tendina e' sempre
        // identica a quello che il widget mostrera' davvero (prima
        // dell'intervento 129 usava un font Ionicons v2 diverso da quello
        // renderizzato dal widget: anteprima e risultato non coincidevano
        // mai, vedi 118).
        function formatIcon(icon) {
            var originalOption = icon.element;
            var value = originalOption ? $(originalOption).val() : '';
            if (!value) {
                return icon.text;
            }
            var url = '{{ asset("vendor/lucide/icons") }}/' + value + '.svg';
            var label = $(originalOption).text();
            var $preview = $('<span></span>');
            var $icon = $('<i></i>').css({
                width: '18px',
                height: '18px',
                display: 'inline-block',
                verticalAlign: 'middle',
                marginRight: '6px',
                backgroundColor: 'currentColor',
                webkitMaskImage: 'url("' + url + '")',
                maskImage: 'url("' + url + '")',
                webkitMaskRepeat: 'no-repeat',
                maskRepeat: 'no-repeat',
                webkitMaskPosition: 'center',
                maskPosition: 'center',
                webkitMaskSize: 'contain',
                maskSize: 'contain',
            });
            return $preview.append($icon).append(label);
        }
        // Questa form viene iniettata via $.html() dentro #modal-statistic
        // (builder legacy, statistic_builder/index.blade.php) OPPURE nella
        // sidebar del builder a griglia (builder_grid.blade.php), che non
        // ha nessuna modale - un push in coda pagina qui non avrebbe
        // nessuno stack ad ascoltarlo in nessuno dei due casi, quindi lo
        // script per il select2 va incluso ed eseguito direttamente qui.
        // dropdownParent va agganciata a #modal-statistic SOLO se esiste
        // (builder legacy): passare un dropdownParent a un elemento
        // inesistente fa fallire select2 internamente ("Cannot read
        // properties of undefined (reading 'top')") invece di limitarsi
        // ad attaccare il dropdown al <body> come farebbe di default.
        function initIconSelect() {
            var options = {
                width: '100%',
                templateResult: formatIcon,
                templateSelection: formatIcon,
            };
            var $modal = $('#modal-statistic');
            if ($modal.length) {
                options.dropdownParent = $modal;
            }
            $('#smallbox-icon-select').select2(options);
        }
        window.__chEnsureSelect2(initIconSelect);
    })();
</script>
@elseif($command=='showFunction')
<?php
    if ($key == 'sql') {
        // $value e' un array quando il widget e' in modalita' 'builder'
        // (query guidata, vedi DashboardDatasetRegistry::execute() e
        // StatisticBuilderController) invece che SQL libera: gia' risolto,
        // niente DB::select() da eseguire. Una sola riga attesa (nessun
        // group by) per lo Small Box.
        if (is_array($value)) {
            try {
                echo e((string) ($value[0]['value'] ?? 0));
            } catch (\Throwable $e) {
                echo "<span class='small-box-sql-error'>" . e($e->getMessage()) . "</span>";
            }
        } else {
            try {
                $sessions = Session::all();
                foreach ($sessions as $key => $val) {
                    if (gettype($val) == gettype($value)) {
                        $value = str_replace("[".$key."]", $val, $value);
                    }

                }
                echo reset(DB::select($value)[0]);
            } catch (\Exception $e) {
                echo "<span class='small-box-sql-error'>" . e($e->getMessage()) . "</span>";
            }
        }
    } elseif ($key == 'description') {
        // A differenza di [name]/[link]/[link_label] (mai passati attraverso
        // e()), qui il testo e' pensato come frase libera piu' lunga - meglio
        // partire escapato invece di allargare la superficie XSS gia'
        // esistente sugli altri campi.
        echo e($value);
    } else {
        echo $value;
    }

    ?>
@endif
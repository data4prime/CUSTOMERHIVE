{{--
    Fase 3 del piano "dashboard a griglia libera" (vedi
    docs/piano-dashboard-griglia-libera.md, docs/refactoring/120-*).

    Placeholder mostrato al posto dei segnaposto grezzi ([sql]/[name]/...)
    quando un widget e' stato appena aggiunto e non ha ancora un
    config->name salvato - incluso dal ramo 'layout' di smallbox/table/
    chartline_v2/chartbar_v2 .blade.php con
    empty($config->name) come condizione.

    Parametri: $icon (testo/simbolo breve), $title, $subtitle, $link
    (opzionale - se valorizzato, sostituisce $subtitle con un link
    "Clicca qui per configurare" verso il builder: usato dalla vista
    pubblica di sola lettura, dove "seleziona il widget" non ha senso -
    non c'e' nessuna sidebar li'. Nel builder $link resta sempre null).
--}}
<div class="ch-empty-widget">
    <span class="ch-empty-widget-icon">{{ $icon }}</span>
    <strong class="ch-empty-widget-title">{{ $title }}</strong>
    @if(!empty($link))
    <a href="{{ $link }}" class="ch-empty-widget-subtitle ch-empty-widget-link">Clicca qui per configurare</a>
    @else
    <span class="ch-empty-widget-subtitle">{{ $subtitle }}</span>
    @endif
</div>
<style>
    /* Compatto di proposito: deve stare senza scroll anche in un widget
       KPI alto solo 2 righe di griglia (~60px di area utile). */
    .ch-empty-widget {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        height: 100%;
        min-height: 48px;
        padding: 6px;
        text-align: center;
        color: var(--ch-text-muted);
        font-family: inherit;
    }
    .ch-empty-widget-icon {
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: var(--ch-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        color: var(--ch-text-secondary);
        flex-shrink: 0;
    }
    .ch-empty-widget-title {
        font-size: 11px;
        font-weight: 600;
        color: var(--ch-text-secondary);
        line-height: 1.2;
    }
    .ch-empty-widget-subtitle {
        font-size: 10px;
        line-height: 1.25;
    }
    .ch-empty-widget-link {
        color: var(--ch-accent);
        font-weight: 600;
        text-decoration: none;
    }
    .ch-empty-widget-link:hover {
        text-decoration: underline;
    }
</style>

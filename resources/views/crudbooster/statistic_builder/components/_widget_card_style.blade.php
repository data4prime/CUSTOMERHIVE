{{--
    Stile comune dei widget "a card" (Tabella, Grafici, Pannello, Modulo),
    allineato a quello dell'Indicatore KPI (smallbox.blade.php,
    .kpi-indicator-card): respiro interno di 18px, titolo come etichetta
    (13px, peso 600, colore secondario, senza riga di separazione) e ombra
    al passaggio del mouse. Vedi docs/refactoring/172-*.

    Incluso UNA volta per pagina (show_grid, builder_grid, index legacy) e
    non in ogni componente: i componenti arrivano anche via AJAX dentro
    contenitori gia' in pagina. builder_grid e' una pagina standalone senza
    theme.css, quindi i var() hanno sempre un fallback (come gia' fa lo
    style dell'Indicatore KPI).

    Niente background/border/border-radius qui: il riquadro visibile lo
    disegna gia' il contenitore esterno (.grid-stack-item-content /
    .ch-grid-view-cell) o, nelle dashboard legacy, theme.css - averne un
    secondo qui disegnerebbe due bordi sovrapposti (stesso motivo di
    docs/refactoring/143 per l'Indicatore KPI). Niente transform al hover
    (l'Indicatore KPI ha un translateY di 1px): dentro un widget possono
    esserci iframe, tabelle o elementi position:fixed, che un transform
    sul genitore sposterebbe/ritaglierebbe.

    La specificita' (div.border-box > .card.card-default > ...) e' scelta
    per battere sia le regole di theme.css (.border-box .card.card-default
    .card-header) sia quelle locali di show_grid (.ch-grid-view .card-header)
    senza ricorrere a !important, tranne dove AdminLTE stesso usa
    !important (.no-padding sul corpo della Tabella).
--}}
<style>
    div.border-box > .card.card-default {
        transition: box-shadow 150ms ease;
    }
    div.border-box > .card.card-default:hover {
        box-shadow: var(--ch-shadow-md, 0 4px 12px rgba(16, 24, 40, 0.08));
    }
    div.border-box > .card.card-default > .card-header {
        padding: 18px 18px 0;
        margin-bottom: 0;
        border-bottom: 0;
        background: transparent;
        font-size: 13px;
        font-weight: 600;
        color: var(--ch-text-secondary, #55555f);
    }
    div.border-box > .card.card-default > .card-body {
        padding: 10px 18px 18px;
    }
    div.border-box > .card.card-default > .card-body.no-padding {
        padding: 10px 18px 18px !important;
    }
    /* Il Modulo incorporato vive a filo del bordo (il modulo ha il suo
       layout): qui resta senza padding come prima, vedi panelcustom.blade.php */
    div.border-box > .card.card-default.ch-module-card > .card-body {
        padding: 0;
    }
    div.border-box > .card.card-default.ch-module-card > .card-header {
        padding-bottom: 10px;
    }
</style>

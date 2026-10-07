{{--
  Form dei menu piu' compatto (Aggiungi Menu e modifica): campi su una griglia a 6 colonne
  con l'etichetta sopra (Tenant|Group, Custom Icon|Icon, Active|Home|Nuova scheda sulla
  stessa riga) e Color a pallini. I campi sono gli stessi di prima (stessi name e valori):
  cambia solo la disposizione. Il selettore a pallini pilota la select #color esistente.
  Va incluso una volta per pagina; il contenitore dei campi deve avere la classe .mm-form.
--}}
@push('head')
<style>
    .mm-form { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0 16px; }
    .mm-form > .row { grid-column: span 6; margin: 0 0 14px !important; }
    .mm-form > .row > [class*="col-"] { flex: 0 0 100%; max-width: 100%; padding-left: 0; padding-right: 0; }
    .mm-form > .row > .col-form-label { padding: 0 0 5px; font-size: 12.5px; font-weight: 700; color: var(--ch-text-secondary); }
    .mm-form > #form-group-menu_tenants,
    .mm-form > #form-group-menu_groups,
    .mm-form > #form-group-is_custom,
    .mm-form > #form-group-icon,
    .mm-form > #form-group-icon_upload { grid-column: span 3; }
    .mm-form > #form-group-is_active,
    .mm-form > #form-group-is_dashboard,
    .mm-form > #form-group-new_tab { grid-column: span 2; }
    /* Ordine dei campi: nome e tipo (con il campo che dipende dal tipo), poi chi lo vede
       (ruoli, tenant, gruppo), poi l'aspetto (icona, colore), infine i tre interruttori. */
    .mm-form > #form-group-name { order: 1; }
    .mm-form > #form-group-type { order: 2; }
    .mm-form > #form-group-module_slug,
    .mm-form > #form-group-statistic_slug,
    .mm-form > #form-group-qlik_slug,
    .mm-form > #form-group-chat_ai,
    .mm-form > #form-group-path { order: 3; }
    .mm-form > #form-group-target_layout,
    .mm-form > #form-group-frame_width,
    .mm-form > #form-group-frame_width_unit,
    .mm-form > #form-group-frame_height,
    .mm-form > #form-group-frame_height_unit { order: 4; }
    .mm-form > #form-group-cms_menus_privileges { order: 10; }
    .mm-form > #form-group-menu_tenants,
    .mm-form > #form-group-tenant,
    .mm-form > #form-group-menu_groups { order: 11; }
    .mm-form > #form-group-is_custom { order: 20; }
    .mm-form > #form-group-icon_upload,
    .mm-form > #form-group-icon { order: 21; }
    .mm-form > #form-group-color { order: 30; }
    .mm-form > #form-group-is_active,
    .mm-form > #form-group-is_dashboard,
    .mm-form > #form-group-new_tab { order: 40; }
    .mm-form .help-block:empty { display: none; }
    .mm-form .ch-iconpick.mm-ro .ch-ip-btn { opacity: .7; cursor: not-allowed; }
    .mm-form #form-group-color .select2-container,
    .mm-form #form-group-color select { display: none !important; }
    .mm-dots { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding-top: 4px; }
    .mm-dot { width: 26px; height: 26px; border-radius: var(--ch-radius-pill); border: 2px solid var(--ch-surface); box-shadow: 0 0 0 1px var(--ch-border-strong); cursor: pointer; padding: 0; }
    .mm-dot.on { box-shadow: 0 0 0 2px var(--ch-text); }
    .mm-dot:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--ch-accent); }
    .mm-dots-name { font-size: 12.5px; color: var(--ch-text-muted); margin-left: 4px; }
    @media (max-width: 767px) {
        .mm-form > .row { grid-column: span 6 !important; }
    }
</style>
@endpush
@php
    // Icona di ogni modulo (per le voci di tipo Module: l'icona e' quella del module generator)
    $mmModuleIcons = DB::table('cms_moduls')->whereNull('deleted_at')->pluck('icon', 'id')
        ->map(function ($i) { return \App\Helpers\IconMap::toBi($i); });
@endphp
@push('bottom')
<script type="text/javascript">
    $(function () {
        // Voce di tipo Module: l'icona si legge dal modulo e non si modifica da qui
        var MOD_ICONS = {!! json_encode($mmModuleIcons) !!};
        var MOD_HINT = {!! json_encode(trans('crudbooster.menu_icon_from_module')) !!};
        function applyModuleIcon() {
            var isMod = $('input[name=type]:checked').val() === 'Module';
            var $pick = $('#form-group-icon .ch-iconpick');
            var $hint = $('#mm-icon-hint');
            if (isMod) {
                var ic = MOD_ICONS[$('#module_slug').val()];
                if (ic) {
                    $pick.find('[data-ip-value]').val(ic);
                    $pick.find('.ch-ip-cur').removeClass('is-empty').html('<i class="' + ic + '"></i>');
                    $pick.find('.ch-ip-name').removeClass('is-empty').text(ic.replace(/^bi bi-/, ''));
                }
                $('#form-group-is_custom,#form-group-icon_upload').hide();
                $('#form-group-icon').show();
                if (!$hint.length) {
                    $('<div class="help-block" id="mm-icon-hint"></div>').text(MOD_HINT).appendTo($('#form-group-icon').find('[class*="col-"]').last());
                }
            } else {
                $hint.remove();
                var custom = $('input[name=is_custom]:checked').val() == 1;
                $('#form-group-is_custom').show();
                $('#form-group-icon').toggle(!custom);
                $('#form-group-icon_upload').toggle(custom);
            }
            $pick.toggleClass('mm-ro', isMod).find('[data-ip-open]').prop('disabled', isMod);
        }
        $(document).on('change', '#module_slug, input[name=type]', function () { setTimeout(applyModuleIcon, 0); });
        setTimeout(applyModuleIcon, 100);

        // Colori delle voci di menu (stessi nomi salvati di prima; valori = quelli usati nella sidebar)
        var swatch = {
            'normal': 'var(--ch-text-muted)', 'red': 'var(--ch-danger)', 'green': 'var(--ch-success)',
            'aqua': 'color-mix(in srgb, var(--ch-blue) 50%, var(--ch-success))', 'light-blue': 'color-mix(in srgb, var(--ch-blue) 55%, var(--ch-surface))',
            'yellow': 'var(--ch-warning)', 'muted': 'var(--ch-border-strong)'
        };
        var $sel = $('#color');
        if (!$sel.length) { return; }
        var seen = {}, $dots = $('<div class="mm-dots" role="radiogroup"></div>'), $name = $('<span class="mm-dots-name"></span>');
        $sel.find('option').each(function () {
            var v = this.value;
            if (!v || seen[v]) { return; }
            seen[v] = true;
            $('<button type="button" class="mm-dot" role="radio"></button>')
                .attr({ 'data-v': v, title: v, 'aria-label': v })
                .css('background', swatch[v] || v)
                .appendTo($dots);
        });
        $dots.append($name);
        function paint() {
            var cur = $sel.val() || 'normal';
            $dots.find('.mm-dot').each(function () {
                var on = $(this).data('v') === cur;
                $(this).toggleClass('on', on).attr('aria-checked', on);
            });
            $name.text(cur);
        }
        $dots.on('click', '.mm-dot', function () {
            $sel.val($(this).data('v')).trigger('change');
            paint();
        });
        $sel.on('change', paint);
        $sel.closest('[class*="col-"]').append($dots);
        paint();
    });
</script>
@endpush

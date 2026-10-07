{{--
  Standard unico per i campi testuali (stile in public/css/ch-components.css,
  comportamento in public/js/ch-inputs.js).

  Parametri:
    name, input_id    attributi dell'input (input_id default = name)
    kind              'text' (default) | 'number' (stepper) | 'password' (occhio) | 'textarea'
                      | 'date' | 'datetime' | 'time' (selettore ch-datetime.js, campo di sola lettura)
    input_type        type HTML (default dedotto da kind: text/number/password/email/...)
    value             valore
    ch_label          title dell'input (opzionale)
    placeholder       testo placeholder (opzionale, escapato)
    required, readonly, disabled   bool
    prefix, suffix    testo (escapato) mostrato come addon grigio a sinistra/destra
    prefix_icon       nome icona Bootstrap (es. 'envelope-fill') al posto del testo del prefisso
    input_class       classi aggiuntive sull'input (opzionale)
    input_attrs       attributi extra, es. ['min' => 0, 'step' => '0.5'] (escapati; valori null/false saltati)
    maxlength         int (textarea: mostra anche il contatore)
    rows              righe della textarea (default 5)
--}}
@php
    $chKind = $kind ?? 'text';
    $chType = $input_type ?? ($chKind === 'number' ? 'number' : ($chKind === 'password' ? 'password' : 'text'));
    $chId = !empty($input_id) ? $input_id : $name;
    $chAttrs = '';
    foreach ((array) ($input_attrs ?? []) as $chK => $chV) {
        if ($chV === null || $chV === false || $chV === '') { continue; }
        $chAttrs .= ' ' . e($chK) . '="' . e($chV) . '"';
    }
    if (!empty($maxlength)) { $chAttrs .= ' maxlength="' . (int) $maxlength . '"'; }
    $chCommon = (!empty($ch_label) ? ' title="' . e($ch_label) . '"' : '')
        . (isset($placeholder) && $placeholder !== '' ? ' placeholder="' . e($placeholder) . '"' : '')
        . (!empty($required) ? ' required' : '') . (!empty($readonly) ? ' readonly' : '') . (!empty($disabled) ? ' disabled' : '');
    $chPick = in_array($chKind, ['date', 'datetime', 'time'], true);
    if ($chPick) {
        $prefix_icon = $chKind === 'time' ? 'clock' : 'calendar3';
        $chLocked = !empty($readonly) || !empty($disabled);
        $chCommon .= ' data-ch-picker="' . $chKind . '"' . ($chLocked ? ' data-ch-locked' : '');
        if (empty($readonly)) { $chCommon .= ' readonly'; }
    }
    $chHasPrefix = !empty($prefix) || !empty($prefix_icon);
    $chHasGroup = $chHasPrefix || !empty($suffix) || $chKind === 'number' || $chKind === 'password' || $chPick;
@endphp
@if($chKind === 'textarea')
<div class="ch-textarea">
    <textarea class="form-control {{ $input_class ?? '' }}" name="{{ $name }}" id="{{ $chId }}" rows="{{ $rows ?? 5 }}"{!! $chCommon !!}{!! $chAttrs !!}>{{ $value ?? '' }}</textarea>
    @if(!empty($maxlength))
    <div class="ch-counter"><span data-ch-count>0</span>/{{ (int) $maxlength }}</div>
    @endif
</div>
@elseif(!$chHasGroup)
<input type="{{ $chType }}" class="form-control {{ $input_class ?? '' }}" name="{{ $name }}" id="{{ $chId }}" value="{{ $value ?? '' }}"{!! $chCommon !!}{!! $chAttrs !!}>
@else
<div class="input-group ch-input{{ $chPick && ($value ?? '') !== '' ? ' has-value' : '' }}">
    @if($chHasPrefix)
    <span class="input-group-text ch-addon-pre">@if(!empty($prefix_icon))<i class="bi bi-{{ $prefix_icon }}"></i>@else{{ $prefix }}@endif</span>
    @endif
    <input type="{{ $chType }}" class="form-control {{ $input_class ?? '' }}" name="{{ $name }}" id="{{ $chId }}" value="{{ $value ?? '' }}"{!! $chCommon !!}{!! $chAttrs !!}>
    @if(!empty($suffix))
    <span class="input-group-text ch-addon-suf">{{ $suffix }}</span>
    @endif
    @if($chKind === 'number' && empty($readonly) && empty($disabled))
    <span class="ch-step"><button type="button" tabindex="-1" data-ch-step="1" aria-label="+"><i class="bi bi-chevron-up"></i></button><button type="button" tabindex="-1" data-ch-step="-1" aria-label="-"><i class="bi bi-chevron-down"></i></button></span>
    @endif
    @if($chPick && empty($chLocked))
    <button type="button" class="ch-clear" data-ch-clear tabindex="-1" aria-label="×"><i class="bi bi-x-lg"></i></button>
    @endif
    @if($chKind === 'password')
    <button type="button" class="ch-eye" data-ch-eye aria-pressed="false" tabindex="-1"><i class="bi bi-eye"></i></button>
    @endif
</div>
@endif

{{--
  Campo file standard: riquadro con "Scegli file" a sinistra e il nome del file scelto
  (comportamento in public/js/ch-inputs.js). L'<input type="file"> resta figlio diretto
  della colonna del form (come prima: il codice dei moduli che nasconde il campo con
  input.parentNode.parentNode continua a funzionare) ed e' invisibile; la label
  "Scegli file" lo apre tramite for=. name, required e la selezione file funzionano come sempre.

  Parametri: name, ch_label (title), required/readonly/disabled (bool),
             accept (opzionale), file_image (bool: testo e icona "immagine")
--}}
@php $fImg = !empty($file_image); @endphp
<input type="file" id="{{ $name }}" name="{{ $name }}" data-ch-file class="ch-file-input"
       @if(!empty($ch_label)) title="{{ $ch_label }}" @endif
       @if(!empty($accept)) accept="{{ $accept }}" @endif
       @if(!empty($required)) required @endif @if(!empty($readonly)) readonly @endif @if(!empty($disabled)) disabled @endif>
<div class="input-group ch-input ch-file">
    <label class="input-group-text ch-addon-pre ch-file-btn" for="{{ $name }}">
        <i class="bi bi-{{ $fImg ? 'image' : 'paperclip' }}"></i>
        {{ trans($fImg ? 'crudbooster.chose_an_image' : 'crudbooster.chose_an_file') }}
    </label>
    <div class="ch-fname" data-ch-fname>{{ trans('crudbooster.file_none_selected') }}</div>
</div>

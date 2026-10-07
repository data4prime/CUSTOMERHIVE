{{--
  Lista del popup di scelta (dentro l'iframe della finestra datamodal): ricerca in
  alto (si invia da sola dopo una breve pausa o con Invio) e tabella in cui si sceglie
  cliccando una riga. La scelta va alla pagina padre con selectAdditionalData<name>(payload).

  Parametri:
    dm_name     name del campo (name_column)
    dm_headers  intestazioni di colonna (array di testi)
    dm_rows     array di ['values' => [valori grezzi per colonna], 'payload' => array inviato al padre]
    dm_pager    (opzionale) HTML della paginazione
--}}
@php
    $dmImg = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
    $dmCols = count($dm_headers ?? []);
@endphp
<form method="get" action="" class="ch-dm-search">
    {!! CRUDBooster::getUrlParameters(['q']) !!}
    <div class="input-group ch-input">
        <span class="input-group-text ch-addon-pre"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" id="ch-dm-q" name="q" value="{{ Request::get('q') }}" autocomplete="off" autofocus
               placeholder="{{ trans('crudbooster.datamodal_search_and_enter') }}" title="{{ trans('crudbooster.datamodal_enter_to_search') }}">
    </div>
</form>

<table id="table_dashboard" class="table table-hover table-sm ch-dm-table">
    <thead>
        <tr>@foreach($dm_headers as $h)<th>{{ $h }}</th>@endforeach</tr>
    </thead>
    <tbody>
        @forelse($dm_rows as $r)
        <tr class="ch-dm-row" tabindex="0" data-payload="{{ json_encode($r['payload']) }}">
            @foreach($r['values'] as $v)
            @php $ext = strtolower(pathinfo((string) $v, PATHINFO_EXTENSION)); @endphp
            @if($ext && in_array($ext, $dmImg))
            <td><img src="{{ asset($v) }}" width="50" height="30" alt=""></td>
            @else
            <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) $v), 50) }}</td>
            @endif
            @endforeach
        </tr>
        @empty
        <tr><td colspan="{{ max($dmCols, 1) }}" class="ch-dm-empty">{{ trans('crudbooster.select_no_results') }}</td></tr>
        @endforelse
    </tbody>
</table>
@if(!empty($dm_paginator) && $dm_paginator->hasPages())
@php
    // Paginazione compatta: "43-48 di 71", precedente/successivo e al massimo 5 numeri
    // (prima, ultima e quelle vicine alla corrente, con "…" nei salti).
    $pgCur = $dm_paginator->currentPage();
    $pgLast = $dm_paginator->lastPage();
    $pgUrl = function ($page) use ($dm_paginator) { return str_replace('/?', '?', $dm_paginator->url($page)); };
    $pgNums = array_values(array_unique(array_filter([1, $pgCur - 1, $pgCur, $pgCur + 1, $pgLast], function ($x) use ($pgLast) { return $x >= 1 && $x <= $pgLast; })));
    sort($pgNums);
@endphp
<div class="ch-dm-pager">
    <span class="ch-pg-info">{{ $dm_paginator->firstItem() }}–{{ $dm_paginator->lastItem() }} / {{ $dm_paginator->total() }}</span>
    <nav class="ch-pg" aria-label="pagination">
        <a class="ch-pg-btn{{ $pgCur <= 1 ? ' disabled' : '' }}" @if($pgCur > 1) href="{{ $pgUrl($pgCur - 1) }}" @endif aria-label="‹"><i class="bi bi-chevron-left"></i></a>
        @php $pgPrev = 0; @endphp
        @foreach($pgNums as $pgN)
            @if($pgN - $pgPrev > 1)<span class="ch-pg-gap">…</span>@endif
            <a class="ch-pg-btn{{ $pgN == $pgCur ? ' active' : '' }}" href="{{ $pgUrl($pgN) }}">{{ $pgN }}</a>
            @php $pgPrev = $pgN; @endphp
        @endforeach
        <a class="ch-pg-btn{{ $pgCur >= $pgLast ? ' disabled' : '' }}" @if($pgCur < $pgLast) href="{{ $pgUrl($pgCur + 1) }}" @endif aria-label="›"><i class="bi bi-chevron-right"></i></a>
    </nav>
</div>
@elseif(!empty($dm_pager))
<div class="ch-dm-pager">{!! $dm_pager !!}</div>
@endif

<script>
(function () {
    document.body.classList.add('ch-dm-page');
    var name = {!! json_encode($dm_name) !!};
    function choose(tr) { parent['selectAdditionalData' + name]($(tr).data('payload')); }
    $(document).on('click', '.ch-dm-row', function () { choose(this); });
    $(document).on('keydown', '.ch-dm-row', function (e) { if (e.key === 'Enter') { e.preventDefault(); choose(this); } });
    var q = document.getElementById('ch-dm-q'), timer = null;
    if (q) {
        q.focus(); q.setSelectionRange(q.value.length, q.value.length);
        q.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { q.form.submit(); }, 400); });
    }
})();
</script>

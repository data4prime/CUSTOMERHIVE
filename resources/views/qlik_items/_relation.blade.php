{{-- Pagina relazione di un item Qlik (gruppi o tenant autorizzati), come le pagine
     relazione dei tenant/gruppi: testata con conteggio, pulsante "aggiungi" che apre
     un selettore in modale, elenco con rimozione confermata sulla riga.
     L'aggiunta fa lo stesso POST di prima (campo "name" = id, action, return_url,
     ref_mainpath); la rimozione e' lo stesso GET di prima.
     Parametri: $rel_title, $rel_rows (id,name,description), $rel_available (id,name,description),
     $rel_opener, $rel_modal_title, $rel_empty_available, $rel_remove_url (con ":id") --}}
<div>
  @include('groups._page_head', [
    'crumb_name' => $qlik_item->title ?? ('#' . $item_id),
    'title' => $rel_title,
    'count' => count($rel_rows),
    'opener_label' => $rel_opener,
    'opener_modal' => '#add-rel-modal',
  ])

  @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())
  <form id="add-rel-form" method="post" action="{{ $action }}">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="return_url" value="{{ $return_url }}">
    <input type="hidden" name="ref_mainpath" value="{{ CRUDBooster::mainpath() }}">
    <input type="hidden" name="name" id="add-rel-id" value="">
  </form>
  <div class="modal fade" id="add-rel-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $rel_modal_title }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="search" id="add-rel-search" class="form-control mb-3" placeholder="{{ trans('crudbooster.filter_search') }}" autocomplete="off">
          <div class="list-group" id="add-rel-list">
            @foreach($rel_available as $a)
            <button type="button" class="list-group-item list-group-item-action" data-id="{{ $a->id }}" data-text="{{ mb_strtolower($a->name . ' ' . $a->description) }}">
              <div class="fw-semibold">{{ $a->name }}</div>
              @if($a->description)<div class="small text-secondary">{{ $a->description }}</div>@endif
            </button>
            @endforeach
          </div>
          <div class="text-center text-secondary py-3 {{ count($rel_available) ? 'd-none' : '' }}" id="add-rel-empty">{{ $rel_empty_available }}</div>
        </div>
      </div>
    </div>
  </div>
  @push('bottom')
  <script>
    (function () {
      var list = document.getElementById('add-rel-list');
      var empty = document.getElementById('add-rel-empty');
      list.addEventListener('click', function (e) {
        var item = e.target.closest('[data-id]');
        if (!item) { return; }
        document.getElementById('add-rel-id').value = item.getAttribute('data-id');
        document.getElementById('add-rel-form').submit();
      });
      document.getElementById('add-rel-search').addEventListener('input', function () {
        var q = this.value.toLowerCase().trim(), shown = 0;
        list.querySelectorAll('[data-id]').forEach(function (el) {
          var ok = el.getAttribute('data-text').indexOf(q) !== -1;
          el.classList.toggle('d-none', !ok);
          if (ok) { shown++; }
        });
        empty.classList.toggle('d-none', shown > 0);
      });
      document.getElementById('add-rel-modal').addEventListener('shown.bs.modal', function () {
        document.getElementById('add-rel-search').focus();
      });
    })();
  </script>
  @endpush
  @endif

  <div class="table-responsive rel-table">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>{!! __('crudbooster.name') !!}</th>
          <th>{!! __('crudbooster.description') !!}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($rel_rows as $r)
        <tr>
          <td>{{ $r->name }}</td>
          <td>{{ $r->description }}</td>
          <td class="text-end">
            @if(CRUDBooster::isDelete() && $button_edit)
            @include('groups._remove', ['url' => str_replace(':id', $r->id, $rel_remove_url)])
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="3" class="text-center text-secondary">{{ trans('crudbooster.adm_no_rows') }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@push('bottom')
@include('groups._remove_script')
@endpush

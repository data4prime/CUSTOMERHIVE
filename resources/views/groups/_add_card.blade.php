{{-- Form "aggiungi" delle pagine relazione (membri/tenant/item/gruppi), chiuso
     di default dietro un pulsante: chi apre la pagina vuole vedere l'elenco
     (intervento 232). Si apre da solo se c'e' un avviso (es. campo vuoto).
     Il form e' quello di prima: stessi campi, stessa action, stesso POST.
     Parametri: $card_title, $submit_label, $opener_label --}}
<div class="collapse {{ !empty($alerts) ? 'show' : '' }}" id="add-panel">
  <div class="card card-default mb-3">
    <div class="card-header">
      <strong><i class='{{ $card_icon ?? CRUDBooster::getCurrentModule()->icon }}'></i> {!! $card_title !!}</strong>
    </div>
    <div class="card-body" style="padding:20px 0px 0px 0px">
      <form class='form-horizontal' method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type='hidden' name='return_url' value='{{ @$return_url }}' />
        <input type='hidden' name='ref_mainpath' value='{{ CRUDBooster::mainpath() }}' />
        <input type='hidden' name='ref_parameter' value='{{urldecode(http_build_query(@$_GET))}}' />
        @if($hide_form)
        <input type="hidden" name="hide_form" value='{!! serialize($hide_form) !!}'>
        @endif
        <div class="box-body" id="parent-form-area">
          @if( isset($command) && $command == 'detail')
          @include("crudbooster::default.form_detail")
          @else
          @include("crudbooster::default.form_body")
          @endif
        </div>

        <div class="box-footer" style="background: var(--ch-bg)">
          <div class="mb-3 row">
            <label class="col-form-label col-sm-2"></label>
            <div class="col-sm-10">
              @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())
              @if(CRUDBooster::isCreate() && $button_addmore==TRUE && isset($command) && $command == 'add')
              <input type="submit" name="submit" value='{{trans("crudbooster.button_save_more")}}' class='btn btn-success'>
              @endif
              @if($button_save && isset($command) && $command != 'detail')
              <input type="submit" name="submit" value='{{ $submit_label }}' class='btn btn-success'>
              @endif
              @endif
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@extends('crudbooster::admin_template')
@section('content')
@include('crudbooster::menus._form_compact')
@push('head')
<style type="text/css">
    body.dragging,
    body.dragging * {
        cursor: move !important;
    }

    .dragged {
        position: absolute;
        opacity: 0.7;
        z-index: 2000;
    }

    .draggable-menu {
        padding: 0 0 0 0;
        margin: 0 0 0 0;
    }

    .draggable-menu li ul {
        margin-top: 6px;
    }

    .draggable-menu li {
        list-style-type: none;
        margin-bottom: 8px;
        min-height: 35px;
    }

    .draggable-menu li > .mm-row {
        padding: 12px 14px;
        border: 1px solid var(--ch-border);
        border-radius: 14px;
        background: var(--ch-surface);
        cursor: move;
        transition: border-color .15s, background .15s;
    }

    .draggable-menu li > .mm-row:hover {
        border-color: var(--ch-border-strong);
        background: var(--ch-hover);
    }

    .draggable-menu li > .mm-row.is-dashboard {
        background: linear-gradient(135deg, var(--ch-accent-soft), transparent);
        border-color: var(--ch-accent);
    }

    .mm-line { display: flex; align-items: center; gap: 10px; font-size: 14px; }
    .mm-name { font-weight: 700; }
    .mm-ico { display: grid; place-items: center; width: 30px; height: 30px; border-radius: 10px; background: var(--ch-accent-soft); color: var(--ch-accent); flex: none; }
    .mm-actions { margin-left: auto; display: flex; gap: 4px; }
    .mm-act { display: grid; place-items: center; width: 30px; height: 30px; border-radius: 10px; color: var(--ch-text-muted); text-decoration: none; cursor: pointer; }
    .mm-act:hover { background: var(--ch-accent-soft); color: var(--ch-accent); }
    .mm-act.mm-del:hover { background: var(--ch-danger-soft, var(--ch-accent-soft)); color: var(--ch-danger); }
    .mm-confirm { display: inline-flex; align-items: center; gap: 6px; }
    .mm-confirm[hidden] { display: none; }
    .mm-confirm-q { font-size: 12.5px; font-weight: 600; color: var(--ch-danger); margin-right: 2px; }
    .mm-meta { display: flex; justify-content: space-between; gap: 14px; margin: 6px 0 0 40px; font-size: 12px; color: var(--ch-text-muted); }

    .mm-card { border: 1px solid var(--ch-border); border-radius: 20px; background: var(--ch-surface); box-shadow: var(--ch-shadow-sm); overflow: hidden; }
    .mm-card > .card-header { display: flex; align-items: center; gap: 10px; padding: 18px 22px; font-size: 16px; font-weight: 800; letter-spacing: -.01em; background: transparent; border-bottom: 1px solid var(--ch-border); }
    .mm-card > .card-header:before { content: ''; width: 9px; height: 9px; border-radius: 50%; background: var(--ch-accent); }
    .mm-card.mm-ok > .card-header:before { background: var(--ch-success); }
    .mm-card.mm-ko > .card-header:before { background: var(--ch-danger); }
    .mm-card > .card-body { padding: 16px; }
    .draggable-menu li.placeholder {
        position: relative;
        border: 1px dashed var(--ch-danger);
        background: var(--ch-surface);
        /** More li styles **/
    }

    .draggable-menu li.placeholder:before {
        position: absolute;
        /** Define arrowhead **/
    }
</style>
@endpush

{{-- L'init select2 con preview icona per #list-icon e' ora centralizzata in
     resources/views/crudbooster/components/list_icon.blade.php, incluso in
     ogni punto (qui e nel form add/edit standard) dove questo select viene
     stampato: farla anche qui duplicherebbe l'init sullo stesso elemento
     (stesso bug gia' visto nel picker icone del Module Generator: doppia
     init -> campo che appare vuoto). --}}

@push('bottom')
<script src='{{asset("vendor/crudbooster/assets/jquery-sortable-min.js")}}'></script>
<script type="text/javascript">
    // Conferma di eliminazione sulla riga: "Eliminare? Si! / Annulla" (nessun popup).
    // Gestori inline (onclick sul link): il drag&drop ferma la propagazione dei clic
    // verso document, quindi un gestore delegato non scatterebbe.
    function mmAsk(el) {
        var $a = $(el).closest('.mm-actions');
        $('.mm-actions').not($a).find('.mm-confirm').attr('hidden', true).end().find('.mm-act').show();
        $a.find('.mm-act').hide();
        $a.find('.mm-confirm').removeAttr('hidden');
        return false;
    }
    function mmCancel(el) {
        var $a = $(el).closest('.mm-actions');
        $a.find('.mm-confirm').attr('hidden', true);
        $a.find('.mm-act').show();
    }
$(function () {
    var id_cms_privileges = '{!! $id_cms_privileges !!}';
    var sortactive = $(".draggable-menu").sortable({
        group: '.draggable-menu',
        delay: 200,
        cancel: ".ui-state-disabled",
        isValidTarget: function (item, container) {
            if ($(item).hasClass("ui-state-disabled")) {
                return false;
            }
            var depth = 1; // Start with a depth of one (the element itself)
            var maxDepth = '{!! config("app.menu_max_nesting_levels") !!}'; // Assumendo che config() sia definita altrove nel tuo codice.
            
            var children = item.find('ul').first().find('li');
            // Add the amount of parents to the depth
            depth += container.el.parents('ul').length;
            // Increment the depth for each time a child
            while (children.length) {
                depth++;
                children = children.find('ul').first().find('li');
            }
            return depth <= maxDepth;
        },
        onDrop: function (item, container, _super) {
            var is_active = 1;
            if (item.parents('ul').hasClass('draggable-menu-active')) {
                is_active = 1;
                var data = $('.draggable-menu-active').sortable("serialize").get();
                var jsonString = JSON.stringify(data, null, ' ');
            }
            else {
                is_active = 0;
                var data = $('.draggable-menu-inactive').sortable("serialize").get();
                var jsonString = JSON.stringify(data, null, ' ');
                $('#inactive_text').remove();
            }
            console.log(data);
            // console.log(jsonString);
            console.log(is_active);
            $.post("{{route('MenusControllerPostSaveMenu')}}", { menus: jsonString, isActive: is_active }, function (resp) {
                console.log(resp);
                $('#menu-saved-info').fadeIn('fast').delay(1000).fadeOut('fast');
            });
            _super(item, container);
        }
    });
});


    /*
     $(function () {
         var id_cms_privileges = '{{$id_cms_privileges}}';
         var sortactive = $(".draggable-menu").sortable({
             group: '.draggable-menu',
             delay: 200,
             cancel: ".ui-state-disabled",
             isValidTarget: function (item, container) {
                 if ($(item).hasClass("ui-state-disabled")) {
                     return false;
                 }
                 var depth = 1, // Start with a depth of one (the element itself)
                     maxDepth = config('app.menu_max_nesting_levels')
             }
         },
             children = item.find('ul').first().find('li');
         // Add the amount of parents to the depth
         depth += container.el.parents('ul').length;
         // Increment the depth for each time a child
         while (children.length) {
             depth++;
             children = children.find('ul').first().find('li');
         }
         return depth <= maxDepth;
     },
         onDrop: function (item, container, _super) {
             var is_active = 1;
             if (item.parents('ul').hasClass('draggable-menu-active')) {
                 is_active = 1;
                 var data = $('.draggable-menu-active').sortable("serialize").get();
                 var jsonString = JSON.stringify(data, null, ' ');
             }
             else {
                 is_active = 0;
                 var data = $('.draggable-menu-inactive').sortable("serialize").get();
                 var jsonString = JSON.stringify(data, null, ' ');
                 $('#inactive_text').remove();
             }
             console.log(data);
             // console.log(jsonString);
             console.log(is_active);
             $.post("{{route('MenusControllerPostSaveMenu')}}", { menus: jsonString, isActive: is_active }, function (resp) {
                 console.log(resp);
                 $('#menu-saved-info').fadeIn('fast').delay(1000).fadeOut('fast');
             });
             _super(item, container);
         }
                 });
             });
    */
</script>
@endpush

<div class='row'>
    <div class="col-sm-5">

        <div class="card mm-card mm-ok mb-3">
            <div class="card-header">
                <strong>{{ trans('crudbooster.menu_order_active') }}</strong>
                <span id='menu-saved-info' style="display:none" class='float-end text-success'>
                    <i class='bi bi-check-lg'></i> {{ trans('crudbooster.menu_saved') }}
                </span>
            </div>
            <div class="card-body clearfix">
                <ul class='draggable-menu draggable-menu-active'>
                    @php echo $menu_active_html @endphp
                </ul>
                @if(count($menu_active)==0)
                <div align="center">{{ trans('crudbooster.active_menu_is_empty_please_add_new_menu') }}</div>
                @endif
            </div>
        </div>

        <div class="card mm-card mm-ko">
            <div class="card-header">
                <strong>{{ trans('crudbooster.menu_order_inactive') }}</strong>
            </div>
            <div class="card-body clearfix">

                <ul class='draggable-menu draggable-menu-inactive'>
                    @php echo $menu_inactive_html @endphp
                </ul>

                @if(count($menu_inactive)==0)
                <div align="center" id='inactive_text' class='text-muted'>{{ trans('crudbooster.inactive_menu_is_empty') }}</div>
                @endif
            </div>
        </div>


    </div>

    <?php

    //split width and height into dimension and unit
    if(isset($name) && ($name == 'frame_width' OR $name == 'frame_height')){
      @$value = (isset($row->{$name})) ? (int)$row->{$name} : $value;
    }
    elseif(isset($name) && ($name == 'frame_width_unit' OR $name == 'frame_height_unit')){
      if((isset($row->{substr($name, 0, -5)}))){
        $last_char = substr($row->{substr($name, 0, -5)}, -1);
        @$value = $last_char == '%' ? '%' : 'px';
      }
      else{
        @$value = $value;
      }
    }
    ?>

    <div class="col-sm-7">
        <div class="card mm-card">
            <div class="card-header">
                {{ trans('crudbooster.add_menu') }}
            </div>
            <div class="card-body">
                <form class='form-horizontal' method='post' id="form" enctype="multipart/form-data"
                    action='{{CRUDBooster::mainpath("add-save")}}'>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type='hidden' name='return_url' value='{{Request::fullUrl()}}' />
                    <div class="mm-form">@include("crudbooster::default.form_body")</div>
                    <p align="right"><input type='submit' class='btn btn-primary' value='{{ trans("crudbooster.add_menu") }}' /></p>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
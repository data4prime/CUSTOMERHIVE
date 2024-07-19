@extends('crudbooster::admin_template')
@section('content')
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

    .draggable-menu li div {
        padding: 5px;
        border: 1px solid #cccccc;
        background: #eeeeee;
        cursor: move;
    }

    .draggable-menu li .is-dashboard {
        background: #fff6e0;
    }

    .draggable-menu li .icon-is-dashboard {
        color: #ffb600;
    }

    .draggable-menu li {
        list-style-type: none;
        margin-bottom: 4px;
        min-height: 35px;
    }

    .draggable-menu li.placeholder {
        position: relative;
        border: 1px dashed #b7042c;
        background: #ffffff;
        /** More li styles **/
    }

    .draggable-menu li.placeholder:before {
        position: absolute;
        /** Define arrowhead **/
    }
</style>
@endpush

@push('bottom')
<script type="text/javascript">
    $(function () {
        function format(icon) {
            var originalOption = icon.element;
            var label = $(originalOption).text();
            var val = $(originalOption).val();
            if (!val) return label;
            var $resp = $('<span><i style="margin-top:5px" class="pull-right ' + $(originalOption).val() + '"></i> ' + $(originalOption).data('label') + '</span>');
            return $resp;
        }

        $('#list-icon').select2({
            width: "100%",
            templateResult: format,
            templateSelection: format
        });
    })
</script>
@endpush

@push('bottom')
<script src='{{asset("vendor/crudbooster/assets/jquery-sortable-min.js")}}'></script>
<script type="text/javascript">
    
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

        <div class="panel panel-success">
            <div class="panel-heading">
                <strong>Menu Order (Active)</strong>
                <span id='menu-saved-info' style="display:none" class='pull-right text-success'>
                    <i class='fa fa-check'></i> Menu Saved !
                </span>
            </div>
            <div class="panel-body clearfix">
                <ul class='draggable-menu draggable-menu-active'>
                    @php echo $menu_active_html @endphp
                </ul>
                @if(count($menu_active)==0)
                <div align="center">Active menu is empty, please add new menu</div>
                @endif
            </div>
        </div>

        <div class="panel panel-danger">
            <div class="panel-heading">
                <strong>Menu Order (Inactive)</strong>
            </div>
            <div class="panel-body clearfix">

                <ul class='draggable-menu draggable-menu-inactive'>
                    @php echo $menu_inactive_html @endphp
                </ul>

                @if(count($menu_inactive)==0)
                <div align="center" id='inactive_text' class='text-muted'>Inactive menu is empty</div>
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
        <div class="panel panel-primary">
            <div class="panel-heading">
                Add Menu
            </div>
            <div class="panel-body">
                <form class='form-horizontal' method='post' id="form" enctype="multipart/form-data"
                    action='{{CRUDBooster::mainpath("add-save")}}'>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type='hidden' name='return_url' value='{{Request::fullUrl()}}' />
                    @include("crudbooster::default.form_body")
                    <p align="right"><input type='submit' class='btn btn-primary' value='Add Menu' /></p>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
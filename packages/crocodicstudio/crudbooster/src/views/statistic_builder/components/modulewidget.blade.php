@php

use crocodicstudio\crudbooster\helpers\CRUDBooster;
use crocodicstudio\crudbooster\helpers\UserHelper;

$menu_active = DB::table('cms_menus')
            ->whereRaw(
                "cms_menus.id IN
                      (
                        select id_cms_menus
                        from cms_menus_privileges
                        where id_cms_privileges = '" . CRUDBooster::myPrivilegeId() . "'
                      )"
            )
            ->where('parent_id', $parent_id)
            ->where('is_active', 1)
            ->where('is_dashboard', 0)
            ->join('menu_tenants', 'menu_tenants.menu_id', 'cms_menus.id')
            ->where('menu_tenants.tenant_id', UserHelper::current_user_tenant());

        // se l'utente corrente non è superadmin e non è Tenantadmin..
        if (!CRUDBooster::isSuperadmin() and !UserHelper::isTenantAdmin()) {
            //..allora filtra i menu visibili in base ai suoi gruppi
            $menu_active = $menu_active->join('menu_groups', 'cms_menus.id', '=', 'menu_groups.menu_id')
                ->whereIn('menu_groups.group_id', UserHelper::current_user_groups());
        }
        $menu_active = $menu_active->orderby('sorting', 'asc')
            ->select('cms_menus.*')
            ->distinct() // moltiplica le righe senza duplicate se item ha molti gruppi
            ->get();

dd($menu_active);


@endphp 

@if($command=='layout')
    <div id='{{$componentID}}' class='border-box'>

        <div class="panel panel-default">
            <div class="panel-heading">
                [name]
            </div>
            <div class="panel-body">
                [value]
            </div>
        </div>

        <div class='action pull-right'>
            <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Panel Custom' class='btn-edit-component'><i
                        class='fa fa-pencil'></i></a> &nbsp;
            <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i class='fa fa-trash'></i></a>
        </div>
    </div>
@elseif($command=='configuration')
    <form method='post'>
        <input type='hidden' name='_token' value='{{csrf_token()}}'/>
        <input type='hidden' name='componentid' value='{{$componentID}}'/>
        <div class="form-group">
            <label>Name</label>
            <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}'/>
        </div>

        <div class="form-group">
            <label>Type</label>
            <select name='config[type]' class='form-control'>
                <option {{(@$config->type == 'controller')?"selected":""}} value='controller'>Controller & Method</option>
                <option {{(@$config->type == 'route')?"selected":""}} value='route'>Route Name</option>
            </select>
        </div>

        <!--<div class="form-group">
            <label>Value</label>
            <input name='config[value]' type='text' class='form-control' value='{{@$config->value}}'/>
            <div class='help-block'>You must enter the valid value related with current TYPE unless, widget will not work</div>
        </div>-->

    </form>
@elseif($command=='showFunction')
    <?php
    if($key == 'value') {
    if ($config->type == 'controller') {
        $url = action($value);
    } elseif ($config->type == 'route') {
        $url = route($value);
    }
    echo "<div id='content-$componentID'></div>";
    ?>

    <script>
        $(function () {
            $('#content-{{$componentID}}').html("<i class='fa fa-spin fa-spinner'></i> Please wait loading...");
            $.get('{{$url}}', function (response) {

                //from respose, we need to get the content_section id
                var content_section = $(response).find('#content_section').html();


                $('#content-{{$componentID}}').html(content_section);
            });
        })
    </script>

    <?php
    }else {
        echo $value;
    }
    ?>
@endif	
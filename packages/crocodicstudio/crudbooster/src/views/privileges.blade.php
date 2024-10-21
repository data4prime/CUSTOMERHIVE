@extends('crudbooster::admin_template')

@section('content')
<div style="width:750px;margin:0 auto ">


  @if(CRUDBooster::getCurrentMethod() != 'getProfile')
  <p><a
      href='{{CRUDBooster::mainpath()}}'>{{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}</a>
  </p>
  @endif



  <!-- Box -->
  <div class="box box-primary">
    <div class="box-header mb-3 with-border">
      <h3 class="box-title">{{ $page_title }}</h3>
      <div class="box-tools">

      </div>
    </div>
    <form method='post'
      action='{{ (@$row->id)?route("PrivilegesControllerPostEditSave")."/$row->id":route("PrivilegesControllerPostAddSave") }}'>
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <div class="box-body">
        <!-- <div class="alert alert-info">
                        <strong>Note:</strong> To show the menu you have to create a menu at Menu Management
                    </div> -->
        <div class='mb-3 row'>
          <label>{{trans('crudbooster.privileges_name')}}</label>
          <input type='text' class='form-control' name='name' required value='{{ @$row->name }}' />
          <div class="text-danger">{{ $errors->first('name') }}</div>
        </div>
        <div class='mb-3 row'>
          <label>{{trans('crudbooster.set_privilege')}}</label>
          <div id='set_as_superadmin' class='radio'>
            <label>
              <input {{ (@$row->is_superadmin==1) ? 'checked' : '' }} type='radio' name='superprivilege' value='1'/>
              {{trans('crudbooster.superadmin')}}
            </label> &nbsp;&nbsp;
            <label>
              <input {{ (@$row->is_tenantadmin==1) ? 'checked' : '' }} type='radio' name='superprivilege' value='2'/>
              {{trans('crudbooster.tenantadmin')}}
            </label> &nbsp;&nbsp;
            <label>
              <input {{ (@$row->is_superadmin!=1 AND @$row->is_tenantadmin!=1) ? 'checked' : '' }} type='radio'
              name='superprivilege' value='0'/> {{trans('crudbooster.none')}}
            </label>
          </div>
          <div class="text-danger">{{ $errors->first('is_superadmin') }}</div>
        </div>

        <div class='mb-3 row'>
          <label>{{trans('crudbooster.chose_theme_color')}}</label>

          <select name='theme_color' class='form-control' required>
            <option value=''>{{trans('crudbooster.chose_theme_color_select')}}</option>
            <?php
                            $skins = array(
                                'skin-blue',
                                'skin-blue-light',
                                'skin-yellow',
                                'skin-yellow-light',
                                'skin-green',
                                'skin-green-light',
                                'skin-purple',
                                'skin-purple-light',
                                'skin-red',
                                'skin-red-light',
                                'skin-black',
                                'skin-black-light'
                            );
                            foreach($skins as $skin):
                            ?>
            <option <?php echo (@$row->theme_color == $skin) ? "selected" : ""?> value='<?php echo $skin ?>'><?php echo ucwords(str_replace('-', ' ', $skin))?>
            </option>
            <?php endforeach;?>
          </select>
          <div class="text-danger">{{ $errors->first('theme_color') }}</div>
          @push('bottom')
          <script type="text/javascript">
            $(function () {
              $("select[name=theme_color]").change(function () {
                var n = $(this).val();
                $("body").attr("class", n);
              })

              $('#set_as_superadmin input').click(function () {
                var n = $(this).val();
                if (n == '1') {
                  $('#privileges_configuration').hide();
                } else {
                  $('#privileges_configuration').show();
                }
              })

              $('#set_as_superadmin input:checked').trigger('click');
            })
          </script>
          @endpush
        </div>

        <div id='privileges_configuration' class='mb-3 row'>
          <label>{{trans('crudbooster.privileges_configuration')}}</label>
          @push('bottom')
          <script>
            $(function () {
              $("#is_visible").click(function () {
                var is_ch = $(this).prop('checked');
                // console.log('is checked create ' + is_ch);
                $(".is_visible").prop("checked", is_ch);
                // console.log('Create all');
              })
              $("#is_create").click(function () {
                var is_ch = $(this).prop('checked');
                // console.log('is checked create ' + is_ch);
                $(".is_create").prop("checked", is_ch);
                // console.log('Create all');
              })
              $("#is_read").click(function () {
                var is_ch = $(this).is(':checked');
                $(".is_read").prop("checked", is_ch);
              })
              $("#is_edit").click(function () {
                var is_ch = $(this).is(':checked');
                $(".is_edit").prop("checked", is_ch);
              })
              $("#is_delete").click(function () {
                var is_ch = $(this).is(':checked');
                $(".is_delete").prop("checked", is_ch);
              })
              $(".select_horizontal").click(function () {
                var p = $(this).parents('tr');
                var is_ch = $(this).is(':checked');
                p.find("input[type=checkbox]").prop("checked", is_ch);
              })
            })
          </script>
          @endpush
          <table class='table table-striped table-hover table-bordered'>
            <thead>
              <tr class='active'>
                <th width='3%'>{{trans('crudbooster.privileges_module_list_no')}}</th>
                <th width='60%'>{{trans('crudbooster.privileges_module_list_mod_names')}}</th>
                <th>&nbsp;</th>
                <th>{{trans('crudbooster.privileges_module_list_view')}}</th>
                <th>{{trans('crudbooster.privileges_module_list_create')}}</th>
                <th>{{trans('crudbooster.privileges_module_list_read')}}</th>
                <th>{{trans('crudbooster.privileges_module_list_update')}}</th>
                <th>{{trans('crudbooster.privileges_module_list_delete')}}</th>
              </tr>
              <?php
                              /**
                              * Check all vertically initially checked if enabled on all modules
                              */
                              //list of privilege modes
                              $modes = ['is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete'];
                              //set all as initially checked
                              foreach ($modes as $mode) {
                                if(isset($roles) && isset($roles->{$mode})) {
                                  if ($roles->{$mode} != 1) {
                                    $vertical_checked[$mode] = 'checked';
                                  }
                                  
                                }
                              }
                              //loop through modules
                              foreach ($moduls as $module) {

                                $roles = DB::table('cms_privileges_roles')
                                              ->where('id_cms_moduls', $module->id)
                                              ->where('id_cms_privileges', isset($row->id) ? $row->id : 0)
                                              ->toSql();


                                //check if each mode is disabled
                                foreach ($modes as $mode) {
                                  //if disabled..
                                  if( isset($roles) && isset($roles->{$mode}) && $roles->{$mode} != 1) {
                                    //..then uncheck the Check all vertical checkbox
                                    $vertical_checked[$mode] = '';
                                  }
                                }
                              }

                              if(!isset($vertical_checked)) {
                                $vertical_checked = [];
                                foreach ($modes as $mode) {
                                  $vertical_checked[$mode] = '';
                                }
                                
                                
                              }
                            ?>
              <tr>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>

                <td class='info' align="center">
                  <input {{isset($vertical_checked['is_visible']) ? $vertical_checked['is_visible'] : '' }}
                    title='Check all vertical' type='checkbox' id='is_visible' />
                </td>
                <td class='info' align="center">
                  <input {{isset($vertical_checked['is_create']) ? $vertical_checked['is_create'] : '' }}
                    title='Check all vertical' type='checkbox' id='is_create' />
                </td>
                <td class='info' align="center">
                  <input {{isset($vertical_checked['is_read']) ? $vertical_checked['is_read'] : '' }}
                    title='Check all vertical' type='checkbox' id='is_read' />
                </td>
                <td class='info' align="center">
                  <input {{isset($vertical_checked['is_edit']) ? $vertical_checked['is_edit'] : '' }}
                    title='Check all vertical' type='checkbox' id='is_edit' />
                </td>
                <td class='info' align="center">
                  <input {{isset($vertical_checked['is_delete']) ? $vertical_checked['is_delete'] : '' }}
                    title='Check all vertical' type='checkbox' id='is_delete' />
                </td>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1;



?>
              @foreach($moduls as $modul)
              <?php



                                $roles = DB::table('cms_privileges_roles')
                                              ->where('id_cms_moduls', $modul->id)
                                              ->where('id_cms_privileges', isset($row->id) ? $row->id : 0)
                                              ->first();


                                ?>
              <tr>
                <td>
                  <?php echo $no++;?>
                </td>
                <td>{{$modul->name}}</td>
                <td class='info' align="center">
                  <input type='checkbox' title='Check All Horizontal' <?php ( (isset($roles->is_visible) &&
                  isset($roles->is_create) && isset($roles->is_read) && isset($roles->is_edit) &&
                  isset($roles->is_delet)) && ($roles->is_visible && $roles->is_create &&
                  $roles->is_read && $roles->is_edit && $roles->is_delete)) ? "checked" : ""?>
                  class='select_horizontal'/>
                </td>
                <td class='active' align="center">
                  <input type='checkbox' class='is_visible' name='privileges[<?php echo $modul->id ?>][is_visible]'
                    <?php echo @$roles->is_visible ? "checked" : ""?> value='1'/>
                </td>
                <td class='warning' align="center">
                  <input type='checkbox' class='is_create' name='privileges[<?php echo $modul->id ?>][is_create]'
                    <?php echo @$roles->is_create ? "checked" : ""?> value='1'/>
                </td>
                <td class='info' align="center">
                  <input type='checkbox' class='is_read' name='privileges[<?php echo $modul->id ?>][is_read]' <?php echo @$roles->is_read
                  ? "checked" : ""?> value='1'/>
                </td>
                <td class='success' align="center">
                  <input type='checkbox' class='is_edit' name='privileges[<?php echo $modul->id ?>][is_edit]' <?php echo @$roles->is_edit
                  ? "checked" : ""?> value='1'/>
                </td>
                <td class='danger' align="center">
                  <input type='checkbox' class='is_delete' name='privileges[<?php echo $modul->id ?>][is_delete]'
                    <?php echo @$roles->is_delete ? "checked" : ""?> value='1'/>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>

        </div>

      </div><!-- /.box-body -->
      <div class="box-footer" align="right">
        <button type='button' onclick="location.href='{{CRUDBooster::mainpath()}}'"
          class='btn btn-default'>{{trans("crudbooster.button_cancel")}}</button>
        <button type='submit' class='btn btn-primary'><i class='fa fa-save'></i>
          {{trans("crudbooster.button_save")}}</button>
      </div><!-- /.box-footer-->
  </div><!-- /.box -->

</div><!-- /.row -->
@endsection
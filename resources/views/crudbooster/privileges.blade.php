@extends('crudbooster::admin_template')

@section('content')
<?php
  // Una sola query per tutti i permessi del ruolo (prima una per modulo, due
  // volte: matrice e "check all"). Chiave = id del modulo.
  $rolesByModule = DB::table('cms_privileges_roles')
      ->where('id_cms_privileges', isset($row->id) ? $row->id : 0)
      ->get()
      ->keyBy('id_cms_moduls');

  // Colore del ruolo: sei accenti (come li riduce admin_template). I valori
  // salvati restano le vecchie "skin-*": una skin "-light" si ripresenta
  // selezionata sul suo accento, senza migrazione dati.
  $accents = [
      'skin-blue'   => 'var(--ch-blue)',
      'skin-yellow' => 'var(--ch-warning)',
      'skin-green'  => 'var(--ch-success)',
      'skin-purple' => 'var(--ch-violet)',
      'skin-red'    => 'var(--ch-danger)',
      'skin-black'  => 'var(--ch-text)',
  ];
  $currentAccent = preg_replace('/-light$/', '', (string) @$row->theme_color);
  $modes = ['is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete'];
  $readonly = !empty($readonly);
?>
<div>

  {{-- Intestazione in stile mockup (FlatForm::share in PrivilegesController, intervento 232) --}}
  @if(!empty($flat_form_header))
  @include($flat_form_header)
  @endif

  <style>
    .role-accents { display: flex; flex-wrap: wrap; gap: var(--ch-space-2); padding-top: 3px; }
    .role-accents label { cursor: pointer; margin: 0; }
    .role-accents input { position: absolute; opacity: 0; pointer-events: none; }
    .role-accents span { display: block; width: 30px; height: 30px; border-radius: var(--ch-radius-pill); border: 2px solid var(--ch-surface); box-shadow: 0 0 0 1px var(--ch-border-strong); }
    .role-accents input:checked + span { box-shadow: 0 0 0 2px var(--ch-text); }
    .role-accents input:focus-visible + span { box-shadow: 0 0 0 3px var(--ch-accent); }
    .perm-scroll { max-height: 60vh; overflow: auto; border: 1px solid var(--ch-border); border-radius: var(--ch-radius-md); }
    .perm-scroll table { margin: 0; }
    .perm-scroll thead th { position: sticky; top: 0; z-index: 2; background: var(--ch-surface); }
    .perm-scroll thead tr.perm-colall td { position: sticky; top: 38px; z-index: 2; background: var(--ch-surface); }
    .perm-scroll td.perm-c, .perm-scroll th.perm-c { text-align: center; }
  </style>

  <div class="flat-form">
      <form method='post'
        action='{{ (@$row->id)?route("PrivilegesControllerPostEditSave")."/$row->id":route("PrivilegesControllerPostAddSave") }}'>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        @if($readonly)
        {{-- Dettaglio: valori come testo, non campi disabilitati --}}
        <div class="card mb-3"><div class="card-body">
          <div class="mb-3 row">
            <div class="col-form-label col-sm-2 fw-semibold">{{ trans('crudbooster.privileges_name') }}</div>
            <div class="col-sm-10 pt-2">{{ $row->name }}</div>
          </div>
          <div class="mb-3 row">
            <div class="col-form-label col-sm-2 fw-semibold">{{ trans('crudbooster.set_privilege') }}</div>
            <div class="col-sm-10 pt-2">
              @if($row->is_superadmin == 1)
              <span class="ch-pill ch-pill-ok">{{ trans('crudbooster.superadmin') }}</span>
              @elseif($row->is_tenantadmin == 1)
              <span class="ch-pill ch-pill-warn">{{ trans('crudbooster.tenantadmin') }}</span>
              @else
              <span class="ch-pill ch-pill-gray">{{ trans('crudbooster.none') }}</span>
              @endif
              <div class="small text-secondary mt-1">{{ trans('crudbooster.adm_role_level_hint') }}</div>
            </div>
          </div>
          <div class="mb-3 row">
            <div class="col-form-label col-sm-2 fw-semibold">{{ trans('crudbooster.chose_theme_color') }}</div>
            <div class="col-sm-10 pt-2">
              <span style="display:inline-block;width:14px;height:14px;border-radius:50%;margin-right:8px;vertical-align:middle;background: {{ $accents[$currentAccent] ?? 'var(--ch-border-strong)' }}"></span>{{ ucwords(preg_replace('/^skin-/', '', $currentAccent)) }}
            </div>
          </div>
        </div></div>

        @if($row->is_superadmin != 1)
        <?php $enabledCount = $moduls->filter(function ($m) use ($rolesByModule) { return @$rolesByModule->get($m->id)->is_visible; })->count(); ?>
        <style>
          .perm-ro th { font-size: var(--ch-font-size-sm); font-weight: 600; color: var(--ch-text-secondary); text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
          .perm-ro td { vertical-align: middle; padding-top: 10px; padding-bottom: 10px; }
          .perm-ro tr.perm-off td.perm-name { color: var(--ch-text-secondary); }
          .perm-ro .perm-yes { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: color-mix(in srgb, var(--ch-success) 14%, transparent); color: var(--ch-success); font-size: 14px; }
          .perm-ro .perm-no { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--ch-border-strong); opacity: .6; }
        </style>
        <div class="card mb-3"><div class="card-header d-flex align-items-center justify-content-between"><strong>{{ trans('crudbooster.privileges_configuration') }}</strong><span class="badge text-bg-secondary" title="{{ trans('crudbooster.adm_modules_enabled') }}">{{ $enabledCount }} / {{ $moduls->count() }}</span></div><div class="card-body p-0">
          <div class="perm-scroll" style="border:0;border-radius:0">
            <table class="table table-hover perm-ro mb-0">
              <thead>
                <tr>
                  <th width="3%">{{ trans('crudbooster.privileges_module_list_no') }}</th>
                  <th>{{ trans('crudbooster.privileges_module_list_mod_names') }}</th>
                  <th class="perm-c">{{ trans('crudbooster.privileges_module_list_view') }}</th>
                  <th class="perm-c">{{ trans('crudbooster.privileges_module_list_create') }}</th>
                  <th class="perm-c">{{ trans('crudbooster.privileges_module_list_read') }}</th>
                  <th class="perm-c">{{ trans('crudbooster.privileges_module_list_update') }}</th>
                  <th class="perm-c">{{ trans('crudbooster.privileges_module_list_delete') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($moduls as $modul)
                <?php $roles = $rolesByModule->get($modul->id); ?>
                <tr class="{{ @$roles->is_visible ? '' : 'perm-off' }}">
                  <td class="text-secondary">{{ $loop->iteration }}</td>
                  <td class="perm-name fw-semibold">{{ $modul->name }}</td>
                  @foreach($modes as $mode)
                  <td class="perm-c">
                    @if(@$roles->{$mode})
                    <span class="perm-yes"><i class="bi bi-check-lg"></i></span>
                    @else
                    <span class="perm-no"></span>
                    @endif
                  </td>
                  @endforeach
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div></div>
        @endif

        <div class="mb-3">
          <a href='{{ g("return_url") ?: CRUDBooster::mainpath() }}' class='btn btn-secondary'>
            <i class='bi bi-chevron-left'></i> {{trans("crudbooster.button_back")}}
          </a>
          @if(CRUDBooster::isUpdate())
          <a href='{{ CRUDBooster::mainpath("edit/".$row->id) }}' class='btn btn-primary'>
            <i class='bi bi-pencil'></i> {{ trans('crudbooster.adm_role_edit') }}
          </a>
          @endif
        </div>
        @else
        <div class="card mb-3"><div class="card-body" id="parent-form-area">
          <div class='mb-3 row {{ $errors->first("name")?"has-error":"" }}'>
            <label class='col-form-label col-sm-2'>
              {{trans('crudbooster.privileges_name')}}
              <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}">*</span>
            </label>
            <div class="col-sm-10">
              <input type='text' class='form-control' name='name' required value='{{ @$row->name }}' />
              <div class="text-danger">{!! $errors->first('name')?"<i class='bi bi-info-circle-fill'></i> ".$errors->first('name'):"" !!}</div>
            </div>
          </div>
          <div class='mb-3 row {{ $errors->first("is_superadmin")?"has-error":"" }}'>
            <label class='col-form-label col-sm-2'>
              {{trans('crudbooster.set_privilege')}}
              <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}">*</span>
            </label>
            <div class="col-sm-10">
              <div id='set_as_superadmin' style="padding-top:3px">
                @include('crudbooster::partials.ch_radio', [
                    'name' => 'superprivilege',
                    'disabled' => false,
                    'rd_options' => [
                        ['value' => '1', 'label' => trans('crudbooster.superadmin'), 'checked' => (@$row->is_superadmin == 1)],
                        ['value' => '2', 'label' => trans('crudbooster.tenantadmin'), 'checked' => (@$row->is_tenantadmin == 1)],
                        ['value' => '0', 'label' => trans('crudbooster.none'), 'checked' => (@$row->is_superadmin != 1 AND @$row->is_tenantadmin != 1)],
                    ],
                ])
              </div>
              <div class="hint small text-secondary mt-1">{{ trans('crudbooster.adm_role_level_hint') }}</div>
              <div class="text-danger">{!! $errors->first('is_superadmin')?"<i class='bi bi-info-circle-fill'></i> ".$errors->first('is_superadmin'):"" !!}</div>
            </div>
          </div>

          <div class='mb-3 row {{ $errors->first("theme_color")?"has-error":"" }}'>
            <label class='col-form-label col-sm-2'>
              {{trans('crudbooster.chose_theme_color')}}
              <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}">*</span>
            </label>
            <div class="col-sm-10">
              <div class="role-accents" role="radiogroup">
                @foreach($accents as $skin => $css)
                <label title="{{ ucwords(str_replace('skin-', '', $skin)) }}">
                  <input type="radio" name="theme_color" value="{{ $skin }}" required {{ $currentAccent === $skin ? 'checked' : '' }}>
                  <span style="background: {{ $css }}"></span>
                </label>
                @endforeach
              </div>
              <div class="text-danger">{!! $errors->first('theme_color')?"<i class='bi bi-info-circle-fill'></i> ".$errors->first('theme_color'):"" !!}</div>
              @push('bottom')
              <script type="text/javascript">
                $(function () {
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
          </div>

          </div></div>

          <div class="card mb-3" id="privileges_configuration"><div class="card-header"><strong>{{trans('crudbooster.privileges_configuration')}}</strong></div><div class="card-body">
            @push('bottom')
            <script>
              $(function () {
                var modes = ['is_visible', 'is_create', 'is_read', 'is_edit', 'is_delete'];
                var $rows = $('#perm-table tbody tr.perm-row');

                function visibleRows() { return $rows.filter(':visible'); }

                // Stato delle caselle "tutta la colonna" e "tutta la riga":
                // spuntata se tutte le righe visibili/le caselle della riga lo sono,
                // "parziale" se solo alcune.
                function refresh() {
                  var vis = visibleRows();
                  modes.forEach(function (m) {
                    var all = vis.find('.' + m);
                    var on = all.filter(':checked').length;
                    var el = $('#col_' + m)[0];
                    el.checked = all.length > 0 && on === all.length;
                    el.indeterminate = on > 0 && on < all.length;
                  });
                  $rows.each(function () {
                    var cbs = $(this).find('input.perm-cb');
                    var on = cbs.filter(':checked').length;
                    var h = $(this).find('.select_horizontal')[0];
                    h.checked = on === cbs.length;
                    h.indeterminate = on > 0 && on < cbs.length;
                  });
                  var enabled = $rows.filter(function () { return $(this).find('.is_visible').is(':checked'); }).length;
                  $('#perm-count').text(enabled + ' / ' + $rows.length);
                }

                $('.col-all').on('click', function () {
                  var m = $(this).data('mode'), on = this.checked;
                  visibleRows().find('.' + m).prop('checked', on);
                  refresh();
                });
                $('.select_horizontal').on('click', function () {
                  $(this).closest('tr').find('input.perm-cb').prop('checked', this.checked);
                  refresh();
                });
                $('.perm-cb').on('click', refresh);

                $('#perm-search').on('input', function () {
                  var q = $(this).val().toLowerCase().trim();
                  $rows.each(function () {
                    $(this).toggle(q === '' || $(this).data('name').indexOf(q) !== -1);
                  });
                  refresh();
                });

                refresh();
              })
            </script>
            @endpush
            <div>
              <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <input type="search" id="perm-search" class="form-control" style="max-width:280px"
                  placeholder="{{ trans('crudbooster.adm_search_module') }}" autocomplete="off">
                <span class="ms-auto badge text-bg-secondary" title="{{ trans('crudbooster.adm_modules_enabled') }}"><span id="perm-count">0</span></span>
              </div>
              <div class="perm-scroll">
                <table class='table table-hover' id="perm-table">
                  <thead>
                    <tr>
                      <th width='3%'>{{trans('crudbooster.privileges_module_list_no')}}</th>
                      <th width='60%'>{{trans('crudbooster.privileges_module_list_mod_names')}}</th>
                      <th class="perm-c">{{ trans('crudbooster.adm_all') }}</th>
                      <th class="perm-c">{{trans('crudbooster.privileges_module_list_view')}}</th>
                      <th class="perm-c">{{trans('crudbooster.privileges_module_list_create')}}</th>
                      <th class="perm-c">{{trans('crudbooster.privileges_module_list_read')}}</th>
                      <th class="perm-c">{{trans('crudbooster.privileges_module_list_update')}}</th>
                      <th class="perm-c">{{trans('crudbooster.privileges_module_list_delete')}}</th>
                    </tr>
                    <tr class="perm-colall">
                      <td colspan="3" class="text-secondary small">{{ trans('crudbooster.adm_select_column') }}</td>
                      @foreach($modes as $mode)
                      <td class="perm-c"><input type='checkbox' class="col-all" data-mode="{{ $mode }}" id="col_{{ $mode }}"
                          title="{{ trans('crudbooster.adm_select_column') }}" /></td>
                      @endforeach
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no = 1; ?>
                    @foreach($moduls as $modul)
                    <?php $roles = $rolesByModule->get($modul->id); ?>
                    <tr class="perm-row" data-name="{{ strtolower($modul->name) }}">
                      <td>{{ $no++ }}</td>
                      <td>{{ $modul->name }}</td>
                      <td class="perm-c"><input type='checkbox' class='select_horizontal' title="{{ trans('crudbooster.adm_select_row') }}" /></td>
                      @foreach($modes as $mode)
                      <td class="perm-c">
                        <input type='checkbox' class='perm-cb {{ $mode }}' name='privileges[{{ $modul->id }}][{{ $mode }}]'
                          {{ @$roles->{$mode} ? 'checked' : '' }} value='1' />
                      </td>
                      @endforeach
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div><!-- /card permessi -->
        <div class="box-footer" style="background: var(--ch-bg)">
          <div class="mb-3 row">
            <label class="col-form-label col-sm-2"></label>
            <div class="col-sm-10">
              @if(g('return_url'))
              <a href='{{g("return_url")}}' class='btn btn-secondary'>
                <i class='bi bi-chevron-left'></i> {{trans("crudbooster.button_back")}}
              </a>
              @else
              <a href='{{CRUDBooster::mainpath()}}' class='btn btn-secondary'>
                <i class='bi bi-chevron-left'></i> {{trans("crudbooster.button_back")}}
              </a>
              @endif
              <input type="submit" name="submit" value='{{trans("crudbooster.button_save")}}' class='btn btn-success'>
            </div>
          </div>
        </div><!-- /.box-footer-->
        @endif
      </form>
  </div>

</div><!--END AUTO MARGIN-->
@endsection

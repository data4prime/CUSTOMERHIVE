@extends('crudbooster::admin_template')

@section('content')
<div>

  @if(g('return_url'))
  <p>
    <a title='Return' href='{{g("return_url")}}'>
      <i class='fa fa-chevron-circle-left '></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>
  @else
  <p>
    <a title='Main Module' href='{{CRUDBooster::mainpath()}}'>
      <i class='fa fa-chevron-circle-left '></i>&nbsp;
      {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
    </a>
  </p>
  @endif

  <div class="card card-default">
    <div class="card-header">
      <strong>
        <i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> {!! $page_title !!}
      </strong>
    </div>
    <div class="card-body" style="padding:20px 0px 0px 0px">
      <form method='post' id="dashboard-layout-form" action='{{ $action }}'>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div class="box-body" id="parent-form-area">
          <div class='mb-3 row {{ $errors->first("layoutname")?"has-error":"" }}'>
            <label class='col-form-label col-sm-2'>
              Layout Name
              <span class='text-danger' title="{!! trans('crudbooster.this_field_is_required') !!}">*</span>
            </label>
            <div class="col-sm-10">
              <input type='text' class='form-control' name='layoutname' required value='{{ old("layoutname", @$row->layoutname) }}' />
              <div class="text-danger">{!! $errors->first('layoutname')?"<i class='fa fa-info-circle'></i> ".$errors->first('layoutname'):"" !!}</div>
            </div>
          </div>

          <div class='mb-3 row'>
            <label class='col-form-label col-sm-2'>Code Layout</label>
            <div class="col-sm-10">

              <input type="hidden" name="layout_model" id="layout_model" value="" />

              <p class="help-block">
                Ogni riga è una fascia orizzontale della dashboard, divisa in colonne.
                La larghezza di ogni colonna va da 1 a 12 (una riga piena = colonne che sommano a 12).
              </p>
              <div id="builder-rows"></div>
              <button type="button" class="btn btn-default btn-sm" id="add-row-btn">
                <i class="fa fa-plus"></i> Aggiungi riga
              </button>

            </div>
          </div>
        </div>

        <div class="box-footer" style="background: #F5F5F5">
          <div class="mb-3 row">
            <label class="col-form-label col-sm-2"></label>
            <div class="col-sm-10">
              @if(g('return_url'))
              <a href='{{g("return_url")}}' class='btn btn-default'>
                <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
              </a>
              @else
              <a href='{{CRUDBooster::mainpath()}}' class='btn btn-default'>
                <i class='fa fa-chevron-circle-left'></i> {{trans("crudbooster.button_back")}}
              </a>
              @endif
              <input type="submit" name="submit" value='{{trans("crudbooster.button_save")}}' class='btn btn-success'>
            </div>
          </div>
        </div>

      </form>
    </div>
  </div>

</div>

<style>
  .layout-builder-row {
    display: flex;
    align-items: stretch;
    flex-wrap: wrap;
    gap: 8px;
    border: 1px dashed #ccc;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 10px;
    background: #fafafa;
  }
  .layout-builder-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    background: #fff;
    border: 1px solid #d0d7de;
    border-radius: 4px;
    padding: 8px 4px;
    min-height: 70px;
    box-sizing: border-box;
  }
  .layout-builder-col .area-label {
    font-size: 11px;
    color: #666;
  }
  .layout-builder-col select {
    width: 60px;
  }
  .layout-builder-row-toolbar {
    display: flex;
    align-items: center;
    gap: 6px;
    width: 100%;
    margin-bottom: 6px;
  }
  .layout-builder-row-total {
    font-size: 12px;
    color: #666;
  }
  .layout-builder-row-total.warn {
    color: #b94a48;
    font-weight: bold;
  }
</style>
@endsection

@push('bottom')
<script type="text/javascript">
  $(function () {
    var rowsState = @json($rows_model && count($rows_model) ? $rows_model : [[12]]);

    var PRESETS = {
      '1 colonna': [12],
      '2 uguali': [6, 6],
      '3 uguali': [4, 4, 4],
      '4 uguali': [3, 3, 3, 3],
      '1/3 + 2/3': [4, 8],
      '2/3 + 1/3': [8, 4]
    };

    function widthOptions(selected) {
      var html = '';
      for (var w = 1; w <= 12; w++) {
        html += "<option value='" + w + "'" + (w === selected ? ' selected' : '') + '>' + w + '</option>';
      }
      return html;
    }

    function render() {
      var $container = $('#builder-rows');
      $container.empty();

      var areaCounter = 0;

      rowsState.forEach(function (row, rowIndex) {
        var total = row.reduce(function (a, b) { return a + b; }, 0);

        var $row = $("<div class='layout-builder-row'></div>");

        var $toolbar = $("<div class='layout-builder-row-toolbar'></div>");
        var $total = $("<span class='layout-builder-row-total" + (total !== 12 ? ' warn' : '') + "'>Totale: " + total + "/12</span>");
        $toolbar.append($total);

        Object.keys(PRESETS).forEach(function (label) {
          var $btn = $("<button type='button' class='btn btn-default btn-xs'>" + label + '</button>');
          $btn.on('click', function () {
            rowsState[rowIndex] = PRESETS[label].slice();
            render();
          });
          $toolbar.append($btn);
        });

        var $addCol = $("<button type='button' class='btn btn-default btn-xs'><i class='fa fa-plus'></i> Colonna</button>");
        $addCol.on('click', function () {
          rowsState[rowIndex].push(4);
          render();
        });
        $toolbar.append($addCol);

        var $removeRow = $("<button type='button' class='btn btn-danger btn-xs'><i class='fa fa-trash'></i> Rimuovi riga</button>");
        $removeRow.on('click', function () {
          rowsState.splice(rowIndex, 1);
          render();
        });
        $toolbar.append($removeRow);

        $row.append($toolbar);

        row.forEach(function (width, colIndex) {
          areaCounter++;
          var flexBasis = 'calc(' + (width / 12 * 100) + '% - 8px)';

          var $col = $("<div class='layout-builder-col'></div>").css('flex', '0 0 ' + flexBasis);
          $col.append("<span class='area-label'>Area " + areaCounter + '</span>');

          var $select = $("<select class='form-control form-control-sm'>" + widthOptions(width) + '</select>');
          $select.on('change', function () {
            rowsState[rowIndex][colIndex] = parseInt($(this).val(), 10);
            render();
          });
          $col.append($select);

          var $removeCol = $("<button type='button' class='btn btn-default btn-xs'>&times;</button>");
          $removeCol.on('click', function () {
            rowsState[rowIndex].splice(colIndex, 1);
            if (!rowsState[rowIndex].length) {
              rowsState.splice(rowIndex, 1);
            }
            render();
          });
          $col.append($removeCol);

          $row.append($col);
        });

        $container.append($row);
      });

      $('#layout_model').val(JSON.stringify(rowsState));
    }

    $('#add-row-btn').on('click', function () {
      rowsState.push([12]);
      render();
    });

    render();
  });
</script>
@endpush

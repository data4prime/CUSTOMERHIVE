<tr>
    <td colspan='2'>
        {{-- Il file DEVE iniziare con <tr>: form_detail_row lo controlla per non avvolgerlo in un'altra riga con label --}}
        @if(!empty($form['view_detail']))
        @include($form['view_detail'])
        @else
        {{-- Stessa grafica della griglia del form (ch-grid-*), in sola lettura (intervento 232) --}}
        <div class="ch-grid-card">
            <div class="ch-grid-hd"><i class='bi bi-list'></i> {{$form['label']}}</div>
            <div class="table-responsive">
                <table id='table-{{$name}}' class='ch-grid ch-grid-ro'>
                    <thead>
                    <tr>
                        @foreach($form['columns'] as $col)
                            <th>{{$col['label']}}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    $data_child = DB::table($form['table'])->where($form['foreign_key'], $id);
                    foreach ($form['columns'] as $i => $c) {
                        $data_child->addselect($form['table'].'.'.$c['name']);

                        if ($c['type'] == 'datamodal') {
                            $datamodal_title = explode(',', $c['datamodal_columns'])[0];
                            $datamodal_table = $c['datamodal_table'];
                            $data_child->join($c['datamodal_table'], $c['datamodal_table'].'.id', '=', $c['name']);
                            $data_child->addselect($c['datamodal_table'].'.'.$datamodal_title.' as '.$datamodal_table.'_'.$datamodal_title);
                        } elseif ($c['type'] == 'select') {
                            if ($c['datatable']) {
                                $join_table = explode(',', $c['datatable'])[0];
                                $join_field = explode(',', $c['datatable'])[1];
                                $data_child->join($join_table, $join_table.'.id', '=', $c['name']);
                                $data_child->addselect($join_table.'.'.$join_field.' as '.$join_table.'_'.$join_field);
                            }
                        }
                    }

                    $data_child = $data_child->orderby($form['table'].'.id', 'desc')->get();
                    foreach($data_child as $d):
                    ?>
                    <tr class="ch-row">
                        @foreach($form['columns'] as $col)
                            <td class="{{$col['name']}}">
                                <?php
                                if ($col['type'] == 'select') {
                                    if ($col['datatable']) {
                                        $join_table = explode(',', $col['datatable'])[0];
                                        $join_field = explode(',', $col['datatable'])[1];
                                        echo e($d->{$join_table.'_'.$join_field});
                                    }
                                    if ($col['dataenum']) {
                                        echo e($d->{$col['name']});
                                    }
                                } elseif ($col['type'] == 'datamodal') {
                                    $datamodal_title = explode(',', $col['datamodal_columns'])[0];
                                    $datamodal_table = $col['datamodal_table'];
                                    echo e($d->{$datamodal_table.'_'.$datamodal_title});
                                } elseif ($col['type'] == 'upload') {
                                    $filename = basename($d->{$col['name']});
                                    if ($col['upload_type'] == 'image') {
                                        echo "<a href='".e(asset($d->{$col['name']}))."' class='fancybox'><img data-label='".e($filename)."' src='".e(asset($d->{$col['name']}))."' width='50px' height='50px'/></a>";
                                    } else {
                                        echo "<a data-label='".e($filename)."' href='".e(asset($d->{$col['name']}))."'>".e($filename)."</a>";
                                    }
                                } else {
                                    echo e($d->{$col['name']});
                                }
                                ?>
                            </td>
                        @endforeach
                    </tr>
                    <?php endforeach;?>

                    @if(count($data_child)==0)
                        <tr class="trNull">
                            <td colspan="{{count($form['columns'])}}" class="ch-grid-empty">{{trans('crudbooster.table_data_not_found')}}</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </td>
</tr>

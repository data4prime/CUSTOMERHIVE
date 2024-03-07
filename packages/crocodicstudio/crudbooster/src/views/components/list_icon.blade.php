<select id='list-icon' class="form-control" name="icon" style="font-family: 'FontAwesome', Helvetica;">
    <option value="">** Select an Icon</option>
    @foreach($fontawesome as $font)
    <option value='fa fa-{{$font}}' data-icon="{{ isset($row) && isset($row->icon) ? $row->icon : ''}}" {{ isset($row)
        && isset($row->icon) && ($row->icon == "fa fa-".$font)?"selected":"" }} data-label='{{$font}}'>{{$font}}
    </option>
    @endforeach
</select>
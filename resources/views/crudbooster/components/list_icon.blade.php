<select id='list-icon' class="form-control" name="icon" style="font-family: 'FontAwesome', Helvetica;">
    <option value="">** Select an Icon</option>
    @foreach($fontawesome as $font)
    <option value='fa fa-{{$font}}' data-icon="{{ isset($row) && isset($row->icon) ? $row->icon : ''}}" {{ isset($row)
        && isset($row->icon) && ($row->icon == "fa fa-".$font)?"selected":"" }} data-label='{{$font}}'>{{$font}}
    </option>
    @endforeach
</select>

@push('bottom')
<script type="text/javascript">
    $(function () {
        function formatIcon(icon) {
            var originalOption = icon.element;
            if (!originalOption || !$(originalOption).val()) {
                return icon.text;
            }
            var iconClass = $(originalOption).val();
            var label = $(originalOption).text();
            return $('<span><i class="' + iconClass + '" style="width:18px;display:inline-block;text-align:center;margin-right:6px;"></i>' + label + '</span>');
        }

        $('#list-icon').select2({
            width: '100%',
            templateResult: formatIcon,
            templateSelection: formatIcon
        });
    })
</script>
@endpush
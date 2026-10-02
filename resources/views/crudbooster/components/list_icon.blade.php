@php
    // Le icone gia' salvate come "fa fa-*" vengono tradotte nel nome
    // equivalente di Bootstrap Icons, cosi' risultano preselezionate.
    $currentIcon = isset($row) && isset($row->icon) ? \App\Helpers\IconMap::toBi($row->icon) : '';
@endphp
<select id='list-icon' class="form-control" name="icon">
    <option value="">{{ trans('crudbooster.icon_select') }}</option>
    @foreach($fontawesome as $font)
    <option value='bi bi-{{$font}}' data-icon="{{ $currentIcon }}" {{ $currentIcon == "bi bi-".$font ? "selected" : "" }} data-label='{{$font}}'>{{$font}}
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

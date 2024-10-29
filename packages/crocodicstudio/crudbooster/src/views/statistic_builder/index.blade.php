@push('head')
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css">
<link rel="stylesheet" href="{{ asset('vendor/crudbooster/assets/adminlte/plugins/datatables/dataTables.bootstrap.css') }}">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<style>
    .statistic-row > div { /*overflow: auto;*/ }
    .control-sidebar ul { padding: 0; margin: 0; list-style-type: none; }
    .control-sidebar ul li { text-align: center; padding: 10px; border-bottom: 1px solid #555; }
    .control-sidebar ul li:hover { background: #555; }
    .control-sidebar ul li .title { color: #fff; }
    .control-sidebar ul li img { width: 100%; }
    ::-webkit-scrollbar { width: 5px; height: 5px; }

    .sort-highlight { border: 3px dashed #cccccc; }
    .layout-grid { border: 1px dashed #cccccc; min-height: 150px; }
    .border-box { position: relative; }
    .border-box .action { /* existing styles */ }
    .connectedSortable { position: relative; }
    .area-loading { /* existing styles */ }
    .area-loading i { /* existing styles */ }
</style>
@endpush

@push('bottom')
<script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
<script src="{{ asset('vendor/crudbooster/assets/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/crudbooster/assets/adminlte/plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>

<script type="text/javascript">
    $(function () {
        // AJAX Setup
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Spinner Handling
        $(document).on({
            ajaxStart: () => $('.btn-save-statistic').html("<i class='fa fa-spin fa-spinner'></i>"),
            ajaxStop: () => $('.btn-save-statistic').html("<i class='fa fa-save'></i> Auto Save Ready")
        });

        // Control Sidebar
        $('.btn-show-sidebar').click(e => e.stopPropagation());
        $('html, body').click(() => $('.control-sidebar').removeClass('control-sidebar-open'));

        // Add Widget Functionality
        const id_cms_statistics = '{{$id_cms_statistics}}';
        function addWidget(area, component) {
            const id = Date.now();
            $('#' + area).append(`<div id="${id}" class="area-loading"><i class="fa fa-spin fa-spinner"></i></div>`);
            const sorting = $('#' + area + ' .border-box').length;

            $.post("{{ CRUDBooster::mainpath('add-component') }}", {
                component_name: component,
                id_cms_statistics: id_cms_statistics,
                sorting: sorting,
                area: area
            }, response => {
                $('#' + area).append(response.layout);
                $('#' + id).remove();
            });
        }

        // Sorting
        @if (CRUDBooster::getCurrentMethod() == 'getBuilder')
        function createSortable() {
            $(".connectedSortable").sortable({
                placeholder: "sort-highlight",
                connectWith: ".connectedSortable",
                handle: ".card-header, .inner-box, .box-header.mb-3, .btn-add-widget",
                forcePlaceholderSize: true,
                zIndex: 999999,
                stop: function (event, ui) {
                    const idName = ui.item.attr('id');
                    const areaName = $('#' + idName).parent('.connectedSortable').attr('id');
                    const component = $('#' + idName + ' > a').data('component');
                    $('#' + idName).remove();
                    addWidget(areaName, component);
                },
                update: function (event, ui) {
                    if (ui.sender) {
                        const componentID = ui.item.attr('id');
                        const areaName = $('#' + componentID).parent('.connectedSortable').attr("id");
                        const index = $('#' + componentID).index();
                        $.post("{{ CRUDBooster::mainpath('update-area-component') }}", {
                            componentid: componentID,
                            sorting: index,
                            areaname: areaName
                        });
                    }
                }
            });
        }
        createSortable();
        @endif

        // Load Components
        $('.connectedSortable').each(function () {
            const areaName = $(this).attr('id');
            $.get("{{ CRUDBooster::adminpath('statistic_builder/list-component') }}/" + id_cms_statistics + "/" + areaName, function (response) {
                if (response.components) {
                    response.components.forEach(obj => {
                        const loadingDiv = $(`<div id='area-loading-${obj.componentID}' class='area-loading'><i class='fa fa-spin fa-spinner'></i></div>`);
                        $('#' + areaName).append(loadingDiv);
                        $.get("{{ CRUDBooster::adminpath('statistic_builder/view-component') }}/" + obj.componentID, function (view) {
                            loadingDiv.remove();
                            $('#' + areaName).append(view.layout);
                        });
                    });
                }
            });
        });

        // Delete Component
        $(document).on('click', '.btn-delete-component', function () {
            const componentID = $(this).data('componentid');
            const $this = $(this);
            swal({
                title: "Are you sure?",
                text: "You will not be able to recover this widget!",
                icon: "warning",
                buttons: true,
            }).then(willDelete => {
                if (willDelete) {
                    $.get("{{ CRUDBooster::mainpath('delete-component') }}/" + componentID, () => {
                        $this.parents('.border-box').remove();
                    });
                }
            });
        });

        // Edit Component
        $(document).on('click', '.btn-edit-component', function () {
            const componentID = $(this).data('componentid');
            const name = $(this).data('name');

            $('#modal-statistic .modal-title').text(name);
            $('#modal-statistic .modal-body').html("<i class='fa fa-spin fa-spinner'></i> Please wait loading...");
            $('#modal-statistic').modal('show');

            $.get("{{ CRUDBooster::mainpath('edit-component') }}/" + componentID, response => {
                $('#modal-statistic .modal-body').html(response);
            });
        });

        // Modal Submit
        $('#modal-statistic .btn-submit').click(function () {
            console.log('submit');
            const $form = $('#modal-statistic form');
            console.log($form);
            $form.find('.has-error').removeClass('has-error');
            const requiredInputs = $form.find('input[required], textarea[required], select[required]').filter((_, input) => !$(input).val()).map((_, input) => $(input).attr('name')).toArray();

            /*if (requiredInputs.length) {
                setTimeout(
                    () => requiredInputs.forEach(name => $form.find(`[name="${name}"]`).parent('.mb-3.row').addClass('has-error')), 200);
                return false;
            }*/

            const $button = $(this).text('Saving...').addClass('disabled');
            $.ajax({
                data: $form.serialize(),
                type: 'POST',
                url: "{{ CRUDBooster::mainpath('save-component') }}",
                success: function () {
                    $button.removeClass('disabled').text('Save Changes');
                    $('#modal-statistic').modal('hide');
                    window.location.href = "{{ Request::fullUrl() }}";
                },
                error: function () {
                    alert('Sorry, something went wrong!');
                    $button.removeClass('disabled').text('Save Changes');
                }
            });
        });
    });
</script>
@endpush

<div id="modal-statistic" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h4 class="modal-title">Modal Title</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p>One fine body&hellip;</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-submit" data-bs-loading-text="Saving..." autocomplete="off">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<div id="statistic-area">
    @if (!empty($code_layout))
        {!! str_replace("", "", $code_layout) !!}
    @else
        <div class="statistic-row row">
            @for ($i = 1; $i <= 8; $i++)
                <div id="area{{ $i }}" class="col-sm-3 connectedSortable"></div>
            @endfor
        </div>
        <div class='statistic-row row'>
            <div id='area9' class="col-sm-12 connectedSortable"></div>
        </div>
    @endif
</div>

<script defer>
if (window.location.href.includes('statistic_builder/builder')) {
    document.querySelectorAll('td[id^="area"]').forEach(area => {
        area.style.border = '1px solid black';
    });
}
</script>

@extends("crudbooster::module_generator.template")

@push('bottom')
<script type="text/javascript">
$(function () {
  // on type change, if boolean disable size input field else enable size input field
  $('.type-select').change( function () {
    // new data type selected in the type dropdown
    var selected_data_type = $(this).val();
    // identifies the table row
    var index = $(this).data('index');
    // console.log('selected_data_type', selected_data_type);
    // console.log('data index', index);
    switch (selected_data_type) {
      case 'boolean':
        //disable size field
        $('.size[data-index="'+index+'"]').prop('disabled',true);
        //set size value
        $('.size[data-index="'+index+'"]').val(1);
        break;
      case 'text':
        //enable size field
        $('.size[data-index="'+index+'"]').prop('disabled',false);
        //set default size value
        $('.size[data-index="'+index+'"]').val(255);
        break;
      case 'number':
        //enable size field
        $('.size[data-index="'+index+'"]').prop('disabled',false);
        //set default size value
        $('.size[data-index="'+index+'"]').val(11);
        break;
      default:
        //size enabled by default
        $('.size[data-index="'+index+'"]').prop('disabled',false);
    }
  })
  $(document).on('click', '.btn-plus', function () {
    // var tr_parent = $(this).parent().parent('tr');
    var clone = $('#tr-sample').clone();
    clone.removeAttr('id');
    var new_index = parseInt($('.data_index').val());
    $('#tr-sample td input.data_index').val(new_index+1);
    // console.log(tr_parent);
    // tr_parent.after(clone);
    $('tbody').append(clone);
    $('.table-form tr').not('#tr-sample').show();
  })
  //init row
  $('.btn-plus').last().click();
  $(document).mouseup(function (e) {
    var container = $(".sub");
    if (!container.is(e.target)
    && container.has(e.target).length === 0) {
      container.hide();
    }
  });
  $(document).on('click', '.table-form .btn-delete', function () {
    $(this).parent().parent().remove();
  })
  $(document).on('click', '.table-form .btn-up', function () {
    var tr = $(this).parent().parent();
    var trPrev = tr.prev('tr');
    if (trPrev.length != 0) {
      tr.prev('tr').before(tr.clone());
      tr.remove();
    }
  })
  $(document).on('click', '.table-form .btn-down', function () {
    var tr = $(this).parent().parent();
    var trPrev = tr.next('tr');
    if (trPrev.length != 0) {
      tr.next('tr').after(tr.clone());
      tr.remove();
    }
  })
  var current_option_area = null;
  $(document).on('click', '.btn-options', function () {
    $('#myModal .modal-body').empty();
    current_option_area = $(this).next('.option_area');
    var clone = $(this).next('.option_area').clone();
    clone.removeAttr('style');
    clone.appendTo('#myModal .modal-body');
    $('#myModal').modal('show');
  })
  $('#myModal .btn-save-option').click(function () {
    //Validation
    var i_required = [];
    $('#myModal .modal-body .required').each(function () {
      var value = $(this).val();
      var name = $(this).attr('name');
      if (value == '') {
        i_required.push(name);
      }
    });
    if (i_required.length > 0) {
      console.log(i_required);
      alert("Some these fields are required : " + i_required.join(", "));
      return false;
    }
    //Validation
    var i_required_one = [];
    $('#myModal .modal-body .required-one').each(function () {
      var value = $(this).val();
      var name = $(this).attr('name');
      if (value == '') {
        i_required_one.push(name);
      }
    })
    if (i_required_one.length > 0 && i_required_one.length == $('#myModal .modal-body .required-one').length) {
      alert("One of these fields are required : " + i_required_one.join(", "));
      return false;
    }
    current_option_area.empty();
    var clone = $('#myModal .option_area').children().clone();
    current_option_area.html(clone);
    $('#myModal .modal-body').empty();
    $('#myModal').modal('hide');
  })
})
</script>
@endpush

@section("inner_content")
<div id="myModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class='fa fa-cog'></i> Options</h4>
      </div>
      <div class="modal-body">
        <p>One fine body&hellip;</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn-save-option btn btn-primary">Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="box box-default">
  <div class="box-header mb-3 with-border">
    <h3 class="box-title">{{ $box_title }}</h3>
  </div>
  <div class="box-body">
    @if($table_exists)
    <div class="alert alert-warning">
      <strong style="text-transform: capitalize">Warning!</strong> this is the data structure, editing may damage data.
    </div>
    @endif
    <form method="post" action="{{ Route('ModulsControllerPostStep2') }}">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="id" value="{{ $id }}">
      <table class='table-form table table-striped'>
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Size</th>
            <th width="180px">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php $index = 0;?>
          @foreach($cb_form as $form)
          <tr>
            <td>
              <input type='hidden' name='index[]' value="{{ $index }}"/>
              <input data-index="{{ $index }}" type='text' value='{{$form["name"]}}' placeholder="Insert column name" class='form-control name' name='name[]' autocomplete="off"/>
              <div class="help-block">
                Symbols, special characters and accents are forbidden. Use only alphanumeric lower case characters and underscore.
              </div>
            </td>
            <td>
              <select data-index="{{ $index }}" name="type[]" class='form-control type-select'>
                @foreach($types as $type)
                  <option {{ ($type==$form['type']) ? 'selected' : '' }} value="{{ $type }}">{{ $type }}</option>
                @endforeach
              </select>
            </td>
            <td>
              <input data-index="{{ $index }}" {{ ($form['type']=='boolean') ? 'disabled' : '' }} type='text' value='{{$form["size"]}}' placeholder="Number of characters for text or number of digits for numbers" class='form-control size' name='size[]' autocomplete="off"/>
            </td>
            <td>
              <a href="javascript:void(0)" class="btn btn-info btn-plus"><i class='fa fa-plus'></i></a>
              <a href="javascript:void(0)" class="btn btn-danger btn-delete"><i class='fa fa-trash'></i></a>
              <!-- <a href="javascript:void(0)" class="btn btn-success btn-up"><i class='fa fa-arrow-up'></i></a>
              <a href="javascript:void(0)" class="btn btn-success btn-down"><i class='fa fa-arrow-down'></i></a> -->
            </td>
          </tr>
          <?php $index++;?>
          @endforeach

          <!-- new column fields -->
          <tr id='tr-sample' style="display: none">
            <td>
              <input class="data_index" type='hidden' name='index[]' value="{{ $index }}"/>
              <input data-index="{{ $index }}" type='text' placeholder="Insert column name" class='form-control name' name='name[]' autocomplete="off"/>
              <div class="help-block">
                Symbols, special characters and accents are forbidden. Use only alphanumeric lower case characters and underscore.
              </div>
            </td>
            <td>
              <select data-index="{{ $index }}" name="type[]" class='form-control type-select'>
                @foreach($types as $type)
                  <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
              </select>
            </td>
            <td>
              <input data-index="{{ $index }}" type='text' placeholder="Number of characters for text or number of digits for numbers" class='form-control size' name='size[]' autocomplete="off"/>
            </td>
            <td>
              <a href="javascript:void(0)" class="btn btn-info btn-plus"><i class='fa fa-plus'></i></a>
              <a href="javascript:void(0)" class="btn btn-danger btn-delete"><i class='fa fa-trash'></i></a>
              <!-- <a href="javascript:void(0)" class="btn btn-success btn-up"><i class='fa fa-arrow-up'></i></a>
              <a href="javascript:void(0)" class="btn btn-success btn-down"><i class='fa fa-arrow-down'></i></a> -->
            </td>
          </tr>

        </tbody>
      </table>
    </div>
    <div class="box-footer">

      <div class='pull-right'>
        <button type="button" onclick="location.href='{{CRUDBooster::mainpath('step1').'/'.$id}}'" class="btn btn-default">&laquo; Back</button>
        <input type="submit" class="btn btn-primary" value="Step 3 &raquo;">
      </div>
    </div>
  </form>
</div>
@endsection

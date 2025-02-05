@if($command=='layout')
<div id='{{$componentID}}' class='border-box'>

    <div class="card card-default">
        <div class="card-header">
            [name]
        </div>
        <div class="card-body">
            <p>[content]</p>
        </div>
    </div>

    <div class='action pull-right'>
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' data-name='Panel Area'
            class='btn-edit-component'><i class='fa fa-pencil'></i></a>
        &nbsp;
        <a href='javascript:void(0)' data-componentid='{{$componentID}}' class='btn-delete-component'><i
                class='fa fa-trash'></i></a>
    </div>
</div>
@elseif($command=='configuration')
<form method='post'>
    <input type='hidden' name='_token' value='{{csrf_token()}}' />
    <input type='hidden' name='componentid' value='{{$componentID}}' />
    <div class="mb-3 row">
        <label>Name</label>
        <input class="form-control" required name='config[name]' type='text' value='{{@$config->name}}' />
    </div>

    <div class="mb-3 row">
        <label>Content</label>
        <textarea name='config[content]' required rows="10" class='form-control'>{{@$config->content}}</textarea>
    </div>

</form>
@elseif($command=='showFunction')
<?php
    echo $value;
    ?>
@endif
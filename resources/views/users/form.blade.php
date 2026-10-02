@extends('crudbooster::admin_template')
@section('content')
<div>
    @if(CRUDBooster::getCurrentMethod() != 'getProfile' && $button_cancel)
    @if(g('return_url'))
    <p>
        <a title='Return' href='{{g("return_url")}}'>
            <i class='bi bi-chevron-left'></i>&nbsp;
            {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
        </a>
    </p>
    @else
    <p>
        <a title='Main Module' href='{{CRUDBooster::mainpath()}}'>
            <i class='bi bi-chevron-left'></i>&nbsp;
            {{trans("crudbooster.form_back_to_list",['module'=>CRUDBooster::getCurrentModule()->name])}}
        </a>
    </p>
    @endif
    @endif

    <div class="card card-default">
        <div class="card-header">
            <strong><i class='{{CRUDBooster::getCurrentModule()->icon}}'></i> {!! $page_title !!}</strong>
        </div>
        <div class="card-body" style="padding:20px 0px 0px 0px">
            <?php
                $action = (@$row) ? CRUDBooster::mainpath("edit-save/$row->id") : CRUDBooster::mainpath("add-save");
                $return_url = isset($return_url) ? $return_url: g('return_url');
                ?>
            {{-- Reset password di un altro utente: la password altrui non si
                 imposta ne' si vede mai, si manda all'utente un link per
                 sceglierla (AdminCmsUsersController::postUserResetPassword). --}}
            @if(@$row && isset($command) && $command == 'edit' && (int) $row->id !== (int) CRUDBooster::myId() && \App\Helpers\UserHelper::can_do_on_user('edit', $row->id))
            <div style="padding:0 20px 16px 20px;">
                <div class="alert" id="ch-reset-alert" role="alert" hidden></div>
                <button type="button" class="btn btn-secondary" id="ch-reset-btn">
                    <i class="bi bi-envelope-fill"></i> {{ trans('crudbooster.user_reset_password_button') }}
                </button>
                <span class="help-block" style="display:inline-block;margin:0 0 0 10px;">{{ trans('crudbooster.user_reset_password_hint') }}</span>
            </div>
            <script>
            (function () {
                var btn = document.getElementById('ch-reset-btn');
                var box = document.getElementById('ch-reset-alert');
                btn.addEventListener('click', function () {
                    if (!confirm({!! json_encode(trans('crudbooster.user_reset_password_confirm')) !!})) { return; }
                    btn.disabled = true;
                    fetch({!! json_encode(CRUDBooster::adminPath('users/user-reset-password/' . $row->id)) !!}, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': {!! json_encode(csrf_token()) !!}, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (r) { return r.json(); }).catch(function () {
                        return { ok: false, message: {!! json_encode(trans('crudbooster.profile_generic_error')) !!} };
                    }).then(function (res) {
                        btn.disabled = false;
                        box.textContent = res.message;
                        box.className = 'alert ' + (res.ok ? 'alert-success' : 'alert-danger');
                        box.hidden = false;
                    });
                });
            })();
            </script>
            @endif
            <form class='form-horizontal' method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type='hidden' name='return_url' value='{{ @$return_url }}' />
                <input type='hidden' name='ref_mainpath' value='{{ CRUDBooster::mainpath() }}' />
                <input type='hidden' name='ref_parameter' value='{{urldecode(http_build_query(@$_GET))}}' />
                @if($hide_form)
                <input type="hidden" name="hide_form" value='{!! serialize($hide_form) !!}'>
                @endif
                <div class="box-body" id="parent-form-area">

                    @if( isset($command) && isset($command) && $command == 'detail')
                    @include("users.form_detail")
                    @else
                    @include("users.form_body")
                    @endif

                </div><!-- /.box-body -->

                <div class="box-footer" style="background: var(--ch-bg)">

                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-2"></label>
                        <div class="col-sm-10">
                            @if($button_cancel && CRUDBooster::getCurrentMethod() != 'getDetail')
                            @if(g('return_url'))
                            <a href='{{g("return_url")}}' class='btn btn-secondary'><i
                                    class='bi bi-chevron-left'></i> {{trans("crudbooster.button_back")}}</a>
                            @else
                            <a href='{{CRUDBooster::mainpath("?".http_build_query(@$_GET)) }}'
                                class='btn btn-secondary'><i class='bi bi-chevron-left'></i>
                                {{trans("crudbooster.button_back")}}</a>
                            @endif
                            @endif
                            @if(CRUDBooster::isCreate() || CRUDBooster::isUpdate())

                            @if(CRUDBooster::isCreate() && $button_addmore==TRUE && isset($command) && $command == 'add')
                            <input type="submit" name="submit" value='{{trans("crudbooster.button_save_more")}}'
                                class='btn btn-success'>
                            @endif


                            @if($button_save && isset($command) && $command != 'detail')
                            <input type="submit" name="submit" value='{{trans("crudbooster.button_save")}}'
                                class='btn btn-success'>
                            @endif

                            @endif
                        </div>
                    </div>


                </div><!-- /.box-footer-->

            </form>

        </div>
    </div>
</div><!--END AUTO MARGIN-->

@endsection
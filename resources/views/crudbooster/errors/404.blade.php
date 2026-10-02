<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>404 PAGE NOT FOUND</title>
    <meta content='width=device-width, initial-scale=1, viewport-fit=cover' name='viewport'>
    <meta name='robots' content='noindex,nofollow' />
    @include('crudbooster::partials.ch_head')
</head>
<body class="ch-shell">

<section class="content">
    <div class="error-page" style="max-width: 640px; margin: 12vh auto 0; padding: 0 16px;">
        <h2 class="headline" style="font-size: 72px; font-weight: 800; color: var(--ch-warning); margin: 0 0 8px;"> 404</h2>
        <div class="error-content">
            <h3><i class="bi bi-exclamation-triangle-fill" style="color: var(--ch-warning);"></i> {{trans('crudbooster.page_not_found')}}</h3>
            <p>
                {{trans('crudbooster.page_not_found_text')}}
            </p>
            <p>Tips : <br/>
                {{trans('crudbooster.page_not_found_tips')}}
            </p>
        </div><!-- /.error-content -->
    </div><!-- /.error-page -->
</section><!-- /.content -->

@include('crudbooster::partials.ch_scripts', ['ch_shell' => false])
</body>
</html>

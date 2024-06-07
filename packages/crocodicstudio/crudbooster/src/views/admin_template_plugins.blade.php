<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->

<!-- REQUIRED JS SCRIPTS -->

<!-- jQuery 2.2.3 -->





<!--<script type="text/javascript" src="https://sense.izsvenezie.it/pub/resources/assets/external/requirejs/require.js"></script>-->

<script>
    var ASSET_URL = "{{asset('/')}}";
    var APP_NAME = "{{Session::get('appname')}}";
    var ADMIN_PATH = '{{url(config("crudbooster.ADMIN_PATH")) }}';
    var NOTIFICATION_JSON = "{{route('NotificationsControllerGetLatestJson')}}";
    var NOTIFICATION_INDEX = "{{route('NotificationsControllerGetIndex')}}";

    var NOTIFICATION_YOU_HAVE = "{{trans('crudbooster.notification_you_have')}}";
    var NOTIFICATION_NOTIFICATIONS = "{{trans('crudbooster.notification_notification')}}";
    var NOTIFICATION_NEW = "{{trans('crudbooster.notification_new')}}";

    $(function () {
        $('.datatables-simple').DataTable();
    })
</script>
<script src="{{asset('vendor/crudbooster/assets/js/main.js').'?r='.time()}}"></script>






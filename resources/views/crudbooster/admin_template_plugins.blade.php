{{-- Script di base in locale (nessun CDN): jQuery, jQuery UI, Bootstrap 5 (bundle con Popper), guscio --}}
@include('crudbooster::partials.ch_scripts', ['ch_jqui' => true])

<!--BOOTSTRAP DATEPICKER-->
<script src="{{ asset ('vendor/plugins/datepicker/bootstrap-datepicker.js') }}"></script>
<link rel="stylesheet" href="{{ asset ('vendor/plugins/datepicker/datepicker3.css') }}">

<!--BOOTSTRAP DATERANGEPICKER 2.1.27 AND MOMENT 2.13.0 -->
<script src="{{ asset ('vendor/plugins/daterangepicker/moment.min.js') }}"></script>
<script src="{{ asset ('vendor/plugins/daterangepicker/daterangepicker.js') }}"></script>
<link rel="stylesheet" href="{{ asset ('vendor/plugins/daterangepicker/daterangepicker-bs3.css') }}">

<!-- Bootstrap time Picker -->
<link rel="stylesheet" href="{{ asset ('vendor/plugins/timepicker/bootstrap-timepicker.min.css') }}">
<script src="{{ asset ('vendor/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>

<link rel='stylesheet' href='{{ asset("vendor/crudbooster/assets/lightbox/dist/css/lightbox.min.css") }}'/>
<script src="{{ asset('vendor/crudbooster/assets/lightbox/dist/js/lightbox.min.js') }}"></script>

<!--SWEET ALERT-->
<script src="{{asset('vendor/crudbooster/assets/sweetalert/dist/sweetalert.min.js')}}"></script>
<link rel="stylesheet" type="text/css" href="{{asset('vendor/crudbooster/assets/sweetalert/dist/sweetalert.css')}}">

<!--MONEY FORMAT-->
<script src="{{asset('vendor/crudbooster/jquery.price_format.2.0.min.js')}}"></script>

<!--DATATABLE-->
<link rel="stylesheet" href="{{ asset ('vendor/plugins/datatables/dataTables.bootstrap5.min.css')}}">
<script src="{{ asset ('vendor/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{ asset ('vendor/plugins/datatables/dataTables.bootstrap5.min.js')}}"></script>

<!--TINYMCE-->
<script src="{{ asset('tinymce/js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>

<script src="{{ asset('vendor/libs/interactjs/interact.min.js') }}"></script>


<!--
<link rel="stylesheet" href="{{ asset ('css/chatai.css')}}">
-->

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
/*
$("#draggable").draggable();
$("#draggable").resizable();
*/

        $("#chatWindow").draggable({
            handle: ".chat-header",
            containment: ".content-wrapper" 
        }).resizable();

        $("#openChat").click(function() {
            $("#chatWindow").toggle();
        });

    })


</script>

<script>

// Presenti solo nelle pagine dove il widget ChatAI e' attivo/renderizzato -
// null-check perche' su tutte le altre pagine admin questi elementi non
// esistono nel DOM.
var xCloseChatai = document.querySelector('#x-close-chatai');
if (xCloseChatai) {
    xCloseChatai.addEventListener('click', function () {

$("#chatWindow").toggle();



});
}

var toggleSidebarBtn = document.querySelector('.toggle-sidebar-btn');
if (toggleSidebarBtn) {
    toggleSidebarBtn.addEventListener('click', function () {

$("#chatWindow").toggle();

/*
  var chatai = document.querySelector('.main-sidebar-right');

  if (chatai.style.right === '0px') {
    chatai.style.right = '-350px';
    chatai.style.display = 'none';
  } else {
    chatai.style.right = '0';
    chatai.style.display = 'block';
  }
*/


});
}




</script>
<script src="{{ asset('vendor/libs/raphael/raphael-min.js') }}"></script>
<script src="{{asset('vendor/crudbooster/assets/js/main.js').'?r='.time()}}"></script>
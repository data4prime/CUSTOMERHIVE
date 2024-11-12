<?php namespace crocodicstudio\crudbooster\controllers;

use Illuminate\Http\Request;
use App\QlikItem;
use App\Menu;

use crocodicstudio\crudbooster\helpers\QlikHelper;
use crocodicstudio\crudbooster\helpers\QlikHelper as HelpersQlikHelper;

//CRUDBooster 
use CRUDBooster;

class QlikItemsController extends Controller
{
  public function show($proxy_token) {
    $qlik_item = QlikItem::where('proxy_token',$proxy_token)->first();
    if(empty($qlik_item)){
      //TODO 404 page
      echo '404: item not found';
    }
    else{
      //show public item
      $item_url = $qlik_item->url;
      $url = $qlik_item->url;
      $page_title = $qlik_item->title;
      $subtitle = $qlik_item->subtitle;
      $debug = $qlik_item->debug_url;
      $menu = Menu::where('path', 'qlik_items/content/' . $qlik_item->id)->first();
      if (empty($menu) || !isset($menu)) {
        $frame_width = '100%';
        $frame_height = '100%';
      } else {
        $frame_width = $menu->frame_width;
        $frame_height = $menu->frame_height;
      }
		  $target_layout = isset($menu) ? $menu->target_layout : '';

  		$conf = QlikHelper::getConfFromItem($qlik_item->id);
      $type = $conf->type;
      $auth = $conf->auth;
      if ($auth == 'JWT') {
				if ($type == 'SAAS') {
					//Log::debug('Getting token for SaaS');
					$token = HelpersQlikHelper::getJWTToken(CRUDBooster::myId(), $conf->id);
					$js_login = "js/qliksaas_login.js";
				} else {
					//Log::debug('Getting token for on-premises');
					$token = HelpersQlikHelper::getJWTTokenOP(CRUDBooster::myId(), $conf->id);
					$js_login = "js/qlik_op_jwt_login.js";
				}
				//Log::debug('Token: ' . $token);
				if (empty($token)) {
            //Log::debug('Token generation failed');
            $error = 'JWT Token generation failed!';
            CRUDBooster::redirectBack($error, 'error');
          }
          $token = $token;
          $item_url = $qlik_item->url;

          $tenant = $conf->url;
          $web_int_id = $conf->web_int_id;
          $prefix = $conf->endpoint;

          $js_login = $js_login;

          

          /*if (isset($menu->target_layout) && $menu->target_layout == 1) {

            $this->cbView('qlik_items.fullscreen_view_saas', $data);
          } else {

            $this->cbView('qlik_items.view_saas', $data);
          }*/
		  }

      // $url = 'https://www.google.com';
      // echo(file_get_contents($url));
      return view('qlik_items.public', compact(
                  'item_url','url', 'page_title', 'frame_width', 'frame_height', 'target_layout', 'subtitle', 'debug',
                  'token', 'tenant', 'web_int_id', 'prefix', 'js_login'
      ));

      // echo "
      // <script type='text/javascript'>
      // function printExternal(url) {
      //     var printWindow = window.open( url, 'Print', 'left=200, top=200, width=950, height=500, toolbar=0, resizable=0');
      //     printWindow.addEventListener('load', function(){
      //         printWindow.print();
      //         printWindow.close();
      //     }, true);
      // }
      // printExternal('".$url."');
      // </script>
      // ";
    }
  }
}

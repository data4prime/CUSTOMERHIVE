<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\Controller;

use Illuminate\Http\Request;
use App\QlikItem;
use App\Menu;

use App\Helpers\QlikHelper;
use App\Helpers\QlikHelper as HelpersQlikHelper;

//CRUDBooster 
use CRUDBooster;

class QlikItemsController extends Controller
{
  public function show($proxy_token) {
    $qlik_item = QlikItem::where('proxy_token',$proxy_token)->first();
    if(empty($qlik_item)){
      abort(404);
    }

    //show public item
    $item_url = $qlik_item->url;
    $url = $qlik_item->url;
    $page_title = $qlik_item->title;
    $subtitle = $qlik_item->subtitle;
    $debug = $qlik_item->debug_url;
    $menu = Menu::where('path', 'qlik_items/content/' . $qlik_item->id)->first();
    if (empty($menu)) {
      $frame_width = '100%';
      $frame_height = '100%';
    } else {
      $frame_width = $menu->frame_width;
      $frame_height = $menu->frame_height;
    }
    $target_layout = isset($menu) ? $menu->target_layout : '';

    $conf = QlikHelper::getConfFromItem($qlik_item->id);
    //configurazione cancellata o metodo di autenticazione non gestito
    //dalla pagina pubblica: nessun contenuto da mostrare
    if (empty($conf) || $conf->auth != 'JWT') {
      abort(404);
    }

    //il token JWT resta vuoto: la pagina pubblica non ha un utente loggato
    $token = "";
    $js_login = ($conf->type == 'SAAS') ? "js/qliksaas_login.js" : "js/qlik_op_jwt_login.js";

    $tenant = $conf->url;
    $web_int_id = $conf->web_int_id;
    $prefix = $conf->endpoint;

    return view('qlik_items.public', compact(
                'item_url','url', 'page_title', 'frame_width', 'frame_height', 'target_layout', 'subtitle', 'debug',
                'token', 'tenant', 'web_int_id', 'prefix', 'js_login'
    ));
  }
}

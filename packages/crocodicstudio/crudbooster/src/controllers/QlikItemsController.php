<?php namespace crocodicstudio\crudbooster\controllers;

use Illuminate\Http\Request;
use App\QlikItem;
use App\Menu;

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

      // $url = 'https://www.google.com';
      // echo(file_get_contents($url));
      return view('qlik_items.public', compact('item_url','url', 'page_title', 'frame_width', 'frame_height', 'target_layout', 'subtitle', 'debug'));

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

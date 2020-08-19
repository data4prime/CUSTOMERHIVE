<?php namespace crocodicstudio\crudbooster\controllers;

use Illuminate\Http\Request;
use App\QlikItem;

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
      $url = $qlik_item->url;
      // $url = 'https://www.google.com';
      // echo(file_get_contents($url));
      return view('qlik_items.public', compact('url'));

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

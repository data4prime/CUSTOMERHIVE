<?php
namespace crocodicstudio\crudbooster\helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use App\Menu;

class MenuHelper  {

  /**
  * save menu, recursive
  */
  public static function save_menu($menu, $counter, $isActive, $parent_id = 0){
    //if menu has children
    if($menu['children'][0]) {
      //keep count for sorting
      $child_counter = 1;
      //loop through children
      foreach ($menu['children'][0] as $child) {
        //recursive call to save child
        self::save_menu($child, $child_counter, $isActive, $menu['id']);
        $child_counter++;
      }
    }
    //save menu
    DB::table('cms_menus')
    ->where('id', $menu['id'])
    ->update([
      'sorting' => $counter,
      'parent_id' => $parent_id,
      'is_active' => $isActive
    ]);
  }


  /**
  * When deleting a menu, remove the menu id as parent id from his children
  * promoting them, no need to check recursively for children's children
  *
  * @param int menu's id
  */
  public static function promote_orphans($id) {
    $orphans = Menu::where('parent_id',$id)->get();
    foreach ($orphans as $orphan) {
      $orphan->parent_id = 0;
      $orphan->save();
    }
  }

  public static function parse_path_for_qlik_item_id($URI) {
     $last_element = end(explode('/',$URI));
     $last_element_exploded = explode('?',$last_element);
     return $last_element_exploded[0];
  }

  /**
  *	Get menus
  *
  * @param boolean 1 to load active menus or 0 to load inactive menus
  *
  * @return array struttura del menu
  */
  public static function get_menu($is_active) {

    $menu_list = DB::table('cms_menus')
                    ->where('parent_id', 0)//menu di primo livello o parent, che non sono figli di un altro menu
                    ->where('is_active', $is_active)
                    ->orderby('sorting', 'asc');

    if(!CRUDBooster::isSuperadmin())
    {
      //tenant admin vede nella lista solo le voci di menu del proprio tenant
      $menu_list = $menu_list->join('menu_tenants', 'cms_menus.id', '=', 'menu_tenants.menu_id')
                              ->where('menu_tenants.tenant_id',UserHelper::current_user_tenant());
    }
    $menu_list = $menu_list->select('cms_menus.*')
                            ->get();

    foreach ($menu_list as &$menu) {
      $menu->children = self::get_child_menus($menu);
    }
    return $menu_list;
  }

  public static function get_child_menus($menu){
    $children = DB::table('cms_menus')
                ->where('parent_id', $menu->id)
                ->orderby('sorting', 'asc')
                ->get();

    foreach ($children as $child) {
      $child->children = self::get_child_menus($child);
    }

    return $children;
  }

  /**
  * builds html string to print menus in the menu management list
  */
  public static function menu_to_html($menu_list, $return_url){

    foreach($menu_list as $menu){
      $privileges = DB::table('cms_menus_privileges')
        ->join('cms_privileges','cms_privileges.id','=','cms_menus_privileges.id_cms_privileges')
        ->where('id_cms_menus',$menu->id)
        ->pluck('cms_privileges.name')
        ->toArray();

      $tenants_name = \App\Menu::find($menu->id)->tenants_name();

      $can_edit_menu = false;
      $disable = 'ui-state-disabled';
      if(UserHelper::can_menu('edit', $menu->id)) {
        $can_edit_menu = true;
        $disable = '';
      }

      $result .= "<li class='$disable' data-id='$menu->id' data-name='$menu->name'>";
      if($menu->is_dashboard){
        $class = 'is-dashboard';
        $title = 'This is set as Dashboard';
        $icon = 'icon-is-dashboard fa fa-dashboard';
      }
      else{
        $class = '';
        $title = '';
        $icon = $menu->icon;
      }
      $result .= "<div class='$class' title='$title'>";
      $result .= "<i class='$icon'></i>";
      $result .= $menu->name;
      $result .= "<span class='pull-right'>";
      if($can_edit_menu){
        $href = "";
        if(route("MenusControllerGetEdit",["id"=>$menu->id])){
          $href = route("MenusControllerGetEdit",["id"=>$menu->id])."?return_url=".$return_url;
        }
        $result .= "<a class='fa fa-pencil' title='Edit' href='$href'></a>";
      }
      $result .= "&nbsp;&nbsp;";
      if(UserHelper::can_menu('delete', $menu->id)){
        $onclick = CRUDBooster::deleteConfirm(route("MenusControllerGetDelete",["id"=>$menu->id]), false);
        $result .= "<a title='Delete' class='fa fa-trash' onclick='$onclick' href='javascript:void(0)'></a>";
      }
      $result .= "</span>";
      $result .= "<br/>";
      $result .= "<em class='text-muted'>";
      $privileges_html = implode(', ',$privileges);
      $result .= "<small><i class='fa fa-users'></i> &nbsp; $privileges_html</small>";
      $result .= "</em>";
      if(CRUDBooster::isSuperadmin()){
        $result .= "<em class='text-muted pull-right'>";
        $result .= "<small><i class='fa fa-industry'></i> &nbsp; $tenants_name</small>";
        $result .= "</em>";
      }
      $result .= "</div>";
      $result .= "<ul>";
      //if this menu has children
      if($menu->children){
        //recursive call
        $result .= self::menu_to_html($menu->children, $return_url);
      }
      $result .= "</ul>";
      $result .= "</li>";
    }
    return $result;
  }

  /**
  * builds html string to print menus in the sidebar
  */
  public static function build_main_sidebar(){
    $result = '';
    foreach(CRUDBooster::sidebarMenu() as $menu){
    // foreach(MenuHelper::get_menu(1) as $menu){
      $result .= self::menu_to_html_for_sidebar($menu);
    }
    return $result;
  }

  /**
  * builds html string to print a single menu in the sidebar
  */
  public static function menu_to_html_for_sidebar($menu){
    $result = '';
    if($menu->new_tab){
      $target='target="_blank"';
    }
    else{
      $target='';
    }
    $classes = "";
    //if menu has children
    if(!empty($menu->children) AND count($menu->children) > 0){
      $classes = "treeview ".count($menu->children);
    }
    if(Request::is($menu->url_path."*")){
      $classes .= " active";
    }
    $result .= "<li data-id='$menu->id' data-collapse='1' class='$classes'>";
    if($menu->is_broken){
      $href = "javascript:alert('".trans('crudbooster.controller_route_404')."')";
    }
    else{
      $href = $menu->url;
    }
    $class = "";
    if($menu->color){
      $class = "text-".$menu->color;
    }
    $result .= "<a $target href='$href' class='$class'>";
    $classes = "$menu->icon ";
    if($menu->color){
      $classes .= " text-$menu->color";
    }
    $result .= "<i class='$classes'></i>";
    $result .= "<span>$menu->name</span>";
    if(!empty($menu->children) AND count($menu->children) > 0){
      $result .= "<i class='fa fa-angle-".trans("crudbooster.right")." pull-".trans("crudbooster.right")."'></i>";
    }
    $result .= "</a>";
    if(!empty($menu->children) AND count($menu->children) > 0){
      $result .= '<ul class="treeview-menu">';
      foreach ($menu->children as $child) {
        $result .= self::menu_to_html_for_sidebar($child);
      }
      $result .= '</ul>';
    }
    $result .= '</li>';

    return $result;
  }
}

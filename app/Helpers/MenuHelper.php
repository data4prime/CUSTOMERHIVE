<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use App\Menu;
use App\Helpers\CRUDBooster;
use App\Helpers\UserHelper;

class MenuHelper
{

  /**
   * save menu, recursive
   */
  public static function save_menu($menu, $counter, $isActive, $parent_id = 0)
  {
    //if menu has children
    if ($menu['children'][0]) {
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
  public static function promote_orphans($id)
  {
    $orphans = Menu::where('parent_id', $id)->get();
    foreach ($orphans as $orphan) {
      $orphan->parent_id = 0;
      $orphan->save();
    }
  }

  public static function parse_path_for_qlik_item_id($URI)
  {
    $last_element = end(explode('/', $URI));
    $last_element_exploded = explode('?', $last_element);
    return $last_element_exploded[0];
  }

  public static function parse_path_for_chat_ai_id($URI)
  {
    $last_element = end(explode('/', $URI));
    $last_element_exploded = explode('?', $last_element);
    return $last_element_exploded[0];
  }

  /**
   *	Get menus
   *
   * @param boolean 1 to load active menus or 0 to load inactive menus
   *
   * @return array struttura del menu
   */
  public static function get_menu($is_active)
  {

    $menu_list = DB::table('cms_menus')
      ->where('parent_id', 0) //menu di primo livello o parent, che non sono figli di un altro menu
      ->where('is_active', $is_active)
      ->orderby('sorting', 'asc');

    if (!CRUDBooster::isSuperadmin()) {
      //tenant admin vede nella lista solo le voci di menu del proprio tenant
      $menu_list = $menu_list->join('menu_tenants', 'cms_menus.id', '=', 'menu_tenants.menu_id')
        ->where('menu_tenants.tenant_id', UserHelper::current_user_tenant());
    }
    $menu_list = $menu_list->select('cms_menus.*')
      ->get();

    foreach ($menu_list as &$menu) {
      $menu->children = self::get_child_menus($menu);
    }
    return $menu_list;
  }

  public static function get_child_menus($menu)
  {
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
  public static function menu_to_html($menu_list, $return_url)
  {
    $result = "";

    foreach ($menu_list as $menu) {
      $privileges = DB::table('cms_menus_privileges')
        ->join('cms_privileges', 'cms_privileges.id', '=', 'cms_menus_privileges.id_cms_privileges')
        ->where('id_cms_menus', $menu->id)
        ->pluck('cms_privileges.name')
        ->toArray();

      $tenants_name = \App\Menu::find($menu->id)->tenants_name();

      $can_edit_menu = false;
      $disable = 'ui-state-disabled';
      if (UserHelper::can_menu('edit', $menu->id)) {
        $can_edit_menu = true;
        $disable = '';
      }



      $result .= "<li class='$disable' data-id='$menu->id' data-name='$menu->name'>";
      /*
      if (isset($result)) {
        $result .= "<li class='$disable' data-id='$menu->id' data-name='$menu->name'>";
      } else {
        $result = "<li class='$disable' data-id='$menu->id' data-name='$menu->name'>";
      }
      */


      if ($menu->is_dashboard) {
        $class = 'is-dashboard';
        $title = 'This is set as Dashboard';
        $icon = 'icon-is-dashboard bi bi-house-fill';
      } else {
        $class = '';
        $title = '';
        $icon = IconMap::toBi($menu->icon);
      }
      $result .= "<div class='mm-row $class' title='$title'>";
      $result .= "<div class='mm-line'><span class='mm-ico'><i class='$icon'></i></span>";
      $result .= "<b class='mm-name'>" . e($menu->name) . "</b>";
      $result .= "<span class='mm-actions'>";
      if ($can_edit_menu) {
        // route() richiede sempre il parametro 'id' (la rotta e'
        // admin/menu_management/edit/{id}, non opzionale): la versione
        // precedente lo passava solo nell'if di controllo, mai nella
        // chiamata usata per l'href reale, causando un
        // UrlGenerationException su ogni voce di menu modificabile.
        $href = route("MenusControllerGetEdit", ["id" => $menu->id]) . "?return_url=" . $return_url;
        $result .= "<a class='mm-act bi bi-pencil-fill' title='Edit' href='$href'></a>";
      }
      if (UserHelper::can_menu('delete', $menu->id)) {
        // Conferma sulla riga (come nel mockup) al posto del popup SweetAlert: il link
        // finale e' lo stesso GET di prima.
        $deleteUrl = route("MenusControllerGetDelete") . "/{$menu->id}";
        $result .= "<span class='mm-confirm' hidden><span class='mm-confirm-q'>" . e(trans('crudbooster.adm_delete_confirm')) . "</span>"
          . "<a class='btn btn-danger btn-sm' href='$deleteUrl'>" . e(trans('crudbooster.confirmation_yes')) . "</a>"
          . "<button type='button' class='btn btn-secondary btn-sm mm-cancel' onclick='mmCancel(this)'>" . e(trans('crudbooster.button_cancel')) . "</button></span>";
        $result .= "<a title='Delete' class='mm-act mm-del mm-ask bi bi-trash-fill' onclick='mmAsk(this)' href='javascript:void(0)'></a>";
      }
      $result .= "</span></div>";
      $privileges_html = e(implode(', ', $privileges));
      $result .= "<div class='mm-meta'><span><i class='bi bi-people-fill'></i> $privileges_html</span>";
      if (CRUDBooster::isSuperadmin()) {
        $result .= "<span><i class='bi bi-buildings-fill'></i> " . e($tenants_name) . "</span>";
      }
      $result .= "</div>";
      $result .= "</div>";
      $result .= "<ul>";
      //if this menu has children
      if ($menu->children) {
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
  public static function build_main_sidebar()
  {
    $result = '';
    foreach (CRUDBooster::sidebarMenu() as $menu) {
      $result .= self::menu_to_html_for_sidebar($menu);
    }
    return $result;
  }

  /**
   * builds html string to print a single menu in the sidebar
   */
  public static function menu_to_html_for_sidebar($menu)
  {
    $result = '';
    if ($menu->new_tab) {
      $target = 'target="_blank"';
    } else {
      $target = '';
    }
    $classes = "";
    //if menu has children
    if (!empty($menu->children) and count($menu->children) > 0) {
      $classes = "treeview " . count($menu->children);
    }
    if (Request::is($menu->url_path . "*")) {
      $classes .= " active";
    }
    $result .= "<li data-id='$menu->id' data-collapse='1' class='$classes'>";
    if ($menu->is_broken) {
      $href = "javascript:alert('" . trans('crudbooster.controller_route_404') . "')";
    } else {
      $href = $menu->url;
    }
    $class = "";
    if ($menu->color) {
      $class = "text-" . $menu->color;
    }
    $result .= "<a style='text-decoration:none;' $target href='$href' class='$class'>";
    $classes = IconMap::toBi($menu->icon) . " ";
    if ($menu->color) {
      $classes .= " text-$menu->color";
    }

    if ($menu->is_custom == 1){
      $result .= "<img src='$menu->icon_upload' style='width: 20px; height: 20px; margin-right: 10px;'>";
    } else {
      $result .= "<i class='$classes'></i>";
    }


    $result .= "<span>$menu->name</span>";
    if (!empty($menu->children) and count($menu->children) > 0) {
      $result .= "<i class='bi bi-chevron-" . trans("crudbooster.right") . " pull-" . trans("crudbooster.right") . "'></i>";
    }
    $result .= "</a>";
    if (!empty($menu->children) and count($menu->children) > 0) {
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

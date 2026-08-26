<?php

namespace crocodicstudio\crudbooster\middlewares;

use Closure;
use CRUDBooster;
use DB;
use Illuminate\Support\Facades\Auth;

class CBBackend
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $admin_path = config('crudbooster.ADMIN_PATH') ?: 'admin';

        // Migrato dal controllo legacy CRUDBooster::myId() == '' al guard
        // Laravel nativo (Fase 3 del refactoring auth, vedi
        // docs/refactoring/). Equivalente perche' Auth::login()/Auth::logout()
        // vengono ormai chiamati in coppia con la sessione legacy in
        // postLogin()/getLogout() (Fase 1 e fix successivo).
        if (Auth::guest()) {
            $url = url($admin_path.'/login');

            return redirect($url)->with('message', trans('crudbooster.not_logged_in'));
        }
        if (CRUDBooster::isLocked()) {
            $url = url($admin_path.'/lock-screen');

            return redirect($url);
        }
        if($request->url()==CRUDBooster::adminPath('')){
            $menus=DB::table('cms_menus')->whereRaw("cms_menus.id IN (select id_cms_menus from cms_menus_privileges where id_cms_privileges = '".CRUDBooster::myPrivilegeId()."')")->where('is_dashboard', 1)->where('is_active', 1)->first();
            if ($menus) {
                if ($menus->type == 'Statistic') {
                    return redirect()->action('\crocodicstudio\crudbooster\controllers\StatisticBuilderController@getDashboard');
                } elseif ($menus->type == 'Module') {
                    $module = CRUDBooster::first('cms_moduls', ['path' => $menus->path]);
                    return redirect()->action( $module->controller.'@getIndex');
                } elseif ($menus->type == 'Route') {
                    $action = str_replace("Controller", "Controller@", $menus->path);
                    $action = str_replace(['Get', 'Post'], ['get', 'post'], $action);
                    return redirect()->action($action);
                } elseif ($menus->type == 'Controller & Method') {
                    return redirect()->action($menus->path);
                } elseif ($menus->type == 'URL') {
                    return redirect($menus->path);
                } elseif ($menus->type == 'Qlik') {
                    return redirect('admin/'.$menus->path);
                } else if ($menus->type == 'Agent AI') {
                    return redirect('admin/'.$menus->path);
                }
            }
        }
        //dd($request);
        return $next($request);
    }
}

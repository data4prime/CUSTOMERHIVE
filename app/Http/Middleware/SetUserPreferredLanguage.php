<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

use crocodicstudio\crudbooster\helpers\CRUDBooster;

class SetUserPreferredLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        if (CRUDBooster::myId() && CRUDBooster::getLang()) {
            App::setLocale( CRUDBooster::getLang());
        }
        return $next($request);
    }
}

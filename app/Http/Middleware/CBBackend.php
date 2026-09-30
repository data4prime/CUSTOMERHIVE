<?php

namespace App\Http\Middleware;

use Closure;
use CRUDBooster;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        // Modalita' "embed" (docs/refactoring/162-*): pagina del modulo
        // caricata dentro l'iframe del widget "Modulo incorporato". Attiva
        // solo con ?embed=1 esplicito nella query - senza, tutto questo
        // metodo si comporta esattamente come prima.
        $embed = $request->query('embed') === '1';

        if ($embed) {
            view()->share('ch_embed', true);
        }

        $response = $this->handleRequest($request, $next);

        return $embed ? $this->adaptResponseForEmbed($request, $response) : $response;
    }

    private function handleRequest($request, Closure $next)
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
        // Sessione invalidata da un cambio password/email fatto altrove
        // (MfaHelper::bumpSessionVersion): il contatore in sessione e'
        // rimasto indietro rispetto a quello su cms_users. Una sessione
        // nata prima della colonna non ha la chiave: vale 0, come il
        // default del DB, quindi non viene toccata finche' nessuno incrementa.
        $sessionUser = Auth::user();
        if ($sessionUser && (int) $request->session()->get('admin_session_version', 0) !== (int) ($sessionUser->session_version ?? 0)) {
            Auth::logout();
            $request->session()->flush();

            return redirect(url($admin_path.'/login'))->with('message', trans('crudbooster.session_invalidated'));
        }
        if (CRUDBooster::isLocked()) {
            $url = url($admin_path.'/lock-screen');

            return redirect($url);
        }
        if($request->url()==CRUDBooster::adminPath('')){
            // Se arriviamo qui a seguito di un CRUDBooster::redirect(adminPath(), $msg)
            // (es. azione bloccata/accesso negato), il redirect verso la
            // dashboard qui sotto sostituisce la risposta e il messaggio flash
            // andrebbe perso prima di essere mai mostrato: lo si rimanda
            // avanti di una richiesta cosi' compare sulla pagina di dashboard.
            if ($request->session()->has('message')) {
                $request->session()->reflash();
            }
            $menus=DB::table('cms_menus')->whereRaw("cms_menus.id IN (select id_cms_menus from cms_menus_privileges where id_cms_privileges = '".CRUDBooster::myPrivilegeId()."')")->where('is_dashboard', 1)->where('is_active', 1)->first();
            if ($menus) {
                if ($menus->type == 'Statistic') {
                    return redirect()->action('\App\Http\Controllers\System\StatisticBuilderController@getDashboard');
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

    /**
     * Solo per richieste con ?embed=1: i redirect (e il redirect_url delle
     * risposte JSON di CRUDBooster::redirect() per le chiamate ajax) devono
     * restare in modalita' embed, altrimenti dopo un salvataggio l'iframe
     * mostrerebbe la pagina completa con sidebar/header. L'unica eccezione
     * sono login e lock-screen (sessione scaduta o schermo bloccato): li'
     * serve la pagina intera, non un login dentro il widget.
     */
    private function adaptResponseForEmbed($request, $response)
    {
        if ($response instanceof RedirectResponse) {
            $target = $response->getTargetUrl();

            if ($this->isLoginRedirect($target)) {
                return $this->breakOutOfFrame($target);
            }

            $response->setTargetUrl($this->withEmbed($request, $target));
        } elseif ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            if (is_array($data) && !empty($data['redirect_url']) && is_string($data['redirect_url'])) {
                if ($this->isLoginRedirect($data['redirect_url'])) {
                    return $this->breakOutOfFrame($data['redirect_url'], true);
                }

                $data['redirect_url'] = $this->withEmbed($request, $data['redirect_url']);
                $response->setData($data);
            }
        }

        return $response;
    }

    private function isLoginRedirect($url)
    {
        $admin_path = config('crudbooster.ADMIN_PATH') ?: 'admin';
        $path = rtrim((string) parse_url($url, PHP_URL_PATH), '/');

        return in_array($path, ['/'.$admin_path.'/login', '/'.$admin_path.'/lock-screen'], true);
    }

    /**
     * Pagina minima che porta la finestra INTERA (non l'iframe) su $url. Il
     * messaggio flash ("devi effettuare il login") resta in sessione e lo
     * consuma la richiesta di login vera, non questa.
     */
    private function breakOutOfFrame($url, $json = false)
    {
        if ($json) {
            return response()->json(['message' => trans('crudbooster.not_logged_in'), 'message_type' => 'warning', 'redirect_url' => $url, 'break_frame' => true]);
        }

        $js = json_encode($url);
        $href = e($url);

        return response('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
            .'<script>window.top.location.href = '.$js.';</script>'
            .'<noscript><a href="'.$href.'" target="_top">'.$href.'</a></noscript>'
            .'</body></html>');
    }

    private function withEmbed($request, $url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        // Solo URL dello stesso sito (path relativi inclusi): mai aggiungere
        // parametri a un redirect verso un host esterno.
        if ($host && $host !== $request->getHost()) {
            return $url;
        }

        if (preg_match('/[?&]embed=1(&|#|$)/', $url)) {
            return $url;
        }

        $parts = explode('#', $url, 2);
        $parts[0] .= (strpos($parts[0], '?') === false ? '?' : '&').'embed=1';

        return implode('#', $parts);
    }
}

<?php

namespace App\Http\Controllers\System;

use crocodicstudio\crudbooster\controllers\CBController;

use Illuminate\Support\Facades\Excel;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use \App\Helpers\UserHelper;
use \App\Helpers\ModuleHelper;
use \App\Helpers\CRUDBooster;
use \App\User;

class LogsController extends CBController
{
    public function cbInit()
    {
        $this->table = 'cms_logs';
        $this->primary_key = 'id';
        $this->title_field = "ipaddress";
        // I log non si modificano ne' si eliminano - button_edit era gia'
        // disattivato, aggiunto anche button_delete. button_bulk_action era
        // usato solo per l'azione "elimina selezionati" (nessuna altra
        // azione bulk definita in questo modulo), quindi disattivato anche
        // lui - altrimenti l'eliminazione restava comunque possibile da li'.
        $this->button_bulk_action = false;
        $this->button_export = false;
        $this->button_import = false;
        $this->button_add = false;
        $this->button_edit = false;
        $this->button_delete = false;
        // Con lo stile azioni di default, button_edit/button_delete=false
        // qui sopra venivano comunque ignorati per il superadmin
        // (ModuleHelper::can_edit()/can_delete() ritornano sempre true per
        // lui, a prescindere dal flag del modulo - vedi
        // docs/refactoring/089/091). button_icon_strict rispetta davvero il
        // flag, per chiunque - button_detail resta true (default, non
        // toccato: il dettaglio di un log e' comunque consultabile).
        $this->button_action_style = "button_icon_strict";

        // Lista (intervento 232): data leggibile, utente con avatar, tipo di
        // evento come etichetta tenue. Il filtro e l'ordinamento restano
        // quelli standard del modulo.
        $this->col = [];
        $this->col[] = ["label" => trans('crudbooster.adm_log_time'), "name" => "created_at", "callback" => function ($row) {
            return $row->created_at ? '<span style="white-space:nowrap">' . e(date('d/m/Y H:i', strtotime($row->created_at))) . '</span>' : '';
        }];
        $this->col[] = ["label" => "User", "name" => "id_cms_users", "join" => config('crudbooster.USER_TABLE').",name", "callback" => function ($row) {
            $name = (string) ($row->cms_users_name ?? '');
            if ($name === '') {
                return '';
            }
            return '<div class="d-flex align-items-center gap-2">' . self::avatarHtml($name, $row->cms_users_photo ?? null, $row->id_cms_users) . e($name) . '</div>';
        }];
        $this->col[] = ["label" => trans('crudbooster.adm_log_event'), "name" => "description", "callback" => function ($row) {
            return self::eventPill($row->description);
        }];
        $this->col[] = ["label" => "Description", "name" => "description"];
        $this->col[] = ["label" => "IP Address", "name" => "ipaddress"];

        $this->form = [];
        $this->form[] = ["label" => "Time Access", "name" => "created_at", "readonly" => true];
        $this->form[] = ["label" => "IP Address", "name" => "ipaddress", "readonly" => true];
        // Browser/sistema ricavati dallo user agent; la stringa originale
        // resta in coda, tra parentesi, per chi la vuole intera.
        $this->form[] = [
            "label" => "User Agent",
            "name" => "useragent",
            "readonly" => true,
            "callback_php" => '\\App\\Http\\Controllers\\System\\LogsController::fieldUserAgent($row ?? null)',
        ];
        $this->form[] = ["label" => "URL", "name" => "url", "readonly" => true];
        $this->form[] = [
            "label" => "User",
            "name" => "id_cms_users",
            "type" => "select",
            "datatable" => config('crudbooster.USER_TABLE').",name",
            "readonly" => true,
        ];
        $this->form[] = ["label" => "Description", "name" => "description", "readonly" => true];
        // "details" contiene la tabella HTML prodotta da displayDiff() e salvata
        // cosi' com'e': i log vecchi hanno valori non escapati, quindi in
        // visualizzazione si tengono solo i tag della tabella, senza attributi.
        $this->form[] = [
            "label" => "Details",
            "name" => "details",
            "type" => "custom",
            "callback_php" => '\\App\\Http\\Controllers\\System\\LogsController::fieldDetails($row ?? null)',
        ];
    }

    /**
     * Pagina di dettaglio come nel mockup: a sinistra i dati dell'evento,
     * a destra "Cosa e' cambiato". Stessi controlli di accesso del dettaglio
     * standard (CBController::getDetail).
     */
    public function getDetail($id)
    {
        $this->cbLoader();
        $row = DB::table($this->table)->where($this->primary_key, $id)->first();

        if (!$row || !ModuleHelper::can_view($this, $row)) {
            CRUDBooster::insertLog(trans("crudbooster.log_try_view", [
                'name' => $this->table,
                'module' => CRUDBooster::getCurrentModule()->name,
            ]));
            return CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }

        $module = CRUDBooster::getCurrentModule();
        $page_menu = Route::getCurrentRoute()->getActionName();
        $page_title = $module->name;
        $command = 'detail';
        Session::put('current_row_id', $id);

        $user = $row->id_cms_users ? DB::table('cms_users')->where('id', $row->id_cms_users)->first() : null;
        $diff = self::parseDiffRows((string) $row->details);
        $event = self::eventInfo($row->description);

        return view('logs.detail', compact('row', 'page_menu', 'page_title', 'command', 'id', 'user', 'diff', 'event'));
    }

    /** Valore del campo "User Agent" del dettaglio. $row puo' essere null: il form di modifica multipla dell'elenco non ha una riga. */
    public static function fieldUserAgent($row)
    {
        $ua = (string) ($row->useragent ?? '');
        $parsed = self::parseUserAgent($ua);

        return $parsed === '' ? $ua : $parsed . ' (' . $ua . ')';
    }

    /** Valore del campo "Details" del dettaglio, ripulito (vedi sanitizeDetails). */
    public static function fieldDetails($row)
    {
        return self::sanitizeDetails((string) ($row->details ?? ''));
    }

    /** Avatar tondo: la foto se c'e', altrimenti le iniziali (prime due parole del nome). */
    public static function avatarHtml($name, $photo, $user_id, $size = 32)
    {
        if (!empty($photo)) {
            return '<img width="' . $size . '" height="' . $size . '" style="border-radius:50%;object-fit:cover;flex:none" src="' . e(UserHelper::icon($user_id)) . '" alt="">';
        }
        $initials = '';
        foreach (array_slice(preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY), 0, 2) as $w) {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1));
        }

        return '<span style="display:inline-grid;place-items:center;flex:none;width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;font-weight:700;font-size:' . round($size * 0.38) . 'px;background:var(--ch-accent-soft);color:var(--ch-accent)">' . e($initials ?: '?') . '</span>';
    }

    /**
     * Tipo di evento ricavato dalla descrizione. La descrizione e' una frase
     * tradotta e salvata nella lingua di chi ha agito, quindi si confronta con
     * i modelli delle chiavi log_* di crudbooster in tutte le lingue
     * disponibili (en, it). Ritorna [chiave_tipo, classe_pill].
     */
    public static function eventInfo($description)
    {
        static $patterns = null;
        if ($patterns === null) {
            $patterns = [];
            foreach (['en', 'it'] as $locale) {
                $all = app('translator')->getLoader()->load($locale, 'crudbooster');
                if (!is_array($all)) {
                    continue;
                }
                foreach ($all as $key => $text) {
                    if (strpos($key, 'log_') !== 0 || !is_string($text)) {
                        continue;
                    }
                    $regex = '/^' . preg_replace('/\\\\:[a-z_]+/i', '.*', preg_quote($text, '/')) . '$/isu';
                    $patterns[] = [$regex, self::eventTypeForKey($key)];
                }
            }
            // i modelli piu' lunghi (piu' specifici) per primi
            usort($patterns, function ($a, $b) {
                return strlen($b[0]) - strlen($a[0]);
            });
        }

        foreach ($patterns as $p) {
            if (preg_match($p[0], (string) $description)) {
                return $p[1];
            }
        }

        return ['other', 'gray'];
    }

    private static function eventTypeForKey($key)
    {
        if (strpos($key, 'try_') !== false) {
            return ['denied', 'bad'];
        }
        if (strpos($key, 'logout') !== false) {
            return ['logout', 'gray'];
        }
        if (preg_match('/login|mfa_verified/', $key)) {
            return ['login', 'ok'];
        }
        if (strpos($key, 'delete') !== false) {
            return ['delete', 'warn'];
        }
        if (preg_match('/mfa|password|forgot|reset|recovery/', $key)) {
            return ['security', 'vio'];
        }
        if (preg_match('/(^|_)add(_|$)|created|imported/', $key)) {
            return ['create', 'ok'];
        }
        if (preg_match('/update|changed|updated/', $key)) {
            return ['update', 'blue'];
        }

        return ['other', 'gray'];
    }

    public static function eventLabel($type)
    {
        return trans('crudbooster.adm_ev_' . $type);
    }

    public static function eventPill($description)
    {
        list($type, $class) = self::eventInfo($description);

        return '<span class="ch-pill ch-pill-' . $class . '">' . e(self::eventLabel($type)) . '</span>';
    }

    /** "Chrome 141 - Windows 11" da uno user agent; stringa vuota se non riconosciuto. */
    public static function parseUserAgent($ua)
    {
        $browser = '';
        foreach ([
            'Edg' => 'Edge', 'OPR' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari',
        ] as $token => $name) {
            if (preg_match('/' . $token . '\/(\d+)/', $ua, $m)) {
                // Safari espone la versione in "Version/x"
                if ($name === 'Safari' && preg_match('/Version\/(\d+)/', $ua, $v)) {
                    $m[1] = $v[1];
                }
                $browser = $name . ' ' . $m[1];
                break;
            }
        }

        $os = '';
        foreach ([
            '/Windows NT 10/' => 'Windows', '/Windows/' => 'Windows', '/Android/' => 'Android',
            '/iPhone|iPad/' => 'iOS', '/Mac OS X/' => 'macOS', '/Linux/' => 'Linux',
        ] as $pattern => $name) {
            if (preg_match($pattern, $ua)) {
                $os = $name;
                break;
            }
        }

        return trim($browser . ($browser && $os ? ' - ' : '') . $os);
    }

    /** Lascia solo i tag di tabella (senza attributi) e rimette la classe Bootstrap. */
    public static function sanitizeDetails($html)
    {
        $clean = strip_tags($html, '<table><thead><tbody><tr><th><td>');
        $clean = preg_replace('/<(table|thead|tbody|tr|th|td)\b[^>]*>/i', '<$1>', $clean);

        return str_ireplace('<table>', '<table class="table table-striped">', $clean);
    }

    /**
     * Righe [campo, prima, dopo] dalla tabella salvata da displayDiff().
     * I testi sono gia' senza tag; si decodificano le entita' (nei log nuovi
     * sono escapate) cosi' la vista le puo' riescapare una sola volta.
     */
    public static function parseDiffRows($html)
    {
        $rows = [];
        $clean = self::sanitizeDetails($html);
        if (preg_match_all('/<tr>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>\s*<\/tr>/is', $clean, $m, PREG_SET_ORDER)) {
            foreach ($m as $r) {
                $rows[] = [html_entity_decode($r[1]), html_entity_decode($r[2]), html_entity_decode($r[3])];
            }
        }

        return $rows;
    }

    public static function displayDiff($old_values, $new_values)
    {
        $diff = self::getDiff($old_values, $new_values);
        // Chiavi e valori escapati: finiscono in HTML salvato nel log.
        $cell = function ($v) {
            return e(is_scalar($v) ? (string) $v : json_encode($v));
        };
        $table = '<table class="table table-striped"><thead><tr><th>Key</th><th>Old Value</th><th>New Value</th></thead><tbody>';
        foreach ($diff as $key => $value) {
            $old_key = isset($old_values[$key]) ? $old_values[$key] : '';
            $new_key = isset($new_values[$key]) ? $new_values[$key] : '';
            $table .= '<tr><td>' . e($key) . '</td><td>' . $cell($old_key) . '</td><td>' . $cell($new_key) . '</td></tr>';
        }
        $table .= '</tbody></table>';

        return $table;
    }

    private static function getDiff($old_values, $new_values)
    {
        unset($old_values['id']);
        unset($old_values['created_at']);
        unset($old_values['updated_at']);
        unset($new_values['created_at']);
        unset($new_values['updated_at']);

        return array_diff($old_values, $new_values);
    }

  	public function hook_query_index(&$query) {
  		if(UserHelper::isTenantAdmin())
  		{
        //Tenantadmin vede nella lista dei log solo quelli degli utenti del proprio tenant
        $tenant_id = UserHelper::current_user_tenant();
        $tenant_users = User::where('tenant',$tenant_id)->pluck('id');
  			$query->whereIn('id_cms_users',$tenant_users);
  		}
  	}
}

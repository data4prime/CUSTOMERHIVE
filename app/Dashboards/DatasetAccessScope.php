<?php

namespace App\Dashboards;

use App\Helpers\CRUDBooster;
use App\Helpers\ModuleHelper;
use App\Helpers\UserHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nucleo di sicurezza del widget Tabella "Elenco record" (vedi
 * docs/piano-widget-tabella-elenco-record.md, Fase 1): chi puo' leggere una
 * tabella e quali righe puo' vedere, indipendentemente dal modulo corrente
 * (nel widget e' `statistic_builder`, quindi CRUDBooster::isRead() non e'
 * utilizzabile).
 *
 * applyRowScope() contiene il blocco di scoping che prima viveva inline in
 * CBController::getModalData() (docs/refactoring/070): ora e' condiviso, e
 * getModalData() lo richiama senza cambiare le query generate.
 */
class DatasetAccessScope
{
    /**
     * L'utente corrente puo' leggere la tabella? Superadmin sempre (se la
     * tabella e' mg_*); gli altri solo se esiste almeno un modulo attivo e
     * non cancellato su quella tabella con is_read = 1 per il loro ruolo.
     */
    public static function canRead(string $table): bool
    {
        if (!ModuleHelper::is_manually_generated($table)) {
            return false;
        }
        if (CRUDBooster::isSuperadmin()) {
            return true;
        }

        $privilegeId = CRUDBooster::myPrivilegeId();
        if (!$privilegeId) {
            return false;
        }

        return DB::table('cms_moduls')
            ->join('cms_privileges_roles', 'cms_privileges_roles.id_cms_moduls', '=', 'cms_moduls.id')
            ->where('cms_moduls.table_name', $table)
            ->where('cms_moduls.is_active', 1)
            ->whereNull('cms_moduls.deleted_at')
            ->where('cms_privileges_roles.id_cms_privileges', $privilegeId)
            ->where('cms_privileges_roles.is_read', 1)
            ->exists();
    }

    /**
     * Esiste almeno un modulo attivo e non cancellato su questa tabella?
     * (indipendente dal ruolo: serve a escludere tabelle `mg_*` orfane, senza
     * modulo, anche per il superadmin).
     */
    public static function hasModule(string $table): bool
    {
        return DB::table('cms_moduls')
            ->where('table_name', $table)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Tabelle `mg_*` che hanno almeno un modulo attivo e non cancellato
     * (stessa regola di hasModule(), in una sola query per l'elenco dei
     * dataset offerti dal builder).
     *
     * @return string[]
     */
    public static function moduleTables(): array
    {
        return DB::table('cms_moduls')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->whereNotNull('table_name')
            ->pluck('table_name')
            ->filter(fn ($table) => ModuleHelper::is_manually_generated($table))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Restringe la query alle righe visibili all'utente corrente (tenant,
     * gruppi, righe create da tenant admin). Non tocca `deleted_at`: resta
     * responsabilita' del chiamante, come in getModalData().
     */
    public static function applyRowScope($query, string $table)
    {
        if (ModuleHelper::is_manually_generated($table) && !CRUDBooster::isSuperadmin()) {
            if (Schema::hasColumn($table, 'tenant')) {
                $query->where($table . '.tenant', UserHelper::current_user_tenant());
            }
            if (!UserHelper::isTenantAdmin()) {
                if (Schema::hasColumn($table, 'group')) {
                    $query->whereIn($table . '.group', UserHelper::current_user_groups());
                }
                // Stessa regola di ModuleHelper::can_view() (riga "se row e'
                // di tenant admin e current user non e' tenant admin"),
                // replicata a livello di query.
                if (Schema::hasColumn($table, 'created_by')) {
                    $query->whereNotIn($table . '.created_by', function ($q) {
                        $q->select('cms_users.id')
                            ->from('cms_users')
                            ->join('cms_privileges', 'cms_privileges.id', '=', 'cms_users.id_cms_privileges')
                            ->where('cms_privileges.is_tenantadmin', 1);
                    });
                }
            }
        }

        return $query;
    }
}

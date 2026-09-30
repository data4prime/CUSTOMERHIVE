<?php

namespace App\Services\QlikSync;

use App\Helpers\QlikHelper;
use Illuminate\Support\Facades\DB;

/**
 * Costruisce il driver giusto per una configurazione e per l'utente che
 * sincronizza. L'identita' verso Qlik e' quella dell'utente Qlik associato
 * (tabella qlik_users): senza associazione non si sincronizza.
 */
class QlikDriverFactory
{
    /**
     * L'utente ha un utente Qlik associato a questa configurazione?
     */
    public static function userHasQlikUser(int $userId, int $confId): bool
    {
        return DB::table('qlik_users')
            ->where('user_id', $userId)
            ->where('qlik_conf_id', $confId)
            ->exists();
    }

    /**
     * @throws QlikSyncException
     */
    public static function make(int $confId, int $userId): QlikDriver
    {
        $conf = DB::table('qlik_confs')->where('id', $confId)->first();
        if (! $conf) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_conf_not_found'));
        }

        if ($conf->auth !== 'JWT') {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_auth_unsupported', ['auth' => (string) $conf->auth]));
        }

        if (! self::userHasQlikUser($userId, $confId)) {
            throw new QlikSyncException(trans('crudbooster.qlik_sync_err_no_qlik_user'));
        }

        $isSaas = $conf->type === 'SAAS';

        try {
            $token = $isSaas
                ? QlikHelper::getJWTToken($userId, $confId)
                : QlikHelper::getJWTTokenOP($userId, $confId);
        } catch (\Throwable $e) {
            \Log::warning('Qlik sync: JWT non generato (conf '.$confId.'): '.get_class($e));
            $token = '';
        }

        if (! is_string($token) || $token === '') {
            throw new QlikSyncException(trans('crudbooster.qlik_jwt_generation_failed'));
        }

        return $isSaas ? new SaasQlikDriver($conf, $token) : new OnPremQlikDriver($conf, $token);
    }
}

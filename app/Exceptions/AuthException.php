<?php

namespace App\Exceptions;

use Exception;

/**
 * Eccezione lanciata quando il license server risponde ma nega
 * l'autenticazione (credenziali/licenza non valide) - distinta da un
 * server irraggiungibile (ConnectionException/RequestException), che va
 * gestito diversamente. Prima vissuta come LaravelReady\LicenseConnector\
 * Exceptions\AuthException, portata qui per togliere la dipendenza da
 * laravel-ready/license-connector (usata solo per questa classe e per il
 * trait CacheKeys, ora anch'esso inlineato in ConnectorService).
 */
final class AuthException extends Exception
{
}

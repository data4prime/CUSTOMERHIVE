<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * GET /health: controllo "sei vivo?" per l'orchestratore delle istanze Docker.
 *
 * 200 se l'app risponde e il DB e' raggiungibile, 503 altrimenti. Volutamente
 * fuori da sessione, CBBackend, EnforceModuleLicense e ADMIN_PATH, e senza
 * dettagli nel corpo (niente versione, host, messaggi d'errore): vedi
 * docs/refactoring/ (intervento sul Dockerfile di produzione).
 */
class HealthController
{
    public function __invoke(): JsonResponse
    {
        try {
            // Un DB irraggiungibile (non solo "rifiuta la connessione") non deve
            // tenere appesa la richiesta per il timeout di default di PDO.
            $connection = config('database.default');
            config(["database.connections.{$connection}.options" => array_replace(
                (array) config("database.connections.{$connection}.options", []),
                [\PDO::ATTR_TIMEOUT => 3]
            )]);
            DB::purge($connection);

            DB::connection($connection)->select('select 1');

            return $this->respond(['status' => 'ok'], 200);
        } catch (Throwable $e) {
            return $this->respond(['status' => 'unavailable'], 503);
        }
    }

    private function respond(array $body, int $status): JsonResponse
    {
        return response()->json($body, $status, ['Cache-Control' => 'no-store']);
    }
}

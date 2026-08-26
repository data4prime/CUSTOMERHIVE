<?php

namespace Tests\Concerns;

/**
 * Helper condiviso per simulare POST /admin/login nei test.
 *
 * Due dettagli non ovvi, entrambi necessari per riprodurre fedelmente il
 * comportamento reale:
 * - va passato come URL assoluto (non con un header 'Host' separato): con
 *   il routing dinamico di CRUDBooster (registra le route ad ogni
 *   richiesta), un semplice header Host senza URL assoluto produce un 404
 *   invece di raggiungere il controller.
 * - AdminController::postLogin() legge il dominio da $_SERVER['HTTP_HOST']
 *   DIRETTAMENTE (non da Request::getHost()) — il client di test di
 *   Laravel non sincronizza quella superglobale con l'URL simulato, va
 *   quindi impostata a mano. In produzione (richiesta HTTP reale via
 *   Apache) non serve, $_SERVER è sempre popolato correttamente: è solo un
 *   problema di testabilità di questo pezzo di codice, da tenere presente
 *   per il futuro refactoring dell'auth.
 */
trait LogsInAdmin
{
    protected function postLoginFrom(?string $host, array $data)
    {
        $host = $host ?: parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTP_HOST'] = $host;

        try {
            return $this->post("http://{$host}/admin/login", $data);
        } finally {
            if ($previousHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
        }
    }
}

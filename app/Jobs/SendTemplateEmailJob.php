<?php

namespace App\Jobs;

use CRUDBooster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Una email da template (CRUDBooster::sendEmail) inviata fuori dalla richiesta
 * o dal comando che la genera. Usato dagli avvisi di scadenza utente: un SMTP
 * lento o un destinatario che fallisce non blocca piu' gli altri invii.
 *
 * Connessione/coda: config('queue.mail_connection') (default 'sync' = invio
 * immediato come prima, nessun worker necessario) e coda 'mail'.
 */
class SendTemplateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 300;

    /** @var array Config per CRUDBooster::sendEmail (to, data, template, content_replace). */
    public $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Accoda (o, con connessione 'sync', invia subito) una email da template.
     */
    public static function enqueue(array $config): void
    {
        static::dispatch($config)
            ->onConnection(config('queue.mail_connection'))
            ->onQueue('mail');
    }

    public function handle(): void
    {
        CRUDBooster::sendEmail($this->config);
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('Email "'.($this->config['template'] ?? '?').'" a '.json_encode($this->config['to'] ?? null).' fallita: '.get_class($e).': '.$e->getMessage());
    }
}

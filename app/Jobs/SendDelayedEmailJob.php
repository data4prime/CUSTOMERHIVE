<?php

namespace App\Jobs;

use App\Helpers\MailSettings;
use CRUDBooster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Email ritardata: CRUDBooster::sendEmail() con l'opzione 'send_at' la mette
 * in coda con un delay invece di inviarla subito. Il contenuto e' gia'
 * composto dal template al momento dell'accodamento. Sostituisce la vecchia
 * tabella cms_email_queues + comando mailqueues.
 */
class SendDelayedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 300;

    /** @var array */
    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        // Disattivata dai Settings nel frattempo: come in sendEmail(), niente invio.
        if (! CRUDBooster::isEmailEnabled()) {
            return;
        }

        MailSettings::fromSettings();

        $p = $this->payload;

        Mail::send('crudbooster::emails.blank', ['content' => $p['content']], function ($message) use ($p) {
            $message->priority(1);
            $message->to($p['to']);

            if (! empty($p['from_email'])) {
                $message->from($p['from_email'], $p['from_name'] ?? null);
            }

            if (! empty($p['cc_email'])) {
                $message->cc($p['cc_email']);
            }

            foreach ($p['attachments'] ?? [] as $attachment) {
                $message->attach($attachment);
            }

            $message->subject($p['subject']);
        });
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('Email ritardata a '.json_encode($this->payload['to'] ?? null).' fallita: '.get_class($e).': '.$e->getMessage());
    }
}

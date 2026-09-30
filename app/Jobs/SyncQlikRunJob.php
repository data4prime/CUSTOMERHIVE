<?php

namespace App\Jobs;

use App\Services\QlikSync\QlikSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Un passo di una sincronizzazione Qlik (app, oppure gli item di una app).
 * Al termine accoda il passo successivo, cosi' i job restano brevi e si puo'
 * annullare tra un passo e l'altro. Vedi docs/piano-qlik-sync-app-items.md.
 */
class SyncQlikRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 3600;

    /** @var int */
    public $runId;

    /** @var string */
    public $stage;

    /** @var int */
    public $cursor;

    public function __construct(int $runId, string $stage, int $cursor = 0)
    {
        $this->runId = $runId;
        $this->stage = $stage;
        $this->cursor = $cursor;
    }

    public function handle(): void
    {
        $next = QlikSyncService::step($this->runId, $this->stage, $this->cursor);

        if ($next !== null) {
            QlikSyncService::dispatchStep($this->runId, $next[0], $next[1]);
        }
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('Qlik sync run '.$this->runId.': job fallito: '.get_class($e).': '.$e->getMessage());
        QlikSyncService::markFailed($this->runId, trans('crudbooster.qlik_sync_err_internal'));
    }
}

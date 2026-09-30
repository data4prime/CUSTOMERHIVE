<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Cosa ha fatto una sincronizzazione su un singolo record (app o item):
 * serve al report e al rollback.
 */
class QlikSyncRunRecord extends Model
{
    protected $table = 'qlik_sync_run_records';

    protected $guarded = [];

    const ACTION_CREATED = 'created';
    const ACTION_LINKED = 'linked';
    const ACTION_UPDATED = 'updated';
    const ACTION_SKIPPED = 'skipped';
    const ACTION_FAILED = 'failed';
}

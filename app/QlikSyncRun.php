<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Una sincronizzazione di app o item Qlik (docs/piano-qlik-sync-app-items.md).
 */
class QlikSyncRun extends Model
{
    protected $table = 'qlik_sync_runs';

    protected $guarded = [];

    protected $casts = [
        'cancel_requested' => 'boolean',
        'cancel_delete' => 'boolean',
        'selected_app_ids' => 'array',
        'selected_sheet_ids' => 'array',
    ];

    const TYPE_APPS = 'apps';
    const TYPE_ITEMS = 'items';

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_CANCELLING = 'cancelling';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /** Stati in cui il run non e' ancora concluso. */
    public static function activeStatuses(): array
    {
        return [self::STATUS_QUEUED, self::STATUS_RUNNING, self::STATUS_CANCELLING];
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::activeStatuses(), true);
    }

    public function records()
    {
        return $this->hasMany(QlikSyncRunRecord::class, 'run_id');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sincronizzazione app/item Qlik (docs/piano-qlik-sync-app-items.md): solo
 * modifiche additive e nullable. App e item esistenti restano invariati.
 */
class AddSyncColumnsAndCreateSyncTables extends Migration
{
    public function up()
    {
        Schema::table('qlik_apps', function (Blueprint $table) {
            $table->boolean('is_missing')->default(false)->after('appid');
            $table->timestamp('last_synced_at')->nullable()->after('is_missing');
            // Non unico: dati legacy potrebbero avere duplicati (conf, appid).
            $table->index(['conf', 'appid'], 'qlik_apps_conf_appid_index');
        });

        Schema::table('qlik_items', function (Blueprint $table) {
            $table->unsignedBigInteger('qlik_app_id')->nullable()->after('qlik_conf');
            $table->string('item_type', 50)->default('sheet')->after('qlik_app_id');
            $table->string('external_id', 191)->nullable()->after('item_type');
            $table->boolean('is_missing')->default(false)->after('external_id');
            $table->timestamp('last_synced_at')->nullable()->after('is_missing');
            $table->index(['qlik_conf', 'qlik_app_id', 'external_id'], 'qlik_items_sync_key_index');
        });

        Schema::create('qlik_sync_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 20); // apps | items
            $table->unsignedBigInteger('qlik_conf_id');
            $table->unsignedBigInteger('qlik_app_id')->nullable(); // solo se scelta una singola app
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('tenant_id'); // snapshot di quello assegnato ai record
            $table->unsignedInteger('group_id'); // snapshot del gruppo assegnato ai record
            $table->string('status', 20)->default('queued'); // queued|running|cancelling|cancelled|completed|failed
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('linked')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('missing')->default(0);
            $table->text('error')->nullable();
            $table->boolean('cancel_requested')->default(false);
            $table->boolean('cancel_delete')->default(false); // annulla eliminando i record creati
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'status']);
        });

        Schema::create('qlik_sync_run_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('run_id');
            $table->string('record_type', 10); // app | item
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('external_id', 191)->nullable(); // appid / id foglio, per i record falliti
            $table->string('action', 10); // created|linked|updated|skipped|failed
            $table->unsignedInteger('added_tenant_id')->nullable();
            $table->unsignedInteger('added_group_id')->nullable();
            $table->string('message', 500)->nullable();
            $table->timestamps();
            $table->index(['run_id', 'record_type']);
            $table->foreign('run_id')->references('id')->on('qlik_sync_runs')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('qlik_sync_run_records');
        Schema::dropIfExists('qlik_sync_runs');

        Schema::table('qlik_items', function (Blueprint $table) {
            $table->dropIndex('qlik_items_sync_key_index');
            $table->dropColumn(['qlik_app_id', 'item_type', 'external_id', 'is_missing', 'last_synced_at']);
        });

        Schema::table('qlik_apps', function (Blueprint $table) {
            $table->dropIndex('qlik_apps_conf_appid_index');
            $table->dropColumn(['is_missing', 'last_synced_at']);
        });
    }
}

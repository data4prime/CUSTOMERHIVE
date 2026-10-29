<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Autorizzazioni delle conf ChatAI (gruppi e tenant) in tabelle dedicate,
 * separate da items_allowed / tenants_allowed che appartengono ai Qlik items
 * (piano 318). Stesse colonne di audit delle tabelle Qlik, nessuna FK.
 */
return new class () extends Migration {
    public function up()
    {
        Schema::create('chatai_groups_allowed', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chatai_conf_id');
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->unique(['chatai_conf_id', 'group_id'], 'chatai_groups_allowed_unique');
        });

        Schema::create('chatai_tenants_allowed', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chatai_conf_id');
            $table->unsignedInteger('tenant_id');
            $table->unsignedInteger('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->unique(['chatai_conf_id', 'tenant_id'], 'chatai_tenants_allowed_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chatai_tenants_allowed');
        Schema::dropIfExists('chatai_groups_allowed');
    }
};

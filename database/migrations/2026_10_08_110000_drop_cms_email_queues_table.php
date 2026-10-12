<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le email ritardate (sendEmail con 'send_at') ora usano un job in coda con
 * delay (App\Jobs\SendDelayedEmailJob): la tabella cms_email_queues e il
 * comando mailqueues non servono piu'. Vedi docs/refactoring/289-*.
 */
class DropCmsEmailQueuesTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('cms_email_queues');
    }

    public function down()
    {
        if (! Schema::hasTable('cms_email_queues')) {
            Schema::create('cms_email_queues', function (Blueprint $table) {
                $table->increments('id');

                $table->dateTime('send_at')->nullable();
                $table->string('email_recipient')->nullable();
                $table->string('email_from_email')->nullable();
                $table->string('email_from_name')->nullable();
                $table->string('email_cc_email')->nullable();
                $table->string('email_subject')->nullable();
                $table->text('email_content')->nullable();
                $table->text('email_attachments')->nullable();
                $table->boolean('is_sent')->nullable();

                $table->timestamps();
            });
        }
    }
}

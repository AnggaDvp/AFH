<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index();
            $table->string('recipient', 20);
            $table->string('message_type', 30)->default('text');
            $table->string('reference_type', 50);
            $table->string('reference_id', 64);
            $table->string('idempotency_key', 64)->unique();
            $table->string('status', 20)->default('queued')->index();
            $table->string('provider_message_id', 150)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id'], 'whatsapp_reference_idx');
            $table->index(['recipient', 'status', 'created_at'], 'whatsapp_recipient_status_idx');
        });

        Schema::create('whatsapp_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_jobs');
        Schema::dropIfExists('whatsapp_notifications');
    }
};

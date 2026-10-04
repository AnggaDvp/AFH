<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notifications', function (Blueprint $table) {
            $table->string('template_name', 100)->nullable()->after('message_type');
            $table->json('payload')->nullable()->after('reference_id');
            $table->foreignId('customer_id')->nullable()->after('payload')
                ->constrained('customers')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->after('customer_id')
                ->constrained('chat_conversations')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->after('conversation_id')
                ->constrained('credit_applications')->nullOnDelete();
            $table->foreignId('motor_product_id')->nullable()->after('application_id')
                ->constrained('motor_products')->nullOnDelete();
        });

        $this->makeReferencesNullable();
    }

    public function down(): void
    {
        Schema::table('whatsapp_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('motor_product_id');
            $table->dropConstrainedForeignId('application_id');
            $table->dropConstrainedForeignId('conversation_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['payload', 'template_name']);
        });
    }

    private function makeReferencesNullable(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                'ALTER TABLE whatsapp_notifications '
                . 'MODIFY COLUMN reference_type VARCHAR(50) NULL, '
                . 'MODIFY COLUMN reference_id VARCHAR(64) NULL'
            );

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(
                'ALTER TABLE whatsapp_notifications '
                . 'ALTER COLUMN reference_type DROP NOT NULL, '
                . 'ALTER COLUMN reference_id DROP NOT NULL'
            );

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        $columns = collect(DB::select("PRAGMA table_info('whatsapp_notifications')"))
            ->keyBy('name');
        if ((int) $columns['reference_type']->notnull === 0
            && (int) $columns['reference_id']->notnull === 0) {
            return;
        }

        Schema::create('whatsapp_notifications_nullable', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->string('recipient', 20);
            $table->string('message_type', 30)->default('text');
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 64)->nullable();
            $table->string('idempotency_key', 64);
            $table->string('status', 20)->default('queued');
            $table->string('provider_message_id', 150)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->string('template_name', 100)->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('chat_conversations')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('credit_applications')->nullOnDelete();
            $table->foreignId('motor_product_id')->nullable()->constrained('motor_products')->nullOnDelete();
        });

        $columnNames = [
            'id',
            'event_type',
            'recipient',
            'message_type',
            'reference_type',
            'reference_id',
            'idempotency_key',
            'status',
            'provider_message_id',
            'error_message',
            'attempts',
            'sent_at',
            'created_at',
            'updated_at',
            'template_name',
            'payload',
            'customer_id',
            'conversation_id',
            'application_id',
            'motor_product_id',
        ];
        $columnsSql = implode(', ', array_map(fn (string $column) => '"' . $column . '"', $columnNames));
        DB::statement(
            "INSERT INTO whatsapp_notifications_nullable ({$columnsSql}) "
            . "SELECT {$columnsSql} FROM whatsapp_notifications"
        );

        Schema::drop('whatsapp_notifications');
        Schema::rename('whatsapp_notifications_nullable', 'whatsapp_notifications');
        Schema::table('whatsapp_notifications', function (Blueprint $table) {
            $table->unique('idempotency_key');
            $table->index(['reference_type', 'reference_id'], 'whatsapp_reference_idx');
            $table->index(
                ['recipient', 'status', 'created_at'],
                'whatsapp_recipient_status_idx'
            );
        });
    }
};

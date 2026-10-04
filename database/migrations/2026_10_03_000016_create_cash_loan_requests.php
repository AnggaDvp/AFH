<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_loan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('conversation_id')->nullable()
                ->constrained('chat_conversations')->nullOnDelete();
            $table->string('submission_key', 36);
            $table->string('nopol', 20);
            $table->string('motor_brand', 20);
            $table->string('stnk_name_type', 30);
            $table->string('house_ownership', 30);
            $table->string('status', 30)->default('submitted')->index();
            $table->timestamps();
            $table->unique(['customer_id', 'submission_key'], 'cash_loan_request_submission_unique');
            $table->index(['customer_id', 'created_at'], 'cash_loan_request_customer_created_idx');
        });

        $this->makeMessageSenderNullable();
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_loan_requests');
    }

    private function makeMessageSenderNullable(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE chat_messages MODIFY sender_id BIGINT UNSIGNED NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE chat_messages ALTER COLUMN sender_id DROP NOT NULL');

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        $senderColumn = collect(DB::select("PRAGMA table_info('chat_messages')"))
            ->firstWhere('name', 'sender_id');
        if (!$senderColumn || (int) $senderColumn->notnull === 0) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        try {
            DB::statement('CREATE TABLE chat_messages_rebuilt (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                chat_conversation_id INTEGER NOT NULL,
                sender_type VARCHAR(20) NOT NULL,
                sender_id INTEGER NULL,
                body TEXT NULL,
                read_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(chat_conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
            )');
            DB::statement('INSERT INTO chat_messages_rebuilt
                (id, chat_conversation_id, sender_type, sender_id, body, read_at, created_at, updated_at)
                SELECT id, chat_conversation_id, sender_type, sender_id, body, read_at, created_at, updated_at
                FROM chat_messages');
            Schema::drop('chat_messages');
            Schema::rename('chat_messages_rebuilt', 'chat_messages');
            DB::statement('CREATE INDEX chat_message_unread_lookup ON chat_messages
                (chat_conversation_id, sender_type, read_at)');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};

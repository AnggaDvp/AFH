<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->changeBodyNullability(true);
    }

    public function down(): void
    {
        // Keep rollback safe if an attachment-only message already exists.
        DB::table('chat_messages')
            ->whereNull('body')
            ->update(['body' => '']);
        $this->changeBodyNullability(false);
    }

    private function changeBodyNullability(bool $nullable): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE chat_messages MODIFY body TEXT ' . ($nullable ? 'NULL' : 'NOT NULL'));
            return;
        }

        // SQLite 3.33 (the bundled test runtime) cannot change nullability in place.
        Schema::disableForeignKeyConstraints();
        try {
            DB::statement('CREATE TABLE chat_messages_rebuilt (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                chat_conversation_id INTEGER NOT NULL,
                sender_type VARCHAR(20) NOT NULL,
                sender_id INTEGER NOT NULL,
                body TEXT ' . ($nullable ? 'NULL' : 'NOT NULL') . ',
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

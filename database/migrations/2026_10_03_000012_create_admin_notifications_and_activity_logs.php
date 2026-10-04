<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_notifications')) {
            Schema::create('admin_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50)->index();
                $table->string('title', 180);
                $table->text('message');
                $table->json('data')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->foreignId('recipient_admin_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->index(['recipient_admin_id', 'read_at', 'created_at'], 'admin_notif_recipient_read_idx');
            });
        }
        $this->ensureIndex('admin_notifications', 'admin_notif_recipient_read_idx', [
            'recipient_admin_id', 'read_at', 'created_at',
        ]);

        if (!Schema::hasTable('admin_activity_logs')) {
            Schema::create('admin_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100)->index();
                $table->string('entity_type', 100)->nullable();
                $table->string('entity_id', 64)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['entity_type', 'entity_id'], 'admin_activity_entity_idx');
                $table->index(['admin_id', 'created_at'], 'admin_activity_actor_time_idx');
            });
        }
        $this->ensureIndex('admin_activity_logs', 'admin_activity_entity_idx', ['entity_type', 'entity_id']);
        $this->ensureIndex('admin_activity_logs', 'admin_activity_actor_time_idx', ['admin_id', 'created_at']);
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('admin_notifications');
    }

    private function ensureIndex(string $tableName, string $indexName, array $columns): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = collect(DB::select("PRAGMA index_list('{$tableName}')"))->pluck('name')->all();
        } else {
            $indexes = collect(DB::select("SHOW INDEX FROM `{$tableName}`"))->pluck('Key_name')->all();
        }

        if (!in_array($indexName, $indexes, true)) {
            Schema::table($tableName, fn (Blueprint $table) => $table->index($columns, $indexName));
        }
    }
};

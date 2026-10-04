<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_applications')) {
            Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 40)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('motor_product_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('brochure_item_id')->nullable()->constrained('motor_brochure_items')->nullOnDelete();
            $table->decimal('cash_price', 15, 2)->nullable();
            $table->decimal('down_payment', 15, 2)->nullable();
            $table->unsignedSmallInteger('tenor_months')->nullable();
            $table->decimal('installment_amount', 15, 2)->nullable();
            $table->string('installment_source', 100)->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->string('source', 100)->nullable()->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('dana_tunai_applications')) {
            Schema::create('dana_tunai_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 40)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('chat_conversation_id')->nullable()
                ->constrained('chat_conversations')->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->string('source', 100)->nullable()->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('application_status_histories')) {
            Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_application_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();
                $table->index(['credit_application_id', 'created_at'], 'credit_app_status_hist_idx');
            });
        }
        $this->ensureIndex('application_status_histories', 'credit_app_status_hist_idx', ['credit_application_id', 'created_at']);

        if (!Schema::hasTable('dana_tunai_status_histories')) {
            Schema::create('dana_tunai_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dana_tunai_application_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();
                $table->index(['dana_tunai_application_id', 'created_at'], 'dana_status_hist_idx');
            });
        }
        $this->ensureIndex('dana_tunai_status_histories', 'dana_status_hist_idx', ['dana_tunai_application_id', 'created_at']);

        if (!Schema::hasTable('customer_documents')) {
            Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('credit_application_id')->nullable()
                ->constrained('credit_applications')->restrictOnDelete();
            $table->foreignId('dana_tunai_application_id')->nullable()
                ->constrained('dana_tunai_applications')->restrictOnDelete();
            $table->string('document_type', 50);
            $table->string('original_name', 200);
            $table->string('disk', 50)->default('chat_private');
            $table->string('path', 255);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->string('status', 30)->default('uploaded')->index();
            $table->timestamp('uploaded_at')->nullable()->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dana_tunai_status_histories');
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('dana_tunai_applications');
        Schema::dropIfExists('credit_applications');
    }

    private function ensureIndex(string $tableName, string $indexName, array $columns): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = collect(DB::select("PRAGMA index_list('{$tableName}')"))
                ->pluck('name')->all();
        } else {
            $indexes = collect(DB::select("SHOW INDEX FROM `{$tableName}`"))
                ->pluck('Key_name')->all();
        }

        if (!in_array($indexName, $indexes, true)) {
            Schema::table($tableName, fn (Blueprint $table) => $table->index($columns, $indexName));
        }
    }
};

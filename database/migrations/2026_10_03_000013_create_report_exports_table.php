<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_type', 50);
            $table->json('filters')->nullable();
            $table->string('file_name', 255);
            $table->unsignedBigInteger('row_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['admin_id', 'created_at'], 'report_exports_admin_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};

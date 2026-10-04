<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('disk', 50);
            $table->string('mime_type', 150);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size');
            $table->string('attachment_type', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_attachments');
    }
};

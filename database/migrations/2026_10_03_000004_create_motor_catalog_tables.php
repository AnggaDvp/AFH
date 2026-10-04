<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motor_products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->string('family', 50)->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('motor_brochures', function (Blueprint $table) {
            $table->id();
            $table->string('period_label', 100)->unique();
            $table->string('source_name', 150);
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('motor_brochure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brochure_id')->constrained('motor_brochures')->cascadeOnDelete();
            $table->foreignId('motor_product_id')->constrained('motor_products')->restrictOnDelete();
            $table->unsignedBigInteger('cash_price');
            $table->timestamps();
            $table->unique(['brochure_id', 'motor_product_id']);
            $table->index(['motor_product_id', 'brochure_id']);
        });

        Schema::create('motor_financing_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brochure_item_id')->constrained('motor_brochure_items')->cascadeOnDelete();
            $table->string('down_payment_source_value', 50);
            $table->string('tenor_code', 10);
            $table->string('tenor_label', 15);
            $table->string('installment_source_value', 50)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['brochure_item_id', 'down_payment_source_value', 'tenor_code'], 'motor_financing_option_identity');
            $table->index(['brochure_item_id', 'tenor_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motor_financing_options');
        Schema::dropIfExists('motor_brochure_items');
        Schema::dropIfExists('motor_brochures');
        Schema::dropIfExists('motor_products');
    }
};

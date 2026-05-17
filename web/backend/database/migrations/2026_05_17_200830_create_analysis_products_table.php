<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analysis_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('weight_g', 8, 1)->default(100);

            $table->unsignedInteger('detected_count')->nullable();
            $table->decimal('max_confidence', 6, 4)->nullable();

            $table->decimal('kcal_per_100g', 8, 2)->nullable();
            $table->decimal('protein_per_100g', 8, 2)->nullable();
            $table->decimal('fat_per_100g', 8, 2)->nullable();
            $table->decimal('carbs_per_100g', 8, 2)->nullable();

            $table->decimal('total_kcal', 10, 2)->default(0);
            $table->decimal('total_protein', 10, 2)->default(0);
            $table->decimal('total_fat', 10, 2)->default(0);
            $table->decimal('total_carbs', 10, 2)->default(0);

            $table->timestamps();

            $table->unique(['analysis_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_products');
    }
};
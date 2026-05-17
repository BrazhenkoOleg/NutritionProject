<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::table('analyses', function (Blueprint $table) {
            $table->unsignedInteger('detections_count')->default(0);
            $table->unsignedInteger('products_count')->default(0);
            $table->json('detections')->nullable();
            $table->json('products')->nullable();
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn([
                'detections_count',
                'products_count',
                'detections',
                'products',
                'note',
            ]);
        });
    }
};

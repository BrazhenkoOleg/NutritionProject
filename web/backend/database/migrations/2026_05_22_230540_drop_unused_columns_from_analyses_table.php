<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            if (Schema::hasColumn('analyses', 'products')) {
                $table->dropColumn('products');
            }

            if (Schema::hasColumn('analyses', 'note')) {
                $table->dropColumn('note');
            }

            if (Schema::hasColumn('analyses', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            if (!Schema::hasColumn('analyses', 'products')) {
                $table->json('products')->nullable();
            }

            if (!Schema::hasColumn('analyses', 'note')) {
                $table->text('note')->nullable();
            }

            if (!Schema::hasColumn('analyses', 'image_path')) {
                $table->string('image_path')->nullable();
            }
        });
    }
};
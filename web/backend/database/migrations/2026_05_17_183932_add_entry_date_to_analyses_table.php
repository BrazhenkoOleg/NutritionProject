<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->date('entry_date')
                ->nullable()
                ->after('meal_type');
        });

        DB::statement('UPDATE analyses SET entry_date = DATE(created_at) WHERE entry_date IS NULL');

        Schema::table('analyses', function (Blueprint $table) {
            $table->date('entry_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn('entry_date');
        });
    }
};
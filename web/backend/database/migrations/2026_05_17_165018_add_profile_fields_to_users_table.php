<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('password');
            $table->unsignedTinyInteger('age')->nullable()->after('gender');

            $table->decimal('height_cm', 5, 1)->nullable()->after('age');
            $table->decimal('weight_kg', 5, 1)->nullable()->after('height_cm');

            $table->string('activity_level')->nullable()->after('weight_kg');
            $table->string('goal')->nullable()->after('activity_level');

            $table->unsignedInteger('daily_kcal_goal')->nullable()->after('goal');
            $table->decimal('daily_protein_goal', 8, 2)->nullable()->after('daily_kcal_goal');
            $table->decimal('daily_fat_goal', 8, 2)->nullable()->after('daily_protein_goal');
            $table->decimal('daily_carbs_goal', 8, 2)->nullable()->after('daily_fat_goal');

            $table->boolean('profile_completed')->default(false)->after('daily_carbs_goal');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'age',
                'height_cm',
                'weight_kg',
                'activity_level',
                'goal',
                'daily_kcal_goal',
                'daily_protein_goal',
                'daily_fat_goal',
                'daily_carbs_goal',
                'profile_completed',
            ]);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE analyses ALTER COLUMN image_path DROP NOT NULL');
        DB::statement('ALTER TABLE analyses ALTER COLUMN image_url DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE analyses SET image_path = '' WHERE image_path IS NULL");
        DB::statement("UPDATE analyses SET image_url = '' WHERE image_url IS NULL");

        DB::statement('ALTER TABLE analyses ALTER COLUMN image_path SET NOT NULL');
        DB::statement('ALTER TABLE analyses ALTER COLUMN image_url SET NOT NULL');
    }
};
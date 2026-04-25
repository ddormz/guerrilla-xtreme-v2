<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand event_type column to 20 chars to support 'torneo_ranking' (15 chars)
        Schema::table('league_events', function (Blueprint $table) {
            $table->string('event_type', 20)->default('liga')->change();
        });

        // Create site_settings table for module visibility toggles
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default module visibility settings
        DB::table('site_settings')->insert([
            ['key' => 'module_rifas_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'module_torneos_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'module_liga_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'module_ranking_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('league_events', function (Blueprint $table) {
            $table->string('event_type', 10)->default('liga')->change();
        });

        Schema::dropIfExists('site_settings');
    }
};

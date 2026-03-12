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
        Schema::table('league_events', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->string('location')->nullable();
            $table->string('time')->nullable();
            $table->text('prizes')->nullable();
            $table->decimal('registration_cost', 10, 2)->default(0);
            $table->boolean('show_on_index')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('league_events', function (Blueprint $table) {
            $table->dropColumn(['description', 'rules', 'location', 'time', 'prizes', 'registration_cost', 'show_on_index']);
        });
    }
};

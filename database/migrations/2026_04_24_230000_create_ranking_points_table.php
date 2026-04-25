<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('league_players')->cascadeOnDelete();
            $table->integer('points_for')->default(0)->comment('Total points scored');
            $table->integer('points_against')->default(0)->comment('Total points conceded');
            $table->integer('differential')->default(0)->comment('points_for - points_against');
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('xtremes')->default(0);
            $table->unsignedInteger('matches_played')->default(0);
            $table->timestamps();

            $table->unique('player_id');
            $table->index('differential');
            $table->index('xtremes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_points');
    }
};

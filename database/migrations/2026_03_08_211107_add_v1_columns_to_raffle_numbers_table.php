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
        Schema::table('raffle_numbers', function (Blueprint $table) {
            $table->string('proof_path', 255)->nullable()->after('email');
            $table->string('winner_photo', 255)->nullable()->after('proof_path');
            $table->unsignedInteger('prize_position')->nullable()->after('winner_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raffle_numbers', function (Blueprint $table) {
            $table->dropColumn(['proof_path', 'winner_photo', 'prize_position']);
        });
    }
};

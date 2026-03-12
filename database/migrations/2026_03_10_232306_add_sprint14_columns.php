<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // League Events
        if (!Schema::hasColumn('league_events', 'flyer_image_path')) {
            Schema::table('league_events', function (Blueprint $table) {
                $table->string('flyer_image_path', 500)->nullable()->after('event_type');
            });
        }
        if (!Schema::hasColumn('league_events', 'matches_locked')) {
            Schema::table('league_events', function (Blueprint $table) {
                $table->boolean('matches_locked')->default(false)->after('is_live');
            });
        }

        // League Matches
        if (!Schema::hasColumn('league_matches', 'is_recovery')) {
            Schema::table('league_matches', function (Blueprint $table) {
                $table->boolean('is_recovery')->default(false)->after('game_no');
            });
        }
        if (!Schema::hasColumn('league_matches', 'recovery_event_id')) {
            Schema::table('league_matches', function (Blueprint $table) {
                $table->unsignedBigInteger('recovery_event_id')->nullable()->after('is_recovery');
                $table->foreign('recovery_event_id')->references('id')->on('league_events')->nullOnDelete();
            });
        }

        // Raffles
        if (!Schema::hasColumn('raffles', 'rules')) {
            Schema::table('raffles', function (Blueprint $table) {
                $table->text('rules')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('league_matches', 'recovery_event_id')) {
            Schema::table('league_matches', function (Blueprint $table) {
                $table->dropForeign(['recovery_event_id']);
                $table->dropColumn(['recovery_event_id']);
            });
        }
        if (Schema::hasColumn('league_matches', 'is_recovery')) {
            Schema::table('league_matches', function (Blueprint $table) {
                $table->dropColumn('is_recovery');
            });
        }
        if (Schema::hasColumn('league_events', 'matches_locked')) {
            Schema::table('league_events', function (Blueprint $table) {
                $table->dropColumn('matches_locked');
            });
        }
        if (Schema::hasColumn('league_events', 'flyer_image_path')) {
            Schema::table('league_events', function (Blueprint $table) {
                $table->dropColumn('flyer_image_path');
            });
        }
        if (Schema::hasColumn('raffles', 'rules')) {
            Schema::table('raffles', function (Blueprint $table) {
                $table->dropColumn('rules');
            });
        }
    }
};

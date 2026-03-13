<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_attendance', function (Blueprint $table) {
            $table->index(['event_id', 'present', 'paid'], 'league_attendance_event_present_paid_idx');
            $table->index(['player_id', 'present', 'paid'], 'league_attendance_player_present_paid_idx');
        });

        Schema::table('league_events', function (Blueprint $table) {
            $table->index(['season_id', 'event_type', 'event_date'], 'league_events_season_type_date_idx');
            $table->index(['show_on_index', 'event_date'], 'league_events_show_on_index_date_idx');
        });

        Schema::table('league_matches', function (Blueprint $table) {
            $table->index(['referee_user_id', 'concluded'], 'league_matches_referee_concluded_idx');
            $table->index(['event_id', 'concluded'], 'league_matches_event_concluded_idx');
        });
    }

    public function down(): void
    {
        Schema::table('league_matches', function (Blueprint $table) {
            $table->dropIndex('league_matches_referee_concluded_idx');
            $table->dropIndex('league_matches_event_concluded_idx');
        });

        Schema::table('league_events', function (Blueprint $table) {
            $table->dropIndex('league_events_season_type_date_idx');
            $table->dropIndex('league_events_show_on_index_date_idx');
        });

        Schema::table('league_attendance', function (Blueprint $table) {
            $table->dropIndex('league_attendance_event_present_paid_idx');
            $table->dropIndex('league_attendance_player_present_paid_idx');
        });
    }
};

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
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->index(['event_id', 'status'], 'tr_event_status_idx');
            $table->index(['event_id', 'blader_name'], 'tr_event_blader_idx');
            $table->index(['event_id', 'email'], 'tr_event_email_idx');
            $table->index(['event_id', 'generated_user_id'], 'tr_event_generated_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropIndex('tr_event_status_idx');
            $table->dropIndex('tr_event_blader_idx');
            $table->dropIndex('tr_event_email_idx');
            $table->dropIndex('tr_event_generated_user_idx');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('league_matches')
            ->where('concluded', false)
            ->whereNotNull('winner_id')
            ->update(['concluded' => true]);
    }

    public function down(): void
    {
        // No-op: data correction migration.
    }
};

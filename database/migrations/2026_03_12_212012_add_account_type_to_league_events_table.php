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
            if (!Schema::hasColumn('league_events', 'account_type')) {
                $table->string('account_type')->nullable()->after('account_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('league_events', function (Blueprint $table) {
            if (Schema::hasColumn('league_events', 'account_type')) {
                $table->dropColumn('account_type');
            }
        });
    }
};

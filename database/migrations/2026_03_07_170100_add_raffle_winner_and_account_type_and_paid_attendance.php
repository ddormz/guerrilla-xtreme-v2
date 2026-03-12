<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table) {
            if (!Schema::hasColumn('raffles', 'winner_number')) {
                $table->unsignedInteger('winner_number')->nullable()->after('winner_photo');
            }

            if (!Schema::hasColumn('raffles', 'account_type')) {
                $table->string('account_type', 80)->nullable()->after('account_number');
            }
        });

        Schema::table('league_attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('league_attendance', 'paid')) {
                $table->boolean('paid')->default(false)->after('present');
            }
        });
    }

    public function down(): void
    {
        Schema::table('league_attendance', function (Blueprint $table) {
            if (Schema::hasColumn('league_attendance', 'paid')) {
                $table->dropColumn('paid');
            }
        });

        Schema::table('raffles', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('raffles', 'winner_number')) {
                $dropColumns[] = 'winner_number';
            }

            if (Schema::hasColumn('raffles', 'account_type')) {
                $dropColumns[] = 'account_type';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

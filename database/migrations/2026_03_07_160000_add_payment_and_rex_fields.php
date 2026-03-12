<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffles', function (Blueprint $table) {
            $table->string('bank_name', 120)->nullable()->after('rules');
            $table->string('account_holder', 120)->nullable()->after('bank_name');
            $table->string('account_number', 120)->nullable()->after('account_holder');
            $table->string('account_email', 150)->nullable()->after('account_number');
            $table->text('payment_instructions')->nullable()->after('account_email');
        });

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->boolean('is_rex_registered')->default(false)->after('is_internal');
            $table->foreignId('generated_user_id')->nullable()->after('email')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generated_user_id');
            $table->dropColumn('is_rex_registered');
        });

        Schema::table('raffles', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'account_holder',
                'account_number',
                'account_email',
                'payment_instructions',
            ]);
        });
    }
};

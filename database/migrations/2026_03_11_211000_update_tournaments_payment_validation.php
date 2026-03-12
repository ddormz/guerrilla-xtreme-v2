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
        // Add bank details to league_events
        Schema::table('league_events', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('flyer_image_path');
            $table->string('account_holder')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('account_holder');
            $table->string('account_email')->nullable()->after('account_number');
            $table->text('payment_instructions')->nullable()->after('account_email');
        });

        // Add payment validation fields to tournament_registrations
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('generated_user_id');
            $table->string('status')->default('pending')->after('proof_path'); // pending, confirmed, rejected
            $table->timestamp('payment_date')->nullable()->after('status');
            $table->timestamp('validated_at')->nullable()->after('payment_date');
            $table->text('validation_notes')->nullable()->after('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropColumn(['proof_path', 'status', 'payment_date', 'validated_at', 'validation_notes']);
        });

        Schema::table('league_events', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_holder', 'account_number', 'account_email', 'payment_instructions']);
        });
    }
};

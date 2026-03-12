<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1. USERS
        // ============================================================
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('blader_name', 150)->default('');
            $table->string('email', 150)->unique();
            $table->string('role', 30)->default('usuario');
            $table->string('phone', 50)->default('');
            $table->string('password');
            $table->boolean('password_temporary')->default(false);
            $table->string('avatar_path', 500)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // ============================================================
        // 2. SESSIONS (Laravel default)
        // ============================================================
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ============================================================
        // 3. CACHE (Laravel default)
        // ============================================================
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // ============================================================
        // 4. JOBS (Laravel default)
        // ============================================================
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // ============================================================
        // 5. TEAM MEMBERS (for landing page roster)
        // ============================================================
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->string('blader_name', 200)->nullable();
            $table->string('role_title', 100)->default('Miembro');
            $table->string('photo_path', 500)->nullable();
            $table->string('instagram', 150)->nullable();
            $table->string('tiktok', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->date('joined_date')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // 6. RAFFLES
        // ============================================================
        Schema::create('raffles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('ticket_price');
            $table->unsignedInteger('total_numbers');
            $table->string('status', 20)->default('draft');
            $table->dateTime('sales_start_at')->nullable();
            $table->dateTime('sales_end_at')->nullable();
            $table->dateTime('draw_at')->nullable();
            $table->string('winner_photo', 255)->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // ============================================================
        // 7. RAFFLE NUMBERS
        // ============================================================
        Schema::create('raffle_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('status', 20)->default('available');
            $table->string('buyer_name', 200)->nullable();
            $table->string('blader_name', 200)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 200)->nullable();
            $table->timestamps();

            $table->unique(['raffle_id', 'number']);
        });

        // ============================================================
        // 8. RAFFLE PRIZES
        // ============================================================
        Schema::create('raffle_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->timestamps();
        });

        // ============================================================
        // 9. RAFFLE RESERVATIONS
        // ============================================================
        Schema::create('raffle_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('buyer_name', 150);
            $table->string('blader_name', 150)->default('');
            $table->string('email', 150);
            $table->string('phone', 50)->nullable();
            $table->string('status', 20)->default('reserved');
            $table->unsignedInteger('total_amount');
            $table->string('proof_path', 255)->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->timestamps();

            $table->index(['raffle_id', 'status']);
        });

        // ============================================================
        // 10. RAFFLE RESERVATION NUMBERS
        // ============================================================
        Schema::create('raffle_reservation_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('raffle_reservations')->cascadeOnDelete();
            $table->unsignedInteger('number');

            $table->unique(['reservation_id', 'number']);
        });

        // ============================================================
        // 11. LEAGUE PLAYERS
        // ============================================================
        Schema::create('league_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('real_name', 200);
            $table->string('blader_name', 200)->nullable();
            $table->string('avatar_path', 500)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });

        // ============================================================
        // 12. LEAGUE SEASONS
        // ============================================================
        Schema::create('league_seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('prize_1', 10, 2)->default(0);
            $table->decimal('prize_2', 10, 2)->default(0);
            $table->decimal('prize_3', 10, 2)->default(0);
            $table->decimal('precio_inscripcion', 10, 2)->default(0);
            $table->string('status', 50)->default('borrador');
            $table->timestamps();

            $table->index('status');
        });

        // ============================================================
        // 13. LEAGUE ROSTER (pivot)
        // ============================================================
        Schema::create('league_roster', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('league_seasons')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('league_players')->cascadeOnDelete();
            $table->unsignedInteger('seed')->nullable();
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['season_id', 'player_id']);
        });

        // ============================================================
        // 14. LEAGUE EVENTS
        // ============================================================
        Schema::create('league_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->nullable()->constrained('league_seasons')->cascadeOnDelete();
            $table->string('name', 120);
            $table->dateTime('event_date')->nullable();
            $table->string('status', 50)->default('programado');
            $table->string('event_type', 10)->default('liga');
            $table->boolean('is_live')->default(false);
            $table->timestamps();

            $table->index('status');
        });

        // ============================================================
        // 15. LEAGUE ATTENDANCE
        // ============================================================
        Schema::create('league_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('league_events')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('league_players')->cascadeOnDelete();
            $table->boolean('present')->default(true);

            $table->unique(['event_id', 'player_id']);
        });

        // ============================================================
        // 16. LEAGUE MATCHES
        // ============================================================
        Schema::create('league_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('league_events')->cascadeOnDelete();
            $table->unsignedInteger('round_no')->default(1);
            $table->unsignedBigInteger('player_a_id')->nullable();
            $table->unsignedBigInteger('player_b_id')->nullable();
            $table->unsignedInteger('best_of')->default(3);
            $table->unsignedInteger('score_a')->default(0);
            $table->unsignedInteger('score_b')->default(0);
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->unsignedInteger('xtreme_a')->default(0);
            $table->unsignedInteger('xtreme_b')->default(0);
            $table->unsignedInteger('spin_a')->default(0);
            $table->unsignedInteger('spin_b')->default(0);
            $table->unsignedInteger('over_a')->default(0);
            $table->unsignedInteger('over_b')->default(0);
            $table->unsignedInteger('burst_a')->default(0);
            $table->unsignedInteger('burst_b')->default(0);
            $table->unsignedInteger('strikes_a')->default(0);
            $table->unsignedInteger('strikes_b')->default(0);
            $table->boolean('concluded')->default(false);
            $table->unsignedBigInteger('referee_user_id')->nullable();
            $table->string('group_id', 64)->nullable();
            $table->unsignedInteger('game_no')->default(1);
            $table->timestamps();

            $table->foreign('player_a_id')->references('id')->on('league_players')->nullOnDelete();
            $table->foreign('player_b_id')->references('id')->on('league_players')->nullOnDelete();
            $table->foreign('winner_id')->references('id')->on('league_players')->nullOnDelete();

            $table->index('concluded');
            $table->index('group_id');
        });

        // ============================================================
        // 17. MATCH ACTIONS (Event Sourcing)
        // ============================================================
        Schema::create('match_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('league_matches')->cascadeOnDelete();
            $table->enum('side', ['a', 'b']);
            $table->string('action_type', 10);
            $table->unsignedBigInteger('created_by')->nullable()->comment('Referee user ID');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['match_id', 'created_at']);
        });

        // ============================================================
        // 18. LEAGUE POINTS
        // ============================================================
        Schema::create('league_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('league_seasons')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('league_players')->cascadeOnDelete();
            $table->integer('points')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('xtremes')->default(0);
            $table->unsignedInteger('spins')->default(0);
            $table->unsignedInteger('overs')->default(0);
            $table->unsignedInteger('bursts')->default(0);
            $table->timestamps();

            $table->unique(['season_id', 'player_id']);
            $table->index('points');
        });

        // ============================================================
        // 19. TOURNAMENT PARTICIPANTS
        // ============================================================
        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('league_events')->cascadeOnDelete();
            $table->unsignedBigInteger('player_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_name', 255)->nullable();
            $table->string('blader_name', 255);
            $table->unsignedInteger('seed')->nullable();
            $table->boolean('eliminated')->default(false);
            $table->string('status', 50)->default('inscrito');
            $table->unsignedInteger('bye_count')->default(0);
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('league_players')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['event_id', 'player_id']);
        });

        // ============================================================
        // 20. FINANCE WALLETS
        // ============================================================
        Schema::create('finance_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->decimal('balance', 10, 2)->default(0);
            $table->timestamps();
        });

        // ============================================================
        // 21. FINANCE CATEGORIES
        // ============================================================
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        // ============================================================
        // 22. FINANCE MOVEMENTS
        // ============================================================
        Schema::create('finance_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('finance_wallets')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('finance_categories');
            $table->string('type', 10); // ingreso/gasto
            $table->decimal('amount', 10, 2);
            $table->string('description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        // ============================================================
        // 23. FINANCE SPLIT
        // ============================================================
        Schema::create('finance_split', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_id')->constrained('finance_movements')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('share', 10, 2);
        });

        // ============================================================
        // 24. AUDIT LOGS
        // ============================================================
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('payload_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
            $table->index('created_at');
        });

        // ============================================================
        // 25. PASSWORD RESET TOKENS (Laravel)
        // ============================================================
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        $tables = [
            'password_reset_tokens', 'audit_logs', 'finance_split', 'finance_movements',
            'finance_categories', 'finance_wallets', 'tournament_participants',
            'league_points', 'match_actions', 'league_matches', 'league_attendance',
            'league_events', 'league_roster', 'league_seasons', 'league_players',
            'raffle_reservation_numbers', 'raffle_reservations', 'raffle_prizes',
            'raffle_numbers', 'raffles', 'team_members', 'failed_jobs', 'job_batches',
            'jobs', 'cache_locks', 'cache', 'sessions', 'users',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Raffle;
use App\Models\LeaguePlayer;
use App\Models\LeagueSeason;
use App\Models\FinanceWallet;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

class MigrateProductionData extends Command
{
    protected $signature = 'gx:migrate-production';
    protected $description = 'Migra datos desde la base de datos legacy a la nueva arquitectura v2.';

    public function handle()
    {
        $this->info('Iniciando migración de datos de producción...');

        try {
            DB::connection('legacy')->getPdo();
        } catch (\Exception $e) {
            $this->error('No se pudo conectar a la base de datos legacy. Verifica el connection "legacy" en database.php');
            return 1;
        }

        $this->migrateUsers();
        $this->migrateRaffles();
        $this->migrateLeague();
        $this->migrateFinance();

        $this->info('Migración completada con éxito.');
    }

    private function migrateUsers()
    {
        $this->info('Migrando usuarios...');
        $legacyUsers = DB::connection('legacy')->table('users')->get();

        foreach ($legacyUsers as $lu) {
            User::updateOrCreate(
                ['email' => $lu->email],
                [
                    'name' => $lu->name,
                    'blader_name' => $lu->blader_name ?? $lu->name,
                    'password' => $lu->password, // Asumiendo que ya están hasheadas compatiblemente
                    'phone' => $lu->phone ?? null,
                    'role' => $this->mapRole($lu->role ?? 'invitado'),
                    'avatar_path' => $lu->avatar ?? null,
                    'created_at' => $lu->created_at ?? now(),
                ]
            );
        }
    }

    private function migrateRaffles()
    {
        $this->info('Migrando rifas...');
        $legacyRaffles = DB::connection('legacy')->table('raffles')->get();

        foreach ($legacyRaffles as $lr) {
            Raffle::updateOrCreate(
                ['id' => $lr->id],
                [
                    'name' => $lr->name,
                    'description' => $lr->description,
                    'ticket_price' => $lr->ticket_price,
                    'total_numbers' => $lr->total_numbers,
                    'status' => $lr->status, // Map correct enum if necessary
                    'draw_at' => $lr->draw_at,
                    'created_at' => $lr->created_at ?? now(),
                ]
            );
        }
    }

    private function migrateLeague()
    {
        $this->info('Migrando League Players...');
        $legacyPlayers = DB::connection('legacy')->table('league_players')->get();

        foreach ($legacyPlayers as $lp) {
            LeaguePlayer::updateOrCreate(
                ['id' => $lp->id],
                [
                    'user_id' => $lp->user_id,
                    'blader_name' => $lp->blader_name,
                    'avatar_path' => $lp->avatar ?? null,
                ]
            );
        }
    }

    private function migrateFinance()
    {
        $this->info('Creando wallets iniciales...');
        if (FinanceWallet::count() === 0) {
            FinanceWallet::create(['name' => 'Caja General', 'balance' => 0]);
        }
    }

    private function mapRole($oldRole): UserRole
    {
        return match ($oldRole) {
            'admin' => UserRole::Admin,
            'miembro' => UserRole::Miembro,
            'arbitro' => UserRole::ArbitroGX,
            default => UserRole::Invitado,
        };
    }
}

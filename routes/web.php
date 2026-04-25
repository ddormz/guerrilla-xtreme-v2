<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminLeagueController;
use App\Http\Controllers\Admin\AdminRaffleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RaffleController;
use App\Http\Controllers\RefereeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Inertia
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'role:admin'])->get('/dev/recalc', function () {
    $results = [];
    foreach(\App\Models\LeagueSeason::all() as $season) {
        app(\App\Services\LeagueService::class)->recalculateSeasonPoints($season);
        $standings = \App\Models\LeaguePoints::where('season_id', $season->id)
            ->with('player')
            ->orderByDesc('points')
            ->orderByDesc('xtremes')
            ->get();
        $results[$season->name] = $standings->map(fn($s) => [
            'player' => $s->player->display_name ?? $s->player_id,
            'points' => $s->points,
            'wins' => $s->wins,
            'losses' => $s->losses,
            'xtremes' => $s->xtremes,
        ]);
    }
    return response()->json(['status' => 'OK', 'standings' => $results], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Route::get('liga', [LeagueController::class, 'standings'])->name('league.standings');
Route::get('liga/jugadores/{player}', [LeagueController::class, 'playerProfile'])->name('league.players.show');
Route::get('ranking', [LeagueController::class, 'ranking'])->name('ranking.index');
Route::get('torneos', [LeagueController::class, 'events'])->name('tournaments.index');
Route::get('torneos/{event}/registro', [LeagueController::class, 'registrationForm'])->name('tournaments.register');
Route::post('torneos/{event}/registro/check', [LeagueController::class, 'checkRegistration'])->middleware('throttle:10,1')->name('tournaments.register.check');
Route::post('torneos/{event}/registro', [LeagueController::class, 'storeRegistration'])->middleware('device.guard')->name('tournaments.register.store');
Route::get('rifas', [RaffleController::class, 'index'])->name('raffles.index');
Route::get('rifas/{raffle:slug}', [RaffleController::class, 'show'])->name('raffles.show');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('registro', [RegisterController::class, 'create'])->name('register');
    Route::post('registro', [RegisterController::class, 'store']);
    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('usuarios', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('admin.users.index');
    Route::patch('usuarios/{user}/rol', [\App\Http\Controllers\Admin\AdminUserController::class, 'updateRole'])->name('admin.users.role');
    Route::patch('usuarios/{user}/password', [\App\Http\Controllers\Admin\AdminUserController::class, 'resetPassword'])->name('admin.users.password');
    Route::put('usuarios/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('usuarios/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('logs', [\App\Http\Controllers\Admin\AdminAuditController::class, 'index'])->name('admin.audit.index');

    Route::get('roster', [\App\Http\Controllers\Admin\AdminMemberController::class, 'index'])->name('admin.members.index');
    Route::post('roster', [\App\Http\Controllers\Admin\AdminMemberController::class, 'store'])->name('admin.members.store');
    Route::put('roster/{member}', [\App\Http\Controllers\Admin\AdminMemberController::class, 'update'])->name('admin.members.update');
    Route::delete('roster/{member}', [\App\Http\Controllers\Admin\AdminMemberController::class, 'destroy'])->name('admin.members.destroy');
    Route::patch('roster/{member}/toggle', [\App\Http\Controllers\Admin\AdminMemberController::class, 'toggleActive'])->name('admin.members.toggle');

    Route::get('finanzas', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'index'])->name('admin.finance.index');
    Route::post('finanzas/billeteras', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'storeWallet'])->name('admin.finance.wallet.store');
    Route::put('finanzas/billeteras/{wallet}', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'updateWallet'])->name('admin.finance.wallet.update');
    Route::delete('finanzas/billeteras/{wallet}', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'destroyWallet'])->name('admin.finance.wallet.destroy');

    Route::post('finanzas/categorias', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'storeCategory'])->name('admin.finance.category.store');
    Route::put('finanzas/categorias/{category}', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'updateCategory'])->name('admin.finance.category.update');
    Route::delete('finanzas/categorias/{category}', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'destroyCategory'])->name('admin.finance.category.destroy');

    Route::post('finanzas/movimiento', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'storeMovement'])->name('admin.finance.movement.store');
    Route::put('finanzas/movimiento/{movement}', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'updateMovement'])->name('admin.finance.movement.update');
    Route::delete('finanzas/movimiento/{movement}', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'destroyMovement'])->name('admin.finance.movement.destroy');

    Route::get('rifas', [AdminRaffleController::class, 'index'])->name('admin.raffles.index');
    Route::post('rifas', [AdminRaffleController::class, 'store'])->name('admin.raffles.store');
    Route::get('rifas/crear', [AdminRaffleController::class, 'create'])->name('admin.raffles.create');
    Route::get('rifas/{raffle}/editar', [AdminRaffleController::class, 'edit'])->name('admin.raffles.edit');
    Route::get('rifas/{raffle}', [AdminRaffleController::class, 'show'])->name('admin.raffles.show');
    Route::put('rifas/{raffle}', [AdminRaffleController::class, 'update'])->name('admin.raffles.update');
    Route::delete('rifas/{raffle}', [AdminRaffleController::class, 'destroy'])->name('admin.raffles.destroy');
    Route::post('rifas/{raffle}/publicar', [AdminRaffleController::class, 'publish'])->name('admin.raffles.publish');
    Route::post('rifas/{raffle}/premios', [AdminRaffleController::class, 'storePrize'])->name('admin.raffles.prizes.store');
    Route::delete('rifas/premios/{prize}', [AdminRaffleController::class, 'deletePrize'])->name('admin.raffles.prizes.destroy');
    Route::post('rifas/{raffle}/asignar', [AdminRaffleController::class, 'manualAssign'])->name('admin.raffles.assign');
    Route::post('rifas/{raffle}/sortear', [AdminRaffleController::class, 'draw'])->name('admin.raffles.draw');
    Route::post('rifas/{raffle}/numeros/liberar-todos', [AdminRaffleController::class, 'clearAllNumbers'])->name('admin.raffles.numbers.clear-all');
    Route::post('rifas/{raffle}/numeros/{number}/liberar', [AdminRaffleController::class, 'clearSingleNumber'])->name('admin.raffles.numbers.clear');
    Route::post('rifas/{raffle}/numeros/{number}/comprobante', [AdminRaffleController::class, 'uploadManualProof'])->name('admin.raffles.numbers.proof');
    Route::post('rifas/{raffle}/numeros/{number}/ganador', [AdminRaffleController::class, 'markWinner'])->name('admin.raffles.numbers.winner');
    Route::post('rifas/{raffle}/numeros/{number}/foto-ganador', [AdminRaffleController::class, 'uploadWinnerPhoto'])->name('admin.raffles.numbers.winner-photo');
    Route::post('reservas/{reservation}/approve', [AdminRaffleController::class, 'approveReservation'])->name('admin.reservations.approve');

    Route::get('ligas', [AdminLeagueController::class, 'index'])->name('admin.league.index');
    Route::get('recuperacion', [\App\Http\Controllers\Admin\AdminRecoveryController::class, 'index'])->name('admin.recovery.index');
    Route::post('recuperacion/generate', [\App\Http\Controllers\Admin\AdminRecoveryController::class, 'generate'])->name('admin.recovery.generate');
    Route::post('temporadas', [AdminLeagueController::class, 'storeSeason'])->name('admin.seasons.store');
    Route::put('temporadas/{season}', [AdminLeagueController::class, 'updateSeason'])->name('admin.seasons.update');
    Route::delete('temporadas/{season}', [AdminLeagueController::class, 'destroySeason'])->name('admin.seasons.destroy');
    Route::post('eventos', [AdminLeagueController::class, 'storeEvent'])->name('admin.events.store');
    Route::get('eventos/{event}', [AdminLeagueController::class, 'showEvent'])->name('admin.events.show');
    Route::put('eventos/{event}', [AdminLeagueController::class, 'updateEvent'])->name('admin.events.update');
    Route::delete('eventos/{event}', [AdminLeagueController::class, 'destroyEvent'])->name('admin.events.destroy');
    Route::post('eventos/{event}/asistencia', [AdminLeagueController::class, 'updateAttendance'])->name('admin.events.attendance.update');
    Route::post('eventos/{event}/matches', [AdminLeagueController::class, 'generateMatches'])->name('admin.events.matches.generate');
    Route::post('eventos/{event}/matches/auto-assign', [AdminLeagueController::class, 'autoAssignReferees'])->name('admin.events.matches.auto-assign');
    Route::post('eventos/{event}/toggle-lock', [AdminLeagueController::class, 'toggleMatchLock'])->name('admin.events.toggle-lock');
    Route::post('eventos/{event}/add-late-player', [AdminLeagueController::class, 'addLatePlayer'])->name('admin.events.add-late-player');
    Route::post('torneo-registros/{registration}/approve', [AdminLeagueController::class, 'approveTournamentRegistration'])->name('admin.tournament-registrations.approve');
    Route::post('torneo-registros/{registration}/reject', [AdminLeagueController::class, 'rejectTournamentRegistration'])->name('admin.tournament-registrations.reject');
    Route::put('torneo-registros/{registration}', [AdminLeagueController::class, 'updateTournamentRegistration'])->name('admin.tournament-registrations.update');
    Route::delete('torneo-registros/{registration}', [AdminLeagueController::class, 'destroyTournamentRegistration'])->name('admin.tournament-registrations.destroy');
    Route::post('eventos/matches/{match}/referee', [AdminLeagueController::class, 'assignReferee'])->name('admin.events.matches.referee');
    Route::delete('eventos/matches/{match}', [AdminLeagueController::class, 'destroyMatch'])->name('admin.events.matches.destroy');
    Route::post('jugadores', [AdminLeagueController::class, 'addPlayer'])->name('admin.players.store');
    Route::put('jugadores/{player}', [AdminLeagueController::class, 'updatePlayer'])->name('admin.players.update');
    Route::post('jugadores/todos', [AdminLeagueController::class, 'addAllGxMembers'])->name('admin.players.addAll');
    Route::delete('jugadores/{player}', [AdminLeagueController::class, 'destroyPlayer'])->name('admin.players.destroy');
    Route::patch('jugadores/{player}/active', [AdminLeagueController::class, 'togglePlayerActive'])->name('admin.players.toggle-active');
});

Route::middleware(['auth', 'role:miembro,miembro_gx,arbitro_gx,admin'])->group(function () {
    Route::get('/finanzas', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/contrasena', [ProfileController::class, 'updatePassword'])->name('password.update');
});

Route::middleware(['auth', 'role:arbitro_gx,admin'])->prefix('arbitrio')->group(function () {
    Route::get('/arbitraje', [RefereeController::class, 'dashboard'])->name('referee.dashboard');
    Route::post('/arbitraje/match/{match}/take', [RefereeController::class, 'takeMatch'])->name('referee.match.take');
    Route::post('/arbitraje/match/{match}/reset', [RefereeController::class, 'resetMatch'])->name('referee.match.reset');
    Route::get('/arbitraje/match/{match}', [RefereeController::class, 'panel'])->name('referee.match.panel');
    Route::post('/arbitraje/match/{match}/action', [RefereeController::class, 'addAction'])->name('referee.match.action');
    Route::post('/arbitraje/match/{match}/undo', [RefereeController::class, 'undoAction'])->name('referee.match.undo');
    Route::post('/arbitraje/match/{match}/finalize', [RefereeController::class, 'finalizeMatch'])->name('referee.match.finalize');
    Route::get('match/{match}', [RefereeController::class, 'show'])->name('referee.show');
    Route::post('match/{match}/action', [RefereeController::class, 'addAction'])->name('referee.action');
    Route::post('match/{match}/undo', [RefereeController::class, 'undoAction'])->name('referee.undo');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('rifas/{raffle}/reservar', [RaffleController::class, 'reserve'])->name('raffles.reserve');
    Route::get('dashboard', fn () => redirect()->route('home'))->name('dashboard');
});

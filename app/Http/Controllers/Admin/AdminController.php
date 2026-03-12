<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Raffle;
use App\Models\LeagueEvent;
use App\Models\LeagueAttendance;
use App\Models\FinanceWallet;
use App\Models\RaffleReservation;
use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Dashboard overview with high-level stats.
     */
    public function dashboard(): Response
    {
        $stats = [
            'users_count' => User::count(),
            'pending_reservations' => RaffleReservation::where('status', 'reserved')->count(),
            'active_raffles' => Raffle::where('status', 'published')->count(),
            'next_event' => LeagueEvent::where('status', 'programado')->orderBy('event_date')->first()?->name ?? 'Ninguno',
            'total_balance' => FinanceWallet::sum('balance'),
            'league_revenue' => LeagueEvent::with('attendance')
                ->get()
                ->sum(fn($e) => $e->attendance->where('paid', true)->count() * ($e->registration_cost ?? 0)),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentUsers' => User::orderBy('created_at', 'desc')->limit(5)->get(),
        ]);
    }

    /**
     * Display the system audit logs.
     */
    public function logs(): Response
    {
        $logs = AuditLog::with('actor')
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->through(fn($log) => [
                'id' => $log->id,
                'actor' => $log->actor?->name ?? 'Sistema/Huésped',
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'payload' => $log->payload_json,
                'ip' => $log->ip_address,
                'date' => $log->created_at->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs
        ]);
    }
}

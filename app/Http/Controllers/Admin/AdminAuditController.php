<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('actor');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('action', 'like', "%{$request->search}%")
                  ->orWhere('entity_type', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('actor')) {
            $query->where('actor_user_id', $request->actor);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Audit/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'actor']),
            'actors' => \App\Models\User::whereHas('auditLogs')->get(['id', 'name'])
        ]);
    }
}

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
                  ->orWhere('entity_type', 'like', "%{$request->search}%")
                  ->orWhere('payload_json->device_id', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('actor')) {
            $query->where('actor_user_id', $request->actor);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'payload_json' => $log->payload_json,
                'device_id' => $log->payload_json['device_id'] ?? null,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                ] : null,
            ]);

        return Inertia::render('Admin/Audit/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'actor']),
            'actors' => \App\Models\User::whereHas('auditLogs')->get(['id', 'name'])
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $members = TeamMember::active()
            ->with(['user'])
            ->leftJoin('users', 'team_members.user_id', '=', 'users.id')
            ->select('team_members.*')
            // If they are linked to a user, filter by role. If not (managed bladers), show them anyway.
            // Relaxed filter: show all active team members. The join is for supplementary info.
            // The previous filter was:
            // ->where(function ($query) {
            //     $query->whereNull('team_members.user_id')
            //           ->orWhereIn('users.role', [
            //               \App\Enums\UserRole::MiembroGx->value,
            //               \App\Enums\UserRole::Admin->value,
            //               \App\Enums\UserRole::ArbitroGx->value
            //           ]);
            // })
            ->get()
            ->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'blader_name' => $m->blader_name,
            'role_title' => $m->role_title,
            'photo_path' => $m->photo_path,
            'photo_url' => $m->photo_url,
            'lock_chip_url' => $m->lock_chip_url,
            'instagram' => $m->instagram,
            'tiktok' => $m->tiktok,
            'joined_date' => $m->joined_date?->format('Y-m-d'),
            'created_at' => $m->created_at?->format('Y-m-d H:i:s'),
        ]);

        $featuredEvents = \App\Models\LeagueEvent::where('show_on_index', true)
            ->where('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->take(2)
            ->get();

        return Inertia::render('Home', [
            'teamMembers' => $members,
            'memberCount' => $members->count(),
            'featuredEvents' => $featuredEvents,
        ]);
    }
}

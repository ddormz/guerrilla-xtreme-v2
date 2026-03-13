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
            ->select([
                'id',
                'name',
                'blader_name',
                'role_title',
                'photo_path',
                'lock_chip_photo',
                'instagram',
                'tiktok',
                'joined_date',
                'created_at',
            ])
            ->orderBy('display_order')
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
            ->select(['id', 'name', 'event_date', 'time', 'location', 'prizes', 'registration_cost'])
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

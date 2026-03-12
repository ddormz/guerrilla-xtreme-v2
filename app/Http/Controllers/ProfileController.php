<?php

namespace App\Http\Controllers;

use App\Models\LeagueMatch;
use App\Models\LeaguePoints;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $leaguePlayer = $user->leaguePlayer;

        $leagueStats = null;
        $rankingHistory = [];

        if ($leaguePlayer) {
            $pointsRows = LeaguePoints::with('season')
                ->where('player_id', $leaguePlayer->id)
                ->orderByDesc('season_id')
                ->get();

            $latest = $pointsRows->first();

            $currentRank = null;
            if ($latest) {
                $ordered = LeaguePoints::where('season_id', $latest->season_id)
                    ->orderByDesc('points')
                    ->orderByDesc('wins')
                    ->orderByDesc('xtremes')
                    ->pluck('player_id')
                    ->values();

                $rankPos = $ordered->search($leaguePlayer->id);
                $currentRank = $rankPos !== false ? $rankPos + 1 : null;
            }

            $wins = (int) ($latest?->wins ?? 0);
            $losses = (int) ($latest?->losses ?? 0);
            $totalMatches = $wins + $losses;
            $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0;

            $headToHead = [];
            $matches = LeagueMatch::with(['playerA', 'playerB'])
                ->where('concluded', true)
                ->where(function ($query) use ($leaguePlayer) {
                    $query->where('player_a_id', $leaguePlayer->id)
                        ->orWhere('player_b_id', $leaguePlayer->id);
                })
                ->get();

            foreach ($matches as $match) {
                $isA = $match->player_a_id === $leaguePlayer->id;
                $opponent = $isA ? $match->playerB : $match->playerA;
                if (!$opponent) {
                    continue;
                }

                if (!isset($headToHead[$opponent->id])) {
                    $headToHead[$opponent->id] = [
                        'name' => $opponent->display_name,
                        'wins' => 0,
                        'losses' => 0,
                    ];
                }

                if ((int) $match->winner_id === (int) $leaguePlayer->id) {
                    $headToHead[$opponent->id]['wins']++;
                } else {
                    $headToHead[$opponent->id]['losses']++;
                }
            }

            $father = collect($headToHead)
                ->filter(fn ($item) => $item['losses'] > 0)
                ->sortByDesc('losses')
                ->first();

            $son = collect($headToHead)
                ->filter(fn ($item) => $item['wins'] > 0)
                ->sortByDesc('wins')
                ->first();

            $leagueStats = [
                'player_id' => $leaguePlayer->id,
                'blader_name' => $leaguePlayer->display_name,
                'avatar_url' => $leaguePlayer->avatar_url,
                'rank' => $currentRank,
                'points' => (int) ($latest?->points ?? 0),
                'wins' => $wins,
                'losses' => $losses,
                'xtremes' => (int) ($latest?->xtremes ?? 0),
                'overs' => (int) ($latest?->overs ?? 0),
                'bursts' => (int) ($latest?->bursts ?? 0),
                'total_matches' => $totalMatches,
                'win_rate' => $winRate,
                'father' => $father ? ['name' => $father['name'], 'count' => $father['losses']] : null,
                'son' => $son ? ['name' => $son['name'], 'count' => $son['wins']] : null,
            ];

            $rankingHistory = $pointsRows->map(function ($row) {
                $ordered = LeaguePoints::where('season_id', $row->season_id)
                    ->orderByDesc('points')
                    ->orderByDesc('wins')
                    ->orderByDesc('xtremes')
                    ->pluck('player_id')
                    ->values();

                $rank = $ordered->search($row->player_id);

                return [
                    'season' => $row->season?->name ?? 'Temporada',
                    'rank' => $rank !== false ? $rank + 1 : null,
                    'points' => $row->points,
                    'wins' => $row->wins,
                    'losses' => $row->losses,
                    'xtremes' => $row->xtremes,
                    'overs' => $row->overs,
                    'bursts' => $row->bursts,
                ];
            });
        }

        return Inertia::render('Profile/Edit', [
            'user' => [
                'name' => $user->name,
                'blader_name' => $user->blader_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_path' => $user->avatar_path ? Storage::url($user->avatar_path) : null,
            ],
            'leagueStats' => $leagueStats,
            'rankingHistory' => $rankingHistory,
            'globalLeaders' => $leaguePlayer ? [
                'xtremes' => LeaguePoints::orderByDesc('xtremes')->first()?->player_id === $leaguePlayer->id,
                'overs' => LeaguePoints::orderByDesc('overs')->first()?->player_id === $leaguePlayer->id,
                'bursts' => LeaguePoints::orderByDesc('bursts')->first()?->player_id === $leaguePlayer->id,
                'wins' => LeaguePoints::orderByDesc('wins')->first()?->player_id === $leaguePlayer->id,
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'blader_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        AuditLogger::log('update_profile', 'User', $user->id, [
            'email' => $user->email,
            'blader_name' => $user->blader_name,
        ]);

        return back()->with('success', 'Perfil actualizado exitosamente.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Contrasena actualizada exitosamente.');
    }
}

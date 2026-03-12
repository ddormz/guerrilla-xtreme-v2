<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Channels for Laravel Reverb WebSocket broadcasting.
|--------------------------------------------------------------------------
*/

// Match channel: only referees/admins assigned to this match
Broadcast::channel('match.{matchId}', function ($user, $matchId) {
    return $user->isReferee() || $user->isAdmin();
});

// Event live channel: public for spectators
Broadcast::channel('event.{eventId}', function () {
    return true;
});

// Tournament bracket channel: public
Broadcast::channel('tournament.{eventId}', function () {
    return true;
});

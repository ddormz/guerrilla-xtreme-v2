<?php

namespace App\Events;

use App\Models\LeagueMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public LeagueMatch $match
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('match.' . $this->match->id), // For referees/admins
            new Channel('event.' . $this->match->event_id), // For public spectators
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->match->id,
            'score_a' => $this->match->score_a,
            'score_b' => $this->match->score_b,
            'concluded' => $this->match->concluded,
            'winner_id' => $this->match->winner_id,
            'game_no' => $this->match->game_no,
            'strikes_a' => $this->match->strikes_a,
            'strikes_b' => $this->match->strikes_b,
        ];
    }
}

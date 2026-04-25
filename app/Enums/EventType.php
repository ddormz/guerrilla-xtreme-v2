<?php

namespace App\Enums;

enum EventType: string
{
    case Liga = 'liga';
    case Torneo = 'torneo';
    case TorneoRanking = 'torneo_ranking';

    public function label(): string
    {
        return match ($this) {
            self::Liga => 'Liga',
            self::Torneo => 'Torneo',
            self::TorneoRanking => 'Torneo + Ranking',
        };
    }

    public function isRanking(): bool
    {
        return $this === self::TorneoRanking;
    }
}

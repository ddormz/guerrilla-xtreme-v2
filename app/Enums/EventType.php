<?php

namespace App\Enums;

enum EventType: string
{
    case Liga = 'liga';
    case Torneo = 'torneo';

    public function label(): string
    {
        return match ($this) {
            self::Liga => 'Liga',
            self::Torneo => 'Torneo',
        };
    }
}

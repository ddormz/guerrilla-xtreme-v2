<?php

namespace App\Enums;

enum MatchActionType: string
{
    case Spin = 'spin';
    case Over = 'over';
    case Burst = 'burst';
    case Xtreme = 'xtreme';
    case Strike = 'strike';

    public function points(): int
    {
        return match ($this) {
            self::Spin => 1,
            self::Over => 2,
            self::Burst => 2,
            self::Xtreme => 3,
            self::Strike => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Spin => 'Spin Finish',
            self::Over => 'Over Finish',
            self::Burst => 'Burst Finish',
            self::Xtreme => 'Xtreme Finish',
            self::Strike => 'Strike',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Spin => '🌀',
            self::Over => '⬆️',
            self::Burst => '💥',
            self::Xtreme => '⚡',
            self::Strike => '⚠️',
        };
    }
}

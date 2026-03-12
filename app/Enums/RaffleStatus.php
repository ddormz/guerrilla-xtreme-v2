<?php

namespace App\Enums;

enum RaffleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Drawn = 'drawn';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicada',
            self::Closed => 'Cerrada',
            self::Drawn => 'Sorteada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Published, self::Closed]);
    }
}

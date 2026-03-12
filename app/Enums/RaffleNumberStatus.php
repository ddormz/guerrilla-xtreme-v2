<?php

namespace App\Enums;

enum RaffleNumberStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Winner = 'winner';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Reserved => 'Reservado',
            self::Sold => 'Vendido',
            self::Winner => 'Ganador',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'gray',
            self::Reserved => 'amber',
            self::Sold => 'blue',
            self::Winner => 'green',
        };
    }
}

<?php

namespace App\Enums;

enum FinanceType: string
{
    case Ingreso = 'ingreso';
    case Gasto = 'gasto';

    public function label(): string
    {
        return match ($this) {
            self::Ingreso => 'Ingreso',
            self::Gasto => 'Gasto',
        };
    }

    public function sign(): int
    {
        return match ($this) {
            self::Ingreso => 1,
            self::Gasto => -1,
        };
    }
}

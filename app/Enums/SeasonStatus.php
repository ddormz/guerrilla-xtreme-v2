<?php

namespace App\Enums;

enum SeasonStatus: string
{
    case Borrador = 'borrador';
    case EnCurso = 'en_curso';
    case Finalizada = 'finalizada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::EnCurso => 'En Curso',
            self::Finalizada => 'Finalizada',
        };
    }
}

<?php

namespace App\Enums;

enum AuctionStatus: string
{
    case Setup = 'setup';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Setup => 'In preparazione',
            self::InProgress => 'In corso',
            self::Paused => 'In pausa',
            self::Completed => 'Conclusa',
        };
    }
}

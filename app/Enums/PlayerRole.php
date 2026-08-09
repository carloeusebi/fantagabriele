<?php

namespace App\Enums;

enum PlayerRole: string
{
    case Goalkeeper = 'P';
    case Defender = 'D';
    case Midfielder = 'C';
    case Forward = 'A';

    /**
     * The role that follows this one in the auction's phase order, or null
     * if this is the last phase.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Goalkeeper => self::Defender,
            self::Defender => self::Midfielder,
            self::Midfielder => self::Forward,
            self::Forward => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Goalkeeper => 'Portiere',
            self::Defender => 'Difensore',
            self::Midfielder => 'Centrocampista',
            self::Forward => 'Attaccante',
        };
    }
}

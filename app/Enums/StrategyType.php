<?php

namespace App\Enums;

enum StrategyType: string
{
    case Diversified = 'diversified';
    case Balanced = 'balanced';
    case Superstars = 'superstars';

    public function label(): string
    {
        return match ($this) {
            self::Diversified => 'Diversificata',
            self::Balanced => 'Bilanciato',
            self::Superstars => 'Fuoriclasse',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Diversified => 'Punta a tanti giocatori buoni, senza spendere cifre assurde per i singoli.',
            self::Balanced => 'Ripartizione equilibrata tra tutti i reparti.',
            self::Superstars => 'Risparmia su portieri, difensori e centrocampisti per puntare forte sui fuoriclasse in attacco.',
        };
    }
}

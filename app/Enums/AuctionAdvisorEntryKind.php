<?php

namespace App\Enums;

enum AuctionAdvisorEntryKind: string
{
    case Strategy = 'strategy';
    case Call = 'call';
    case Bid = 'bid';

    public function label(): string
    {
        return match ($this) {
            self::Strategy => 'Strategia',
            self::Call => 'Chiamata',
            self::Bid => 'Offerta',
        };
    }
}

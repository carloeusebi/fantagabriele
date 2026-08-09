<?php

namespace App\Exceptions\Auction;

use Exception;

class AuctionRuleException extends Exception
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}

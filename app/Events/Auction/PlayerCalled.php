<?php

namespace App\Events\Auction;

use App\Models\Auction;
use App\Models\AuctionCall;

class PlayerCalled extends AuctionEvent
{
    public function __construct(Auction $auction, public AuctionCall $call)
    {
        parent::__construct($auction);
    }

    public function broadcastAs(): string
    {
        return 'PlayerCalled';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => "{$this->call->calledBy->name} chiama {$this->call->player->name}.",
        ];
    }
}

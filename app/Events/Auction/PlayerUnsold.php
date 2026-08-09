<?php

namespace App\Events\Auction;

use App\Models\Auction;
use App\Models\AuctionCall;

class PlayerUnsold extends AuctionEvent
{
    public function __construct(Auction $auction, public AuctionCall $call)
    {
        parent::__construct($auction);
    }

    public function broadcastAs(): string
    {
        return 'PlayerUnsold';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => "Nessuno si aggiudica {$this->call->player->name}.",
        ];
    }
}

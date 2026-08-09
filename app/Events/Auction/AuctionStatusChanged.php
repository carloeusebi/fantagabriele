<?php

namespace App\Events\Auction;

class AuctionStatusChanged extends AuctionEvent
{
    public function broadcastAs(): string
    {
        return 'AuctionStatusChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => "Stato asta: {$this->auction->status->label()}.",
        ];
    }
}

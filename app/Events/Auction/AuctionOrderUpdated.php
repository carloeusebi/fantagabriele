<?php

namespace App\Events\Auction;

class AuctionOrderUpdated extends AuctionEvent
{
    public function broadcastAs(): string
    {
        return 'AuctionOrderUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => 'Ordine di chiamata aggiornato.',
        ];
    }
}

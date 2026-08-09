<?php

namespace App\Events\Auction;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

class AuctionStatusChanged extends AuctionEvent
{
    public function broadcastAs(): string
    {
        return 'AuctionStatusChanged';
    }

    /**
     * Also broadcast on the global auctions channel so the auctions index
     * page can reflect status changes (e.g. setup -> in progress) live.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('auction.'.$this->auction->id),
            new PrivateChannel('auctions'),
        ];
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

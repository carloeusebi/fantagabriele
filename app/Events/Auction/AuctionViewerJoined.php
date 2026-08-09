<?php

namespace App\Events\Auction;

use App\Models\Auction;
use App\Models\AuctionViewer;

class AuctionViewerJoined extends AuctionEvent
{
    public function __construct(Auction $auction, public AuctionViewer $viewer)
    {
        parent::__construct($auction);
    }

    public function broadcastAs(): string
    {
        return 'AuctionViewerJoined';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $message = $this->viewer->isSpectator()
            ? "{$this->viewer->user->name} si è unito come spettatore."
            : "{$this->viewer->user->name} ora è {$this->viewer->participant->name}.";

        return ['message' => $message];
    }
}

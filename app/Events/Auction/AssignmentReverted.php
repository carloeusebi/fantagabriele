<?php

namespace App\Events\Auction;

use App\Models\Auction;
use App\Models\PlayerAssignment;

class AssignmentReverted extends AuctionEvent
{
    public function __construct(Auction $auction, public PlayerAssignment $assignment)
    {
        parent::__construct($auction);
    }

    public function broadcastAs(): string
    {
        return 'AssignmentReverted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => "Annullata l'assegnazione di {$this->assignment->player->name}.",
        ];
    }
}

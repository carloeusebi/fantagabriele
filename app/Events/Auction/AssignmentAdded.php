<?php

namespace App\Events\Auction;

use App\Models\Auction;
use App\Models\PlayerAssignment;

class AssignmentAdded extends AuctionEvent
{
    public function __construct(Auction $auction, public PlayerAssignment $assignment)
    {
        parent::__construct($auction);
    }

    public function broadcastAs(): string
    {
        return 'AssignmentAdded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => "{$this->assignment->participant->name} riceve {$this->assignment->player->name} per {$this->assignment->price}.",
        ];
    }
}

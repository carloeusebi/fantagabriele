<?php

namespace App\Services\Auction;

use App\Models\Auction;
use App\Models\AuctionAdvisorConversation;
use App\Models\User;

/**
 * Reads and persists the laravel/ai conversation id backing a single
 * user's ongoing advisor dialogue for a given auction, so the AI can
 * remember the strategy it proposed and the suggestions it already gave
 * across separate requests.
 */
class AuctionAdvisorConversations
{
    public function resolve(Auction $auction, User $user): ?string
    {
        return AuctionAdvisorConversation::query()
            ->where('auction_id', $auction->id)
            ->where('user_id', $user->id)
            ->value('conversation_id');
    }

    public function remember(Auction $auction, User $user, string $conversationId): void
    {
        AuctionAdvisorConversation::query()->updateOrCreate(
            ['auction_id' => $auction->id, 'user_id' => $user->id],
            ['conversation_id' => $conversationId],
        );
    }
}

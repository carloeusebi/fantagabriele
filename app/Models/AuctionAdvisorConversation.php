<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks the laravel/ai conversation id backing a single user's ongoing
 * strategic dialogue with the auction assistant for a given auction, so the
 * assistant can remember its own plan and prior suggestions across requests.
 *
 * @property int $id
 * @property int $auction_id
 * @property int $user_id
 * @property string|null $conversation_id
 */
#[Fillable(['auction_id', 'user_id', 'conversation_id'])]
class AuctionAdvisorConversation extends Model
{
    /**
     * @return BelongsTo<Auction, $this>
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records whether a user is watching an auction as a spectator or has
 * claimed one of its participants.
 *
 * @property int $id
 * @property int $auction_id
 * @property int $user_id
 * @property int|null $auction_participant_id
 * @property int|null $strategy_id
 */
#[Fillable(['auction_id', 'user_id', 'auction_participant_id', 'strategy_id'])]
class AuctionViewer extends Model
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

    /**
     * @return BelongsTo<AuctionParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(AuctionParticipant::class, 'auction_participant_id');
    }

    public function isSpectator(): bool
    {
        return $this->auction_participant_id === null;
    }

    /**
     * @return BelongsTo<Strategy, $this>
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }
}

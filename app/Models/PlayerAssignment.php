<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $auction_id
 * @property int $auction_participant_id
 * @property int $player_id
 * @property int|null $auction_call_id
 * @property int $price
 * @property int|null $replaces_assignment_id
 */
#[Fillable([
    'auction_id', 'auction_participant_id', 'player_id',
    'auction_call_id', 'price', 'replaces_assignment_id',
])]
class PlayerAssignment extends Model
{
    use SoftDeletes;

    /**
     * @return BelongsTo<Auction, $this>
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * @return BelongsTo<AuctionParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(AuctionParticipant::class, 'auction_participant_id');
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<AuctionCall, $this>
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(AuctionCall::class, 'auction_call_id');
    }

    /**
     * @return BelongsTo<PlayerAssignment, $this>
     */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_assignment_id');
    }

    /**
     * @return HasOne<PlayerAssignment, $this>
     */
    public function replacedBy(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_assignment_id');
    }
}

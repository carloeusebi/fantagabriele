<?php

namespace App\Models;

use App\Enums\AuctionCallStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $auction_id
 * @property int $player_id
 * @property int $called_by_auction_participant_id
 * @property AuctionCallStatus $status
 * @property Carbon|null $resolved_at
 */
#[Fillable(['auction_id', 'player_id', 'called_by_auction_participant_id', 'status', 'resolved_at'])]
class AuctionCall extends Model
{
    /**
     * @return BelongsTo<Auction, $this>
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<AuctionParticipant, $this>
     */
    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(AuctionParticipant::class, 'called_by_auction_participant_id');
    }

    /**
     * @return HasOne<PlayerAssignment, $this>
     */
    public function assignment(): HasOne
    {
        return $this->hasOne(PlayerAssignment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AuctionCallStatus::class,
            'resolved_at' => 'datetime',
        ];
    }
}

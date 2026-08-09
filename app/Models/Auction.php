<?php

namespace App\Models;

use App\Enums\AuctionStatus;
use App\Enums\PlayerRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property AuctionStatus $status
 * @property int $budget_per_participant
 * @property int $slots_goalkeeper
 * @property int $slots_defender
 * @property int $slots_midfielder
 * @property int $slots_forward
 * @property PlayerRole|null $current_phase
 * @property int|null $current_turn_auction_participant_id
 * @property int|null $current_call_id
 */
#[Fillable([
    'name', 'status', 'budget_per_participant',
    'slots_goalkeeper', 'slots_defender', 'slots_midfielder', 'slots_forward',
    'current_phase', 'current_turn_auction_participant_id', 'current_call_id',
])]
class Auction extends Model
{
    /**
     * @return HasMany<AuctionParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(AuctionParticipant::class);
    }

    /**
     * @return HasMany<AuctionCall, $this>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(AuctionCall::class);
    }

    /**
     * @return HasMany<PlayerAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PlayerAssignment::class);
    }

    /**
     * @return BelongsTo<AuctionParticipant, $this>
     */
    public function currentTurnParticipant(): BelongsTo
    {
        return $this->belongsTo(AuctionParticipant::class, 'current_turn_auction_participant_id');
    }

    /**
     * @return BelongsTo<AuctionCall, $this>
     */
    public function currentCall(): BelongsTo
    {
        return $this->belongsTo(AuctionCall::class, 'current_call_id');
    }

    public function slotsFor(PlayerRole $role): int
    {
        return match ($role) {
            PlayerRole::Goalkeeper => $this->slots_goalkeeper,
            PlayerRole::Defender => $this->slots_defender,
            PlayerRole::Midfielder => $this->slots_midfielder,
            PlayerRole::Forward => $this->slots_forward,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AuctionStatus::class,
            'current_phase' => PlayerRole::class,
        ];
    }
}

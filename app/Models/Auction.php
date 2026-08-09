<?php

namespace App\Models;

use App\Enums\AuctionStatus;
use App\Enums\PlayerRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $password
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
    'user_id', 'name', 'password', 'status', 'budget_per_participant',
    'slots_goalkeeper', 'slots_defender', 'slots_midfielder', 'slots_forward',
    'current_phase', 'current_turn_auction_participant_id', 'current_call_id',
])]
#[Hidden(['password'])]
class Auction extends Model
{
    /** @use HasFactory<\Database\Factories\AuctionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
     * @return HasMany<AuctionViewer, $this>
     */
    public function viewers(): HasMany
    {
        return $this->hasMany(AuctionViewer::class);
    }

    public function viewerFor(User $user): ?AuctionViewer
    {
        return $this->viewers->firstWhere('user_id', $user->id);
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

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => AuctionStatus::class,
            'current_phase' => PlayerRole::class,
        ];
    }
}

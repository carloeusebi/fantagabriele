<?php

namespace App\Models;

use App\Enums\PlayerRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $auction_id
 * @property string $name
 * @property int $call_order
 */
#[Fillable(['auction_id', 'name', 'call_order'])]
class AuctionParticipant extends Model
{
    /**
     * @return BelongsTo<Auction, $this>
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * @return HasMany<PlayerAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PlayerAssignment::class);
    }

    /**
     * @return HasMany<AuctionCall, $this>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(AuctionCall::class, 'called_by_auction_participant_id');
    }

    /**
     * @return HasOne<AuctionViewer, $this>
     */
    public function viewer(): HasOne
    {
        return $this->hasOne(AuctionViewer::class);
    }

    public function budgetRemaining(): int
    {
        return $this->auction->budget_per_participant - (int) $this->assignments()->sum('price');
    }

    public function slotsFilledFor(PlayerRole $role): int
    {
        return $this->assignments()->whereHas('player', fn ($query) => $query->where('role', $role))->count();
    }

    public function hasCompletedRole(PlayerRole $role): bool
    {
        return $this->slotsFilledFor($role) >= $this->auction->slotsFor($role);
    }

    /**
     * @return Collection<int, PlayerAssignment>
     */
    public function rosterFor(PlayerRole $role): Collection
    {
        return $this->assignments()->whereHas('player', fn ($query) => $query->where('role', $role))
            ->with('player')
            ->get();
    }

    /**
     * Serialize this participant's live board state (budget, roster slots,
     * players bought) for the Inertia response and, later, the AI context.
     *
     * @return array<string, mixed>
     */
    public function toBoardArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'call_order' => $this->call_order,
            'claimed_by' => $this->viewer?->user?->name,
            'claimed_by_user_id' => $this->viewer?->user_id,
            'claimed_by_avatar' => $this->viewer?->user?->avatar,
            'budget_remaining' => $this->budgetRemaining(),
            'slots' => collect(PlayerRole::cases())->map(fn (PlayerRole $role) => [
                'role' => $role->value,
                'label' => $role->label(),
                'filled' => $this->slotsFilledFor($role),
                'total' => $this->auction->slotsFor($role),
            ])->values(),
            'roster' => $this->assignments()->with('player.team')->get()->map(fn (PlayerAssignment $assignment) => [
                'assignment_id' => $assignment->id,
                'price' => $assignment->price,
                'player_id' => $assignment->player->id,
                'player_name' => $assignment->player->name,
                'role' => $assignment->player->role->value,
                'team' => $assignment->player->team?->name,
            ])->values(),
        ];
    }
}

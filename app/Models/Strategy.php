<?php

namespace App\Models;

use App\Enums\PlayerRole;
use App\Enums\StrategyType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A user-authored, reusable auction plan: how to split the budget across
 * roles, which players to prioritize, an overall style, and a free-text
 * comment. Independent of any specific auction; the user picks one (or asks
 * the AI to build a plan from scratch instead) once they've claimed a
 * participant in a live auction.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property StrategyType $type
 * @property int $budget_goalkeeper_percent
 * @property int $budget_defender_percent
 * @property int $budget_midfielder_percent
 * @property int $budget_forward_percent
 * @property array<int, string> $locked_roles
 * @property string|null $comment
 */
#[Fillable([
    'user_id', 'name', 'type',
    'budget_goalkeeper_percent', 'budget_defender_percent', 'budget_midfielder_percent', 'budget_forward_percent',
    'locked_roles', 'comment',
])]
class Strategy extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function priorityPlayers(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'strategy_players')->withTimestamps();
    }

    /**
     * The budget split, keyed by PlayerRole value (P/D/C/A).
     *
     * @return array<string, int>
     */
    public function budgetPercents(): array
    {
        return [
            PlayerRole::Goalkeeper->value => $this->budget_goalkeeper_percent,
            PlayerRole::Defender->value => $this->budget_defender_percent,
            PlayerRole::Midfielder->value => $this->budget_midfielder_percent,
            PlayerRole::Forward->value => $this->budget_forward_percent,
        ];
    }

    /**
     * The condensed shape shown to the user (as the auction "brief" card)
     * and sent to the AI as context — the single source of truth for both.
     *
     * @return array<string, mixed>
     */
    public function toBriefArray(): array
    {
        $this->loadMissing('priorityPlayers.team');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'budget_plan' => $this->budgetPercents(),
            'priority_players' => $this->priorityPlayers->map(fn (Player $player) => [
                'id' => $player->id,
                'name' => $player->name,
                'role' => $player->role->value,
                'team' => $player->team?->name,
            ])->values()->all(),
            'comment' => $this->comment,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StrategyType::class,
            'locked_roles' => 'array',
        ];
    }
}

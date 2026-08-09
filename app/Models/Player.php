<?php

namespace App\Models;

use App\Enums\PlayerRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $fanta_id
 * @property int|null $team_id
 * @property string $name
 * @property PlayerRole $role
 * @property string|null $role_mantra
 * @property int $initial_quotation
 * @property int $current_quotation
 * @property int|null $initial_quotation_mantra
 * @property int|null $current_quotation_mantra
 * @property int|null $fvm
 * @property int|null $fvm_mantra
 */
#[Fillable([
    'fanta_id', 'team_id', 'name', 'role', 'role_mantra',
    'initial_quotation', 'current_quotation',
    'initial_quotation_mantra', 'current_quotation_mantra',
    'fvm', 'fvm_mantra',
])]
class Player extends Model
{
    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<PlayerAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PlayerAssignment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => PlayerRole::class,
        ];
    }
}

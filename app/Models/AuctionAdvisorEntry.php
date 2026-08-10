<?php

namespace App\Models;

use App\Enums\AuctionAdvisorEntryKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single logged interaction with the AI auction advisor (strategy
 * briefing, call suggestion, or bid suggestion), kept so the full
 * conversation — the JSON context sent and the AI's response — stays
 * visible to the user who asked for it.
 *
 * @property int $id
 * @property int $auction_id
 * @property int $user_id
 * @property AuctionAdvisorEntryKind $kind
 * @property array<string, mixed> $request_context
 * @property string|null $reasoning
 * @property array<string, mixed>|null $response
 * @property bool $is_bluff
 */
#[Fillable(['auction_id', 'user_id', 'kind', 'request_context', 'reasoning', 'response', 'is_bluff'])]
class AuctionAdvisorEntry extends Model
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AuctionAdvisorEntryKind::class,
            'request_context' => 'array',
            'response' => 'array',
            'is_bluff' => 'boolean',
        ];
    }
}

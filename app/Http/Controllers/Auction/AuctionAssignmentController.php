<?php

namespace App\Http\Controllers\Auction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auction\AddAssignmentRequest;
use App\Http\Requests\Auction\StoreAssignmentRequest;
use App\Http\Requests\Auction\UpdateAssignmentRequest;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Player;
use App\Models\PlayerAssignment;
use App\Services\Auction\AuctionEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AuctionAssignmentController extends Controller
{
    public function store(StoreAssignmentRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('resolveCall', $auction);

        $participant = AuctionParticipant::query()->findOrFail((int) $request->validated('auction_participant_id'));

        $engine->assign($auction, $participant, (int) $request->validated('price'));

        return back();
    }

    /**
     * Manually add a player who never went through a call. Used to rectify
     * a mistaken transaction: any player may be assigned in any role,
     * regardless of the auction's current phase.
     */
    public function storeManual(AddAssignmentRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('resolveCall', $auction);

        $player = Player::query()->findOrFail((int) $request->validated('player_id'));
        $participant = AuctionParticipant::query()->findOrFail((int) $request->validated('auction_participant_id'));

        $engine->addAssignment($auction, $player, $participant, (int) $request->validated('price'));

        return back();
    }

    public function update(UpdateAssignmentRequest $request, Auction $auction, PlayerAssignment $assignment, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('resolveCall', $auction);

        abort_unless($assignment->auction_id === $auction->id, 404);

        $participant = AuctionParticipant::query()->findOrFail((int) $request->validated('auction_participant_id'));

        $engine->correct($assignment, $participant, (int) $request->validated('price'));

        return back();
    }

    public function destroy(Auction $auction, PlayerAssignment $assignment, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('resolveCall', $auction);

        abort_unless($assignment->auction_id === $auction->id, 404);

        $engine->revert($assignment);

        return back();
    }
}

<?php

namespace App\Http\Controllers\Auction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auction\StoreAssignmentRequest;
use App\Http\Requests\Auction\UpdateAssignmentRequest;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\PlayerAssignment;
use App\Services\Auction\AuctionEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AuctionAssignmentController extends Controller
{
    public function store(StoreAssignmentRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('act', $auction);

        $participant = AuctionParticipant::query()->findOrFail((int) $request->validated('auction_participant_id'));

        $engine->assign($auction, $participant, (int) $request->validated('price'));

        return back();
    }

    public function update(UpdateAssignmentRequest $request, Auction $auction, PlayerAssignment $assignment, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('act', $auction);

        abort_unless($assignment->auction_id === $auction->id, 404);

        $participant = AuctionParticipant::query()->findOrFail((int) $request->validated('auction_participant_id'));

        $engine->correct($assignment, $participant, (int) $request->validated('price'));

        return back();
    }

    public function destroy(Auction $auction, PlayerAssignment $assignment, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('act', $auction);

        abort_unless($assignment->auction_id === $auction->id, 404);

        $engine->revert($assignment);

        return back();
    }
}

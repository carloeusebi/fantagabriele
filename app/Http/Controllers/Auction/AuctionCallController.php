<?php

namespace App\Http\Controllers\Auction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auction\StoreCallRequest;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Player;
use App\Services\Auction\AuctionEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AuctionCallController extends Controller
{
    public function store(StoreCallRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        $player = Player::query()->findOrFail((int) $request->validated('player_id'));
        $caller = AuctionParticipant::query()->findOrFail((int) $request->validated('called_by_auction_participant_id'));

        Gate::authorize('call', [$auction, $caller]);

        $engine->call($auction, $player, $caller);

        return back();
    }

    public function destroy(Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('resolveCall', $auction);

        $engine->cancelCall($auction);

        return back();
    }
}

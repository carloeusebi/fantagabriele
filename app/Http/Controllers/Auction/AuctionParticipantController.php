<?php

namespace App\Http\Controllers\Auction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auction\ReorderParticipantsRequest;
use App\Http\Requests\Auction\StoreParticipantRequest;
use App\Http\Requests\Auction\UpdateParticipantRequest;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Services\Auction\AuctionEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AuctionParticipantController extends Controller
{
    public function store(StoreParticipantRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('manageParticipants', $auction);

        $engine->addParticipant($auction, $request->validated('name'));

        return back();
    }

    public function update(UpdateParticipantRequest $request, Auction $auction, AuctionParticipant $participant, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('manageParticipants', $auction);

        abort_unless($participant->auction_id === $auction->id, 404);

        if ($request->boolean('is_agent')) {
            $engine->designateAgent($participant);
        }

        if ($request->has('name')) {
            $participant->update(['name' => $request->validated('name')]);
        }

        return back();
    }

    public function destroy(Auction $auction, AuctionParticipant $participant, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('manageParticipants', $auction);

        abort_unless($participant->auction_id === $auction->id, 404);

        $engine->removeParticipant($participant);

        return back();
    }

    public function reorder(ReorderParticipantsRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('manageParticipants', $auction);

        $engine->reorderParticipants($auction, $request->validated('order'));

        return back();
    }
}

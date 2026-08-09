<?php

namespace App\Http\Controllers\Auction;

use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\Exceptions\Auction\AuctionRuleException;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Services\Auction\AuctionContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuctionAdvisorController extends Controller
{
    public function suggestCall(Request $request, Auction $auction, AuctionContextBuilder $contextBuilder, AuctionAssistant $assistant): JsonResponse
    {
        Gate::authorize('advise', $auction);

        $participant = $this->controlledParticipant($request, $auction);

        $suggestion = $assistant->suggestCall($contextBuilder->forCall($auction, $participant));

        return response()->json([
            'player_id' => $suggestion->playerId,
            'reasoning' => $suggestion->reasoning,
        ]);
    }

    public function suggestMaxBid(Request $request, Auction $auction, AuctionContextBuilder $contextBuilder, AuctionAssistant $assistant): JsonResponse
    {
        Gate::authorize('advise', $auction);

        $participant = $this->controlledParticipant($request, $auction);

        $call = $auction->currentCall;

        if (! $call) {
            throw AuctionRuleException::because('Non c\'è nessuna chiamata aperta.');
        }

        $suggestion = $assistant->suggestMaxBid($contextBuilder->forBid($auction, $call->player, $participant));

        return response()->json([
            'max_price' => $suggestion->maxPrice,
            'reasoning' => $suggestion->reasoning,
        ]);
    }

    /**
     * The AI always reasons from the point of view of the participant the
     * requesting user has claimed — every player can ask for advice, but
     * only about their own team.
     */
    private function controlledParticipant(Request $request, Auction $auction): AuctionParticipant
    {
        $participant = $auction->viewerFor($request->user())?->participant;

        if ($participant === null) {
            throw AuctionRuleException::because('Devi partecipare all\'asta per chiedere consiglio all\'IA.');
        }

        return $participant;
    }
}

<?php

namespace App\Http\Controllers\Auction;

use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\Exceptions\Auction\AuctionRuleException;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Services\Auction\AuctionContextBuilder;
use Illuminate\Http\JsonResponse;

class AuctionAdvisorController extends Controller
{
    public function suggestCall(Auction $auction, AuctionContextBuilder $contextBuilder, AuctionAssistant $assistant): JsonResponse
    {
        $suggestion = $assistant->suggestCall($contextBuilder->forCall($auction));

        return response()->json([
            'player_id' => $suggestion->playerId,
            'reasoning' => $suggestion->reasoning,
        ]);
    }

    public function suggestMaxBid(Auction $auction, AuctionContextBuilder $contextBuilder, AuctionAssistant $assistant): JsonResponse
    {
        $call = $auction->currentCall;

        if (! $call) {
            throw AuctionRuleException::because('Non c\'è nessuna chiamata aperta.');
        }

        $suggestion = $assistant->suggestMaxBid($contextBuilder->forBid($auction, $call->player));

        return response()->json([
            'max_price' => $suggestion->maxPrice,
            'reasoning' => $suggestion->reasoning,
        ]);
    }
}

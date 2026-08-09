<?php

namespace App\AuctionAssistant;

use App\Ai\Agents\SuggestCallAgent;
use App\Ai\Agents\SuggestMaxBidAgent;
use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestion;
use App\AuctionAssistant\Data\CallSuggestionContext;
use App\Exceptions\Auction\AuctionRuleException;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

/**
 * The only place that chooses the AI provider — swapping providers, or
 * replacing this with a non-AI heuristic implementation, only requires
 * changing this class and its binding in AppServiceProvider.
 */
class LaravelAiAuctionAssistant implements AuctionAssistant
{
    public function suggestCall(CallSuggestionContext $context): CallSuggestion
    {
        $response = (new SuggestCallAgent($context))->prompt(
            'Scegli il prossimo giocatore da chiamare.',
            provider: Lab::DeepSeek,
        );

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Risposta dell\'agente non strutturata.');
        }

        $playerId = (int) $response['player_id'];
        $availableIds = array_column($context->availablePlayers, 'id');

        if (! in_array($playerId, $availableIds, true)) {
            throw AuctionRuleException::because('L\'agente ha suggerito un giocatore non disponibile.');
        }

        return new CallSuggestion($playerId, (string) $response['reasoning']);
    }

    public function suggestMaxBid(BidSuggestionContext $context): BidSuggestion
    {
        $response = (new SuggestMaxBidAgent($context))->prompt(
            'Calcola il prezzo massimo da offrire per questo giocatore.',
            provider: Lab::DeepSeek,
        );

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Risposta dell\'agente non strutturata.');
        }

        $budgetRemaining = (int) $context->controlledParticipant['budget_remaining'];
        $maxPrice = max(0, min((int) $response['max_price'], $budgetRemaining));

        return new BidSuggestion($maxPrice, (string) $response['reasoning']);
    }
}

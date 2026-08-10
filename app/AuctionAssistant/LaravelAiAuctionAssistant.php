<?php

namespace App\AuctionAssistant;

use App\Ai\Agents\NarrateBidAgent;
use App\Ai\Agents\NarrateCallAgent;
use App\Ai\Agents\StrategyAgent;
use App\Ai\Agents\SuggestCallAgent;
use App\Ai\Agents\SuggestMaxBidAgent;
use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\BidSuggestionContext;
use App\AuctionAssistant\Data\CallSuggestion;
use App\AuctionAssistant\Data\CallSuggestionContext;
use App\AuctionAssistant\Data\StrategyContext;
use App\AuctionAssistant\Data\TeamStrategy;
use App\Exceptions\Auction\AuctionRuleException;
use App\Models\Auction;
use App\Models\User;
use App\Services\Auction\AuctionAdvisorConversations;
use Generator;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use RuntimeException;

/**
 * The only place that chooses the AI provider — swapping providers, or
 * replacing this with a non-AI heuristic implementation, only requires
 * changing this class and its binding in AppServiceProvider.
 *
 * Every agent shares a single laravel/ai conversation per (auction, user),
 * tracked via AuctionAdvisorConversations, so the assistant remembers the
 * strategy it proposed and the suggestions it already gave as the auction
 * progresses.
 */
class LaravelAiAuctionAssistant implements AuctionAssistant
{
    public function __construct(private AuctionAdvisorConversations $conversations) {}

    public function suggestCall(CallSuggestionContext $context, Auction $auction, User $user): CallSuggestion
    {
        $agent = $this->withConversation(new SuggestCallAgent($context), $auction, $user);

        $response = $agent->prompt(
            'Scegli il prossimo giocatore da chiamare.',
            provider: Lab::OpenAI,
        );

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Risposta dell\'agente non strutturata.');
        }

        $this->rememberConversation($auction, $user, $response->conversationId);

        $playerId = (int) $response['player_id'];
        $availableIds = array_column($context->availablePlayers, 'id');

        if (! in_array($playerId, $availableIds, true)) {
            throw AuctionRuleException::because('L\'agente ha suggerito un giocatore non disponibile.');
        }

        return new CallSuggestion($playerId, (string) $response['reasoning'], (bool) $response['is_bluff']);
    }

    public function suggestMaxBid(BidSuggestionContext $context, Auction $auction, User $user): BidSuggestion
    {
        $agent = $this->withConversation(new SuggestMaxBidAgent($context), $auction, $user);

        $response = $agent->prompt(
            'Calcola il prezzo massimo da offrire per questo giocatore.',
            provider: Lab::OpenAI,
        );

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Risposta dell\'agente non strutturata.');
        }

        $this->rememberConversation($auction, $user, $response->conversationId);

        $budgetRemaining = (int) $context->controlledParticipant['budget_remaining'];
        $maxPrice = max(0, min((int) $response['max_price'], $budgetRemaining));

        return new BidSuggestion($maxPrice, (string) $response['reasoning'], (bool) $response['is_bluff']);
    }

    public function strategy(StrategyContext $context, Auction $auction, User $user): TeamStrategy
    {
        $agent = $this->withConversation(new StrategyAgent($context), $auction, $user);

        $response = $agent->prompt(
            'Definisci il piano strategico per questa asta.',
            provider: Lab::OpenAI,
        );

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Risposta dell\'agente non strutturata.');
        }

        $this->rememberConversation($auction, $user, $response->conversationId);

        return new TeamStrategy(
            (string) $response['summary'],
            (array) $response['budget_plan'],
            (array) $response['priority_targets'],
        );
    }

    /**
     * Decides the call first, yields that decision immediately so the caller
     * can show it right away, then narrates the reasoning behind it — rather
     * than narrating and deciding as two independent (and possibly
     * diverging) lines of reasoning.
     *
     * @return Generator<int, CallSuggestion|string, mixed, CallSuggestion>
     */
    public function streamCall(CallSuggestionContext $context, Auction $auction, User $user): Generator
    {
        $suggestion = $this->suggestCall($context, $auction, $user);

        yield $suggestion;

        $agent = $this->withConversation(new NarrateCallAgent($context, $suggestion), $auction, $user);

        $stream = $agent->stream(
            'Spiega brevemente il ragionamento dietro questa decisione.',
            provider: Lab::OpenAI,
        );

        foreach ($stream as $event) {
            if ($event instanceof TextDelta) {
                yield $event->delta;
            }
        }

        return $suggestion;
    }

    /**
     * Decides the max bid first, yields that decision immediately so the
     * caller can show it right away, then narrates the reasoning behind it
     * — rather than narrating and deciding as two independent (and possibly
     * diverging) lines of reasoning.
     *
     * @return Generator<int, BidSuggestion|string, mixed, BidSuggestion>
     */
    public function streamMaxBid(BidSuggestionContext $context, Auction $auction, User $user): Generator
    {
        $suggestion = $this->suggestMaxBid($context, $auction, $user);

        yield $suggestion;

        $agent = $this->withConversation(new NarrateBidAgent($context, $suggestion), $auction, $user);

        $stream = $agent->stream(
            'Spiega brevemente il ragionamento dietro questa decisione.',
            provider: Lab::OpenAI,
        );

        foreach ($stream as $event) {
            if ($event instanceof TextDelta) {
                yield $event->delta;
            }
        }

        return $suggestion;
    }

    /**
     * Resume this (auction, user)'s advisor conversation if one already
     * exists, otherwise start a new one for this user.
     */
    private function withConversation(Agent&Conversational $agent, Auction $auction, User $user): Agent&Conversational
    {
        $conversationId = $this->conversations->resolve($auction, $user);

        return $conversationId
            ? $agent->continue($conversationId, as: $user)
            : $agent->forUser($user);
    }

    private function rememberConversation(Auction $auction, User $user, ?string $conversationId): void
    {
        if ($conversationId !== null) {
            $this->conversations->remember($auction, $user, $conversationId);
        }
    }
}

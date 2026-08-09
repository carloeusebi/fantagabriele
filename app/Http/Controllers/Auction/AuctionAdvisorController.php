<?php

namespace App\Http\Controllers\Auction;

use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\CallSuggestion;
use App\Enums\AuctionAdvisorEntryKind;
use App\Exceptions\Auction\AuctionRuleException;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionAdvisorEntry;
use App\Models\AuctionParticipant;
use App\Models\User;
use App\Services\Auction\AuctionContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AuctionAdvisorController extends Controller
{
    public function suggestCall(Request $request, Auction $auction, AuctionContextBuilder $contextBuilder, AuctionAssistant $assistant): StreamedResponse
    {
        Gate::authorize('adviseCall', $auction);

        $participant = $this->controlledParticipant($request, $auction);

        $context = $contextBuilder->forCall($auction, $participant);
        $user = $request->user();

        return response()->eventStream(function () use ($assistant, $context, $auction, $user) {
            try {
                $stream = $assistant->streamCall($context, $auction, $user);

                foreach ($stream as $delta) {
                    yield new StreamedEvent(event: 'delta', data: $delta);
                }

                /** @var CallSuggestion $suggestion */
                $suggestion = $stream->getReturn();

                $this->recordEntry($auction, $user, AuctionAdvisorEntryKind::Call, $context->toArray(), $suggestion->reasoning, [
                    'player_id' => $suggestion->playerId,
                ], $suggestion->isBluff);

                yield new StreamedEvent(event: 'result', data: json_encode([
                    'player_id' => $suggestion->playerId,
                    'reasoning' => $suggestion->reasoning,
                    'is_bluff' => $suggestion->isBluff,
                ], JSON_THROW_ON_ERROR));
            } catch (Throwable $e) {
                yield new StreamedEvent(event: 'failure', data: json_encode([
                    'message' => $this->failureMessage($e),
                ], JSON_THROW_ON_ERROR));
            }
        }, endStreamWith: null);
    }

    public function suggestMaxBid(Request $request, Auction $auction, AuctionContextBuilder $contextBuilder, AuctionAssistant $assistant): StreamedResponse
    {
        Gate::authorize('advise', $auction);

        $participant = $this->controlledParticipant($request, $auction);

        $call = $auction->currentCall;

        if (! $call) {
            throw AuctionRuleException::because('Non c\'è nessuna chiamata aperta.');
        }

        $context = $contextBuilder->forBid($auction, $call->player, $participant);
        $user = $request->user();

        return response()->eventStream(function () use ($assistant, $context, $auction, $user) {
            try {
                $stream = $assistant->streamMaxBid($context, $auction, $user);

                foreach ($stream as $delta) {
                    yield new StreamedEvent(event: 'delta', data: $delta);
                }

                /** @var BidSuggestion $suggestion */
                $suggestion = $stream->getReturn();

                $this->recordEntry($auction, $user, AuctionAdvisorEntryKind::Bid, $context->toArray(), $suggestion->reasoning, [
                    'max_price' => $suggestion->maxPrice,
                ], $suggestion->isBluff);

                yield new StreamedEvent(event: 'result', data: json_encode([
                    'max_price' => $suggestion->maxPrice,
                    'reasoning' => $suggestion->reasoning,
                    'is_bluff' => $suggestion->isBluff,
                ], JSON_THROW_ON_ERROR));
            } catch (Throwable $e) {
                yield new StreamedEvent(event: 'failure', data: json_encode([
                    'message' => $this->failureMessage($e),
                ], JSON_THROW_ON_ERROR));
            }
        }, endStreamWith: null);
    }

    /**
     * Generate (or, if the conversation already holds one, revise) the
     * controlled participant's overall auction strategy. Unlike the call
     * and bid suggestions, this doesn't narrate live — it's a one-off plan
     * the user reads once it's ready.
     */
    public function strategy(Request $request, Auction $auction, AuctionContextBuilder $contextBuilder, AuctionAssistant $assistant): JsonResponse
    {
        Gate::authorize('advise', $auction);

        $participant = $this->controlledParticipant($request, $auction);
        $user = $request->user();

        $context = $contextBuilder->forStrategy($auction, $participant);

        $strategy = $assistant->strategy($context, $auction, $user);

        $entry = $this->recordEntry($auction, $user, AuctionAdvisorEntryKind::Strategy, $context->toArray(), $strategy->summary, [
            'summary' => $strategy->summary,
            'budget_plan' => $strategy->budgetPlan,
            'priority_targets' => $strategy->priorityTargets,
        ]);

        return response()->json(['entry' => $entry]);
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

    /**
     * @param  array<string, mixed>  $requestContext
     * @param  array<string, mixed>  $response
     */
    private function recordEntry(
        Auction $auction,
        User $user,
        AuctionAdvisorEntryKind $kind,
        array $requestContext,
        ?string $reasoning,
        array $response,
        bool $isBluff = false,
    ): AuctionAdvisorEntry {
        return AuctionAdvisorEntry::query()->create([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'kind' => $kind,
            'request_context' => $requestContext,
            'reasoning' => $reasoning,
            'response' => $response,
            'is_bluff' => $isBluff,
        ]);
    }

    /**
     * Once streaming has started, the response is already a 200 OK, so a
     * thrown exception can no longer become a normal error response — it is
     * instead surfaced to the client as a "failure" stream event.
     */
    private function failureMessage(Throwable $e): string
    {
        return $e instanceof AuctionRuleException
            ? $e->getMessage()
            : 'Errore durante la richiesta all\'IA.';
    }
}

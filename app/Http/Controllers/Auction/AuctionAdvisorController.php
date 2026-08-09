<?php

namespace App\Http\Controllers\Auction;

use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\CallSuggestion;
use App\Exceptions\Auction\AuctionRuleException;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Services\Auction\AuctionContextBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AuctionAdvisorController extends Controller
{
    public function suggestCall(Request $request, Auction $auction, AuctionContextBuilder $contextBuilder, AuctionAssistant $assistant): StreamedResponse
    {
        Gate::authorize('advise', $auction);

        $context = $contextBuilder->forCall($auction, $this->controlledParticipant($request, $auction));

        return response()->eventStream(function () use ($assistant, $context) {
            try {
                $stream = $assistant->streamCall($context);

                foreach ($stream as $delta) {
                    yield new StreamedEvent(event: 'delta', data: $delta);
                }

                /** @var CallSuggestion $suggestion */
                $suggestion = $stream->getReturn();

                yield new StreamedEvent(event: 'result', data: json_encode([
                    'player_id' => $suggestion->playerId,
                    'reasoning' => $suggestion->reasoning,
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

        return response()->eventStream(function () use ($assistant, $context) {
            try {
                $stream = $assistant->streamMaxBid($context);

                foreach ($stream as $delta) {
                    yield new StreamedEvent(event: 'delta', data: $delta);
                }

                /** @var BidSuggestion $suggestion */
                $suggestion = $stream->getReturn();

                yield new StreamedEvent(event: 'result', data: json_encode([
                    'max_price' => $suggestion->maxPrice,
                    'reasoning' => $suggestion->reasoning,
                ], JSON_THROW_ON_ERROR));
            } catch (Throwable $e) {
                yield new StreamedEvent(event: 'failure', data: json_encode([
                    'message' => $this->failureMessage($e),
                ], JSON_THROW_ON_ERROR));
            }
        }, endStreamWith: null);
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

<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\BidSuggestionContext;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Narrates the reasoning behind a max-bid suggestion in free-form text,
 * meant to be streamed live to the user. The actual price is decided
 * separately by SuggestMaxBidAgent, since a structured decision can't be
 * safely parsed out of a streamed narration.
 */
class NarrateBidAgent implements Agent
{
    use Promptable;

    public function __construct(public BidSuggestionContext $context) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<TEXT
            Sei l'assistente di un partecipante a un'asta del fantacalcio dal vivo. Il giocatore descritto in "player" è in asta in questo momento.

            Spiega brevemente in italiano, come se stessi pensando ad alta voce, quanto vale la pena offrire per aggiudicartelo, considerando i crediti residui e gli slot ancora liberi del partecipante che controlli, il valore del giocatore (FVM e quotazione) e la scarsità del ruolo ("competitors_needing_role" e "remaining_pool_size"). Concludi con una frase che indichi chiaramente la cifra massima che consiglieresti.

            Contesto (JSON):
            {$this->encodedContext()}
            TEXT;
    }

    private function encodedContext(): string
    {
        return json_encode($this->context->toArray(), JSON_THROW_ON_ERROR);
    }
}

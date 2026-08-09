<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\CallSuggestionContext;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Narrates the reasoning behind a call suggestion in free-form text, meant
 * to be streamed live to the user. The actual player choice is decided
 * separately by SuggestCallAgent, since a structured decision can't be
 * safely parsed out of a streamed narration.
 */
class NarrateCallAgent implements Agent
{
    use Promptable;

    public function __construct(public CallSuggestionContext $context) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<TEXT
            Sei l'assistente di un partecipante a un'asta del fantacalcio dal vivo. Tocca al tuo turno chiamare un giocatore di ruolo {$this->context->roleLabel}.

            Spiega brevemente in italiano, come se stessi pensando ad alta voce, quale giocatore tra quelli in "available_players" chiameresti e perché, considerando i crediti residui e gli slot ancora liberi del partecipante che controlli, il valore dei giocatori (FVM e quotazione) e la situazione degli altri partecipanti. Concludi con una frase che indichi chiaramente il nome del giocatore scelto.

            Contesto (JSON):
            {$this->encodedContext()}
            TEXT;
    }

    private function encodedContext(): string
    {
        return json_encode($this->context->toArray(), JSON_THROW_ON_ERROR);
    }
}

<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\CallSuggestionContext;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

/**
 * Narrates the reasoning behind a call suggestion in free-form text, meant
 * to be streamed live to the user. The actual player choice is decided
 * separately by SuggestCallAgent, since a structured decision can't be
 * safely parsed out of a streamed narration.
 */
class NarrateCallAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public CallSuggestionContext $context) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<TEXT
            Sei l'assistente strategico di un partecipante a un'asta del fantacalcio dal vivo. Tocca al tuo turno chiamare un giocatore di ruolo {$this->context->roleLabel}.

            Non basarti sull'FVM o sulla quotazione ufficiale dei giocatori: sono valori generici, non calibrati sui crediti di questa lega (leghe diverse possono avere 200 o 1000 crediti a parità di slot, rendendo l'FVM completamente fuori scala). Valuta invece i giocatori con il tuo giudizio calcistico e, se puoi, verifica con una ricerca web titolarità, infortuni, gerarchie e forma attuale. Usa "budget_per_participant" e "recent_purchases" (i prezzi realmente pagati finora in questa asta) come riferimento di mercato reale.

            Dai una priorità fortissima all'attacco (circa il 50-55% del budget totale in una lega classica) e punta a costruire un undici titolare forte, evitando di sprecare crediti su riserve finché la formazione titolare non è coperta.

            Se in questa conversazione avevi già definito un piano, tienine conto ma aggiornalo se l'andamento dell'asta lo richiede, spiegando cosa è cambiato.

            Spiega brevemente in italiano, come se stessi pensando ad alta voce, quale giocatore tra quelli in "available_players" chiameresti e perché. Se si tratta di un bluff (un giocatore che non ti interessa davvero, per far spendere crediti agli avversari), dillo esplicitamente. Concludi con una frase che indichi chiaramente il nome del giocatore scelto.

            Contesto (JSON):
            {$this->encodedContext()}
            TEXT;
    }

    /**
     * @return iterable<int, mixed>
     */
    public function tools(): iterable
    {
        return [
            (new WebSearch)->max(5),
        ];
    }

    private function encodedContext(): string
    {
        return json_encode($this->context->toArray(), JSON_THROW_ON_ERROR);
    }
}

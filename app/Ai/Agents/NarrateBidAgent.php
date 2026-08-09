<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\BidSuggestionContext;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

/**
 * Narrates the reasoning behind a max-bid suggestion in free-form text,
 * meant to be streamed live to the user. The actual price is decided
 * separately by SuggestMaxBidAgent, since a structured decision can't be
 * safely parsed out of a streamed narration.
 */
class NarrateBidAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public BidSuggestionContext $context) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<TEXT
            Sei l'assistente strategico di un partecipante a un'asta del fantacalcio dal vivo. Il giocatore descritto in "player" è in asta in questo momento.

            Non basarti sull'FVM o sulla quotazione ufficiale del giocatore: sono valori generici, non calibrati sui crediti di questa lega. Valuta il giocatore con il tuo giudizio calcistico e, se puoi, verifica con una ricerca web titolarità, infortuni, gerarchie e forma attuale. Usa "budget_per_participant" e "recent_purchases" (i prezzi realmente pagati finora in questa asta) come riferimento di mercato reale, oltre alla scarsità del ruolo ("competitors_needing_role" e "remaining_pool_size").

            Dai una priorità fortissima all'attacco (circa il 50-55% del budget totale in una lega classica): sii disposto a spendere di più per un attaccante titolare che ti serve, molto meno per un ruolo già coperto o una riserva.

            Se in questa conversazione avevi già definito un piano, tienine conto ma aggiornalo se l'andamento dell'asta lo richiede.

            Spiega brevemente in italiano, come se stessi pensando ad alta voce, quanto vale la pena offrire per aggiudicartelo. Se si tratta di un bluff (un prezzo sopra il valore reale per far spendere un avversario), dillo esplicitamente e avverti del rischio di aggiudicartelo comunque tu se nessuno rilancia. Concludi con una frase che indichi chiaramente la cifra massima che consiglieresti.

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

<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\BidSuggestionContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

class SuggestMaxBidAgent implements Agent, Conversational, HasStructuredOutput, HasTools
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

            Non basarti sull'FVM o sulla quotazione ufficiale del giocatore: sono valori generici, non calibrati sui crediti di questa lega (leghe diverse possono avere 200 o 1000 crediti a parità di slot, rendendo l'FVM completamente fuori scala). Valuta il giocatore con il tuo giudizio calcistico e, se hai a disposizione uno strumento di ricerca web, usalo per verificare titolarità, infortuni, gerarchie e forma attuale prima di calcolare il prezzo. Come riferimento di prezzo reale usa "budget_per_participant" (i crediti iniziali di questa asta) e "recent_purchases" (i prezzi realmente pagati finora in questa stessa asta), oltre alla scarsità del ruolo ("competitors_needing_role" e "remaining_pool_size").

            Dai una priorità fortissima all'attacco: in una lega classica circa il 50-55% del budget totale va speso in attaccanti, quindi sii disposto a spendere di più per un attaccante titolare che ti serve davvero. Il tuo obiettivo primario è un undici titolare forte: sii generoso sui titolari che completano la formazione, molto conservativo se il ruolo è già coperto o se si tratta di una riserva.

            Se in questa conversazione avevi già definito un piano strategico, tienine conto ma aggiornalo se l'andamento dell'asta lo richiede, spiegando nel "reasoning" cosa è cambiato.

            Puoi anche suggerire un prezzo "bluff" più alto del valore reale del giocatore, con l'obiettivo di far spendere crediti a un avversario specifico su un giocatore che a te non interessa davvero. Se lo fai, imposta "is_bluff" a true e avverti SEMPRE chiaramente nel "reasoning" del rischio: se nessuno rilancia oltre quella cifra, il giocatore se lo aggiudica comunque l'utente, a quel prezzo.

            Il prezzo massimo non può mai superare i crediti residui del partecipante controllato.

            Contesto (JSON):
            {$this->encodedContext()}
            TEXT;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'max_price' => $schema->integer()->required()
                ->description('Prezzo massimo in crediti da offrire per il giocatore.'),
            'reasoning' => $schema->string()->required()
                ->description('Breve spiegazione della scelta, in italiano.'),
            'is_bluff' => $schema->boolean()->required()
                ->description('True se questo è un prezzo di bluff (sopra il valore reale, per far spendere un avversario), altrimenti false.'),
        ];
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

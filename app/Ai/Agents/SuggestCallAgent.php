<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\CallSuggestionContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

class SuggestCallAgent implements Agent, Conversational, HasStructuredOutput, HasTools
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

            Non basarti sull'FVM o sulla quotazione ufficiale dei giocatori: sono valori generici, non calibrati sui crediti di questa lega (leghe diverse possono avere 200 o 1000 crediti a parità di slot, rendendo l'FVM completamente fuori scala). Valuta invece i giocatori con il tuo giudizio calcistico. Se hai a disposizione uno strumento di ricerca web, usalo per verificare titolarità, infortuni, gerarchie e forma attuale prima di scegliere. Come riferimento di prezzo reale usa "budget_per_participant" (i crediti iniziali di questa asta) e "recent_purchases" (i prezzi realmente pagati finora in questa stessa asta): sono gli unici segnali di mercato affidabili per questa lega.

            Dai una priorità fortissima all'attacco: in una lega classica circa il 50-55% del budget totale va speso in attaccanti. Il tuo obiettivo primario è costruire un undici titolare forte in ogni reparto, non riempire tutti gli slot con pari importanza: pianifica di destinare la stragrande maggioranza del budget ai titolari, riservando solo pochi crediti simbolici alle riserve/panchinari a fine asta. Puoi comunque scegliere un giocatore di scarso valore per "consumare" il turno senza sprecare crediti, se strategicamente conveniente.

            Se in questa conversazione avevi già definito un piano strategico o dei giocatori obiettivo, tienine conto ma aggiornalo liberamente se l'andamento dell'asta lo richiede (prezzi pagati finora, giocatori già assegnati ad altri): spiega brevemente nel "reasoning" se e perché ti stai discostando dal piano iniziale.

            Puoi anche suggerire un "bluff": chiamare un giocatore che non ti interessa davvero, solo per far spendere crediti agli avversari o consumare il turno di un rivale su un giocatore che non vuoi si aggiudichi a poco. Se lo fai, imposta "is_bluff" a true e spiega SEMPRE chiaramente nel "reasoning" che si tratta di un bluff e perché.

            Rispondi SOLO con l'id di un giocatore presente in "available_players".

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
            'player_id' => $schema->integer()->required()
                ->description('Id del giocatore da chiamare, deve essere uno di quelli in available_players.'),
            'reasoning' => $schema->string()->required()
                ->description('Breve spiegazione della scelta, in italiano.'),
            'is_bluff' => $schema->boolean()->required()
                ->description('True se questa è una chiamata di bluff (giocatore che non interessa davvero), altrimenti false.'),
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

<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\StrategyContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

/**
 * Builds (or, when the conversation already holds one, revises) the
 * participant's overall auction strategy: how to split the budget across
 * roles and which players to prioritize. This is always the first thing
 * asked in a new advisor conversation, so later call/bid suggestions in the
 * same conversation can refer back to the plan it lays out here.
 */
#[Temperature(0.75)]
class StrategyAgent implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public StrategyContext $context) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<TEXT
            Sei l'assistente strategico di un partecipante a un'asta del fantacalcio dal vivo. Devi definire (o, se ne avevi già proposto uno in questa conversazione, aggiornare) il piano d'azione per costruire la squadra migliore possibile con i crediti a disposizione in QUESTA asta.

            Non basarti sull'FVM o sulla quotazione ufficiale dei giocatori: sono valori generici, non calibrati sui crediti di questa lega (leghe diverse possono avere 200 o 1000 crediti a parità di slot, rendendo l'FVM completamente fuori scala). Ragiona sui crediti iniziali reali di questa asta ("budget_per_participant") e sugli slot da riempire per ruolo ("role_slots"). Se ci sono già "recent_purchases" in questa asta, usali come riferimento di mercato reale. Valuta i giocatori elencati in "available_players_by_role" con il tuo giudizio calcistico e, se hai uno strumento di ricerca web, usalo per verificare titolarità, gerarchie, infortuni e stato di forma dei giocatori che stai considerando come obiettivi prioritari.

            Dai una priorità fortissima all'attacco: pianifica di destinare circa il 50-55% del budget totale agli attaccanti, la parte restante ripartita sugli altri reparti secondo necessità. Il tuo obiettivo primario è un undici titolare forte in ogni reparto, non riempire tutti gli slot con pari importanza: pianifica di spendere la stragrande maggioranza del budget sui titolari, riservando solo pochi crediti simbolici alle riserve/panchinari a fine asta.

            Se stai aggiornando un piano precedente perché la situazione dell'asta è cambiata (prezzi pagati finora, giocatori già assegnati agli avversari), spiegalo chiaramente nel "summary".

            Il "summary" deve essere un testo in italiano, chiaro e diretto, che verrà mostrato subito all'utente come piano dichiarato: deve spiegare la ripartizione del budget per reparto con il bias sull'attacco, e i principali obiettivi (senza necessariamente elencare ogni singolo prezzo).

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
            'summary' => $schema->string()->required()
                ->description('Piano strategico in italiano da mostrare direttamente all\'utente.'),
            'budget_plan' => $schema->array()->required()
                ->items(
                    $schema->object([
                        'role' => $schema->string()->required()->description('Codice ruolo: P, D, C o A.'),
                        'target_credits' => $schema->integer()->required()->description('Crediti che pianifichi di spendere in questo reparto.'),
                        'target_percent' => $schema->number()->required()->description('Percentuale del budget totale destinata a questo reparto.'),
                    ])
                )
                ->description('Ripartizione pianificata del budget per ruolo (P, D, C, A).'),
            'priority_targets' => $schema->array()->required()
                ->items(
                    $schema->object([
                        'role' => $schema->string()->required()->description('Codice ruolo: P, D, C o A.'),
                        'name' => $schema->string()->required()->description('Nome del giocatore obiettivo.'),
                        'notes' => $schema->string()->required()->description('Perché è un obiettivo prioritario, in italiano.'),
                    ])
                )
                ->description('Elenco di giocatori obiettivo prioritari, alcuni per reparto.'),
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

<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\CallSuggestionContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class SuggestCallAgent implements Agent, HasStructuredOutput
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

            Scegli il giocatore migliore da chiamare tra quelli in "available_players", considerando i crediti residui e gli slot ancora liberi del partecipante che controlli, il valore dei giocatori (FVM e quotazione) e la situazione degli altri partecipanti. Puoi anche scegliere un giocatore di scarso valore per "consumare" il turno senza sprecare crediti, se strategicamente conveniente.

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
        ];
    }

    private function encodedContext(): string
    {
        return json_encode($this->context->toArray(), JSON_THROW_ON_ERROR);
    }
}

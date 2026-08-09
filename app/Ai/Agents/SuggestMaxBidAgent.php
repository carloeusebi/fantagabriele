<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\BidSuggestionContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class SuggestMaxBidAgent implements Agent, HasStructuredOutput
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

            Calcola il prezzo massimo in crediti che vale la pena offrire per aggiudicarselo, considerando i crediti residui e gli slot ancora liberi del partecipante che controlli, il valore del giocatore (FVM e quotazione) e la scarsità del ruolo ("competitors_needing_role" e "remaining_pool_size"). Il prezzo massimo non può mai superare i crediti residui del partecipante controllato.

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
        ];
    }

    private function encodedContext(): string
    {
        return json_encode($this->context->toArray(), JSON_THROW_ON_ERROR);
    }
}

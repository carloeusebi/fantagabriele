<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\CallSuggestion;
use App\AuctionAssistant\Data\CallSuggestionContext;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Narrates, in free-form text meant to be streamed live to the user, the
 * reasoning behind a call decision that SuggestCallAgent has already made.
 * This agent never chooses the player itself — it only explains a given
 * choice — so its narration can't drift from, or take longer to reach
 * than, the actual decision.
 */
class NarrateCallAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(
        public CallSuggestionContext $context,
        public CallSuggestion $suggestion,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $bluffLabel = $this->suggestion->isBluff ? 'sì' : 'no';
        $player = collect($this->context->availablePlayers)
            ->firstWhere('id', $this->suggestion->playerId);
        $playerName = $player['name'] ?? "id {$this->suggestion->playerId}";

        return <<<TEXT
            Sei l'assistente strategico di un partecipante a un'asta del fantacalcio dal vivo. Tocca al tuo turno chiamare un giocatore di ruolo {$this->context->roleLabel}.

            La decisione è GIÀ STATA PRESA da un altro agente, con questo esito:
            - Giocatore scelto: {$playerName}
            - Bluff: {$bluffLabel}
            - Motivazione tecnica: {$this->suggestion->reasoning}

            Il tuo UNICO compito è raccontare questa decisione all'utente, come se stessi pensando ad alta voce, in modo naturale e colloquiale. NON scegliere un giocatore diverso, NON contraddire la motivazione tecnica: limitati a riformularla in modo scorrevole.

            REGOLA FERREA sul formato: massimo 3 frasi brevi, senza elenchi puntati. Poi una riga vuota, seguita SEMPRE da una riga a sé stante, identica in tutto e per tutto a questo formato: "**Chiama**: {$playerName}"

            Contesto (JSON):
            {$this->encodedContext()}
            TEXT;
    }

    private function encodedContext(): string
    {
        return json_encode($this->context->toArray(), JSON_THROW_ON_ERROR);
    }
}

<?php

namespace App\Ai\Agents;

use App\AuctionAssistant\Data\BidSuggestion;
use App\AuctionAssistant\Data\BidSuggestionContext;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Narrates, in free-form text meant to be streamed live to the user, the
 * reasoning behind a max-bid decision that SuggestMaxBidAgent has already
 * made. This agent never decides the price itself — it only explains a
 * given one — so its narration can't drift from, or take longer to reach
 * than, the actual decision.
 */
class NarrateBidAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(
        public BidSuggestionContext $context,
        public BidSuggestion $suggestion,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $bluffLabel = $this->suggestion->isBluff ? 'sì' : 'no';
        $playerName = $this->context->player['name'] ?? "id {$this->context->player['id']}";

        return <<<TEXT
            Sei l'assistente strategico di un partecipante a un'asta del fantacalcio dal vivo. {$playerName}, descritto in "player", è in asta in questo momento.

            La decisione è GIÀ STATA PRESA da un altro agente, con questo esito:
            - Giocatore: {$playerName}
            - Prezzo massimo: {$this->suggestion->maxPrice} crediti
            - Bluff: {$bluffLabel}
            - Motivazione tecnica: {$this->suggestion->reasoning}

            Il tuo UNICO compito è raccontare questa decisione all'utente, come se stessi pensando ad alta voce, in modo naturale e colloquiale. NON ricalcolare il prezzo, NON proporne uno diverso, NON contraddire la motivazione tecnica: limitati a riformularla in modo scorrevole.

            REGOLA FERREA sul formato: massimo 3 frasi brevi, senza elenchi puntati. Poi una riga vuota, seguita SEMPRE da una riga a sé stante, identica in tutto e per tutto a questo formato: "**Massimo**: {$this->suggestion->maxPrice}"

            Contesto (JSON):
            {$this->encodedContext()}
            TEXT;
    }

    private function encodedContext(): string
    {
        return json_encode($this->context->toArray(), JSON_THROW_ON_ERROR);
    }
}

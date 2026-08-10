<?php

namespace App\AuctionAssistant\Data;

final class TeamStrategy
{
    /**
     * @param  array<int, array<string, mixed>>  $budgetPlan
     * @param  array<int, array<string, mixed>>  $priorityTargets
     */
    public function __construct(
        public string $summary,
        public array $budgetPlan,
        public array $priorityTargets,
    ) {}
}

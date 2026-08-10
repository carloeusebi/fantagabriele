<?php

namespace App\Http\Controllers;

use App\Enums\StrategyType;
use App\Http\Requests\Strategy\StoreStrategyRequest;
use App\Http\Requests\Strategy\UpdateStrategyRequest;
use App\Models\Player;
use App\Models\Strategy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StrategyController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('strategies/index', [
            'strategies' => $request->user()->strategies()->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('strategies/create', [
            'players' => $this->players(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function store(StoreStrategyRequest $request): RedirectResponse
    {
        $strategy = DB::transaction(function () use ($request) {
            $strategy = $request->user()->strategies()->create($request->safe()->except('priority_player_ids'));

            $strategy->priorityPlayers()->sync($request->validated('priority_player_ids', []));

            return $strategy;
        });

        return to_route('strategies.edit', $strategy);
    }

    public function edit(Strategy $strategy): Response
    {
        Gate::authorize('update', $strategy);

        $strategy->load('priorityPlayers.team');

        return Inertia::render('strategies/edit', [
            'strategy' => [
                ...$strategy->toArray(),
                'priority_player_ids' => $strategy->priorityPlayers->pluck('id'),
            ],
            'players' => $this->players(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateStrategyRequest $request, Strategy $strategy): RedirectResponse
    {
        Gate::authorize('update', $strategy);

        DB::transaction(function () use ($request, $strategy) {
            $strategy->update($request->safe()->except('priority_player_ids'));

            $strategy->priorityPlayers()->sync($request->validated('priority_player_ids', []));
        });

        return to_route('strategies.index');
    }

    public function destroy(Strategy $strategy): RedirectResponse
    {
        Gate::authorize('delete', $strategy);

        $strategy->delete();

        return to_route('strategies.index');
    }

    /**
     * @return Collection<int, Player>
     */
    private function players(): Collection
    {
        return Player::query()->with('team')->orderBy('name')->get();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function typeOptions(): array
    {
        return collect(StrategyType::cases())->map(fn (StrategyType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ])->values()->all();
    }
}

<?php

namespace App\Http\Controllers\Auction;

use App\Enums\PlayerRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auction\StoreAuctionRequest;
use App\Http\Requests\Auction\UpdateAuctionStatusRequest;
use App\Models\Auction;
use App\Models\Player;
use App\Models\PlayerAssignment;
use App\Services\Auction\AuctionEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

class AuctionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('auctions/index', [
            'auctions' => Auction::query()->withCount('participants')->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('auctions/create');
    }

    public function store(StoreAuctionRequest $request): RedirectResponse
    {
        $auction = DB::transaction(function () use ($request) {
            $auction = Auction::query()->create($request->safe()->only([
                'name', 'budget_per_participant',
                'slots_goalkeeper', 'slots_defender', 'slots_midfielder', 'slots_forward',
            ]));

            foreach ($request->validated('participants') as $index => $participant) {
                $auction->participants()->create([
                    'name' => $participant['name'],
                    'is_agent' => $participant['is_agent'] ?? false,
                    'call_order' => $index + 1,
                ]);
            }

            return $auction;
        });

        return to_route('auctions.show', $auction);
    }

    public function show(Auction $auction): Response
    {
        $auction->load(['currentTurnParticipant', 'currentCall.player.team', 'currentCall.calledBy']);

        $participants = $auction->participants()->orderBy('call_order')->get()->map->toBoardArray()->values();

        $availablePlayers = $auction->current_phase
            ? Player::query()
                ->where('role', $auction->current_phase)
                ->whereDoesntHave('assignments', fn ($query) => $query->where('auction_id', $auction->id))
                ->with('team')
                ->orderByDesc('fvm')
                ->get()
            : collect();

        $assignments = PlayerAssignment::query()
            ->where('auction_id', $auction->id)
            ->with(['player.team', 'participant'])
            ->latest()
            ->get();

        return Inertia::render('auctions/show', [
            'auction' => $auction,
            'participants' => $participants,
            'availablePlayers' => $availablePlayers,
            'assignments' => $assignments,
            'roles' => collect(PlayerRole::cases())->map(fn (PlayerRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])->values(),
        ]);
    }

    public function updateStatus(UpdateAuctionStatusRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        match ((string) $request->validated('action')) {
            'start' => $engine->start($auction),
            'pause' => $engine->pause($auction),
            'resume' => $engine->resume($auction),
            default => throw new LogicException('Invalid auction status action.'),
        };

        return back();
    }
}

<?php

namespace App\Http\Controllers\Auction;

use App\Enums\PlayerRole;
use App\Exceptions\Auction\AuctionRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auction\JoinAuctionRequest;
use App\Http\Requests\Auction\StoreAuctionRequest;
use App\Http\Requests\Auction\UnlockAuctionRequest;
use App\Http\Requests\Auction\UpdateAuctionStatusRequest;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Player;
use App\Models\PlayerAssignment;
use App\Services\Auction\AuctionEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
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
            $auction = Auction::query()->create([
                ...$request->safe()->only([
                    'name', 'password', 'budget_per_participant',
                    'slots_goalkeeper', 'slots_defender', 'slots_midfielder', 'slots_forward',
                ]),
                'user_id' => $request->user()->id,
            ]);

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

    public function show(Request $request, Auction $auction): Response
    {
        if ($this->isLocked($request, $auction)) {
            return Inertia::render('auctions/show', [
                'locked' => true,
                'auction' => [
                    'id' => $auction->id,
                    'name' => $auction->name,
                ],
            ]);
        }

        $auction->load(['currentTurnParticipant', 'currentCall.player.team', 'currentCall.calledBy']);

        $participants = $auction->participants()->orderBy('call_order')->with('viewer.user')->get()->map->toBoardArray()->values();

        $viewer = $auction->viewers()->where('user_id', $request->user()->id)->first();

        $availablePlayers = $auction->current_phase
            ? Player::query()
                ->where('role', $auction->current_phase)
                ->whereDoesntHave('assignments', fn ($query) => $query->where('auction_id', $auction->id))
                ->with('team')
                ->orderByDesc('fvm')
                ->get()
            : collect();

        $canResolveCall = Gate::allows('resolveCall', $auction);

        $unassignedPlayers = $canResolveCall
            ? Player::query()
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

        $claimedParticipant = $viewer?->auction_participant_id
            ? AuctionParticipant::query()->find($viewer->auction_participant_id)
            : null;

        return Inertia::render('auctions/show', [
            'locked' => false,
            'auction' => $auction,
            'participants' => $participants,
            'availablePlayers' => $availablePlayers,
            'unassignedPlayers' => $unassignedPlayers,
            'assignments' => $assignments,
            'viewer' => $viewer ? [
                'auction_participant_id' => $viewer->auction_participant_id,
            ] : null,
            'permissions' => [
                'manage' => Gate::allows('manageParticipants', $auction),
                'updateStatus' => Gate::allows('updateStatus', $auction),
                'call' => $request->user()->isAdmin()
                    || ($claimedParticipant !== null && Gate::allows('call', [$auction, $claimedParticipant])),
                'resolveCall' => $canResolveCall,
                'advise' => Gate::allows('advise', $auction),
            ],
            'roles' => collect(PlayerRole::cases())->map(fn (PlayerRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])->values(),
        ]);
    }

    public function unlock(UnlockAuctionRequest $request, Auction $auction): RedirectResponse
    {
        if (! $auction->hasPassword() || ! Hash::check($request->validated('password'), $auction->password)) {
            throw AuctionRuleException::because('Password errata.');
        }

        $request->session()->put($this->unlockSessionKey($auction), true);

        return to_route('auctions.show', $auction);
    }

    public function join(JoinAuctionRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        $participant = $request->validated('auction_participant_id')
            ? AuctionParticipant::query()->findOrFail((int) $request->validated('auction_participant_id'))
            : null;

        $engine->joinAuction($auction, $request->user(), $participant);

        return back();
    }

    public function updateStatus(UpdateAuctionStatusRequest $request, Auction $auction, AuctionEngine $engine): RedirectResponse
    {
        Gate::authorize('updateStatus', $auction);

        match ((string) $request->validated('action')) {
            'start' => $engine->start($auction),
            'pause' => $engine->pause($auction),
            'resume' => $engine->resume($auction),
            default => throw new LogicException('Invalid auction status action.'),
        };

        return back();
    }

    public function destroy(Auction $auction): RedirectResponse
    {
        Gate::authorize('delete', $auction);

        $auction->delete();

        return to_route('auctions.index');
    }

    /**
     * A password-protected auction is locked for anyone but its owner and
     * admins until they unlock it for the current session.
     */
    private function isLocked(Request $request, Auction $auction): bool
    {
        if (! $auction->hasPassword()) {
            return false;
        }

        if ($request->user()->isAdmin() || $request->user()->id === $auction->user_id) {
            return false;
        }

        return ! $request->session()->get($this->unlockSessionKey($auction), false);
    }

    private function unlockSessionKey(Auction $auction): string
    {
        return "auctions.{$auction->id}.unlocked";
    }
}

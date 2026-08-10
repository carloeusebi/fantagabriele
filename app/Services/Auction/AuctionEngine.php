<?php

namespace App\Services\Auction;

use App\Enums\AuctionCallStatus;
use App\Enums\AuctionStatus;
use App\Enums\PlayerRole;
use App\Events\Auction\AssignmentAdded;
use App\Events\Auction\AssignmentCorrected;
use App\Events\Auction\AssignmentReverted;
use App\Events\Auction\AuctionOrderUpdated;
use App\Events\Auction\AuctionStatusChanged;
use App\Events\Auction\AuctionViewerJoined;
use App\Events\Auction\PlayerAssigned;
use App\Events\Auction\PlayerCalled;
use App\Events\Auction\PlayerUnsold;
use App\Exceptions\Auction\AuctionRuleException;
use App\Models\Auction;
use App\Models\AuctionCall;
use App\Models\AuctionParticipant;
use App\Models\AuctionViewer;
use App\Models\Player;
use App\Models\PlayerAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuctionEngine
{
    /**
     * Start the auction: opens the goalkeeper phase and sets the turn to the
     * first participant in calling order.
     */
    public function start(Auction $auction): Auction
    {
        $auction = DB::transaction(function () use ($auction) {
            $auction = $this->lock($auction);

            if ($auction->status !== AuctionStatus::Setup) {
                throw AuctionRuleException::because('L\'asta è già stata avviata.');
            }

            $participants = $auction->participants()->orderBy('call_order')->get();

            if ($participants->isEmpty()) {
                throw AuctionRuleException::because('Aggiungi almeno un partecipante prima di avviare l\'asta.');
            }

            $auction->update([
                'status' => AuctionStatus::InProgress,
                'current_phase' => PlayerRole::Goalkeeper,
                'current_turn_auction_participant_id' => $participants->first()->id,
            ]);

            return $auction;
        });

        AuctionStatusChanged::dispatch($auction);

        return $auction;
    }

    public function pause(Auction $auction): void
    {
        $auction = DB::transaction(function () use ($auction) {
            $auction = $this->lock($auction);

            if ($auction->status !== AuctionStatus::InProgress) {
                throw AuctionRuleException::because('Solo un\'asta in corso può essere messa in pausa.');
            }

            $auction->update(['status' => AuctionStatus::Paused]);

            return $auction;
        });

        AuctionStatusChanged::dispatch($auction);
    }

    public function resume(Auction $auction): void
    {
        $auction = DB::transaction(function () use ($auction) {
            $auction = $this->lock($auction);

            if ($auction->status !== AuctionStatus::Paused) {
                throw AuctionRuleException::because('Solo un\'asta in pausa può essere ripresa.');
            }

            $auction->update(['status' => AuctionStatus::InProgress]);

            return $auction;
        });

        AuctionStatusChanged::dispatch($auction);
    }

    /**
     * Choose (or change) how a user is viewing this auction: as a
     * spectator (null participant) or as one of the participants. A
     * participant may only be claimed by one user at a time.
     */
    public function joinAuction(Auction $auction, User $user, ?AuctionParticipant $participant): AuctionViewer
    {
        $viewer = DB::transaction(function () use ($auction, $user, $participant) {
            $auction = $this->lock($auction);

            if ($participant !== null) {
                if ($participant->auction_id !== $auction->id) {
                    throw AuctionRuleException::because('Il partecipante non appartiene a questa asta.');
                }

                $alreadyClaimed = AuctionViewer::query()
                    ->where('auction_participant_id', $participant->id)
                    ->where('user_id', '!=', $user->id)
                    ->exists();

                if ($alreadyClaimed) {
                    throw AuctionRuleException::because("{$participant->name} è già stato scelto da un altro utente.");
                }
            }

            return AuctionViewer::query()->updateOrCreate(
                ['auction_id' => $auction->id, 'user_id' => $user->id],
                ['auction_participant_id' => $participant?->id],
            );
        });

        AuctionViewerJoined::dispatch($auction, $viewer);

        return $viewer;
    }

    /**
     * Adding/removing participants is only allowed before the auction
     * starts: budgets and roster slots are derived from assignments, so
     * changing the roster of participants mid-auction has no well-defined
     * meaning once picks exist.
     */
    public function addParticipant(Auction $auction, string $name): AuctionParticipant
    {
        return DB::transaction(function () use ($auction, $name) {
            $auction = $this->lock($auction);
            $this->assertInSetup($auction);

            $nextOrder = (int) $auction->participants()->max('call_order') + 1;

            return $auction->participants()->create(['name' => $name, 'call_order' => $nextOrder]);
        });
    }

    public function removeParticipant(AuctionParticipant $participant): void
    {
        DB::transaction(function () use ($participant) {
            $auction = $this->lock($participant->auction);
            $this->assertInSetup($auction);

            $participant->delete();
        });
    }

    /**
     * Freely reorder the calling order. May be called at any time, including
     * mid-auction: the current turn is tracked by participant id, not by
     * position, so reordering never disrupts whose turn it currently is.
     *
     * @param  array<int, int>  $orderedParticipantIds
     */
    public function reorderParticipants(Auction $auction, array $orderedParticipantIds): void
    {
        DB::transaction(function () use ($auction, $orderedParticipantIds) {
            $existingIds = $auction->participants()->pluck('id')->sort()->values();

            if ($existingIds->toArray() !== collect($orderedParticipantIds)->sort()->values()->toArray()) {
                throw AuctionRuleException::because('L\'ordine deve contenere esattamente i partecipanti dell\'asta.');
            }

            foreach (array_values($orderedParticipantIds) as $index => $participantId) {
                AuctionParticipant::query()->whereKey($participantId)->update(['call_order' => $index + 1]);
            }
        });

        AuctionOrderUpdated::dispatch($auction);
    }

    /**
     * Open a call for a player. The caller does not have to be whoever the
     * app currently suggests (current_turn_auction_participant_id is only
     * advisory) — real auctions occasionally deviate from the predicted
     * order, and the app must be able to record what actually happened.
     */
    public function call(Auction $auction, Player $player, AuctionParticipant $caller): AuctionCall
    {
        $call = DB::transaction(function () use ($auction, $player, $caller) {
            $auction = $this->lock($auction);

            if ($auction->status !== AuctionStatus::InProgress) {
                throw AuctionRuleException::because('L\'asta non è in corso.');
            }

            if ($auction->current_call_id !== null) {
                throw AuctionRuleException::because('C\'è già una chiamata aperta.');
            }

            if ($player->role !== $auction->current_phase) {
                throw AuctionRuleException::because('Il giocatore chiamato non appartiene al ruolo della fase corrente.');
            }

            if ($player->assignments()->exists()) {
                throw AuctionRuleException::because('Il giocatore è già stato assegnato.');
            }

            $call = $auction->calls()->create([
                'player_id' => $player->id,
                'called_by_auction_participant_id' => $caller->id,
                'status' => AuctionCallStatus::Open,
            ]);

            $auction->update(['current_call_id' => $call->id]);

            return $call;
        });

        PlayerCalled::dispatch($auction, $call);

        return $call;
    }

    /**
     * Close the current call without a winner (nobody bid on the player).
     * The player returns to the available pool and the turn still advances.
     */
    public function cancelCall(Auction $auction): void
    {
        $call = DB::transaction(function () use ($auction) {
            $auction = $this->lock($auction);
            $call = $this->requireOpenCall($auction);

            $call->update(['status' => AuctionCallStatus::Unsold, 'resolved_at' => now()]);
            $auction->update(['current_call_id' => null]);

            $this->advanceTurn($auction, $call->called_by_auction_participant_id);

            return $call;
        });

        PlayerUnsold::dispatch($auction, $call);
    }

    /**
     * Record the real outcome of the currently open call.
     */
    public function assign(Auction $auction, AuctionParticipant $participant, int $price): PlayerAssignment
    {
        $assignment = DB::transaction(function () use ($auction, $participant, $price) {
            $auction = $this->lock($auction);
            $call = $this->requireOpenCall($auction);

            $this->assertCanAfford($auction, $participant, $call->player, $price);

            $assignment = PlayerAssignment::query()->create([
                'auction_id' => $auction->id,
                'auction_participant_id' => $participant->id,
                'player_id' => $call->player_id,
                'auction_call_id' => $call->id,
                'price' => $price,
            ]);

            $call->update(['status' => AuctionCallStatus::Assigned, 'resolved_at' => now()]);
            $auction->update(['current_call_id' => null]);

            $this->advanceTurn($auction, $call->called_by_auction_participant_id);

            return $assignment;
        });

        PlayerAssigned::dispatch($auction, $assignment);

        return $assignment;
    }

    /**
     * Undo a previously recorded assignment. Never touches the current
     * phase/turn/call — this is a retroactive correction to the ledger,
     * budget and roster slots are derived so they update themselves.
     */
    public function revert(PlayerAssignment $assignment): void
    {
        $auction = $assignment->auction;

        DB::transaction(function () use ($assignment) {
            $this->lock($assignment->auction);

            $assignment->delete();
        });

        AssignmentReverted::dispatch($auction, $assignment);
    }

    /**
     * Correct a recorded assignment (wrong winner and/or wrong price)
     * without disturbing the turn/phase state.
     */
    public function correct(PlayerAssignment $assignment, AuctionParticipant $participant, int $price): PlayerAssignment
    {
        $auction = $assignment->auction;

        $newAssignment = DB::transaction(function () use ($assignment, $participant, $price) {
            $auction = $this->lock($assignment->auction);
            $player = $assignment->player;

            $assignment->delete();

            $this->assertCanAfford($auction, $participant, $player, $price);

            return PlayerAssignment::query()->create([
                'auction_id' => $auction->id,
                'auction_participant_id' => $participant->id,
                'player_id' => $player->id,
                'auction_call_id' => $assignment->auction_call_id,
                'price' => $price,
                'replaces_assignment_id' => $assignment->id,
            ]);
        });

        AssignmentCorrected::dispatch($auction, $newAssignment);

        return $newAssignment;
    }

    /**
     * Manually record an assignment for a player who never went through a
     * call. This exists to rectify mistaken transactions (e.g. the wrong
     * player was recorded and the actual one was never assigned): any
     * player may be added to any participant regardless of the auction's
     * current phase, and — like correct()/revert() — it never touches the
     * current phase/turn/call state.
     */
    public function addAssignment(Auction $auction, Player $player, AuctionParticipant $participant, int $price): PlayerAssignment
    {
        $assignment = DB::transaction(function () use ($auction, $player, $participant, $price) {
            $auction = $this->lock($auction);

            $alreadyAssigned = PlayerAssignment::query()
                ->where('auction_id', $auction->id)
                ->where('player_id', $player->id)
                ->exists();

            if ($alreadyAssigned) {
                throw AuctionRuleException::because("{$player->name} è già stato assegnato in questa asta.");
            }

            $this->assertCanAfford($auction, $participant, $player, $price);

            return PlayerAssignment::query()->create([
                'auction_id' => $auction->id,
                'auction_participant_id' => $participant->id,
                'player_id' => $player->id,
                'price' => $price,
            ]);
        });

        AssignmentAdded::dispatch($auction, $assignment);

        return $assignment;
    }

    private function assertInSetup(Auction $auction): void
    {
        if ($auction->status !== AuctionStatus::Setup) {
            throw AuctionRuleException::because('Non è possibile modificare i partecipanti di un\'asta già avviata.');
        }
    }

    private function assertCanAfford(Auction $auction, AuctionParticipant $participant, Player $player, int $price): void
    {
        if ($price > $participant->budgetRemaining()) {
            throw AuctionRuleException::because("{$participant->name} non ha crediti sufficienti.");
        }

        if ($participant->hasCompletedRole($player->role)) {
            throw AuctionRuleException::because("{$participant->name} ha già completato il ruolo {$player->role->label()}.");
        }
    }

    private function requireOpenCall(Auction $auction): AuctionCall
    {
        $call = $auction->currentCall;

        if (! $call) {
            throw AuctionRuleException::because('Non c\'è nessuna chiamata aperta.');
        }

        return $call;
    }

    /**
     * Move the turn to the next eligible participant after the given one,
     * in call_order, skipping anyone who already completed the current
     * phase's role. If everybody has, advance to the next role phase (or
     * complete the auction if the forward phase was the last one).
     */
    private function advanceTurn(Auction $auction, int $afterParticipantId): void
    {
        $role = $auction->current_phase;
        $ordered = $auction->participants()->orderBy('call_order')->get();

        $eligible = $ordered->reject(fn (AuctionParticipant $p) => $p->hasCompletedRole($role));

        if ($eligible->isEmpty()) {
            $nextRole = $role->next();

            if ($nextRole === null) {
                $auction->update([
                    'status' => AuctionStatus::Completed,
                    'current_phase' => null,
                    'current_turn_auction_participant_id' => null,
                ]);

                return;
            }

            $auction->update(['current_phase' => $nextRole]);
            $this->advanceTurn($auction, $afterParticipantId);

            return;
        }

        $startIndex = $ordered->search(fn (AuctionParticipant $p) => $p->id === $afterParticipantId);
        $startIndex = $startIndex === false ? 0 : $startIndex;

        $next = null;

        for ($i = 1; $i <= $ordered->count(); $i++) {
            $candidate = $ordered[($startIndex + $i) % $ordered->count()];

            if ($eligible->contains('id', $candidate->id)) {
                $next = $candidate;
                break;
            }
        }

        $auction->update(['current_turn_auction_participant_id' => $next->id]);
    }

    private function lock(Auction $auction): Auction
    {
        return Auction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();
    }
}

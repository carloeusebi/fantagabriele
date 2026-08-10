import type { AuctionParticipant, PlayerRoleValue } from '@/types/auction';

export function hasCompletedRole(
    participant: AuctionParticipant,
    role: PlayerRoleValue | null,
): boolean {
    if (role === null) {
        return false;
    }

    const slot = participant.slots.find((s) => s.role === role);

    return slot !== undefined && slot.filled >= slot.total;
}

/**
 * Mirrors AuctionEngine::advanceTurn()'s same-phase branch: the next
 * participant, in call_order, after the given one, skipping anyone who
 * already completed the current phase's role. Used to predict the next
 * turn optimistically — returns null (predict nothing) when every
 * participant has completed the phase, since the real turn would then
 * move to the next phase or complete the auction, which is too specific
 * to guess safely here.
 */
export function nextEligibleParticipant(
    participants: AuctionParticipant[],
    currentPhase: PlayerRoleValue | null,
    afterParticipantId: number,
): AuctionParticipant | null {
    if (currentPhase === null) {
        return null;
    }

    const ordered = [...participants].sort(
        (a, b) => a.call_order - b.call_order,
    );

    if (ordered.every((p) => hasCompletedRole(p, currentPhase))) {
        return null;
    }

    const startIndex = ordered.findIndex((p) => p.id === afterParticipantId);
    const start = startIndex === -1 ? 0 : startIndex;

    for (let i = 1; i <= ordered.length; i++) {
        const candidate = ordered[(start + i) % ordered.length];

        if (!hasCompletedRole(candidate, currentPhase)) {
            return candidate;
        }
    }

    return null;
}

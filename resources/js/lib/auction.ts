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

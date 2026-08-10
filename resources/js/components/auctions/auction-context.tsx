import { createContext, useContext, useMemo } from 'react';
import type { ReactNode } from 'react';
import type {
    AdvisorEntry,
    Auction,
    AuctionParticipant,
    AuctionPermissions,
    AuctionViewer,
    Player,
    PlayerAssignment,
    RoleOption,
} from '@/types/auction';
import type { StrategyListItem } from '@/types/strategy';

type OnlineUser = { id: number; name: string };

type AuctionContextValue = {
    auction: Auction;
    participants: AuctionParticipant[];
    availablePlayers: Player[];
    unassignedPlayers: Player[];
    assignments: PlayerAssignment[];
    advisorEntries: AdvisorEntry[];
    viewer: AuctionViewer | null;
    permissions: AuctionPermissions;
    roles: RoleOption[];
    online: OnlineUser[];
    isOwner: boolean;
    myStrategies: StrategyListItem[];
};

const AuctionContext = createContext<AuctionContextValue | null>(null);

/**
 * Carries everything the auction "show" page and its descendants need, so
 * components deep in the tree (call panel, participant board, assignment
 * history, ...) can read it directly instead of having it threaded through
 * every intermediate component as props.
 */
export function AuctionProvider({
    value: {
        auction,
        participants,
        availablePlayers,
        unassignedPlayers,
        assignments,
        advisorEntries,
        viewer,
        permissions,
        roles,
        online,
        isOwner,
        myStrategies,
    },
    children,
}: {
    value: AuctionContextValue;
    children: ReactNode;
}) {
    const memoizedValue = useMemo<AuctionContextValue>(
        () => ({
            auction,
            participants,
            availablePlayers,
            unassignedPlayers,
            assignments,
            advisorEntries,
            viewer,
            permissions,
            roles,
            online,
            isOwner,
            myStrategies,
        }),
        [
            auction,
            participants,
            availablePlayers,
            unassignedPlayers,
            assignments,
            advisorEntries,
            viewer,
            permissions,
            roles,
            online,
            isOwner,
            myStrategies,
        ],
    );

    return (
        <AuctionContext.Provider value={memoizedValue}>
            {children}
        </AuctionContext.Provider>
    );
}

export function useAuction(): AuctionContextValue {
    const context = useContext(AuctionContext);

    if (context === null) {
        throw new Error('useAuction must be used within an AuctionProvider');
    }

    return context;
}

import { router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { toast } from 'sonner';

// useEcho (unlike useEchoModel) does NOT auto-prefix event names with a dot,
// so it must be done here — otherwise Echo prepends its default namespace
// ("App.Events.") and the listener never matches the bare event names our
// broadcastAs() methods send over the wire.
const AUCTION_EVENTS = [
    '.AuctionStatusChanged',
    '.AuctionOrderUpdated',
    '.AuctionViewerJoined',
    '.PlayerCalled',
    '.PlayerAssigned',
    '.PlayerUnsold',
    '.AssignmentReverted',
    '.AssignmentCorrected',
    '.AssignmentAdded',
];

export function useAuctionChannel(auctionId: number) {
    useEcho<{ message?: string }>(
        `auction.${auctionId}`,
        AUCTION_EVENTS,
        (payload) => {
            if (payload.message) {
                toast.info(payload.message);
            }

            router.reload({
                only: [
                    'auction',
                    'participants',
                    'availablePlayers',
                    'unassignedPlayers',
                    'assignments',
                ],
            });
        },
        [auctionId],
    );
}

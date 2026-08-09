import { router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { toast } from 'sonner';

const AUCTION_EVENTS = [
    'AuctionStatusChanged',
    'AuctionOrderUpdated',
    'PlayerCalled',
    'PlayerAssigned',
    'PlayerUnsold',
    'AssignmentReverted',
    'AssignmentCorrected',
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
                only: ['auction', 'participants', 'availablePlayers', 'assignments'],
            });
        },
        [auctionId],
    );
}

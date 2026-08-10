import { router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { appendRealtimeEvent } from '@/lib/realtime-event-log';

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
    const { channel } = useEcho<{ message?: string }>(
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
                    'permissions',
                ],
            });
        },
        [auctionId],
    );

    useEffect(() => {
        const echoChannel = channel();

        if (!echoChannel) {
            return;
        }

        const logEvent = (name: string) => (payload: unknown) => {
            appendRealtimeEvent(`auction.${auctionId}`, {
                event: name.replace(/^\./, ''),
                data: JSON.stringify(payload, null, 2),
                receivedAt: Date.now(),
            });
        };

        const listeners = AUCTION_EVENTS.map((name) => {
            const listener = logEvent(name);
            echoChannel.listen(name, listener);

            return { name, listener };
        });

        return () => {
            listeners.forEach(({ name, listener }) => {
                echoChannel.stopListening(name, listener);
            });
        };
    }, [auctionId, channel]);
}

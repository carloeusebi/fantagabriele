import { router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';

// useEcho (unlike useEchoModel) does NOT auto-prefix event names with a dot,
// so it must be done here — otherwise Echo prepends its default namespace
// ("App.Events.") and the listener never matches the bare event names our
// broadcastAs() methods send over the wire.
const AUCTIONS_INDEX_EVENTS = [
    '.AuctionCreated',
    '.AuctionDeleted',
    '.AuctionStatusChanged',
];

export function useAuctionsIndexChannel() {
    useEcho('auctions', AUCTIONS_INDEX_EVENTS, () => {
        router.reload({ only: ['auctions'] });
    });
}

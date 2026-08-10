import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp } from 'lucide-react';
import { useAuction } from '@/components/auctions/auction-context';
import { Button } from '@/components/ui/button';
import auctionParticipants from '@/routes/auctions/participants';

export function CallOrderEditor() {
    const { auction, participants } = useAuction();
    const auctionId = auction.id;
    const ordered = [...participants].sort(
        (a, b) => a.call_order - b.call_order,
    );

    function move(index: number, direction: -1 | 1) {
        const next = [...ordered];
        const swapWith = index + direction;

        if (swapWith < 0 || swapWith >= next.length) {
            return;
        }

        [next[index], next[swapWith]] = [next[swapWith], next[index]];

        router.put(
            auctionParticipants.reorder(auctionId).url,
            { order: next.map((participant) => participant.id) },
            { preserveScroll: true },
        );
    }

    return (
        <div className="space-y-2">
            {ordered.map((participant, index) => (
                <div
                    key={participant.id}
                    className="flex items-center justify-between gap-2 rounded-md border border-sidebar-border/70 p-2 dark:border-sidebar-border"
                >
                    <div className="flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            {index + 1}.
                        </span>
                        <span className="font-medium">{participant.name}</span>
                    </div>
                    <div className="flex gap-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            disabled={index === 0}
                            onClick={() => move(index, -1)}
                        >
                            <ArrowUp />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            disabled={index === ordered.length - 1}
                            onClick={() => move(index, 1)}
                        >
                            <ArrowDown />
                        </Button>
                    </div>
                </div>
            ))}
        </div>
    );
}

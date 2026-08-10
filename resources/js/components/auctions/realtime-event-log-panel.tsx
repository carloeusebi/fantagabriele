import { ClockIcon, SearchIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useAuction } from '@/components/auctions/auction-context';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useRealtimeEventLog } from '@/hooks/use-realtime-event-log';

function eventMessage(data: string): string {
    try {
        return (JSON.parse(data) as { message?: string }).message ?? data;
    } catch {
        return data;
    }
}

/**
 * Debug panel: every Reverb broadcast event received on this auction's
 * channel, most recent first, kept in localStorage so it survives reloads.
 */
export function RealtimeEventLogPanel() {
    const { auction } = useAuction();
    const entries = useRealtimeEventLog(`auction.${auction.id}`);
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (!query) {
            return entries;
        }

        return entries.filter((entry) =>
            eventMessage(entry.data).toLowerCase().includes(query),
        );
    }, [entries, search]);

    return (
        <Card className="lg:sticky lg:top-4">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <ClockIcon className="size-4" />
                    Storico eventi
                </CardTitle>
                <div className="relative mt-2">
                    <SearchIcon className="absolute top-1/2 left-2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cerca negli eventi…"
                        className="pl-8"
                    />
                </div>
            </CardHeader>
            <CardContent>
                {entries.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nessun evento ricevuto finora.
                    </p>
                ) : filtered.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nessun evento corrisponde alla ricerca.
                    </p>
                ) : (
                    <div className="max-h[2000px] overflow-y-auto">
                        {[...filtered].reverse().map((entry, index) => (
                            <div key={index} className="rounded-md p-1 text-xs">
                                <div className="mb-1 flex items-center justify-between gap-2">
                                    <span className="text-muted-foreground">
                                        {new Date(
                                            entry.receivedAt,
                                        ).toLocaleTimeString('it-IT')}
                                    </span>
                                </div>
                                <p className="text-sm">
                                    {eventMessage(entry.data)}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

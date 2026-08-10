import { useEffect, useState } from 'react';
import {
    readRealtimeEvents,
    subscribeToRealtimeEventLog,
} from '@/lib/realtime-event-log';
import type { RealtimeEventLogEntry } from '@/lib/realtime-event-log';

export function useRealtimeEventLog(channel: string): RealtimeEventLogEntry[] {
    const [entries, setEntries] = useState<RealtimeEventLogEntry[]>(() =>
        readRealtimeEvents(channel),
    );

    useEffect(() => {
        setEntries(readRealtimeEvents(channel));

        return subscribeToRealtimeEventLog(channel, () =>
            setEntries(readRealtimeEvents(channel)),
        );
    }, [channel]);

    return entries;
}

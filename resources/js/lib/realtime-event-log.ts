export type RealtimeEventLogEntry = {
    event: string;
    data: string;
    receivedAt: number;
};

const MAX_ENTRIES_PER_CHANNEL = 300;
const UPDATED_EVENT = 'realtime-event-log-updated';

function storageKey(channel: string): string {
    return `realtime-events:${channel}`;
}

/**
 * Persists every Reverb broadcast event received on a channel, so the full
 * history of what came over the wire can be inspected later, across page
 * reloads.
 */
export function appendRealtimeEvent(
    channel: string,
    entry: RealtimeEventLogEntry,
): void {
    const entries = [...readRealtimeEvents(channel), entry].slice(
        -MAX_ENTRIES_PER_CHANNEL,
    );

    localStorage.setItem(storageKey(channel), JSON.stringify(entries));

    window.dispatchEvent(
        new CustomEvent(UPDATED_EVENT, { detail: { channel } }),
    );
}

export function readRealtimeEvents(channel: string): RealtimeEventLogEntry[] {
    const raw = localStorage.getItem(storageKey(channel));

    if (!raw) {
        return [];
    }

    try {
        return JSON.parse(raw) as RealtimeEventLogEntry[];
    } catch {
        return [];
    }
}

export function subscribeToRealtimeEventLog(
    channel: string,
    onUpdate: () => void,
): () => void {
    function handleUpdate(event: Event) {
        const { channel: updatedChannel } = (
            event as CustomEvent<{ channel: string }>
        ).detail;

        if (updatedChannel === channel) {
            onUpdate();
        }
    }

    window.addEventListener(UPDATED_EVENT, handleUpdate);

    return () => window.removeEventListener(UPDATED_EVENT, handleUpdate);
}

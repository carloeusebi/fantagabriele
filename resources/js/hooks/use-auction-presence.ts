import { useEchoPresence } from '@laravel/echo-react';
import { useEffect, useState } from 'react';

type PresenceUser = { id: number; name: string };

export function useAuctionPresence(auctionId: number): PresenceUser[] {
    const [online, setOnline] = useState<PresenceUser[]>([]);
    const { channel } = useEchoPresence(`auction-presence.${auctionId}`);

    useEffect(() => {
        const ch = channel();

        if (!ch) {
            return;
        }

        ch.here((users: PresenceUser[]) => setOnline(users));

        ch.joining((user: PresenceUser) => {
            setOnline((current) =>
                current.some((u) => u.id === user.id)
                    ? current
                    : [...current, user],
            );
        });

        ch.leaving((user: PresenceUser) => {
            setOnline((current) => current.filter((u) => u.id !== user.id));
        });
    }, [channel]);

    return online;
}

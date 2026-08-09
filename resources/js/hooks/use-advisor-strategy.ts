import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Triggers generation of the AI advisor's auction strategy. The endpoint
 * returns plain JSON (not an Inertia response), so it's called directly via
 * fetch; once it succeeds, the auction page's advisorEntries prop is
 * refreshed via a normal Inertia partial reload so the new entry shows up
 * in the transcript.
 */
export function useAdvisorStrategy(url: string) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const generate = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error();
            }

            router.reload({ only: ['advisorEntries'] });
        } catch {
            setError('Errore durante la generazione della strategia.');
        } finally {
            setLoading(false);
        }
    }, [url]);

    return { generate, loading, error };
}

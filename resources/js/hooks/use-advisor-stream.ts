import { useCallback, useRef, useState } from 'react';

type AdvisorStreamState<TResult> = {
    reasoning: string;
    result: TResult | null;
    streaming: boolean;
    error: string | null;
};

const initialState = {
    reasoning: '',
    result: null,
    streaming: false,
    error: null,
};

/**
 * Consumes the auction advisor's SSE endpoint: the "result" event carries
 * the structured suggestion as soon as it's decided (before narration even
 * starts), "delta" events carry narration text chunks as the AI explains
 * that decision, and the stream ends with either a "complete" event or a
 * "failure" event (a domain error that couldn't become a normal HTTP error
 * response, since streaming had already started).
 */
export function useAdvisorStream<TResult>() {
    const [state, setState] = useState<AdvisorStreamState<TResult>>(
        initialState,
    );
    const sourceRef = useRef<EventSource | null>(null);

    const start = useCallback((url: string) => {
        sourceRef.current?.close();

        setState({ ...initialState, streaming: true });

        const source = new EventSource(url);
        sourceRef.current = source;

        source.addEventListener('delta', (event) => {
            const chunk = JSON.parse(
                (event as MessageEvent<string>).data,
            ) as string;

            setState((current) => ({
                ...current,
                reasoning: current.reasoning + chunk,
            }));
        });

        source.addEventListener('result', (event) => {
            const result = JSON.parse(
                (event as MessageEvent<string>).data,
            ) as TResult;

            setState((current) => ({ ...current, result }));
        });

        source.addEventListener('complete', () => {
            setState((current) => ({ ...current, streaming: false }));
            source.close();
        });

        source.addEventListener('failure', (event) => {
            const payload = JSON.parse(
                (event as MessageEvent<string>).data,
            ) as { message: string };

            setState((current) => ({
                ...current,
                error: payload.message,
                streaming: false,
            }));
            source.close();
        });

        source.onerror = () => {
            setState((current) =>
                current.streaming
                    ? {
                          ...current,
                          error: "Connessione con l'IA interrotta.",
                          streaming: false,
                      }
                    : current,
            );
            source.close();
        };
    }, []);

    return { ...state, start };
}

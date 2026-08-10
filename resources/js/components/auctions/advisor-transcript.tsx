import { ChevronDown } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { AdvisorEntry, AdvisorEntryKind } from '@/types/auction';

const kindLabels: Record<AdvisorEntryKind, string> = {
    strategy: 'Strategia',
    call: 'Chiamata',
    bid: 'Offerta',
};

/**
 * Shows the full, ongoing dialogue between this user and the AI advisor for
 * this auction: what was asked (the JSON context sent), the AI's reasoning,
 * its response, and whether it flagged a bluff. Only ever the requesting
 * user's own entries — never other participants' — so strategy and bluff
 * intentions stay private.
 */
export function AdvisorTranscript({ entries }: { entries: AdvisorEntry[] }) {
    if (entries.length === 0) {
        return null;
    }

    return (
        <div>
            <h2 className="mb-3 text-lg font-semibold">
                Conversazione con l'IA
            </h2>
            <div className="space-y-3">
                {[...entries].reverse().map((entry) => (
                    <AdvisorTranscriptEntry key={entry.id} entry={entry} />
                ))}
            </div>
        </div>
    );
}

function AdvisorTranscriptEntry({ entry }: { entry: AdvisorEntry }) {
    return (
        <Card>
            <CardHeader className="flex-row items-center justify-between gap-2 space-y-0">
                <CardTitle className="flex items-center gap-2 text-sm font-medium">
                    <Badge variant="outline">{kindLabels[entry.kind]}</Badge>
                    {entry.is_bluff && (
                        <Badge variant="destructive">Bluff</Badge>
                    )}
                </CardTitle>
                <span className="text-xs text-muted-foreground">
                    {new Date(entry.created_at).toLocaleString('it-IT')}
                </span>
            </CardHeader>
            <CardContent className="space-y-3">
                {entry.reasoning && (
                    <p className="text-sm whitespace-pre-wrap">
                        {entry.reasoning}
                    </p>
                )}

                {entry.response && (
                    <JsonBlock label="Risposta" value={entry.response} />
                )}

                <Collapsible>
                    <CollapsibleTrigger className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">
                        <ChevronDown className="size-3" />
                        Dati inviati all'IA
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <JsonBlock value={entry.request_context} />
                    </CollapsibleContent>
                </Collapsible>
            </CardContent>
        </Card>
    );
}

function JsonBlock({
    label,
    value,
}: {
    label?: string;
    value: Record<string, unknown>;
}) {
    return (
        <div className="space-y-1">
            {label && (
                <p className="text-xs font-medium text-muted-foreground">
                    {label}
                </p>
            )}
            <pre className="max-h-64 overflow-auto rounded-md bg-muted p-2 text-xs">
                {JSON.stringify(value, null, 2)}
            </pre>
        </div>
    );
}

import { ChevronDown } from 'lucide-react';
import { useAuction } from '@/components/auctions/auction-context';
import { MarkdownText } from '@/components/markdown-text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
 * intentions stay private. Opens in a modal so it doesn't crowd the auction
 * board.
 */
export function AdvisorTranscript() {
    const { advisorEntries: entries } = useAuction();

    if (entries.length === 0) {
        return null;
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline">
                    Conversazione con l'IA ({entries.length})
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl lg:max-w-4xl xl:max-w-7xl">
                <DialogHeader>
                    <DialogTitle>Conversazione con l'IA</DialogTitle>
                </DialogHeader>
                <div className="min-w-0 space-y-3">
                    {[...entries].reverse().map((entry) => (
                        <AdvisorTranscriptEntry key={entry.id} entry={entry} />
                    ))}
                </div>
            </DialogContent>
        </Dialog>
    );
}

function AdvisorTranscriptEntry({ entry }: { entry: AdvisorEntry }) {
    return (
        <Card className="max-w-full">
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
                    <MarkdownText>{entry.reasoning}</MarkdownText>
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

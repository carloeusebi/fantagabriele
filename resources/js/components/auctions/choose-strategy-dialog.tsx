import { Link, router } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useAuction } from '@/components/auctions/auction-context';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import auctions from '@/routes/auctions';
import strategiesRoutes from '@/routes/strategies';
import type { StrategyListItem } from '@/types/strategy';

/**
 * Shown once a user has claimed a participant in a running auction and
 * hasn't picked a plan yet: they either reuse one of their saved strategies
 * (which then guides the AI advisor for the rest of the auction) or ask the
 * AI to build one from scratch, as it always used to do automatically.
 */
export function ChooseStrategyDialog({
    strategies,
    onGenerateAi,
    generating,
}: {
    strategies: StrategyListItem[];
    onGenerateAi: () => void;
    generating: boolean;
}) {
    const { auction } = useAuction();

    function select(strategyId: number) {
        router.patch(
            auctions.viewer.strategy.update(auction.id).url,
            { strategy_id: strategyId },
            { preserveScroll: true },
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Che strategia usi in questa asta?</CardTitle>
                <CardDescription>
                    Scegli uno dei tuoi piani salvati, oppure lascia che sia
                    l'IA a costruirne uno da zero.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
                {strategies.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                        {strategies.map((strategy) => (
                            <Button
                                key={strategy.id}
                                type="button"
                                variant="outline"
                                onClick={() => select(strategy.id)}
                            >
                                {strategy.name}
                                <span className="text-muted-foreground">
                                    ({strategy.type_label})
                                </span>
                            </Button>
                        ))}
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        onClick={onGenerateAi}
                        disabled={generating}
                    >
                        <Sparkles data-icon="inline-start" />
                        {generating
                            ? "L'IA sta pensando…"
                            : "Fai scegliere all'IA"}
                    </Button>

                    {strategies.length === 0 && (
                        <Button type="button" variant="ghost" asChild>
                            <Link href={strategiesRoutes.create()}>
                                Crea una nuova strategia
                            </Link>
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

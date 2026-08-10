import { ChevronDown } from 'lucide-react';
import { MarkdownText } from '@/components/markdown-text';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { PlayerRoleValue } from '@/types/auction';
import type { StrategyBrief } from '@/types/strategy';

const roleLabels: Record<PlayerRoleValue, string> = {
    P: 'Portieri',
    D: 'Difensori',
    C: 'Centrocampisti',
    A: 'Attaccanti',
};

/**
 * Shows the user's own saved strategy — picked instead of letting the AI
 * build one from scratch — front and center, mirroring AdvisorStrategyBrief
 * so the auction board looks consistent either way.
 */
export function SelectedStrategyBrief({
    strategy,
}: {
    strategy: StrategyBrief;
}) {
    return (
        <Card>
            <Collapsible defaultOpen>
                <CardHeader>
                    <CollapsibleTrigger className="group flex w-full items-center justify-between gap-2">
                        <CardTitle>La tua strategia: {strategy.name}</CardTitle>
                        <ChevronDown className="size-4 text-muted-foreground transition-transform group-data-[state=closed]:-rotate-90" />
                    </CollapsibleTrigger>
                </CardHeader>
                <CollapsibleContent>
                    <CardContent className="space-y-4">
                        <Badge variant="secondary">{strategy.type_label}</Badge>

                        <div className="flex flex-wrap gap-2">
                            {(Object.keys(roleLabels) as PlayerRoleValue[]).map(
                                (role) => (
                                    <Badge key={role} variant="outline">
                                        {roleLabels[role]}:{' '}
                                        {strategy.budget_plan[role]}%
                                    </Badge>
                                ),
                            )}
                        </div>

                        {strategy.priority_players.length > 0 && (
                            <div className="space-y-1">
                                <p className="text-xs font-medium text-muted-foreground">
                                    Giocatori obiettivo
                                </p>
                                <ul className="space-y-1 text-sm">
                                    {strategy.priority_players.map((player) => (
                                        <li key={player.id}>
                                            <span className="font-medium">
                                                {player.name}
                                            </span>{' '}
                                            <span className="text-muted-foreground">
                                                (
                                                {roleLabels[player.role] ??
                                                    player.role}
                                                {player.team
                                                    ? `, ${player.team}`
                                                    : ''}
                                                )
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {strategy.comment && (
                            <div className="space-y-1">
                                <p className="text-xs font-medium text-muted-foreground">
                                    Commento
                                </p>
                                <MarkdownText>{strategy.comment}</MarkdownText>
                            </div>
                        )}
                    </CardContent>
                </CollapsibleContent>
            </Collapsible>
        </Card>
    );
}

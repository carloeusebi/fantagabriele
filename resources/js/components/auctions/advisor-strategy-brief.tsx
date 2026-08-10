import { ChevronDown } from 'lucide-react';
import { MarkdownText } from '@/components/markdown-text';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { AdvisorEntry, PlayerRoleValue } from '@/types/auction';

type BudgetPlanRow = {
    role: PlayerRoleValue;
    target_credits: number;
    target_percent: number;
};

type PriorityTarget = {
    role: PlayerRoleValue;
    name: string;
    notes: string;
};

const roleLabels: Record<PlayerRoleValue, string> = {
    P: 'Portieri',
    D: 'Difensori',
    C: 'Centrocampisti',
    A: 'Attaccanti',
};

/**
 * Shows the AI's declared strategy — the plan it commits to at the start of
 * the auction and revises as it goes — front and center, so the user always
 * knows what the assistant is aiming for.
 */
export function AdvisorStrategyBrief({
    entry,
    loading,
}: {
    entry: AdvisorEntry | null;
    loading: boolean;
}) {
    if (!entry && !loading) {
        return null;
    }

    const budgetPlan = (entry?.response?.budget_plan as BudgetPlanRow[]) ?? [];
    const priorityTargets =
        (entry?.response?.priority_targets as PriorityTarget[]) ?? [];

    return (
        <Card>
            <Collapsible defaultOpen>
                <CardHeader>
                    <CollapsibleTrigger className="group flex w-full items-center justify-between gap-2">
                        <CardTitle>Strategia dell'IA</CardTitle>
                        <ChevronDown className="size-4 text-muted-foreground transition-transform group-data-[state=closed]:-rotate-90" />
                    </CollapsibleTrigger>
                </CardHeader>
                <CollapsibleContent>
                    <CardContent className="space-y-4">
                        {loading && (
                            <p className="text-sm text-muted-foreground italic">
                                L'IA sta preparando il piano d'azione…
                            </p>
                        )}

                        {entry && (
                            <>
                                <MarkdownText>{entry.reasoning}</MarkdownText>

                                {budgetPlan.length > 0 && (
                                    <div className="flex flex-wrap gap-2">
                                        {budgetPlan.map((row) => (
                                            <Badge
                                                key={row.role}
                                                variant="secondary"
                                            >
                                                {roleLabels[row.role] ??
                                                    row.role}
                                                : {row.target_credits} crediti (
                                                {row.target_percent}%)
                                            </Badge>
                                        ))}
                                    </div>
                                )}

                                {priorityTargets.length > 0 && (
                                    <div className="space-y-1">
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Obiettivi prioritari
                                        </p>
                                        <ul className="space-y-1 text-sm">
                                            {priorityTargets.map(
                                                (target, index) => (
                                                    <li key={index}>
                                                        <span className="font-medium">
                                                            {target.name}
                                                        </span>{' '}
                                                        <span className="text-muted-foreground">
                                                            (
                                                            {roleLabels[
                                                                target.role
                                                            ] ?? target.role}
                                                            ) — {target.notes}
                                                        </span>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </CollapsibleContent>
            </Collapsible>
        </Card>
    );
}

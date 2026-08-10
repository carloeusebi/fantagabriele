import { Lock, LockOpen } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ROLE_ORDER, roleLetterClasses } from '@/lib/role-colors';
import { cn } from '@/lib/utils';
import type { PlayerRoleValue } from '@/types/auction';

const ROLE_LABELS: Record<PlayerRoleValue, string> = {
    P: 'Portieri',
    D: 'Difensori',
    C: 'Centrocampisti',
    A: 'Attaccanti',
};

export type BudgetPercents = Record<PlayerRoleValue, number>;

type BudgetPercentEditorProps = {
    percents: BudgetPercents;
    lockedRoles: PlayerRoleValue[];
    onPercentsChange: (percents: BudgetPercents) => void;
    onLockedRolesChange: (lockedRoles: PlayerRoleValue[]) => void;
};

/**
 * Redistributes the delta introduced by editing `changedRole` across the
 * other roles that aren't locked and aren't the one being edited,
 * proportionally to their current share (evenly if they're all at 0),
 * clamping each to [0, 100] and dumping any rounding drift onto the
 * largest unlocked role so the total stays as close to 100 as possible.
 */
function redistribute(
    percents: BudgetPercents,
    lockedRoles: PlayerRoleValue[],
    changedRole: PlayerRoleValue,
    newValue: number,
): BudgetPercents {
    const clampedNew = Math.max(0, Math.min(100, Math.round(newValue) || 0));
    const delta = clampedNew - percents[changedRole];

    if (delta === 0) {
        return percents;
    }

    const others = ROLE_ORDER.filter(
        (role) => role !== changedRole && !lockedRoles.includes(role),
    );

    const next: BudgetPercents = { ...percents, [changedRole]: clampedNew };

    if (others.length === 0) {
        return percents;
    }

    const othersTotal = others.reduce((sum, role) => sum + percents[role], 0);
    const remaining = -delta;
    let allocated = 0;

    others.forEach((role, index) => {
        const share =
            othersTotal > 0 ? percents[role] / othersTotal : 1 / others.length;
        const isLast = index === others.length - 1;
        const portion = isLast
            ? remaining - allocated
            : Math.round(remaining * share);

        allocated += portion;
        next[role] = Math.max(0, Math.min(100, percents[role] + portion));
    });

    const total = ROLE_ORDER.reduce((sum, role) => sum + next[role], 0);
    const drift = 100 - total;

    if (drift !== 0) {
        const target = [...others].sort((a, b) => next[b] - next[a])[0];
        next[target] = Math.max(0, Math.min(100, next[target] + drift));
    }

    return next;
}

export default function BudgetPercentEditor({
    percents,
    lockedRoles,
    onPercentsChange,
    onLockedRolesChange,
}: BudgetPercentEditorProps) {
    function handlePercentChange(role: PlayerRoleValue, value: number) {
        onPercentsChange(redistribute(percents, lockedRoles, role, value));
    }

    function toggleLock(role: PlayerRoleValue) {
        onLockedRolesChange(
            lockedRoles.includes(role)
                ? lockedRoles.filter((r) => r !== role)
                : [...lockedRoles, role],
        );
    }

    const total = ROLE_ORDER.reduce((sum, role) => sum + percents[role], 0);

    return (
        <div className="space-y-2">
            {ROLE_ORDER.map((role) => {
                const locked = lockedRoles.includes(role);

                return (
                    <div key={role} className="flex items-center gap-3">
                        <span
                            className={cn(
                                'inline-flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white',
                                roleLetterClasses[role],
                            )}
                        >
                            {role}
                        </span>
                        <Label className="w-32 shrink-0 font-normal">
                            {ROLE_LABELS[role]}
                        </Label>
                        <Input
                            type="number"
                            min={0}
                            max={100}
                            value={percents[role]}
                            disabled={locked}
                            onChange={(e) =>
                                handlePercentChange(
                                    role,
                                    Number(e.target.value),
                                )
                            }
                            className="w-20"
                        />
                        <span className="text-sm text-muted-foreground">%</span>
                        <button
                            type="button"
                            onClick={() => toggleLock(role)}
                            className="ml-auto text-muted-foreground hover:text-foreground"
                            title={
                                locked
                                    ? 'Sblocca questa percentuale'
                                    : 'Blocca questa percentuale'
                            }
                        >
                            {locked ? (
                                <Lock className="size-4" />
                            ) : (
                                <LockOpen className="size-4" />
                            )}
                        </button>
                    </div>
                );
            })}
            <p
                className={cn(
                    'text-sm',
                    total === 100
                        ? 'text-muted-foreground'
                        : 'text-destructive',
                )}
            >
                Totale: {total}%
            </p>
        </div>
    );
}

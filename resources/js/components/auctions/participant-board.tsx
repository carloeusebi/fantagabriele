import { RoleLetter } from '@/components/auctions/role-letter';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { hasCompletedRole } from '@/lib/auction';
import { ROLE_ORDER, roleBadgeClasses } from '@/lib/role-colors';
import { cn } from '@/lib/utils';
import type { AuctionParticipant, PlayerRoleValue, RosterEntry } from '@/types/auction';

export function ParticipantBoard({
    participants,
    currentTurnParticipantId,
    currentPhase = null,
    onlineUserIds = [],
}: {
    participants: AuctionParticipant[];
    currentTurnParticipantId: number | null;
    currentPhase?: PlayerRoleValue | null;
    onlineUserIds?: number[];
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {participants.map((participant) => (
                <Card
                    key={participant.id}
                    className={cn(
                        participant.id === currentTurnParticipantId &&
                            'border-primary',
                        hasCompletedRole(participant, currentPhase) &&
                            'opacity-50',
                    )}
                >
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between gap-2">
                            <span className="flex items-center gap-2">
                                {participant.claimed_by_user_id !== null &&
                                    onlineUserIds.includes(
                                        participant.claimed_by_user_id,
                                    ) && (
                                        <span
                                            className="size-2 shrink-0 rounded-full bg-green-500"
                                            title="Online"
                                        />
                                    )}
                                {participant.name}
                                {participant.is_agent && (
                                    <Badge variant="outline">IA</Badge>
                                )}
                            </span>
                            <span className="text-sm font-normal text-muted-foreground">
                                {participant.budget_remaining} crediti
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex flex-wrap gap-2">
                            {participant.slots.map((slot) => (
                                <Badge
                                    key={slot.role}
                                    variant="outline"
                                    className={roleBadgeClasses[slot.role]}
                                >
                                    {slot.label}: {slot.filled}/{slot.total}
                                </Badge>
                            ))}
                        </div>

                        {participant.roster.length > 0 && (
                            <RosterList roster={participant.roster} />
                        )}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function RosterList({ roster }: { roster: RosterEntry[] }) {
    const groups = ROLE_ORDER.map((role) => ({
        role,
        entries: roster.filter((entry) => entry.role === role),
    })).filter((group) => group.entries.length > 0);

    return (
        <ul className="space-y-1 text-sm">
            {groups.map((group, index) => (
                <li key={group.role}>
                    {index > 0 && <Separator className="mb-1" />}
                    <ul className="space-y-1">
                        {group.entries.map((entry) => (
                            <li
                                key={entry.assignment_id}
                                className="flex items-center justify-between gap-2 text-muted-foreground"
                            >
                                <span className="flex min-w-0 items-center gap-2">
                                    <RoleLetter role={entry.role} />
                                    <span className="truncate">
                                        {entry.player_name}
                                        {entry.team ? ` (${entry.team})` : ''}
                                    </span>
                                </span>
                                <span className="shrink-0">{entry.price}</span>
                            </li>
                        ))}
                    </ul>
                </li>
            ))}
        </ul>
    );
}

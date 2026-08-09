import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { AuctionParticipant } from '@/types/auction';

export function ParticipantBoard({
    participants,
    currentTurnParticipantId,
}: {
    participants: AuctionParticipant[];
    currentTurnParticipantId: number | null;
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {participants.map((participant) => (
                <Card
                    key={participant.id}
                    className={cn(
                        participant.id === currentTurnParticipantId &&
                            'border-primary',
                    )}
                >
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between gap-2">
                            <span className="flex items-center gap-2">
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
                                <Badge key={slot.role} variant="secondary">
                                    {slot.label}: {slot.filled}/{slot.total}
                                </Badge>
                            ))}
                        </div>

                        {participant.roster.length > 0 && (
                            <ul className="space-y-1 text-sm">
                                {participant.roster.map((entry) => (
                                    <li
                                        key={entry.assignment_id}
                                        className="flex items-center justify-between text-muted-foreground"
                                    >
                                        <span>
                                            {entry.player_name}
                                            {entry.team
                                                ? ` (${entry.team})`
                                                : ''}
                                        </span>
                                        <span>{entry.price}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

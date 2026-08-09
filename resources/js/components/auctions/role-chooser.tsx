import { router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import auctions from '@/routes/auctions';
import type { AuctionParticipant } from '@/types/auction';

export function RoleChooser({
    auctionId,
    participants,
    currentParticipantId,
    onChosen,
}: {
    auctionId: number;
    participants: AuctionParticipant[];
    currentParticipantId: number | null;
    onChosen?: () => void;
}) {
    function choose(participantId: number | null) {
        router.post(
            auctions.join(auctionId).url,
            { auction_participant_id: participantId },
            { preserveScroll: true, onSuccess: () => onChosen?.() },
        );
    }

    return (
        <div className="flex flex-col gap-2">
            <Button
                type="button"
                variant={currentParticipantId === null ? 'default' : 'outline'}
                className="justify-start"
                onClick={() => choose(null)}
            >
                Spettatore
            </Button>

            {participants.map((participant) => {
                const takenByOther =
                    participant.claimed_by !== null &&
                    participant.id !== currentParticipantId;

                return (
                    <Button
                        key={participant.id}
                        type="button"
                        variant={
                            participant.id === currentParticipantId
                                ? 'default'
                                : 'outline'
                        }
                        disabled={takenByOther}
                        onClick={() => choose(participant.id)}
                        className="justify-between"
                    >
                        <span>{participant.name}</span>
                        {takenByOther && (
                            <Badge variant="secondary">
                                {participant.claimed_by}
                            </Badge>
                        )}
                    </Button>
                );
            })}
        </div>
    );
}
